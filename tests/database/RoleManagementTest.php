<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Libraries\RoleService;
use App\Models\RoleModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class RoleManagementTest extends CIUnitTestCase
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
        $this->loginAs('admin');
    }

    public function testCreateRoleWithPermissionsWritesAudit(): void
    {
        $permissionIds = $this->permissionIds(['dashboard.view', 'reports.view']);

        $id = (new RoleService())->create([
            'name'        => 'Relawan',
            'slug'        => 'relawan',
            'description' => 'Membantu operasional lapangan.',
        ], $permissionIds);

        $role = model(RoleModel::class)->find($id);

        $this->assertNotNull($role);
        $this->assertSame('relawan', $role->slug);
        $this->assertSame(0, (int) $role->is_system);
        $this->assertSame($permissionIds, model(RoleModel::class)->getPermissionIds($id));

        $audit = $this->latestAudit('role.created');
        $this->assertNotNull($audit);
        $this->assertSame($permissionIds, json_decode($audit->new_values, true)['permission_ids']);
    }

    public function testCreateRoleRequiresAtLeastOneValidPermission(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Pilih minimal satu hak akses yang valid.');

        (new RoleService())->create([
            'name'        => 'Tanpa Hak Akses',
            'slug'        => 'tanpa-hak-akses',
            'description' => null,
        ], []);
    }

    public function testCreateRoleIgnoresUnknownPermissionIds(): void
    {
        $valid = $this->permissionIds(['dashboard.view'])[0];

        $id = (new RoleService())->create([
            'name'        => 'Relawan',
            'slug'        => 'relawan',
            'description' => null,
        ], [$valid, 999999]);

        $this->assertSame([$valid], model(RoleModel::class)->getPermissionIds($id));
    }

    public function testUpdateRoleReplacesPermissions(): void
    {
        $createIds = $this->permissionIds(['dashboard.view', 'reports.view']);
        $id        = (new RoleService())->create([
            'name'        => 'Relawan',
            'slug'        => 'relawan',
            'description' => null,
        ], $createIds);

        $replaceIds = $this->permissionIds(['dashboard.view', 'donations.view']);

        (new RoleService())->update($id, [
            'name'        => 'Relawan Lapangan',
            'slug'        => 'relawan',
            'description' => 'Diperbarui',
        ], $replaceIds);

        $role = model(RoleModel::class)->find($id);

        $this->assertSame('Relawan Lapangan', $role->name);
        $this->assertSame($replaceIds, model(RoleModel::class)->getPermissionIds($id));
        $this->assertNotNull($this->latestAudit('role.updated'));
    }

    public function testSystemRoleCannotBeUpdated(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Role sistem tidak dapat diubah.');

        (new RoleService())->update($this->roleId('admin'), [
            'name'        => 'Admin',
            'slug'        => 'admin',
            'description' => null,
        ], $this->permissionIds(['dashboard.view']));
    }

    public function testSystemRoleCannotBeDeleted(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Role sistem tidak dapat dihapus.');

        (new RoleService())->delete($this->roleId('admin'));
    }

    public function testRoleInUseCannotBeDeleted(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Role masih digunakan oleh pengguna dan tidak dapat dihapus.');

        (new RoleService())->delete($this->roleId('distribusi'));
    }

    public function testDeleteUnusedRoleRemovesPermissionsAndAudits(): void
    {
        $id = (new RoleService())->create([
            'name'        => 'Relawan',
            'slug'        => 'relawan',
            'description' => null,
        ], $this->permissionIds(['dashboard.view']));

        (new RoleService())->delete($id);

        $this->assertNull(model(RoleModel::class)->find($id));
        $this->assertSame(0, $this->db->table('role_permissions')->where('role_id', $id)->countAllResults());
        $this->assertNotNull($this->latestAudit('role.deleted'));
    }

    private function loginAs(string $username): void
    {
        $row = $this->db->table('users')->select('id')->where('username', $username)->get()->getRow();

        $this->assertNotNull($row, 'Pengguna uji tidak ditemukan: ' . $username);

        session()->set('auth_user_id', (int) $row->id);
    }

    /**
     * @param list<string> $slugs
     *
     * @return list<int>
     */
    private function permissionIds(array $slugs): array
    {
        $rows = $this->db->table('permissions')
            ->select('id')
            ->whereIn('slug', $slugs)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultObject();

        $this->assertCount(count($slugs), $rows, 'Sebagian permission uji tidak ditemukan.');

        return array_map(static fn (object $row): int => (int) $row->id, $rows);
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
