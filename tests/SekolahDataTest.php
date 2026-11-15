<?php

use App\Models\Siswa\MasterSekolahModel;
use App\Services\SekolahImportService;
use App\Services\SekolahTemplateService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Sekolah as SekolahConfig;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Pengujian parser impor data sekolah dan template Excel.
 *
 * Pengujian ini tidak memakai database: layanan pratinjau hanya membaca
 * berkas dan mengembalikan laporan.
 *
 * @internal
 */
final class SekolahDataTest extends CIUnitTestCase
{
    private SekolahConfig $config;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = config('Sekolah');
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->tempFiles = [];
        parent::tearDown();
    }

    public function testTemplateHasDataSheetFirstWithSixColumns(): void
    {
        $this->skipIfXlsxUnsupported();

        $content = (new SekolahTemplateService($this->config))->render();
        $this->assertNotSame('', $content);

        $path = $this->writeTempFile($content, 'xlsx');
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $this->assertSame('Data Sekolah', $spreadsheet->getSheet(0)->getTitle());

        $header = $spreadsheet->getSheet(0)->rangeToArray('A1:I1', null, false, false, false)[0];
        $this->assertSame(
            ['NPSN', 'Nama Sekolah', 'Alamat Jalan', 'Desa/Kelurahan', 'Kecamatan', 'Kabupaten/Kota', 'Provinsi', 'Bentuk Pendidikan', 'Status Sekolah'],
            $header
        );

        $this->assertSame('Referensi', $spreadsheet->getSheet(2)->getTitle());
        // Baris header + 38 kabupaten/kota Jawa Timur.
        $this->assertSame(39, $spreadsheet->getSheet(2)->getHighestDataRow());

        $spreadsheet->disconnectWorksheets();
    }

    public function testTemplateCsvContainsOnlyHeaderRow(): void
    {
        $content = (new SekolahTemplateService($this->config))->renderCsv();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $path = $this->writeTempFile($content, 'csv');

        $handle = fopen($path, 'rb');
        $header = fgetcsv($handle);
        $this->assertNotFalse($header, 'Header CSV harus dapat dibaca.');
        $this->assertFalse(fgetcsv($handle), 'Template CSV hanya boleh berisi baris header.');

        fclose($handle);

        $header[0] = str_replace("\xEF\xBB\xBF", '', (string) $header[0]);

        $this->assertSame(['NPSN', 'Nama Sekolah', 'Alamat Jalan', 'Desa/Kelurahan', 'Kecamatan', 'Kabupaten/Kota', 'Provinsi', 'Bentuk Pendidikan', 'Status Sekolah'], $header);
    }

    public function testPreviewTemplateFileWithoutRowsIsEmptyReport(): void
    {
        $this->skipIfXlsxUnsupported();

        $content = (new SekolahTemplateService($this->config))->render();
        $path    = $this->writeTempFile($content, 'xlsx');

        $report = (new SekolahImportService($this->config))->preview($path);

        $this->assertFalse($report['is_portal']);
        $this->assertSame(0, $report['total_rows']);
        $this->assertSame(0, $report['valid']);
        $this->assertSame([], $report['errors']);
    }

    public function testPreviewPortalCsvFiltersRowsAndCountsIssues(): void
    {
        $csv = <<<CSV
        # Data Induk Satuan Pendidikan
        # Wilayah: JAWA TIMUR
        NPSN,Nama,Bentuk,Jenis,Status,Jenjang,Kabupaten,Kecamatan,Kelurahan,Alamat,Jalur,Pembina
        20500001,SMP NEGERI 1 SURABAYA,SMP,Umum,NEGERI,SMP,KOTA SURABAYA,GUBENG,Airlangga,Jl. Airlangga 1,Formal,Dinas
        20500002,MTs NEGERI 2 MALANG,MTs,Umum,NEGERI,MTs,KAB. MALANG,Lowokwaru,,,Formal,Dinas
        20500003,SMA NEGERI 1 SURABAYA,SMA,Umum,NEGERI,SMA,KOTA SURABAYA,Gubeng,Airlangga,Jl. A,Formal,Dinas
        20500004,SMP LUAR JATIM,SMP,Umum,NEGERI,SMP,KOTA BANDUNG,Coblong,,,Formal,Dinas
        20500001,SMP NEGERI 1 SURABAYA (DUPLIKAT),SMP,Umum,NEGERI,SMP,KOTA SURABAYA,Gubeng,Airlangga,Jl. B,Formal,Dinas
        2050000,SMP NPSN SALAH,SMP,Umum,NEGERI,SMP,KOTA SURABAYA,Gubeng,,,Formal,Dinas
        CSV;

        $path   = $this->writeTempFile($csv, 'csv');
        $report = (new SekolahImportService($this->config))->preview($path);

        $this->assertTrue($report['is_portal']);
        $this->assertSame(3, $report['header_row']);
        $this->assertSame(6, $report['total_rows']);
        $this->assertSame(3, $report['valid']);
        $this->assertSame(1, $report['filtered_bentuk']);
        $this->assertSame(0, $report['outside_province']);
        $this->assertSame(1, $report['duplicates']);
        $this->assertSame(1, $report['invalid']);
        $this->assertSame(2, $report['incomplete_address']);
    }

    public function testPreviewAcceptsTemplateWithOptionalColumnsEmpty(): void
    {
        $csv = <<<CSV
        NPSN,Nama,Kabupaten,Kecamatan,Kelurahan,Alamat
        20501001,SMP NEGERI 3 BLITAR,Kabupaten Blitar,KANIGORO,,
        CSV;

        $path   = $this->writeTempFile($csv, 'csv');
        $report = (new SekolahImportService($this->config))->preview($path);

        $this->assertFalse($report['is_portal']);
        $this->assertSame(1, $report['valid']);
        $this->assertSame(1, $report['incomplete_address']);
        $this->assertSame('KAB. BLITAR', $report['sample'][0]['nama_kabupaten']);
        $this->assertNull($report['sample'][0]['nama_desa']);
        $this->assertNull($report['sample'][0]['alamat_jalan']);
        $this->assertSame('smp negeri 3 blitar', $report['sample'][0]['nama_normalized']);
    }

    public function testPreviewAcceptsNewTemplateColumnsFromAnyProvince(): void
    {
        $csv = <<<CSV
        NPSN,Nama Sekolah,Alamat Jalan,Desa/Kelurahan,Kecamatan,Kabupaten/Kota,Provinsi,Bentuk Pendidikan,Status Sekolah
        20503001,SMP NEGERI 1 SURABAYA,Jl. Airlangga 1,Air Langga,GUBENG,KOTA SURABAYA,JAWA TIMUR,SMP,NEGERI
        20503002,MTs NEGERI 7 BANDUNG,,Tlogomas,LOWOKWARU,KOTA BANDUNG,Jawa Barat,MTS,NEGERI
        CSV;

        $path   = $this->writeTempFile($csv, 'csv');
        $report = (new SekolahImportService($this->config))->preview($path);

        $this->assertFalse($report['is_portal']);
        $this->assertSame(2, $report['total_rows']);
        $this->assertSame(2, $report['valid']);
        $this->assertSame(0, $report['outside_province']);
        $this->assertSame('SMP NEGERI 1 SURABAYA', $report['sample'][0]['nama_sekolah']);
        $this->assertSame('NEGERI', $report['sample'][0]['status_sekolah']);
        $this->assertSame('SMP', $report['sample'][0]['bentuk_pendidikan']);
        $this->assertSame('KOTA BANDUNG', $report['sample'][1]['nama_kabupaten']);
        $this->assertSame('JAWA BARAT', $report['sample'][1]['nama_provinsi']);
    }

    public function testPreviewRejectsUnknownHeader(): void
    {
        $path = $this->writeTempFile("Foo,Bar\n1,2\n", 'csv');

        $this->expectException(\RuntimeException::class);
        (new SekolahImportService($this->config))->preview($path);
    }

    public function testPreviewAcceptsNonJatimRowWhenProvinceFilterIsDisabled(): void
    {
        $csv = <<<CSV
        NPSN,Nama,Kabupaten,Kecamatan,Kelurahan,Alamat
        20502001,SMP NEGERI 1 BANDUNG,KOTA BANDUNG,COBLONG,,Jl. A
        CSV;

        $path   = $this->writeTempFile($csv, 'csv');
        $report = (new SekolahImportService($this->config))->preview($path);

        $this->assertSame(1, $report['valid']);
        $this->assertSame(0, $report['invalid']);
        $this->assertSame(0, $report['outside_province']);
        $this->assertSame('KOTA BANDUNG', $report['sample'][0]['nama_kabupaten']);
    }

    public function testPreviewRejectsNonJatimRowWhenProvinceFilterIsEnforced(): void
    {
        $config = new SekolahConfig();
        $config->enforceProvinceFilter = true;

        $csv = <<<CSV
        NPSN,Nama,Kabupaten,Kecamatan,Kelurahan,Alamat
        20502001,SMP NEGERI 1 BANDUNG,KOTA BANDUNG,COBLONG,,Jl. A
        CSV;

        $path   = $this->writeTempFile($csv, 'csv');
        $report = (new SekolahImportService($config))->preview($path);

        $this->assertSame(0, $report['valid']);
        $this->assertSame(1, $report['invalid']);
        $this->assertSame(0, $report['outside_province']);
        $this->assertStringContainsString('tidak termasuk wilayah', $report['errors'][0]['message']);
    }

    public function testNormalizeNameCollapsesWhitespaceAndCase(): void
    {
        $this->assertSame(
            'smp negeri 1 surabaya',
            MasterSekolahModel::normalizeName("  SMP   Negeri\t1  Surabaya ")
        );
    }

    private function writeTempFile(string $content, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sekolah-test-') . '.' . $extension;

        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    /**
     * Pembuatan berkas .xlsx membutuhkan ekstensi zip; uji khusus XLSX
     * dilewati bila ekstensi belum aktif agar suite tetap hijau.
     */
    private function skipIfXlsxUnsupported(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('Ekstensi PHP zip belum aktif.');
        }
    }
}
