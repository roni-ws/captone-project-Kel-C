<?php

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PanitiaUsersAccessTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use FeatureTestTrait;

    public function testPanitiaSeesOnlyOwnAccountOnUsersPage(): void
    {
        $panitia = auth()->getProvider()->findById(2);
        $this->assertNotNull($panitia, 'User panitia (id=2) harus ada');

        $result = $this->actingAs($panitia)->get('admin/users');
        $result->assertStatus(200);

        $body = (string) $result->getBody();

        $this->assertStringContainsString('Akun Saya', $body);
        $this->assertStringNotContainsString('Kelola Akun Staff', $body);
        $this->assertStringNotContainsString('Tambah Staff Baru', $body);
        $this->assertStringNotContainsString('id="custom-search"', $body);
        $this->assertStringNotContainsString('id="role-filter"', $body);

        // Hanya akun sendiri (1 baris data)
        $this->assertStringContainsString('panitia@smawahasmodel.sch.id', $body);
        $this->assertStringNotContainsString('admin@unggulbangsa.sch.id', $body);

        // Modal add/delete tidak dirender; edit akun menjadi satu modal terpadu.
        $this->assertStringNotContainsString('id="add-modal"', $body);
        $this->assertStringNotContainsString('id="delete-modal"', $body);
        $this->assertStringContainsString('id="edit-modal"', $body);
        $this->assertStringContainsString('name="password_lama"', $body);
        $this->assertStringContainsString('name="password_baru"', $body);
        $this->assertStringContainsString('name="password_konfirmasi"', $body);

        // Menu sidebar panitia: Akun Saya aktif, Pengaturan tidak ada
        $this->assertStringContainsString('Akun Saya', $body);
    }

    public function testAdminSeesAllStaffOnUsersPage(): void
    {
        $admin = auth()->getProvider()->findById(1);
        $this->assertNotNull($admin, 'User admin (id=1) harus ada');

        $result = $this->actingAs($admin)->get('admin/users');
        $result->assertStatus(200);

        $body = (string) $result->getBody();

        $this->assertStringContainsString('Kelola Akun Staff', $body);
        $this->assertStringContainsString('Tambah Staff Baru', $body);
        $this->assertStringContainsString('id="custom-search"', $body);
        $this->assertStringContainsString('id="role-filter"', $body);
        $this->assertStringContainsString('panitia@smawahasmodel.sch.id', $body);
        $this->assertStringContainsString('admin@unggulbangsa.sch.id', $body);
        $this->assertStringContainsString('id="add-modal"', $body);
        $this->assertStringContainsString('id="delete-modal"', $body);
    }

    public function testPanitiaCannotEditOtherAccount(): void
    {
        $panitia = auth()->getProvider()->findById(2);
        $this->assertNotNull($panitia);

        // Coba ubah akun admin (id=1) dan eskalasi role
        $result = $this->actingAs($panitia)->post('admin/users/edit/1', [
            csrf_token() => csrf_hash(),
            'username'   => 'admin-hacked',
            'email'      => 'admin@unggulbangsa.sch.id',
            'role'       => 'admin',
        ]);

        $result->assertRedirectTo(site_url('admin/users'));

        $db = \Config\Database::connect();
        $admin = $db->table('users')->where('id', 1)->get()->getRowArray();
        $this->assertSame('admin', $admin['username'] ?? null, 'Username admin tidak boleh berubah');
        $this->assertSame('admin', $admin['role'] ?? null, 'Role admin tidak boleh berubah');
    }

    public function testPanitiaCannotChangeOtherAccountPassword(): void
    {
        $panitia = auth()->getProvider()->findById(2);
        $this->assertNotNull($panitia);

        $result = $this->actingAs($panitia)->post('admin/users/change-password/1', [
            csrf_token()         => csrf_hash(),
            'password'           => 'Newpass123!',
            'password_confirm'   => 'Newpass123!',
        ]);

        $result->assertRedirectTo(site_url('admin/users'));
    }

    public function testPanitiaCanEditOwnAccountWithoutEscalation(): void
    {
        $panitia = auth()->getProvider()->findById(2);
        $this->assertNotNull($panitia);

        $db = \Config\Database::connect();
        $before = $db->table('users')->where('id', 2)->get()->getRowArray();
        $this->assertNotNull($before);

        // Kirim data sama + role admin (percobaan eskalasi) -> role harus tetap panitia
        $result = $this->actingAs($panitia)->post('admin/users/edit/2', [
            csrf_token() => csrf_hash(),
            'username'   => $before['username'],
            'email'      => $before['email'],
            'role'       => 'admin',
        ]);

        $result->assertRedirectTo(site_url('admin/users'));

        $after = $db->table('users')->where('id', 2)->get()->getRowArray();
        $this->assertSame($before['username'], $after['username'] ?? null);
        $this->assertSame($before['email'], $after['email'] ?? null);
        $this->assertSame(ROLE_PANITIA, $after['role'] ?? null, 'Role panitia tidak boleh berubah');
    }
}
