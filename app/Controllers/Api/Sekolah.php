<?php

namespace App\Controllers\Api;

use App\Models\Siswa\MasterSekolahModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Sekolah as SekolahConfig;

/**
 * Pencarian sekolah asal untuk autocomplete.
 *
 * Data diambil dari tabel lokal master_sekolah yang diisi admin melalui
 * menu Data Sekolah Asal, bukan dari API pihak ketiga secara langsung.
 */
class Sekolah extends ResourceController
{
    private SekolahConfig $config;
    private MasterSekolahModel $sekolahModel;

    public function __construct()
    {
        $this->config       = config('Sekolah');
        $this->sekolahModel = model(MasterSekolahModel::class);
    }

    public function search()
    {
        $keyword = trim((string) $this->request->getGet('q'));

        if (! $this->isAllowedByRateLimit()) {
            return $this->response->setStatusCode(429)->setJSON([
                'status'  => 'error',
                'message' => 'Terlalu banyak permintaan pencarian. Silakan coba lagi sebentar.',
            ]);
        }

        if (mb_strlen($keyword) < $this->config->searchMinLength) {
            return $this->respond([]);
        }

        $limit      = $this->config->searchLimit;
        $cacheKey   = $this->buildCacheKey($keyword);
        $cached     = cache()->get($cacheKey);

        if (is_array($cached)) {
            return $this->respond($cached);
        }

        $results = array_map(
            static function (array $row): array {
                $alamat = array_filter([
                    $row['alamat_jalan'] ?? null,
                    $row['nama_desa'] ?? null,
                    $row['nama_kecamatan'] ?? null,
                    $row['nama_kabupaten'] ?? null,
                    $row['nama_provinsi'] ?? null,
                ], static fn ($part): bool => is_string($part) && trim($part) !== '');

                return [
                    'id'             => (int) $row['id'],
                    'record_id'      => (int) $row['id'],
                    'npsn'           => $row['npsn'] !== null ? (string) $row['npsn'] : '',
                    'text'           => (string) $row['nama_sekolah'],
                    'alamat'         => (string) ($row['alamat_jalan'] ?? ''),
                    'alamat_lengkap' => implode(', ', $alamat),
                    'kecamatan'      => (string) ($row['nama_kecamatan'] ?? ''),
                    'kabupaten'      => (string) ($row['nama_kabupaten'] ?? ''),
                ];
            },
            $this->sekolahModel->searchByKeyword($keyword, $limit)
        );

        cache()->save($cacheKey, $results, $this->config->searchCacheTtl);

        return $this->respond($results);
    }

    /**
     * Batasi jumlah permintaan per alamat IP agar endpoint tetap ringan.
     */
    private function isAllowedByRateLimit(): bool
    {
        $key = 'sekolah-search-' . md5($this->request->getIPAddress());

        return service('throttler')->check($key, $this->config->searchThrottle, MINUTE) !== false;
    }

    /**
     * Kunci cache menyertakan versi data agar hasil lama langsung kedaluwarsa
     * setelah admin mengimpor atau mengubah data sekolah.
     */
    private function buildCacheKey(string $keyword): string
    {
        $version    = (string) (cache()->get('sekolah_search_version') ?: '1');
        $normalized = MasterSekolahModel::normalizeName($keyword);

        return 'sekolah_search_' . md5($version . '|' . $normalized);
    }
}
