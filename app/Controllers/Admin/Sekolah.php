<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingsModel;
use App\Models\Siswa\MasterSekolahModel;
use App\Services\SekolahImportService;
use App\Services\SekolahTemplateService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Sekolah as SekolahConfig;

/**
 * Pengaturan data sekolah asal: unduh template, impor langsung,
 * serta koreksi dan penghapusan entri oleh admin.
 */
class Sekolah extends BaseController
{
    protected $helpers = ['url', 'html', 'form'];

    private const PER_PAGE_OPTIONS = [25, 50, 100];

    private SekolahConfig $config;
    private MasterSekolahModel $sekolahModel;

    public function __construct()
    {
        $this->config       = config('Sekolah');
        $this->sekolahModel = model(MasterSekolahModel::class);
    }

    public function index(): ResponseInterface
    {
        $this->requireAdmin();
        $shieldUser = auth()->user();

        $keyword = trim((string) $this->request->getGet('q'));
        $sumber  = (string) $this->request->getGet('sumber');
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = (int) ($this->request->getGet('per_page') ?? 25);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 25;
        }

        $result = $this->sekolahModel->getAdminPage($keyword, $sumber, $page, $perPage);

        return $this->response->setBody(view('admin/form-sekolah', [
            ...$this->getSiteSettings(),
            'admin_username' => $shieldUser->username,
            'user_email'     => $shieldUser->email,
            'roleLabel'      => $shieldUser->inGroup(ROLE_ADMIN) ? 'Administrator' : 'Panitia SPMB',
            'isAdmin'        => $shieldUser->inGroup(ROLE_ADMIN),
            'rows'           => $result['rows'],
            'total'          => $result['total'],
            'page'           => $result['page'],
            'perPage'        => $result['perPage'],
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'keyword'        => $keyword,
            'sumber'         => $sumber,
            'lastImport'     => $this->getLastImportSummary(),
            'regionSuggestions' => $this->sekolahModel->getRegionSuggestions(),
            'portalUrl'      => $this->config->portalUrl,
            'maxUploadMb'    => (int) round($this->config->maxUploadSizeKb / 1024),
            'xlsxAvailable'  => class_exists(\ZipArchive::class),
        ]));
    }

    /**
     * Mengunduh template Excel impor data sekolah.
     */
    public function downloadTemplate(): ResponseInterface
    {
        $this->requireAdmin();

        $templateService = new SekolahTemplateService($this->config);

        if (class_exists(\ZipArchive::class)) {
            $content  = $templateService->render();
            $fileName = $this->config->templateFileName;
            $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            // Server tanpa ekstensi zip tetap bisa menyediakan template CSV
            // yang dapat dibuka langsung dengan Excel.
            $content  = $templateService->renderCsv();
            $fileName = 'template-data-sekolah.csv';
            $mimeType = 'text/csv; charset=UTF-8';
        }

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setHeader('Content-Length', (string) strlen($content))
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($content);
    }

    /**
     * Menyimpan hasil impor berkas ke tabel master_sekolah.
     */
    public function import(): ResponseInterface
    {
        $this->requireAdmin();

        return $this->handleUpload(function (string $path, string $originalName): ResponseInterface {
            $report = (new SekolahImportService($this->config))->import($path, $originalName);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Impor selesai: {$report['imported']} sekolah baru, {$report['updated']} diperbarui.",
                'report'  => $report,
            ]);
        });
    }

    /**
     * Menyimpan koreksi manual satu entri sekolah.
     */
    public function update(int $sekolahId): ResponseInterface
    {
        $this->requireAdmin();

        $rules = [
            'nama_sekolah'   => 'required|min_length[3]|max_length[150]',
            'nama_provinsi'  => 'permit_empty|max_length[100]',
            'nama_kecamatan' => 'permit_empty|max_length[100]',
            'nama_kabupaten' => 'permit_empty|max_length[100]',
            'nama_desa'      => 'permit_empty|max_length[100]',
            'alamat_jalan'   => 'permit_empty|max_length[500]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $updated = $this->sekolahModel->updateAdmin($sekolahId, $this->request->getPost([
            'nama_sekolah', 'nama_provinsi', 'nama_kecamatan', 'nama_kabupaten', 'nama_desa', 'alamat_jalan',
        ]));

        if (! $updated) {
            return redirect()->back()->withInput()->with('error', 'Data sekolah gagal diperbarui.');
        }

        $this->bumpSearchCacheVersion();

        return redirect()->to(base_url('admin/sekolah'))
            ->with('success', 'Data sekolah berhasil diperbarui.');
    }

    /**
     * Menghapus entri sekolah.
     *
     * Jika sekolah belum dipakai pendaftar, langsung dihapus. Jika masih
     * dipakai, admin wajib memilih: mengubah pendaftar menjadi "Belum Diisi"
     * atau memindahkan mereka ke sekolah lain. Sekolah tidak pernah dihapus
     * tanpa reassign karena FK memakai ON DELETE CASCADE.
     */
    public function delete(int $sekolahId): ResponseInterface
    {
        $this->requireAdmin();

        $sekolah = $this->sekolahModel->find($sekolahId);

        if ($sekolah === null || $sekolah->nama_sekolah === DEFAULT_SEKOLAH) {
            return redirect()->to(base_url('admin/sekolah'))
                ->with('error', 'Data sekolah tidak ditemukan.');
        }

        $usage = $this->sekolahModel->countStudentsUsing($sekolahId);

        if ($usage === 0) {
            if (! $this->sekolahModel->deleteIfUnused($sekolahId)) {
                return redirect()->to(base_url('admin/sekolah'))
                    ->with('error', 'Data sekolah gagal dihapus.');
            }

            $this->bumpSearchCacheVersion();

            return redirect()->to(base_url('admin/sekolah'))
                ->with('success', 'Data sekolah berhasil dihapus.');
        }

        $action = (string) $this->request->getPost('reassign_action');

        try {
            $moved = match ($action) {
                'default' => $this->sekolahModel->deleteWithReassign($sekolahId, null),
                'move'    => $this->deleteWithMove($sekolahId),
                default   => throw new \RuntimeException("Sekolah ini masih dipakai oleh {$usage} pendaftar. Pilih tindakan pada dialog hapus."),
            };
        } catch (\RuntimeException $e) {
            log_message('warning', 'Hapus sekolah gagal: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/sekolah'))
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Hapus sekolah gagal: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/sekolah'))
                ->with('error', 'Data sekolah gagal dihapus.');
        }

        $this->bumpSearchCacheVersion();

        $message = $action === 'move'
            ? "Data sekolah berhasil dihapus. {$moved} pendaftar dipindahkan ke sekolah tujuan."
            : "Data sekolah berhasil dihapus. {$moved} pendaftar diubah menjadi \"Belum Diisi\".";

        return redirect()->to(base_url('admin/sekolah'))
            ->with('success', $message);
    }

    /**
     * Menghapus banyak sekolah sekaligus secara atomik.
     *
     * Sekolah yang masih dipakai pendaftar direassign sesuai pilihan admin
     * (ubah jadi "Belum Diisi" atau pindah ke sekolah tujuan) dalam satu
     * transaksi. Jika ada kegagalan, seluruh penghapusan dibatalkan.
     */
    public function bulkDelete(): ResponseInterface
    {
        $this->requireAdmin();

        $ids = $this->request->getPost('ids');
        $ids = is_array($ids) ? array_map('intval', $ids) : [];

        if ($ids === []) {
            return redirect()->to(base_url('admin/sekolah'))
                ->with('error', 'Pilih minimal satu sekolah yang akan dihapus.');
        }

        if (count($ids) > 100) {
            return redirect()->to(base_url('admin/sekolah'))
                ->with('error', 'Maksimal 100 sekolah per penghapusan massal.');
        }

        $action = (string) $this->request->getPost('reassign_action');

        if (! in_array($action, ['move', 'default'], true)) {
            // Bila ada sekolah terpilih yang masih dipakai pendaftar, admin
            // wajib memilih tindakan reassign — jangan menebak.
            $anyUsed = false;
            foreach (array_unique($ids) as $id) {
                if ($this->sekolahModel->countStudentsUsing($id) > 0) {
                    $anyUsed = true;
                    break;
                }
            }

            if ($anyUsed) {
                return redirect()->to(base_url('admin/sekolah'))
                    ->with('error', 'Ada sekolah yang masih dipakai pendaftar. Pilih tindakan reassign pada dialog hapus.');
            }

            $action = 'default'; // Aman: tidak ada pendaftar yang direassign.
        }

        try {
            $result = $action === 'move'
                ? $this->deleteBulkWithMove($ids)
                : $this->sekolahModel->bulkDeleteWithReassign($ids, null);
        } catch (\RuntimeException $e) {
            log_message('warning', 'Bulk delete sekolah gagal: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/sekolah'))
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Bulk delete sekolah gagal: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/sekolah'))
                ->with('error', 'Penghapusan massal gagal. Tidak ada data yang dihapus.');
        }

        $this->bumpSearchCacheVersion();

        $message = "{$result['deleted']} sekolah berhasil dihapus";

        if ($result['moved'] > 0) {
            $tujuan   = $action === 'move' ? 'ke sekolah tujuan' : 'menjadi "Belum Diisi"';
            $message .= ", {$result['moved']} pendaftar dipindahkan {$tujuan}";
        }

        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} dilewati";
        }

        return redirect()->to(base_url('admin/sekolah'))
            ->with('success', $message . '.');
    }

    /**
     * Bulk delete dengan reassign pendaftar ke sekolah tujuan pilihan admin.
     *
     * @param array<int> $ids
     */
    private function deleteBulkWithMove(array $ids): array
    {
        $targetId = (int) $this->request->getPost('reassign_sekolah_id');

        if ($targetId <= 0) {
            throw new \RuntimeException('Pilih sekolah tujuan terlebih dahulu.');
        }

        return $this->sekolahModel->bulkDeleteWithReassign($ids, $targetId);
    }

    /**
     * Menghapus sekolah sambil memindahkan pendaftar ke sekolah pilihan admin.
     */
    private function deleteWithMove(int $sekolahId): int
    {
        $targetId = (int) $this->request->getPost('reassign_sekolah_id');

        if ($targetId <= 0) {
            throw new \RuntimeException('Pilih sekolah tujuan terlebih dahulu.');
        }

        return $this->sekolahModel->deleteWithReassign($sekolahId, $targetId);
    }

    /**
     * Memvalidasi unggahan, memindahkannya ke berkas sementara, lalu
     * meneruskan path-nya ke pemroses. Berkas sementara selalu dihapus.
     *
     * @param callable(string, string): ResponseInterface $handler
     */
    private function handleUpload(callable $handler): ResponseInterface
    {
        $file = $this->request->getFile('berkas');

        if ($file === null) {
            return $this->uploadError('Pilih berkas yang akan diunggah.');
        }

        if (! $file->isValid()) {
            return $this->uploadError('Berkas gagal diunggah: ' . $file->getErrorString() . '.');
        }

        $originalName = basename((string) $file->getClientName());
        $extension    = strtolower((string) $file->getClientExtension());

        if (! in_array($extension, $this->config->allowedExtensions, true)) {
            return $this->uploadError('Format berkas harus .xlsx atau .csv.');
        }

        if ((int) $file->getSize() > $this->config->maxUploadSizeKb * 1024) {
            return $this->uploadError('Ukuran berkas maksimal ' . round($this->config->maxUploadSizeKb / 1024) . ' MB.');
        }

        $tempPath = WRITEPATH . 'uploads/sekolah-import-' . bin2hex(random_bytes(8)) . '.' . $extension;

        if (! $file->move(dirname($tempPath), basename($tempPath))) {
            return $this->uploadError('Berkas gagal dipindahkan ke folder sementara.');
        }

        try {
            return $handler($tempPath, $originalName);
        } catch (\Throwable $e) {
            log_message('error', 'Impor data sekolah gagal: {message}', ['message' => $e->getMessage()]);

            // RuntimeException dari kode kita sendiri membawa pesan yang aman
            // ditampilkan ke admin; exception lain disembunyikan.
            $message = $e instanceof \RuntimeException && $e->getMessage() !== ''
                ? $e->getMessage()
                : 'Impor gagal diproses. Periksa log untuk detail.';

            return $this->uploadError($message);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function uploadError(string $message): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'status'  => 'error',
            'message' => $message,
        ]);
    }

    /**
     * Mengganti versi cache agar hasil pencarian lama tidak dipakai lagi.
     */
    private function bumpSearchCacheVersion(): void
    {
        cache()->save('sekolah_search_version', (string) time(), DAY);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getLastImportSummary(): ?array
    {
        $raw = (string) SettingsModel::getSetting('sekolah_last_import_summary', '');

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
