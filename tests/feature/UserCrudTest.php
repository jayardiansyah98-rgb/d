<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class UserCrudTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';
    protected $DBGroup   = 'tests';
    protected $seed      = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetServices();
        $this->mockSession();
    }

    public function testAdministratorCanCreateUserThroughForm(): void
    {
        $result = $this->asUser('admin')->post('users', [
            'csrf_token'       => $this->csrfToken(),
            'full_name'        => 'Petugas Baru',
            'username'         => 'petugas_baru',
            'email'            => 'petugas_baru@yayasan.test',
            'phone'            => '081234567890',
            'role_id'          => $this->roleId('distribusi'),
            'status'           => 'active',
            'password'         => 'Rahasia#123',
            'password_confirm' => 'Rahasia#123',
        ]);

        $result->assertRedirectTo(site_url('users'));
        $result->assertSessionHas('success');

        $user = model(UserModel::class)->where('username', 'petugas_baru')->first();

        $this->assertNotNull($user);
        $this->assertTrue(password_verify('Rahasia#123', $user->password_hash));
    }

    public function testCreateUserWithDuplicateUsernameShowsValidationErrors(): void
    {
        $result = $this->asUser('admin')->post('users', [
            'csrf_token'       => $this->csrfToken(),
            'full_name'        => 'Duplikat',
            'username'         => 'admin',
            'email'            => 'duplikat@yayasan.test',
            'phone'            => '',
            'role_id'          => $this->roleId('distribusi'),
            'status'           => 'active',
            'password'         => 'Rahasia#123',
            'password_confirm' => 'Rahasia#123',
        ]);

        $result->assertRedirectTo(site_url('users/new'));
        $result->assertSessionHas('errors');
        $this->assertNull(model(UserModel::class)->where('username', 'admin')->where('email', 'duplikat@yayasan.test')->first());
    }

    public function testAdministratorCanUpdateUserThroughForm(): void
    {
        $targetId = $this->userId('petugas');

        $result = $this->asUser('admin')->post('users/' . $targetId, [
            'csrf_token'       => $this->csrfToken(),
            'full_name'        => 'Petugas Diperbarui',
            'username'         => 'petugas',
            'email'            => 'petugas@yayasan.test',
            'phone'            => '081200000000',
            'role_id'          => $this->roleId('pimpinan'),
            'status'           => 'active',
            'password'         => '',
            'password_confirm' => '',
        ]);

        $result->assertRedirectTo(site_url('users'));

        $updated = model(UserModel::class)->find($targetId);

        $this->assertSame('Petugas Diperbarui', $updated->full_name);
        $this->assertSame($this->roleId('pimpinan'), (int) $updated->role_id);
    }

    public function testAdministratorCannotDeactivateOwnAccountThroughForm(): void
    {
        $adminId = $this->userId('admin');
        $admin   = model(UserModel::class)->find($adminId);

        $result = $this->asUser('admin')->post('users/' . $adminId, [
            'csrf_token'       => $this->csrfToken(),
            'full_name'        => $admin->full_name,
            'username'         => $admin->username,
            'email'            => $admin->email,
            'phone'            => $admin->phone,
            'role_id'          => (int) $admin->role_id,
            'status'           => 'inactive',
            'password'         => '',
            'password_confirm' => '',
        ]);

        $result->assertRedirectTo(site_url('users/' . $adminId . '/edit'));
        $result->assertSessionHas('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        $this->assertSame('active', model(UserModel::class)->find($adminId)->status);
    }

    public function testAdministratorCanArchiveUserThroughForm(): void
    {
        $targetId = $this->userId('pengurus');

        $result = $this->asUser('admin')->post('users/' . $targetId . '/delete', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $result->assertRedirectTo(site_url('users'));
        $this->assertNull(model(UserModel::class)->find($targetId));

        $row = $this->db->table('users')->where('id', $targetId)->get()->getRow();
        $this->assertNotNull($row->deleted_at);
    }

    public function testAuditorCannotArchiveUserThroughForm(): void
    {
        $targetId = $this->userId('petugas');

        $result = $this->asUser('auditor')->post('users/' . $targetId . '/delete', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $this->assertNotNull(model(UserModel::class)->find($targetId));
    }

    private function asUser(string $username): self
    {
        $this->resetServices();
        $this->mockSession();

        return $this->withSession(['auth_user_id' => $this->userId($username)]);
    }

    private function csrfToken(): string
    {
        return (string) service('security')->getHash();
    }

    private function userId(string $username): int
    {
        $row = $this->db->table('users')->select('id')->where('username', $username)->get()->getRow();

        $this->assertNotNull($row, 'Pengguna uji tidak ditemukan: ' . $username);

        return (int) $row->id;
    }

    private function roleId(string $slug): int
    {
        $row = $this->db->table('roles')->select('id')->where('slug', $slug)->get()->getRow();

        $this->assertNotNull($row, 'Role uji tidak ditemukan: ' . $slug);

        return (int) $row->id;
    }
}
