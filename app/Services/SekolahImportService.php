<?php

namespace App\Services;

use App\Models\SettingsModel;
use App\Models\Siswa\MasterSekolahModel;
use CodeIgniter\Files\Exceptions\FileNotFoundException;
use Config\Sekolah as SekolahConfig;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Membaca berkas data sekolah (Excel/CSV hasil unduhan portal Kemendikdasmen
 * atau template internal), lalu menyimpannya ke tabel master_sekolah.
 */
class SekolahImportService
{
    private const MAX_ERRORS    = 50;
    private const XLSX_CHUNK    = 500;
    private const SAMPLE_ROWS   = 10;
    private const MAX_DATA_ROWS = 150_000;

    private SekolahConfig $config;
    private ?MasterSekolahModel $sekolahModel;

    /** @var array<string, string> peta nama kabupaten ternormalisasi => nama kanonik */
    private array $kabupatenMap = [];

    public function __construct(?SekolahConfig $config = null, ?MasterSekolahModel $sekolahModel = null)
    {
        $this->config       = $config ?? config('Sekolah');
        $this->sekolahModel = $sekolahModel;

        foreach ($this->config->kabupaten as $nama) {
            $this->kabupatenMap[$this->normalizeRegion($nama)] = $nama;
        }
    }

    /**
     * Membaca berkas dan mengembalikan laporan tanpa menyentuh database.
     *
     * @return array<string, mixed>
     */
    public function preview(string $path, string $sumber = 'admin'): array
    {
        $analysis = $this->analyze($path, $sumber);
        unset($analysis['rows']);

        return $analysis['report'];
    }

    /**
     * Membaca berkas lalu menyimpan hasilnya secara batch.
     *
     * @return array<string, mixed>
     */
    public function import(string $path, string $originalName, string $sumber = 'admin'): array
    {
        $analysis = $this->analyze($path, $sumber);
        $upsert   = $this->getSekolahModel()->upsertBatch($analysis['rows']);

        $report                 = $analysis['report'];
        $report['imported']     = $upsert['inserted'];
        $report['updated']      = $upsert['updated'];
        $report['sumber']       = $sumber;
        $report['imported_at']  = date('Y-m-d H:i:s');

        SettingsModel::setSetting('sekolah_last_import_at', $report['imported_at']);
        SettingsModel::setSetting('sekolah_last_import_file', $originalName);
        SettingsModel::setSetting('sekolah_last_import_summary', json_encode([
            'total'     => $report['total_rows'],
            'imported'  => $report['imported'],
            'updated'   => $report['updated'],
            'skipped'   => $report['invalid'] + $report['outside_province'],
            'file'      => $originalName,
            'sumber'    => $sumber,
            'timestamp' => $report['imported_at'],
        ]));

        // Mengganti versi cache agar hasil pencarian lama tidak dipakai lagi.
        cache()->save('sekolah_search_version', (string) time(), DAY);

        return $report;
    }

    /**
     * Model sekolah dibuat saat dibutuhkan agar proses pratinjau
     * (yang tidak menyentuh database) tetap bisa diuji tanpa koneksi DB.
     */
    private function getSekolahModel(): MasterSekolahModel
    {
        return $this->sekolahModel ??= model(MasterSekolahModel::class);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, report: array<string, mixed>}
     */
    private function analyze(string $path, string $sumber): array
    {
        $this->assertReadableFile($path);

        $sample      = $this->readSampleRows($path);
        $header      = $this->detectHeader($sample);
        $headerRow   = $header['row'];
        $map         = $header['map'];
        $isPortal    = $this->isPortalFile($sample, $headerRow, $map);

        $report = [
            'file_type'          => $this->fileExtension($path),
            'is_portal'          => $isPortal,
            'header_row'         => $headerRow,
            'total_rows'         => 0,
            'valid'              => 0,
            'invalid'            => 0,
            'outside_province'   => 0,
            'filtered_bentuk'    => 0,
            'filtered_jalur'     => 0,
            'duplicates'         => 0,
            'incomplete_address' => 0,
            'errors'             => [],
            'error_truncated'    => false,
            'sample'             => [],
        ];

        $rows      = [];
        $seenNpsn  = [];
        $now       = date('Y-m-d H:i:s');
        $errorList = [];

        foreach ($this->dataRows($path, $headerRow) as $entry) {
            $values = array_map(static fn ($value): string => trim((string) $value), $entry['values']);

            if (implode('', $values) === '') {
                continue;
            }

            if (++$report['total_rows'] > self::MAX_DATA_ROWS) {
                throw new \RuntimeException('Berkas terlalu besar. Maksimal ' . self::MAX_DATA_ROWS . ' baris data.');
            }

            $get = static fn (string $field): string => isset($map[$field])
                ? trim((string) ($values[$map[$field]] ?? ''))
                : '';

            $bentuk = mb_strtoupper($this->cleanText($get('bentuk')));
            $jalur  = mb_strtoupper($this->cleanText($get('jalur')));
            $provinsi = $this->cleanText($get('provinsi'));

            if ($this->config->enforceProvinceFilter
                && $provinsi !== ''
                && $this->normalizeRegion($provinsi) !== $this->normalizeRegion($this->config->provinsi)) {
                $report['outside_province']++;
                continue;
            }

            if ($bentuk !== '' && ! in_array($bentuk, $this->config->bentukPendidikan, true)) {
                $report['filtered_bentuk']++;
                continue;
            }

            if ($jalur !== '' && ! in_array($jalur, $this->config->jalurPendidikan, true)) {
                $report['filtered_jalur']++;
                continue;
            }

            $npsn    = preg_replace('/\D/', '', $get('npsn'));
            $nama    = $this->cleanText($get('nama_sekolah'));
            $kabRaw  = $get('nama_kabupaten');
            $kab     = $this->resolveKabupaten($kabRaw);
            $provinsiValue = $provinsi !== '' ? mb_strtoupper($this->cleanText($provinsi)) : null;
            $kecamatan = $this->cleanText($get('nama_kecamatan'));
            $desa      = $this->cleanText($get('nama_desa'));
            $alamat    = $this->cleanText($get('alamat_jalan'));
            $status    = mb_strtoupper($this->cleanText($get('status')));

            if (strlen($npsn) !== 8) {
                $this->addError($report, $errorList, $entry['row'], 'NPSN harus 8 digit angka.');
                continue;
            }

            if (mb_strlen($nama) < 3) {
                $this->addError($report, $errorList, $entry['row'], 'Nama sekolah wajib diisi minimal 3 karakter.');
                continue;
            }

            if (trim($kabRaw) === '') {
                $this->addError($report, $errorList, $entry['row'], 'Kabupaten/Kota wajib diisi.');
                continue;
            }

            if ($kab === null) {
                if ($isPortal) {
                    $report['outside_province']++;
                    continue;
                }

                $this->addError(
                    $report,
                    $errorList,
                    $entry['row'],
                    "Kabupaten '{$kabRaw}' tidak termasuk wilayah {$this->config->provinsi}."
                );
                continue;
            }

            if ($kecamatan === '') {
                $this->addError($report, $errorList, $entry['row'], 'Kecamatan wajib diisi.');
                continue;
            }

            if (isset($seenNpsn[$npsn])) {
                $report['duplicates']++;
                continue;
            }

            $seenNpsn[$npsn] = true;

            if ($alamat === '' || $desa === '') {
                $report['incomplete_address']++;
            }

            $rows[] = [
                'npsn'             => $npsn,
                'nama_sekolah'     => mb_substr($nama, 0, 150),
                'nama_normalized'  => MasterSekolahModel::normalizeName($nama),
                'alamat_jalan'     => $alamat !== '' ? $alamat : null,
                'nama_desa'        => $desa !== '' ? $desa : null,
                'nama_kecamatan'   => mb_substr($kecamatan, 0, 100),
                'nama_kabupaten'   => $kab,
                'nama_provinsi'    => $provinsiValue ?? ($this->config->enforceProvinceFilter ? $this->config->provinsi : null),
                'bentuk_pendidikan' => $bentuk !== '' ? $bentuk : null,
                'status_sekolah'   => $status !== '' ? $status : null,
                'sumber'           => $sumber,
                'imported_at'      => $now,
            ];
        }

        $report['valid']  = count($rows);
        $report['sample'] = array_slice($rows, 0, 10);

        return ['rows' => $rows, 'report' => $report];
    }

    /**
     * Menambahkan pesan kesalahan baris, dibatasi agar respons tidak membengkak.
     *
     * @param array<string, mixed> $report
     * @param list<array{row: int, message: string}> $errorList
     */
    private function addError(array &$report, array &$errorList, int $row, string $message): void
    {
        $report['invalid']++;

        if (count($errorList) >= self::MAX_ERRORS) {
            $report['error_truncated'] = true;
            return;
        }

        $errorList[]        = ['row' => $row, 'message' => $message];
        $report['errors']   = $errorList;
    }

    /**
     * Memeriksa ekstensi dan isi dasar berkas.
     */
    private function assertReadableFile(string $path): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new FileNotFoundException('Berkas unggahan tidak ditemukan.');
        }

        $extension = $this->fileExtension($path);

        if (! in_array($extension, $this->config->allowedExtensions, true)) {
            throw new \RuntimeException('Format berkas harus .xlsx atau .csv.');
        }

        if ($extension === 'xlsx') {
            if (! class_exists(\ZipArchive::class)) {
                throw new \RuntimeException(
                    'Berkas .xlsx belum bisa dibaca karena ekstensi PHP zip tidak aktif. '
                    . 'Gunakan berkas .csv atau aktifkan extension=zip pada php.ini.'
                );
            }

            $handle = fopen($path, 'rb');
            $signature = $handle ? fread($handle, 4) : '';
            if ($handle) {
                fclose($handle);
            }

            if ($signature !== "PK\x03\x04") {
                throw new \RuntimeException('Berkas Excel tidak valid. Gunakan format .xlsx.');
            }

            return;
        }

        $handle = fopen($path, 'rb');
        $sample = $handle ? fread($handle, 1024) : '';
        if ($handle) {
            fclose($handle);
        }

        if ($sample === false || str_contains($sample, "\0")) {
            throw new \RuntimeException('Berkas CSV tidak valid.');
        }
    }

    /**
     * Mengambil beberapa baris pertama untuk mendeteksi header.
     *
     * @return list<array{row: int, values: list<string>}>
     */
    private function readSampleRows(string $path): array
    {
        if ($this->fileExtension($path) === 'csv') {
            return $this->readCsvSample($path);
        }

        return $this->readXlsxSample($path);
    }

    /**
     * @return list<array{row: int, values: list<string>}>
     */
    private function readCsvSample(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Berkas CSV tidak dapat dibaca.');
        }

        $rows = [];
        $line = 0;

        while (count($rows) < self::SAMPLE_ROWS && ($cells = fgetcsv($handle)) !== false) {
            $line++;
            $rows[] = [
                'row'    => $line,
                'values' => $this->normalizeCsvCells($cells, $line === 1),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<array{row: int, values: list<string>}>
     */
    private function readXlsxSample(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter($this->rowRangeFilter(1, self::SAMPLE_ROWS));

        $spreadsheet = $reader->load($path);
        $sheet       = $spreadsheet->getSheet(0);
        $lastColumn  = $sheet->getHighestDataColumn();
        $rows        = [];

        $values = $sheet->rangeToArray('A1:' . $lastColumn . self::SAMPLE_ROWS, null, false, true, false);
        foreach ($values as $index => $cells) {
            $rows[] = [
                'row'    => $index + 1,
                'values' => array_map(static fn ($value): string => (string) ($value ?? ''), $cells),
            ];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $rows;
    }

    /**
     * Menghasilkan baris data satu per satu tanpa memuat seluruh berkas.
     *
     * @return \Generator<int, array{row: int, values: list<string>}>
     */
    private function dataRows(string $path, int $headerRow): \Generator
    {
        if ($this->fileExtension($path) === 'csv') {
            yield from $this->csvDataRows($path, $headerRow);

            return;
        }

        yield from $this->xlsxDataRows($path, $headerRow);
    }

    /**
     * @return \Generator<int, array{row: int, values: list<string>}>
     */
    private function csvDataRows(string $path, int $headerRow): \Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Berkas CSV tidak dapat dibaca.');
        }

        $line = 0;

        while (($cells = fgetcsv($handle)) !== false) {
            $line++;

            if ($line <= $headerRow) {
                continue;
            }

            yield ['row' => $line, 'values' => $this->normalizeCsvCells($cells, false)];
        }

        fclose($handle);
    }

    /**
     * @return \Generator<int, array{row: int, values: list<string>}>
     */
    private function xlsxDataRows(string $path, int $headerRow): \Generator
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $info = $reader->listWorksheetInfo($path)[0] ?? null;
        if ($info === null) {
            throw new \RuntimeException('Lembar kerja Excel tidak ditemukan.');
        }

        $totalRows   = (int) ($info['totalRows'] ?? 0);
        $lastColumn  = (string) ($info['lastColumnLetter'] ?? '');
        $sheetName   = (string) ($info['worksheetName'] ?? '');

        for ($start = $headerRow + 1; $start <= $totalRows; $start += self::XLSX_CHUNK) {
            $end = min($totalRows, $start + self::XLSX_CHUNK - 1);

            $reader->setReadFilter($this->rowRangeFilter($start, $end));
            /** @var Spreadsheet $spreadsheet */
            $spreadsheet = $reader->load($path);
            $sheet       = $sheetName !== '' ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getSheet(0);

            if ($sheet === null) {
                $sheet = $spreadsheet->getSheet(0);
            }

            $column = $lastColumn !== '' ? $lastColumn : $sheet->getHighestDataColumn();
            $values = $sheet->rangeToArray('A' . $start . ':' . $column . $end, null, false, true, false);

            foreach ($values as $offset => $cells) {
                yield [
                    'row'    => $start + $offset,
                    'values' => array_map(static fn ($value): string => (string) ($value ?? ''), $cells),
                ];
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);
            gc_collect_cycles();
        }
    }

    /**
     * Menemukan baris header dan memetakan nama kolom ke indeks.
     *
     * @param list<array{row: int, values: list<string>}> $sampleRows
     *
     * @return array{row: int, map: array<string, int>}
     */
    private function detectHeader(array $sampleRows): array
    {
        $aliases = [];
        foreach ($this->config->columnAliases as $field => $fieldAliases) {
            foreach ($fieldAliases as $alias) {
                $aliases[$this->normalizeHeader($alias)] = $field;
            }
        }

        foreach ($sampleRows as $sample) {
            $map = [];

            foreach ($sample['values'] as $index => $cell) {
                $header = $this->normalizeHeader($cell);

                if ($header === '' || ! isset($aliases[$header])) {
                    continue;
                }

                $field = $aliases[$header];

                if (! isset($map[$field])) {
                    $map[$field] = $index;
                }
            }

            if (! isset($map['npsn'], $map['nama_sekolah'])) {
                continue;
            }

            $missing = array_diff($this->config->requiredColumns, array_keys($map));
            if ($missing !== []) {
                throw new \RuntimeException(
                    'Kolom wajib tidak ditemukan pada baris header: ' . implode(', ', $missing) . '.'
                );
            }

            return ['row' => $sample['row'], 'map' => $map];
        }

        throw new \RuntimeException(
            'Header berkas tidak dikenali. Pastikan terdapat kolom NPSN dan Nama, '
            . 'atau gunakan template Excel yang disediakan.'
        );
    }

    /**
     * Menentukan apakah berkas berasal dari portal (punya kolom Bentuk/Jalur
     * atau baris komentar wilayah) atau template internal.
     *
     * @param list<array{row: int, values: list<string>}> $sample
     * @param array<string, int> $map
     */
    private function isPortalFile(array $sample, int $headerRow, array $map): bool
    {
        if (isset($map['bentuk'], $map['jalur'])) {
            return true;
        }

        foreach ($sample as $entry) {
            if ($entry['row'] >= $headerRow) {
                continue;
            }

            $first = ltrim((string) ($entry['values'][0] ?? ''));
            if (str_starts_with($first, '#') && stripos($first, 'wilayah') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menormalkan nama kabupaten dan mencocokkannya dengan daftar wilayah.
     */
    private function resolveKabupaten(string $value): ?string
    {
        $key = $this->normalizeRegion($value);

        if ($key === '') {
            return null;
        }

        if (isset($this->kabupatenMap[$key])) {
            return $this->kabupatenMap[$key];
        }

        // Impor nasional: wilayah di luar daftar contoh Jawa Timur tetap
        // boleh masuk selama mode pembatasan provinsi tidak aktif.
        return $this->config->enforceProvinceFilter ? null : $key;
    }

    private function normalizeRegion(string $value): string
    {
        $value = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $value)));
        $value = str_replace(['.', ','], '', $value);
        $value = preg_replace('/^KABUPATEN\s+/', 'KAB ', $value);
        $value = preg_replace('/^KOTA\s+/', 'KOTA ', $value);

        return trim((string) $value);
    }

    private function normalizeHeader(?string $value): string
    {
        $value = str_replace("\xEF\xBB\xBF", '', (string) $value);
        $value = mb_strtolower(trim($value));
        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s*\/\s*/u', '/', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function cleanText(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '-' || $value === '—') {
            return '';
        }

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * @param list<string|null> $cells
     *
     * @return list<string>
     */
    private function normalizeCsvCells(array $cells, bool $stripBom): array
    {
        $normalized = array_map(static fn ($cell): string => (string) ($cell ?? ''), $cells);

        if ($stripBom && isset($normalized[0])) {
            $normalized[0] = str_replace("\xEF\xBB\xBF", '', $normalized[0]);
        }

        return $normalized;
    }

    private function fileExtension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    private function rowRangeFilter(int $start, int $end): IReadFilter
    {
        return new class ($start, $end) implements IReadFilter {
            public function __construct(private int $start, private int $end)
            {
            }

            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row >= $this->start && $row <= $this->end;
            }
        };
    }
}
