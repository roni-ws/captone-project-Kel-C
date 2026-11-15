<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan kolom data induk (Dapodik) pada tabel master_sekolah.
 *
 * Data lama tidak dihapus; kolom baru boleh NULL sehingga entri manual
 * yang sudah dibuat siswa tetap valid.
 */
class AddDapodikColumnsToMasterSekolah extends Migration
{
    public function up()
    {
        $this->forge->addColumn('master_sekolah', [
            'npsn' => [
                'type'       => 'CHAR',
                'constraint' => 8,
                'null'       => true,
            ],
            'alamat_jalan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'nama_desa' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'nama_kecamatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'nama_kabupaten' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'nama_provinsi' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'bentuk_pendidikan' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'status_sekolah' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'nama_normalized' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'sumber' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
                'default'    => 'manual',
            ],
            'imported_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->db->query('ALTER TABLE `master_sekolah` ADD UNIQUE KEY `uniq_master_sekolah_npsn` (`npsn`)');
        $this->db->query('ALTER TABLE `master_sekolah` ADD KEY `idx_master_sekolah_nama_normalized` (`nama_normalized`)');
        $this->db->query('ALTER TABLE `master_sekolah` ADD KEY `idx_master_sekolah_sumber` (`sumber`)');

        $this->backfillNormalizedNames();
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `master_sekolah` DROP INDEX `uniq_master_sekolah_npsn`');
        $this->db->query('ALTER TABLE `master_sekolah` DROP INDEX `idx_master_sekolah_nama_normalized`');
        $this->db->query('ALTER TABLE `master_sekolah` DROP INDEX `idx_master_sekolah_sumber`');

        $this->forge->dropColumn('master_sekolah', [
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
        ]);
    }

    /**
     * Mengisi nama_normalized untuk data lama agar pencarian langsung berfungsi.
     */
    private function backfillNormalizedNames(): void
    {
        $rows = $this->db->table('master_sekolah')
            ->select('id, nama_sekolah')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string) $row['nama_sekolah'])));

            $this->db->table('master_sekolah')
                ->where('id', $row['id'])
                ->update(['nama_normalized' => $normalized]);
        }
    }
}
