<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Libraries\UserService;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class UserManagementTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

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

    public function testCreateUserHashesPasswordAndWritesAudit(): void
    {
        $this->loginAs('admin');

        $id = (new UserService())->create([
            'role_id'          => $this->roleId('distribusi'),
            'full_name'        => 'Petugas Baru',
            'username'         => 'petugas.baru',
            'email'            => 'PETUGAS.BARU@YAYASAN.TEST',
            'phone'            => '0812-3456-7890',
            'status'           => 'active',
            'password'         => 'Rahasia#123',
            'password_confirm' => 'Rahasia#123',
        ]);

        $user = model(UserModel::class)->find($id);

        $this->assertNotNull($user);
        $this->assertSame('petugas.baru', $user->username);
        $this->assertSame('petugas.baru@yayasan.test', $user->email);
        $this->assertNotSame('Rahasia#123', $user->password_hash);
        $this->assertTrue(password_verify('Rahasia#123', $user->password_hash));

        $audit = $this->latestAudit('user.created');
        $this->assertNotNull($audit);
        $this->assertSame('petugas.baru', json_decode($audit->new_values, true)['username']);
        $this->assertStringNotContainsString('Rahasia#123', (string) $audit->new_values);
        $this->assertStringNotContainsString('password_hash', (string) $audit->new_values);
    }

    public function testUpdateUserChangesDataAndWritesAudit(): void
    {
        $this->loginAs('admin');
        $targetId = $this->userId('petugas');

        (new UserService())->update($targetId, [
            'full_name' => 'Petugas Diperbarui',
            'username'  => 'petugas',
            'email'     => 'petugas@yayasan.test',
            'phone'     => '081200000000',
            'role_id'   => $this->roleId('pimpinan'),
            'status'    => 'active',
            'password'  => '',
            'password_confirm' => '',
        ]);

        $updated = model(UserModel::class)->find($targetId);

        $this->assertSame('Petugas Diperbarui', $updated->full_name);
        $this->assertSame($this->roleId('pimpinan'), (int) $updated->role_id);

        $audit = $this->latestAudit('user.updated');
        $this->assertNotNull($audit);
        $this->assertSame($targetId, (int) $audit->entity_id);
    }

    public function testUserCannotDeactivateOwnAccount(): void
    {
        $adminId = $this->userId('admin');
        $this->loginAs('admin');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Anda tidak dapat menonaktifkan akun sendiri.');

        (new UserService())->update($adminId, $this->payloadFor(
            $adminId,
            'admin',
            'inactive',
        ));
    }

    public function testUserCannotChangeOwnRole(): void
    {
        $adminId = $this->userId('admin');
        $this->loginAs('admin');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Anda tidak dapat mengubah role akun sendiri.');

        (new UserService())->update($adminId, $this->payloadFor(
            $adminId,
            'distribusi',
            'active',
        ));
    }

    public function testLastActiveAdministratorCannotBeDeactivated(): void
    {
        $this->loginAs('pengurus');
        $adminId = $this->userId('admin');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Administrator aktif terakhir tidak dapat dinonaktifkan atau diubah role-nya.');

        (new UserService())->update($adminId, $this->payloadFor(
            $adminId,
            'admin',
            'inactive',
        ));
    }

    public function testLastActiveAdministratorCannotLoseAdministratorRole(): void
    {
        $this->loginAs('pengurus');
        $adminId = $this->userId('admin');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Administrator aktif terakhir tidak dapat dinonaktifkan atau diubah role-nya.');

        (new UserService())->update($adminId, $this->payloadFor(
            $adminId,
            'keuangan',
            'active',
        ));
    }

    public function testToggleStatusChangesStatusAndAudits(): void
    {
        $this->loginAs('admin');
        $targetId = $this->userId('petugas');

        $status = (new UserService())->toggleStatus($targetId);

        $this->assertSame('inactive', $status);
        $this->assertSame('inactive', model(UserModel::class)->find($targetId)->status);
        $this->assertNotNull($this->latestAudit('user.deactivated'));
    }

    public function testUserCannotToggleOwnStatus(): void
    {
        $petugasId = $this->userId('petugas');
        $this->loginAs('petugas');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Anda tidak dapat mengubah status akun sendiri.');

        (new UserService())->toggleStatus($petugasId);
    }

    public function testArchiveIsSoftDeleteAndKeepsRow(): void
    {
        $this->loginAs('admin');
        $targetId = $this->userId('pengurus');

        (new UserService())->archive($targetId);

        $this->assertNull(model(UserModel::class)->find($targetId));

        $row = $this->db->table('users')->where('id', $targetId)->get()->getRow();
        $this->assertNotNull($row);
        $this->assertNotNull($row->deleted_at);
        $this->assertNotNull($this->latestAudit('user.archived'));
    }

    public function testUserCannotArchiveOwnAccount(): void
    {
        $petugasId = $this->userId('petugas');
        $this->loginAs('petugas');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Anda tidak dapat mengarsipkan akun sendiri.');

        (new UserService())->archive($petugasId);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(int $id, string $roleSlug, string $status): array
    {
        $user = model(UserModel::class)->find($id);

        return [
            'full_name'        => $user->full_name,
            'username'         => $user->username,
            'email'            => $user->email,
            'phone'            => $user->phone,
            'role_id'          => $this->roleId($roleSlug),
            'status'           => $status,
            'password'         => '',
            'password_confirm' => '',
        ];
    }

    private function loginAs(string $username): void
    {
        session()->set('auth_user_id', $this->userId($username));
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

    private function latestAudit(string $action): ?object
    {
        return $this->db->table('audit_logs')
            ->where('action', $action)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();
    }
}
