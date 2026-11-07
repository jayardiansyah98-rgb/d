<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Memastikan role awal dan pemetaan permission sesuai kebijakan akses.
 *
 * @internal
 */
final class RolePermissionTest extends CIUnitTestCase
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

    public function testDefaultRolesUseTheApprovedNames(): void
    {
        $roles = $this->db->table('roles')
            ->select('name, slug, is_system')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultObject();

        $this->assertSame(
            ['admin', 'keuangan', 'distribusi', 'pimpinan'],
            array_map(static fn (object $role): string => $role->slug, $roles),
        );

        $this->assertSame(
            ['Admin', 'Keuangan', 'Distribusi', 'Pimpinan'],
            array_map(static fn (object $role): string => $role->name, $roles),
        );

        $this->assertSame(1, (int) $roles[0]->is_system, 'Hanya role Admin yang boleh menjadi role sistem.');
    }

    public function testAdminRoleHasEveryPermission(): void
    {
        $this->assertSame($this->allPermissionSlugs(), $this->permissionSlugs('admin'));
    }

    public function testKeuanganRoleManagesReceiptsAndReports(): void
    {
        $permissions = $this->permissionSlugs('keuangan');

        $this->assertContains('dashboard.view', $permissions);
        $this->assertContains('donors.view', $permissions);
        $this->assertContains('donors.manage', $permissions);
        $this->assertContains('donations.view', $permissions);
        $this->assertContains('donations.manage', $permissions);
        $this->assertContains('reports.view', $permissions);

        $this->assertNotContains('distributions.manage', $permissions);
        $this->assertNotContains('users.view', $permissions);
        $this->assertNotContains('roles.manage', $permissions);
        $this->assertNotContains('audit.view', $permissions);
    }

    public function testDistribusiRoleManagesDistributionWithoutMutatingReceipts(): void
    {
        $permissions = $this->permissionSlugs('distribusi');

        $this->assertContains('dashboard.view', $permissions);
        $this->assertContains('donations.view', $permissions);
        $this->assertContains('distributions.view', $permissions);
        $this->assertContains('distributions.manage', $permissions);

        $this->assertNotContains('donations.manage', $permissions);
        $this->assertNotContains('reports.view', $permissions);
        $this->assertNotContains('users.view', $permissions);
    }

    public function testPimpinanRoleIsReadOnly(): void
    {
        $permissions = $this->permissionSlugs('pimpinan');

        $this->assertContains('dashboard.view', $permissions);
        $this->assertContains('donations.view', $permissions);
        $this->assertContains('distributions.view', $permissions);
        $this->assertContains('reports.view', $permissions);
        $this->assertContains('audit.view', $permissions);

        $this->assertNotContains('donations.manage', $permissions);
        $this->assertNotContains('distributions.manage', $permissions);
        $this->assertNotContains('donors.manage', $permissions);
        $this->assertNotContains('users.view', $permissions);
    }

    /**
     * @return list<string>
     */
    private function permissionSlugs(string $roleSlug): array
    {
        $rows = $this->db->table('role_permissions')
            ->select('permissions.slug')
            ->join('roles', 'roles.id = role_permissions.role_id')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('roles.slug', $roleSlug)
            ->orderBy('permissions.slug', 'ASC')
            ->get()
            ->getResultObject();

        return array_map(static fn (object $row): string => $row->slug, $rows);
    }

    /**
     * @return list<string>
     */
    private function allPermissionSlugs(): array
    {
        $rows = $this->db->table('permissions')
            ->select('slug')
            ->orderBy('slug', 'ASC')
            ->get()
            ->getResultObject();

        return array_map(static fn (object $row): string => $row->slug, $rows);
    }
}
