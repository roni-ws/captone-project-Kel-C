<?php

namespace App\Services;

use Config\Sekolah as SekolahConfig;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Membuat template Excel untuk impor data sekolah asal.
 *
 * Lembar pertama bernama "Data Sekolah" karena proses impor membaca sheet
 * pertama sebagai sumber data. Dua lembar lain hanya berisi petunjuk dan
 * daftar kabupaten sebagai referensi pengisian.
 */
class SekolahTemplateService
{
    private const HEADER_ROW    = 1;
    private const MAX_DATA_ROWS = 5000;

    private const COLUMNS = [
        'NPSN'              => ['width' => 12, 'hint' => '8 digit angka sesuai data induk. Wajib.'],
        'Nama Sekolah'      => ['width' => 42, 'hint' => 'Nama sekolah tanpa gelar, contoh: SMP NEGERI 1 SURABAYA. Wajib.'],
        'Alamat Jalan'      => ['width' => 48, 'hint' => 'Opsional. Nama jalan dan nomor. Boleh dikosongkan.'],
        'Desa/Kelurahan'    => ['width' => 24, 'hint' => 'Opsional. Nama desa/kelurahan sesuai data induk.'],
        'Kecamatan'         => ['width' => 24, 'hint' => 'Nama kecamatan sesuai data induk. Wajib.'],
        'Kabupaten/Kota'    => ['width' => 24, 'hint' => 'Ketik atau pilih contoh dari lembar Referensi. Wajib.'],
        'Provinsi'          => ['width' => 22, 'hint' => 'Isi provinsi sekolah, contoh JAWA TIMUR. Direkomendasikan.'],
        'Bentuk Pendidikan' => ['width' => 20, 'hint' => 'Opsional. Isi SMP atau MTS bila tersedia.'],
        'Status Sekolah'    => ['width' => 18, 'hint' => 'Opsional. Contoh: NEGERI atau SWASTA.'],
    ];

    private SekolahConfig $config;

    public function __construct(?SekolahConfig $config = null)
    {
        $this->config = $config ?? config('Sekolah');
    }

    /**
     * Membuat berkas template dan mengembalikan isinya dalam bentuk biner.
     *
     * @throws \RuntimeException bila ekstensi zip tidak aktif.
     */
    public function render(): string
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException(
                'Ekstensi PHP zip belum aktif, sehingga template Excel tidak dapat dibuat. '
                . 'Aktifkan extension=zip pada php.ini atau gunakan template CSV.'
            );
        }

        $spreadsheet = $this->build();

        $path = tempnam(sys_get_temp_dir(), 'template-sekolah-') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        $content = (string) file_get_contents($path);
        @unlink($path);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        return $content;
    }

    /**
     * Template CSV sederhana sebagai alternatif bila ekstensi zip tidak aktif.
     *
     * Hanya berisi baris header; daftar kabupaten tersedia pada halaman admin.
     */
    public function renderCsv(): string
    {
        $handle = fopen('php://temp', 'r+');

        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, array_keys(self::COLUMNS));

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    /**
     * Menyusun struktur workbook template.
     */
    private function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('SPMB')
            ->setTitle('Template Data Sekolah Asal')
            ->setDescription('Template impor data sekolah asal (SMP/MTs) Provinsi ' . $this->config->provinsi);

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data Sekolah');
        $this->fillDataSheet($dataSheet);

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Petunjuk');
        $this->fillGuideSheet($guideSheet);

        $referenceSheet = $spreadsheet->createSheet();
        $referenceSheet->setTitle('Referensi');
        $this->fillReferenceSheet($referenceSheet);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function fillDataSheet(Worksheet $sheet): void
    {
        $columnIndex = 1;
        foreach (self::COLUMNS as $title => $meta) {
            $letter = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue($letter . self::HEADER_ROW, $title);
            $sheet->getColumnDimension($letter)->setWidth($meta['width']);
            $columnIndex++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count(self::COLUMNS));
        $headerRange = 'A' . self::HEADER_ROW . ':' . $lastColumn . self::HEADER_ROW;

        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]],
        ]);
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(24);

        // Format kolom NPSN sebagai teks agar angka nol di depan tidak hilang.
        $sheet->getStyle('A2:A' . (self::MAX_DATA_ROWS + self::HEADER_ROW))
            ->getNumberFormat()
            ->setFormatCode('@');

        // Area data diberi garis tipis supaya rapi saat diisi manual.
        $dataRange = 'A2:' . $lastColumn . (self::MAX_DATA_ROWS + self::HEADER_ROW);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE0E0E0']]],
        ]);

        $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('C2:C' . (self::MAX_DATA_ROWS + self::HEADER_ROW))
            ->getAlignment()
            ->setWrapText(true);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter($headerRange);

        // Catatan singkat di atas komentar sel header.
        $columnIndex = 1;
        foreach (self::COLUMNS as $meta) {
            $letter = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getComment($letter . self::HEADER_ROW)
                ->getText()
                ->createText($meta['hint']);
            $columnIndex++;
        }
    }

    private function fillGuideSheet(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(110);

        $guideSheetTitle = 'PETUNJUK PENGISIAN TEMPLATE DATA SEKOLAH ASAL';
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A1', $guideSheetTitle);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF1F4E79');
        $sheet->getRowDimension(1)->setRowHeight(28);

        $lines = [
            ['1.', 'Isi data pada lembar "Data Sekolah" mulai baris 2. Jangan mengubah atau menghapus baris header.'],
            ['2.', 'Kolom NPSN, Nama Sekolah, Kecamatan, dan Kabupaten/Kota wajib diisi. Kolom lainnya opsional.'],
            ['3.', 'Nama kolom pada template sudah disesuaikan dengan kolom yang disimpan ke tabel master_sekolah.'],
            ['4.', 'NPSN harus tepat 8 digit angka. NPSN yang sudah ada akan diperbarui, NPSN baru akan ditambahkan.'],
            ['5.', 'Provinsi sebaiknya diisi. Bila pembatasan provinsi diaktifkan, baris dari provinsi lain akan dilewati. Secara bawaan semua provinsi diterima.'],
            ['6.', 'Isi Bentuk Pendidikan dengan SMP atau MTS bila tersedia. Baris selain SMP/MTS akan dilewati.'],
            ['7.', 'Kabupaten/Kota dapat dipilih dari lembar "Referensi" (contoh wilayah Jawa Timur) atau diketik sendiri untuk wilayah lain. Penulisan "Kabupaten" otomatis disamakan menjadi "KAB." oleh sistem.'],
            ['8.', 'Gunakan unduhan resmi dari Data Induk Satuan Pendidikan Kemendikdasmen bila ingin mengimpor banyak sekolah sekaligus:'],
            ['', $this->config->portalUrl],
            ['9.', 'Berkas resmi portal boleh diunggah langsung tanpa diubah. Sistem mengambil sekolah SMP/MTs dari Provinsi yang tercantum pada berkas, atau dari semua provinsi bila kolom Provinsi kosong dan pembatasan tidak aktif.'],
            ['10.', 'Sekolah yang tidak ada pada data induk tetap dapat diinput manual oleh siswa. Entri manual tidak akan dihapus oleh proses impor.'],
            ['11.', 'Maksimal 5.000 baris per unggahan template dan 10 MB per berkas.'],
        ];

        $row = 3;
        foreach ($lines as [$number, $text]) {
            $sheet->setCellValue('A' . $row, $number);
            $sheet->setCellValue('B' . $row, $text);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($row)->setRowHeight(19);
            $row++;
        }

        $sheet->getStyle('B3:B' . ($row - 1))->getFont()->setSize(11);
    }

    private function fillReferenceSheet(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'Kabupaten/Kota (contoh)');
        $sheet->setCellValue('B1', 'Provinsi (contoh)');
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
        ]);
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(20);

        $row = 2;
        foreach ($this->config->kabupaten as $kabupaten) {
            $sheet->setCellValue('A' . $row, $kabupaten);
            $sheet->setCellValue('B' . $row, $this->config->provinsi);
            $row++;
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:B' . ($row - 1));
    }
}
