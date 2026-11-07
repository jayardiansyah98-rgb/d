<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthorizationTest extends CIUnitTestCase
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

    public function testGuestCannotAccessUserManagement(): void
    {
        $result = $this->withSession([])->get('users');

        $result->assertRedirectTo(site_url('login'));
    }

    public function testAdministratorCanAccessUserAndRoleManagement(): void
    {
        $result = $this->asUser('admin')->get('users');

        $result->assertOK();
        $result->assertSee('Daftar Pengguna');
    }

    public function testAdministratorCanAccessRoleManagement(): void
    {
        $result = $this->asUser('admin')->get('roles');

        $result->assertOK();
        $result->assertSee('Role');
    }

    public function testAdministratorCanAccessAuditLog(): void
    {
        $result = $this->asUser('admin')->get('audit-logs');

        $result->assertOK();
        $result->assertSee('Jejak Audit');
    }

    public function testAuditorCanReadAuditLog(): void
    {
        $result = $this->asUser('auditor')->get('audit-logs');

        $result->assertOK();
    }

    public function testAuditorCannotAccessUserManagement(): void
    {
        $result = $this->asUser('auditor')->get('users');

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('error');
    }

    public function testPetugasCannotAccessUserManagement(): void
    {
        $result = $this->asUser('petugas')->get('users');

        $result->assertRedirectTo(site_url('profile'));
    }

    public function testDashboardRequiresAuthentication(): void
    {
        $result = $this->withSession([])->get('dashboard');

        $result->assertRedirectTo(site_url('login'));
    }

    public function testDashboardIsAvailableForAllActiveRoles(): void
    {
        foreach (['admin', 'pengurus', 'petugas', 'auditor'] as $username) {
            $result = $this->asUser($username)->get('dashboard');

            $result->assertOK();
        }
    }

    public function testInactiveUserSessionIsCleared(): void
    {
        $userId = $this->userId('petugas');

        $this->db->table('users')
            ->where('id', $userId)
            ->update(['status' => 'inactive']);

        $result = $this->withSession(['auth_user_id' => $userId])->get('dashboard');

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
    }

    public function testProfileIsAccessibleToEveryAuthenticatedRole(): void
    {
        foreach (['admin', 'pengurus', 'petugas', 'auditor'] as $username) {
            $result = $this->asUser($username)->get('profile');

            $result->assertOK();
        }
    }

    public function testReportTemplateIsAvailableToUsersWithReportPermission(): void
    {
        $result = $this->asUser('admin')->get('template/laporan');

        $result->assertOK();
        $result->assertSee('Pratinjau template laporan');
        $result->assertSee('Belum ada data donasi, distribusi, atau perhitungan laporan');
    }

    public function testReportTemplateIsDeniedToPetugas(): void
    {
        $result = $this->asUser('petugas')->get('template/laporan');

        $result->assertRedirectTo(site_url('profile'));
    }

    private function asUser(string $username): self
    {
        $this->resetServices();
        $this->mockSession();

        return $this->withSession(['auth_user_id' => $this->userId($username)]);
    }

    private function userId(string $username): int
    {
        $row = $this->db->table('users')
            ->select('id')
            ->where('username', $username)
            ->get()
            ->getRow();

        $this->assertNotNull($row, 'Pengguna uji tidak ditemukan: ' . $username);

        return (int) $row->id;
    }
}
