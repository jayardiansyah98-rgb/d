<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\DonasiModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Matriks akses HTTP untuk role Admin, Keuangan, Distribusi, dan Pimpinan.
 *
 * @internal
 */
final class RoleAccessTest extends CIUnitTestCase
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

    public function testKeuanganCanManageReceiptsAndReportsButNotAdministration(): void
    {
        $this->asUser('pengurus')->get('donasi')->assertOK();
        $this->asUser('pengurus')->get('donasi/new')->assertOK();
        $this->asUser('pengurus')->get('template/laporan')->assertOK();

        $this->asUser('pengurus')->get('users')->assertRedirectTo(site_url('profile'));
        $this->asUser('pengurus')->get('roles')->assertRedirectTo(site_url('profile'));
        $this->asUser('pengurus')->get('audit-logs')->assertRedirectTo(site_url('profile'));
    }

    public function testDistribusiCanViewReceiptsButCannotCreateOrVerifyThem(): void
    {
        $this->asUser('petugas')->get('donasi')->assertOK();
        $this->asUser('petugas')->get('donasi/new')->assertRedirectTo(site_url('profile'));

        $result = $this->asUser('petugas')->post('donasi', $this->donationPayload());
        $result->assertRedirectTo(site_url('profile'));

        $this->assertSame(0, model(DonasiModel::class)->countAllResults());
    }

    public function testPimpinanCanViewReportsButCannotChangeTransactions(): void
    {
        $this->asUser('auditor')->get('donasi')->assertOK();
        $this->asUser('auditor')->get('template/laporan')->assertOK();

        $this->asUser('auditor')->get('donasi/new')->assertRedirectTo(site_url('profile'));

        $result = $this->asUser('auditor')->post('donasi', $this->donationPayload());
        $result->assertRedirectTo(site_url('profile'));

        $this->assertSame(0, model(DonasiModel::class)->countAllResults());
    }

    public function testDirectUrlToModuleWithoutPermissionIsDeniedOnTheServer(): void
    {
        // Role tanpa users.view tidak boleh membuka /users walau mengetik URL langsung.
        $this->asUser('petugas')->get('users')->assertRedirectTo(site_url('profile'));

        // Pimpinan hanya membaca, tidak boleh membuka pengaturan role.
        $this->asUser('auditor')->get('roles')->assertRedirectTo(site_url('profile'));

        // Keuangan tidak berhak melihat audit log.
        $this->asUser('pengurus')->get('audit-logs')->assertRedirectTo(site_url('profile'));
    }

    private function asUser(string $username): self
    {
        $this->resetServices();
        $this->mockSession();

        return $this->withSession(['auth_user_id' => $this->userId($username)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function donationPayload(): array
    {
        return [
            'csrf_token'       => $this->csrfToken(),
            'donation_type'    => 'money',
            'donor_id'         => '',
            'is_anonymous'     => '1',
            'donation_date'    => date('Y-m-d'),
            'amount'           => '100000',
            'payment_method'   => 'cash',
            'reference_number' => '',
            'goods_name'       => '',
            'goods_quantity'   => '',
            'goods_unit'       => '',
            'estimated_value'  => '',
            'notes'            => '',
        ];
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
}
