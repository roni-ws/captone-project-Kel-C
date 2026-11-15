<?php

namespace App\Models\Siswa;

use CodeIgniter\Model;

class MasterSekolahModel extends Model
{
    /** Kolom yang ditampilkan pada hasil autocomplete dan daftar admin. */
    public const SEARCH_COLUMNS = 'id, npsn, nama_sekolah, alamat_jalan, nama_desa, '
        . 'nama_kecamatan, nama_kabupaten, nama_provinsi, bentuk_pendidikan, status_sekolah, sumber';

    protected $table            = 'master_sekolah';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\MasterSekolah::class;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_sekolah',
        'npsn',
        'alamat_jalan',
        'nama_desa',
        'nama_kecamatan',
        'nama_kabupaten',
        'nama_provinsi',
        'bentuk_pendidikan',
        'status_sekolah',
        'nama_normalized',
        'sumber',
        'imported_at',
    ];

    protected $validationRules = [
        'nama_sekolah' => 'required|min_length[3]|max_length[150]',
        'npsn'         => 'permit_empty|numeric|exact_length[8]',
        'sumber'       => 'required|in_list[manual,dapodik,admin]',
    ];

    /**
     * Menyeragamkan nama sekolah untuk keperluan pencarian.
     */
    public static function normalizeName(string $name): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($name));

        return mb_strtolower((string) $collapsed);
    }

    /**
     * Mencari sekolah untuk autocomplete.
     *
     * Urutan prioritas: awalan nama, lalu awalan kata, terakhir kemiripan di
     * tengah nama. Hasil selalu dibatasi agar respons tetap ringan.
     *
     * @return list<array<string, mixed>>
     */
    public function searchByKeyword(string $keyword, int $limit = 20): array
    {
        $normalized = self::normalizeName($keyword);

        if (mb_strlen($normalized) < 3) {
            return [];
        }

        $limit    = max(1, min($limit, 50));
        $results  = [];
        $seenIds  = [];
        $patterns = [
            $normalized . '%',
            '% ' . $normalized . '%',
            '%' . $normalized . '%',
        ];

        foreach ($patterns as $pattern) {
            if (count($results) >= $limit) {
                break;
            }

            $rows = $this->baseSearchBuilder()
                ->where('nama_normalized LIKE', $pattern)
                ->orderBy('nama_sekolah', 'ASC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $id = (int) $row['id'];
                if (isset($seenIds[$id])) {
                    continue;
                }

                $seenIds[$id] = true;
                $results[]    = $row;

                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Mengambil sekolah berdasarkan NPSN (8 digit).
     *
     * @return array<string, mixed>|null
     */
    public function findByNpsn(?string $npsn): ?array
    {
        $digits = preg_replace('/\D/', '', (string) $npsn);

        if (strlen($digits) !== 8) {
            return null;
        }

        $row = $this->db->table($this->table)
            ->where('npsn', $digits)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Mencari ID sekolah yang benar-benar ada di database.
     *
     * Dipakai untuk memverifikasi pilihan dari klien sehingga teks yang
     * dikirim tidak langsung dipercaya.
     */
    public function resolveExistingId(?int $sekolahId, ?string $npsn, ?string $namaSekolah): ?int
    {
        if ($sekolahId !== null && $sekolahId > 0) {
            $exists = $this->db->table($this->table)->select('id')->where('id', $sekolahId)->get()->getRowArray();
            if ($exists) {
                return (int) $exists['id'];
            }
        }

        $byNpsn = $this->findByNpsn($npsn);
        if ($byNpsn) {
            return (int) $byNpsn['id'];
        }

        $normalized = self::normalizeName((string) $namaSekolah);
        if ($normalized === '') {
            return null;
        }

        $row = $this->db->table($this->table)
            ->select('id')
            ->where('nama_normalized', $normalized)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        return $row ? (int) $row['id'] : null;
    }

    /**
     * Mengambil ID sekolah berdasarkan nama, atau membuat entri manual baru.
     */
    public function getOrCreateByName(string $namaSekolah, string $sumber = 'manual'): int
    {
        $nama = trim(preg_replace('/\s+/u', ' ', $namaSekolah));

        if ($nama === '') {
            throw new \InvalidArgumentException('Nama sekolah tidak boleh kosong.');
        }

        $normalized = self::normalizeName($nama);
        $existing   = $this->db->table($this->table)
            ->select('id')
            ->where('nama_normalized', $normalized)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        if ($existing) {
            return (int) $existing['id'];
        }

        $this->db->table($this->table)->insert([
            'nama_sekolah'    => $nama,
            'nama_normalized' => $normalized,
            'sumber'          => $sumber,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Jumlah baris maksimum per query INSERT/pengecekan NPSN.
     *
     * Berkas Dapodik lengkap dapat berisi puluhan ribu sekolah; mengirim
     * semuanya dalam satu query melebihi max_allowed_packet MySQL dan
     * memutus koneksi.
     */
    private const UPSERT_CHUNK = 200;

    /**
     * Menyimpan hasil impor secara batch dengan kunci NPSN.
     *
     * Nilai kosong pada berkas tidak menimpa data lama (COALESCE + NULLIF),
     * sehingga unggahan template yang tidak lengkap tetap aman.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return array{inserted: int, updated: int}
     *
     * @throws \RuntimeException jika query ke database gagal
     */
    public function upsertBatch(array $rows): array
    {
        if ($rows === []) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $inserted = 0;
        $updated  = 0;

        foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
            $result    = $this->upsertChunk($chunk);
            $inserted += $result['inserted'];
            $updated  += $result['updated'];
        }

        return ['inserted' => $inserted, 'updated' => $updated];
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array{inserted: int, updated: int}
     */
    private function upsertChunk(array $rows): array
    {
        $npsnList = array_column($rows, 'npsn');

        $existing = [];
        foreach (array_chunk($npsnList, self::UPSERT_CHUNK) as $npsnChunk) {
            $result = $this->db->table($this->table)
                ->select('npsn')
                ->whereIn('npsn', $npsnChunk)
                ->get();

            if ($result === false) {
                throw new \RuntimeException('Gagal memeriksa NPSN yang sudah tersimpan.');
            }

            $existing = array_merge($existing, $result->getResultArray());
        }

        $updatedCount = count($existing);
        $columns      = [
            'npsn', 'nama_sekolah', 'nama_normalized', 'alamat_jalan', 'nama_desa',
            'nama_kecamatan', 'nama_kabupaten', 'nama_provinsi', 'bentuk_pendidikan',
            'status_sekolah', 'sumber', 'imported_at',
        ];

        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $valuesSql      = implode(', ', array_fill(0, count($rows), $rowPlaceholder));
        $columnSql      = '`' . implode('`, `', $columns) . '`';

        $updateSql = <<<'SQL'
            nama_sekolah     = VALUES(nama_sekolah),
            nama_normalized  = VALUES(nama_normalized),
            alamat_jalan     = COALESCE(NULLIF(VALUES(alamat_jalan), ''), alamat_jalan),
            nama_desa        = COALESCE(NULLIF(VALUES(nama_desa), ''), nama_desa),
            nama_kecamatan   = COALESCE(NULLIF(VALUES(nama_kecamatan), ''), nama_kecamatan),
            nama_kabupaten   = COALESCE(NULLIF(VALUES(nama_kabupaten), ''), nama_kabupaten),
            nama_provinsi    = COALESCE(NULLIF(VALUES(nama_provinsi), ''), nama_provinsi),
            bentuk_pendidikan = COALESCE(NULLIF(VALUES(bentuk_pendidikan), ''), bentuk_pendidikan),
            status_sekolah   = COALESCE(NULLIF(VALUES(status_sekolah), ''), status_sekolah),
            sumber           = VALUES(sumber),
            imported_at      = VALUES(imported_at)
            SQL;

        $sql = "INSERT INTO `{$this->table}` ({$columnSql}) VALUES {$valuesSql} "
            . "ON DUPLICATE KEY UPDATE {$updateSql}";

        $binds = [];
        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $binds[] = $row[$column] ?? null;
            }
        }

        $executed = $this->db->query($sql, $binds);

        if ($executed === false) {
            throw new \RuntimeException('Gagal menyimpan batch data sekolah ke database.');
        }

        return [
            'inserted' => count($rows) - $updatedCount,
            'updated'  => $updatedCount,
        ];
    }

    /**
     * Apakah sekolah sudah dipakai pada data calon siswa.
     */
    public function isUsedByStudent(int $sekolahId): bool
    {
        return $this->countStudentsUsing($sekolahId) > 0;
    }

    /**
     * Jumlah calon siswa yang memakai sekolah tertentu.
     */
    public function countStudentsUsing(int $sekolahId): int
    {
        return (int) $this->db->table('calon_siswa')
            ->where('sekolah_id', $sekolahId)
            ->countAllResults();
    }

    /**
     * Menghapus sekolah hanya jika belum direferensikan calon siswa.
     *
     * FK calon_siswa.sekolah_id memakai ON DELETE CASCADE, sehingga
     * penghapusan tanpa pemeriksaan ini akan menghapus data siswa.
     *
     * Query DELETE pada CodeIgniter mengembalikan bool, bukan jumlah
     * baris; jumlah terpengaruh dibaca dari affectedRows().
     */
    public function deleteIfUnused(int $sekolahId): bool
    {
        if ($this->isUsedByStudent($sekolahId)) {
            return false;
        }

        $result = $this->db->table($this->table)->where('id', $sekolahId)->delete();

        if ($result === false) {
            return false;
        }

        return $this->db->affectedRows() === 1;
    }

    /**
     * Menemukan ID sekolah placeholder DEFAULT_SEKOLAH, membuatnya bila belum ada.
     */
    public function findOrCreateBelumDiisiId(): int
    {
        $existing = $this->db->table($this->table)
            ->select('id')
            ->where('nama_sekolah', DEFAULT_SEKOLAH)
            ->get()
            ->getRowArray();

        if ($existing) {
            return (int) $existing['id'];
        }

        $this->db->table($this->table)->insert([
            'nama_sekolah'    => DEFAULT_SEKOLAH,
            'nama_normalized' => self::normalizeName(DEFAULT_SEKOLAH),
            'sumber'          => 'manual',
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Menyusun alamat lengkap dari kolom master_sekolah.
     */
    private function composeAlamat(array $row): string
    {
        $parts = array_filter([
            $row['alamat_jalan'] ?? null,
            $row['nama_desa'] ?? null,
            $row['nama_kecamatan'] ?? null,
            $row['nama_kabupaten'] ?? null,
            $row['nama_provinsi'] ?? null,
        ], static fn ($part): bool => is_string($part) && trim($part) !== '');

        return implode(', ', array_map('trim', $parts));
    }

    /**
     * Memindahkan semua referensi siswa dari sekolah yang dihapus ke sekolah
     * lain (atau ke placeholder DEFAULT_SEKOLAH), lalu menghapus sekolahnya.
     *
     * @param int|null $targetId Sekolah tujuan; null berarti DEFAULT_SEKOLAH
     * @return int Jumlah pendaftar yang dipindahkan
     *
     * @throws \RuntimeException bila sekolah dipilih tidak valid atau gagal terhapus
     */
    public function deleteWithReassign(int $sekolahId, ?int $targetId): int
    {
        $source = $this->db->table($this->table)->where('id', $sekolahId)->get()->getRowArray();

        if ($source === null) {
            throw new \RuntimeException('Data sekolah tidak ditemukan.');
        }

        $this->db->transBegin();

        try {
            $toDefault = $targetId === null;

            if ($targetId !== null) {
                if ($targetId === $sekolahId) {
                    throw new \RuntimeException('Sekolah tujuan tidak boleh sama dengan sekolah yang dihapus.');
                }

                $target = $this->db->table($this->table)->where('id', $targetId)->get()->getRowArray();

                if ($target === null) {
                    throw new \RuntimeException('Sekolah tujuan tidak ditemukan.');
                }
            } else {
                $targetId = $this->findOrCreateBelumDiisiId();
                $target   = ['alamat_jalan' => null, 'nama_desa' => null, 'nama_kecamatan' => null, 'nama_kabupaten' => null, 'nama_provinsi' => null];
            }

            $affected = $this->countStudentsUsing($sekolahId);

            if ($affected > 0) {
                // Alamat sekolah siswa ikut disesuaikan dengan sekolah tujuan
                // (atau placeholder) agar tidak tertinggal dari data lama.
                $alamat = $toDefault ? DEFAULT_SEKOLAH : $this->composeAlamat($target);

                $updated = $this->db->table('calon_siswa')
                    ->where('sekolah_id', $sekolahId)
                    ->update([
                        'sekolah_id'     => $targetId,
                        'alamat_sekolah' => $toDefault ? DEFAULT_SEKOLAH : ($alamat !== '' ? $alamat : null),
                    ]);

                if ($updated === false) {
                    throw new \RuntimeException('Gagal memindahkan data pendaftar.');
                }
            }

            $deleted = $this->db->table($this->table)->where('id', $sekolahId)->delete();

            // DELETE pada CI4 mengembalikan bool; jumlah baris terpengaruh
            // dibaca dari affectedRows().
            if ($deleted === false || $this->db->affectedRows() !== 1) {
                throw new \RuntimeException('Data sekolah gagal dihapus.');
            }

            $this->db->transCommit();

            return $affected;
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * Menghapus banyak sekolah sekaligus secara atomik.
     *
     * Sekolah yang masih dipakai pendaftar direassign ke sekolah tujuan
     * (atau placeholder "Belum Diisi") sebelum dihapus. Sekolah yang tidak
     * ditemukan atau placeholder dilewati. Seluruh proses berada dalam satu
     * transaksi: satu kegagalan membatalkan semuanya.
     *
     * @param array<int|string, mixed> $sekolahIds
     * @param int|null $targetId Sekolah tujuan; null berarti "Belum Diisi"
     * @return array{deleted: int, moved: int, skipped: int}
     *
     * @throws \RuntimeException bila sekolah tujuan tidak valid atau ada langkah yang gagal
     */
    public function bulkDeleteWithReassign(array $sekolahIds, ?int $targetId): array
    {
        $ids = [];
        foreach ($sekolahIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        $ids = array_keys($ids);

        if ($ids === []) {
            throw new \RuntimeException('Pilih minimal satu sekolah yang akan dihapus.');
        }

        $toDefault = $targetId === null;

        if (! $toDefault) {
            if (in_array($targetId, $ids, true)) {
                throw new \RuntimeException('Sekolah tujuan tidak boleh termasuk yang akan dihapus.');
            }

            $target = $this->db->table($this->table)->where('id', $targetId)->get()->getRowArray();

            if ($target === null) {
                throw new \RuntimeException('Sekolah tujuan tidak ditemukan.');
            }
        } else {
            $target = ['alamat_jalan' => null, 'nama_desa' => null, 'nama_kecamatan' => null, 'nama_kabupaten' => null, 'nama_provinsi' => null];
        }

        $this->db->transBegin();

        try {
            $deletedCount = 0;
            $movedCount   = 0;
            $skipped      = 0;
            $belumDiisiId = null;

            foreach ($ids as $id) {
                $source = $this->db->table($this->table)->where('id', $id)->get()->getRowArray();

                if ($source === null || ($source['nama_sekolah'] ?? '') === DEFAULT_SEKOLAH) {
                    $skipped++;
                    continue;
                }

                $affected = $this->countStudentsUsing($id);

                if ($affected > 0) {
                    if ($toDefault) {
                        $belumDiisiId ??= $this->findOrCreateBelumDiisiId();
                        $destId        = $belumDiisiId;
                        $alamat        = DEFAULT_SEKOLAH;
                    } else {
                        $destId = $targetId;
                        $alamat = $this->composeAlamat($target);
                    }

                    $updated = $this->db->table('calon_siswa')
                        ->where('sekolah_id', $id)
                        ->update([
                            'sekolah_id'     => $destId,
                            'alamat_sekolah' => $toDefault ? DEFAULT_SEKOLAH : ($alamat !== '' ? $alamat : null),
                        ]);

                    if ($updated === false) {
                        throw new \RuntimeException('Gagal memindahkan data pendaftar.');
                    }

                    $movedCount += $affected;
                }

                $deleted = $this->db->table($this->table)->where('id', $id)->delete();

                // DELETE pada CI4 mengembalikan bool; jumlah baris terpengaruh
                // dibaca dari affectedRows().
                if ($deleted === false || $this->db->affectedRows() !== 1) {
                    throw new \RuntimeException('Data sekolah gagal dihapus.');
                }

                $deletedCount++;
            }

            $this->db->transCommit();

            return ['deleted' => $deletedCount, 'moved' => $movedCount, 'skipped' => $skipped];
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * Daftar sekolah untuk halaman admin, dilengkapi pencarian dan paginasi.
     *
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, perPage: int}
     */
    public function getAdminPage(string $keyword, string $sumber, int $page, int $perPage): array
    {
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;
        $page    = max(1, $page);

        $total = $this->adminQuery($keyword, $sumber)->countAllResults();

        $rows = $this->adminQuery($keyword, $sumber)
            ->select('(SELECT COUNT(*) FROM calon_siswa WHERE sekolah_id = master_sekolah.id) AS usage_count', false)
            ->orderBy('nama_sekolah', 'ASC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return [
            'rows'    => $rows,
            'total'   => (int) $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Builder dasar daftar admin: tanpa placeholder dan dengan filter opsional.
     */
    private function adminQuery(string $keyword, string $sumber)
    {
        $builder = $this->db->table($this->table)
            ->select(self::SEARCH_COLUMNS)
            ->where('nama_sekolah !=', DEFAULT_SEKOLAH);

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $builder->groupStart()
                ->like('nama_sekolah', $keyword)
                ->orLike('npsn', $keyword)
                ->orLike('nama_kecamatan', $keyword)
                ->orLike('nama_kabupaten', $keyword)
                ->groupEnd();
        }

        if (in_array($sumber, ['manual', 'dapodik', 'admin'], true)) {
            $builder->where('sumber', $sumber);
        }

        return $builder;
    }

    /**
     * Mengambil satu entri sekolah untuk halaman edit admin.
     *
     * @return array<string, mixed>|null
     */
    public function findDetail(int $sekolahId): ?array
    {
        $row = $this->db->table($this->table)
            ->select(self::SEARCH_COLUMNS)
            ->where('id', $sekolahId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Menyimpan perubahan manual dari admin.
     *
     * @param array<string, mixed> $data
     */
    public function updateAdmin(int $sekolahId, array $data): bool
    {
        $nama = trim((string) ($data['nama_sekolah'] ?? ''));

        if ($nama === '') {
            return false;
        }

        $payload = [
            'nama_sekolah'    => $nama,
            'nama_normalized' => self::normalizeName($nama),
            'nama_provinsi'   => $this->nullableRegionString($data['nama_provinsi'] ?? null),
            'nama_kecamatan'  => $this->nullableRegionString($data['nama_kecamatan'] ?? null),
            'nama_kabupaten'  => $this->nullableRegionString($data['nama_kabupaten'] ?? null),
            'nama_desa'       => $this->nullableRegionString($data['nama_desa'] ?? null),
            'alamat_jalan'    => $this->nullableString($data['alamat_jalan'] ?? null),
        ];

        return (bool) $this->db->table($this->table)->where('id', $sekolahId)->update($payload);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function nullableRegionString(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        return $value === null ? null : mb_strtoupper((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Mengambil saran wilayah dari data yang sudah tersimpan.
     *
     * Dipakai untuk datalist pada form edit admin, sehingga saran
     * berkembang otomatis mengikuti data yang diimpor/diisi tanpa
     * daftar hardcoded.
     *
     * @return array{provinsi: list<string>, kabupaten: list<string>, kecamatan: list<string>}
     */
    public function getRegionSuggestions(): array
    {
        return [
            'provinsi'  => $this->distinctRegionColumn('nama_provinsi'),
            'kabupaten' => $this->distinctRegionColumn('nama_kabupaten'),
            'kecamatan' => $this->distinctRegionColumn('nama_kecamatan'),
        ];
    }

    /**
     * @return list<string>
     */
    private function distinctRegionColumn(string $column): array
    {
        $rows = $this->db->table($this->table)
            ->select($column)
            ->distinct()
            ->where($column . ' IS NOT NULL', null, false)
            ->where($column . " != ''", null, false)
            ->orderBy($column, 'ASC')
            ->get()
            ->getResultArray();

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row[$column] ?? '')),
            $rows
        ), static fn (string $value): bool => $value !== ''));
    }

    /**
     * Builder dasar pencarian: hanya sekolah nyata (bukan placeholder).
     */
    private function baseSearchBuilder()
    {
        return $this->db->table($this->table)
            ->select(self::SEARCH_COLUMNS)
            ->where('nama_sekolah !=', DEFAULT_SEKOLAH)
            ->where('nama_normalized !=', '');
    }
}
