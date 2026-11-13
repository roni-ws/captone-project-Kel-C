<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\SettingsModel;
use App\Models\Siswa\SiswaModel;
use CodeIgniter\HTTP\ResponseInterface;

class Pengaduan extends BaseController
{
    protected $helpers = ['url', 'form', 'html'];

    public function index(): ResponseInterface
    {
        $this->requireSiswa();

        $siswa = model(SiswaModel::class)
            ->where('user_id', (int) auth()->user()->id)
            ->first();

        return $this->renderSiswaView('siswa/pengaduan', [
            'siswa'  => $siswa,
            'errors' => session('errors'),
        ]);
    }

    public function store(): ResponseInterface
    {
        $this->requireSiswa();

        $rules = [
            'category' => [
                'label'  => 'Kategori Keluhan',
                'rules'  => 'required|in_list[biodata,sekolah,orang_tua,berkas,teknis,lainnya]',
                'errors' => [
                    'required' => 'Pilih kategori keluhan terlebih dahulu.',
                    'in_list'  => 'Kategori keluhan tidak valid.',
                ],
            ],
            'subject' => [
                'label'  => 'Subjek / Judul',
                'rules'  => 'required|min_length[5]|max_length[200]',
                'errors' => [
                    'required'   => 'Subjek pengaduan wajib diisi.',
                    'min_length' => 'Subjek minimal 5 karakter.',
                    'max_length' => 'Subjek maksimal 200 karakter.',
                ],
            ],
            'message' => [
                'label'  => 'Pesan / Detail Kendala',
                'rules'  => 'required|min_length[10]|max_length[2000]',
                'errors' => [
                    'required'   => 'Detail kendala wajib diisi.',
                    'min_length' => 'Detail kendala minimal 10 karakter.',
                    'max_length' => 'Detail kendala maksimal 2000 karakter.',
                ],
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost(['category', 'subject', 'message']);

        $siswa = model(SiswaModel::class)
            ->where('user_id', (int) auth()->user()->id)
            ->first();

        $labels = [
            'biodata'   => 'Biodata Diri',
            'sekolah'   => 'Asal Sekolah',
            'orang_tua' => 'Data Orang Tua',
            'berkas'    => 'Kelengkapan Berkas',
            'teknis'    => 'Masalah Teknis Website',
            'lainnya'   => 'Pertanyaan Lainnya',
        ];

        $nama       = $siswa['nama_lengkap'] ?? auth()->user()->username ?? 'Calon Siswa';
        $nisn       = $siswa['nisn'] ?? '-';
        $noReg      = $siswa['no_registrasi'] ?? '-';
        $kategori   = $labels[$post['category']] ?? $post['category'];

        $text = "*Pengaduan SPMB*\n"
            . "Nama: {$nama}\n"
            . "NISN: {$nisn}\n"
            . "No. Registrasi: {$noReg}\n"
            . "Kategori: {$kategori}\n"
            . "Subjek: {$post['subject']}\n\n"
            . "*Detail Kendala:*\n"
            . $post['message'];

        $phone = SettingsModel::getSetting(SETTING_WHATSAPP_PENGADUAN, '6281234567890');
        // fallback: extract from whatsapp_link if pengaduan not set
        if (empty($phone)) {
            $waLink = SettingsModel::getSetting(SETTING_WHATSAPP_LINK, 'https://wa.me/6281234567890');
            if (preg_match('/wa\.me\/(\d+)/', (string) $waLink, $matches)) {
                $phone = $matches[1];
            } else {
                $phone = '6281234567890';
            }
        }

        return redirect()->to('https://wa.me/' . $phone . '?text=' . rawurlencode($text));
    }
}