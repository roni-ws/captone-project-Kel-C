<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi data sekolah asal (master_sekolah).
 *
 * Nilai di sini menjadi satu sumber kebenaran untuk impor, template Excel,
 * pencarian autocomplete, dan validasi form.
 */
class Sekolah extends BaseConfig
{
    /**
     * Provinsi default untuk tampilan/template awal. Tidak dipakai sebagai
     * batasan bila $enforceProvinceFilter bernilai false.
     */
    public string $provinsi     = 'JAWA TIMUR';
    public string $provinsiKode = '050000';

    /**
     * true  = hanya terima sekolah dari $provinsi.
     * false = terima sekolah dari provinsi mana pun; nilai tanpa cocok dengan
     *         $kabupaten tetap diimpor sebagai teks ternormalisasi.
     */
    public bool $enforceProvinceFilter = false;

    /**
     * Halaman resmi sumber data (Data Induk Satuan Pendidikan Kemendikdasmen).
     * Admin mengunduh berkas dari sini, lalu mengunggahnya lewat menu Data Sekolah Asal.
     */
    public string $portalUrl = 'https://data.kemendikdasmen.go.id/data-induk/satpen/daftar?bentuk=smp,mts';

    /**
     * Daftar contoh/saran kabupaten/kota Provinsi Jawa Timur sesuai penamaan
     * data induk. Ini bukan batasan ketat: admin dapat mengisi wilayah lain.
     *
     * @var list<string>
     */
    public array $kabupaten = [
        'KAB. BANGKALAN',
        'KAB. BANYUWANGI',
        'KAB. BLITAR',
        'KAB. BOJONEGORO',
        'KAB. BONDOWOSO',
        'KAB. GRESIK',
        'KAB. JEMBER',
        'KAB. JOMBANG',
        'KAB. KEDIRI',
        'KAB. LAMONGAN',
        'KAB. LUMAJANG',
        'KAB. MADIUN',
        'KAB. MAGETAN',
        'KAB. MALANG',
        'KAB. MOJOKERTO',
        'KAB. NGANJUK',
        'KAB. NGAWI',
        'KAB. PACITAN',
        'KAB. PAMEKASAN',
        'KAB. PASURUAN',
        'KAB. PONOROGO',
        'KAB. PROBOLINGGO',
        'KAB. SAMPANG',
        'KAB. SIDOARJO',
        'KAB. SITUBONDO',
        'KAB. SUMENEP',
        'KAB. TRENGGALEK',
        'KAB. TUBAN',
        'KAB. TULUNGAGUNG',
        'KOTA BATU',
        'KOTA BLITAR',
        'KOTA KEDIRI',
        'KOTA MADIUN',
        'KOTA MALANG',
        'KOTA MOJOKERTO',
        'KOTA PASURUAN',
        'KOTA PROBOLINGGO',
        'KOTA SURABAYA',
    ];

    /**
     * Pemetaan kolom berkas ke kolom database.
     * Kunci = field database, nilai = alias header yang diterima (huruf kecil).
     *
     * @var array<string, list<string>>
     */
    public array $columnAliases = [
        'npsn'           => ['npsn'],
        'nama_sekolah'   => ['nama', 'nama sekolah', 'nama satuan pendidikan'],
        'nama_kabupaten' => ['kabupaten', 'kabupaten/kota', 'kabupaten / kota'],
        'nama_kecamatan' => ['kecamatan'],
        'nama_desa'      => ['kelurahan', 'desa', 'desa/kelurahan', 'desa / kelurahan'],
        'alamat_jalan'   => ['alamat', 'alamat jalan'],
        // Kolom opsional pada berkas portal; hanya dipakai untuk penyaringan.
        'bentuk' => ['bentuk', 'bentuk pendidikan'],
        'jalur'  => ['jalur', 'jalur pendidikan'],
        'status' => ['status', 'status satuan pendidikan', 'status sekolah'],
        'provinsi' => ['provinsi', 'nama provinsi'],
    ];

    /**
     * Kolom yang wajib ada pada berkas unggahan.
     *
     * @var list<string>
     */
    public array $requiredColumns = [
        'npsn',
        'nama_sekolah',
        'nama_kabupaten',
        'nama_kecamatan',
    ];

    /**
     * Nilai yang diizinkan untuk penyaringan berkas portal.
     *
     * @var list<string>
     */
    public array $bentukPendidikan = ['SMP', 'MTS'];
    public array $jalurPendidikan  = ['FORMAL'];

    /**
     * Batas unggahan berkas (KB) dan ekstensi yang diterima.
     *
     * @var list<string>
     */
    public int $maxUploadSizeKb = 10240;
    public array $allowedExtensions = ['xlsx', 'csv'];

    /**
     * Pengaturan pencarian autocomplete.
     */
    public int $searchMinLength = 3;
    public int $searchLimit     = 20;
    public int $searchCacheTtl  = 600;
    public int $searchThrottle  = 120;

    public string $templateFileName = 'template-data-sekolah.xlsx';
}
