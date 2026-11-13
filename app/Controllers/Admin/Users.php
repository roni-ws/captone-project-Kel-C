<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\UserService;
use CodeIgniter\HTTP\ResponseInterface;

class Users extends BaseController
{
    protected $helpers = ['url', 'html', 'form'];

    protected UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index(): ResponseInterface
    {
        $this->requireGroup([ROLE_ADMIN, ROLE_PANITIA]);
        $shieldUser = auth()->user();
        $isAdmin    = $shieldUser->inGroup(ROLE_ADMIN);

        // Panitia hanya boleh melihat akunnya sendiri.
        $staffs = $this->userService->getStaffList($isAdmin ? null : (int) $shieldUser->id);

        $data = [
            ...$this->getSiteSettings(),
            'admin_username' => $shieldUser->username,
            'user_email'     => $shieldUser->email,
            'authUserId'     => $shieldUser->id,
            'roleLabel'      => $isAdmin ? 'Administrator' : 'Panitia SPMB',
            'isAdmin'        => $isAdmin,
            'staffs'         => $staffs,
        ];

        return $this->response->setBody(view('admin/users', $data));
    }

    /**
     * Admin boleh mengelola semua akun, panitia hanya akunnya sendiri.
     */
    protected function canManageAccount(int $userId): bool
    {
        $shieldUser = auth()->user();

        if ($shieldUser === null) {
            return false;
        }

        return $shieldUser->inGroup(ROLE_ADMIN) || (int) $shieldUser->id === $userId;
    }

    protected function denyOtherAccount(): ResponseInterface
    {
        return redirect()->to(base_url('admin/users'))
            ->with('error', 'Anda hanya dapat mengubah akun Anda sendiri.');
    }

    public function store(): ResponseInterface
    {
        $this->requireAdmin();

        if (! $this->validateData($this->request->getPost(), 'staffStore')) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $username = $this->request->getPost('username');
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $role     = $this->request->getPost('role');

        try {
            $this->userService->create([
                'username' => $username,
                'email'    => $email,
                'password' => $password,
                'role'     => $role,
            ]);
        } catch (\RuntimeException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('admin/users'))
            ->with('success', 'Akun staff baru dengan peran ' . strtoupper((string) $role) . ' berhasil dibuat!');
    }

    public function delete(int $userId): ResponseInterface
    {
        $this->requireAdmin();

        if (auth()->id() == $userId) {
            return redirect()->back()
                ->with('error', 'Keamanan Sistem: Anda tidak diizinkan untuk menghapus akun Anda sendiri.');
        }

        $targetUser = auth()->getProvider()->findById($userId);
        if ($targetUser && $targetUser->inGroup(ROLE_ADMIN)) {
            return redirect()->back()
                ->with('error', 'Keamanan Sistem: Akun Administrator tidak dapat dihapus.');
        }

        $this->userService->delete($userId);

        return redirect()->to(base_url('admin/users'))
            ->with('success', 'Akun staff berhasil dihapus dari sistem.');
    }

    public function edit(int $userId): ResponseInterface
    {
        $this->requireGroup([ROLE_ADMIN, ROLE_PANITIA]);

        if (! $this->canManageAccount($userId)) {
            return $this->denyOtherAccount();
        }

        if (! $this->validateData($this->request->getPost(), 'staffEdit')) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $shieldUser  = auth()->user();
        $isAdmin     = $shieldUser->inGroup(ROLE_ADMIN);
        $isSelf      = (int) $shieldUser->id === $userId;
        $targetUser  = auth()->getProvider()->findById($userId);
        $username    = $this->request->getPost('username');
        $email       = $this->request->getPost('email');

        // Non-admin tidak boleh mengubah role (cegah eskalasi hak akses).
        $role = $isAdmin ? $this->request->getPost('role') : ($targetUser->role ?? ROLE_PANITIA);

        if ($this->userService->isUsernameTaken($username, $userId)) {
            return redirect()->back()->withInput()->with('errors', ['username' => 'Username sudah digunakan oleh akun lain.']);
        }

        if ($this->userService->isEmailTaken($email, $userId)) {
            return redirect()->back()->withInput()->with('errors', ['email' => 'Email sudah terdaftar di sistem.']);
        }

        // Ubah password bila diisi. Admin mengubah akun lain tidak
        // perlu password lama; pengguna mengubah akun sendiri wajib verifikasi.
        $newPassword = trim((string) $this->request->getPost('password_baru'));
        if ($newPassword !== '') {
            $oldPassword = (string) $this->request->getPost('password_lama');

            if ($isSelf) {
                $oldHash = $targetUser->password_hash ?? '';
                if ($oldPassword === '' || ! password_verify($oldPassword, $oldHash)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('errors', ['password_lama' => 'Password lama tidak sesuai.']);
                }
            }
        }

        $this->userService->updateUserProfile($userId, $username, $email, $role);

        if ($newPassword !== '') {
            $this->userService->updatePassword($userId, $newPassword);
        }

        return redirect()->to(base_url('admin/users'))
            ->with('success', 'Data akun berhasil diperbarui!');
    }
}
