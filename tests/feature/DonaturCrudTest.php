<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\DonaturModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class DonaturCrudTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';
    protected $DBGroup   = 'tests';
    protected $seed      = DatabaseSeeder::class;

    private int $donorSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetServices();
        $this->mockSession();
    }

    public function testAdministratorCanCreateDonorThroughForm(): void
    {
        $result = $this->asUser('admin')->post('donatur', [
            'csrf_token'   => $this->csrfToken(),
            'name'         => 'Yayasan Peduli Sesama',
            'type'         => 'organization',
            'email'        => 'PEDULI@EXAMPLE.ORG',
            'phone'        => '021-555-1234',
            'address'      => 'Jl. Merdeka No. 10',
            'notes'        => 'Mitra tetap bulanan.',
            'is_anonymous' => '0',
            'status'       => 'active',
        ]);

        $result->assertRedirect();
        $this->assertStringStartsWith(site_url('donatur/'), (string) $result->getRedirectUrl());
        $result->assertSessionHas('success');

        $donor = model(DonaturModel::class)->where('email', 'peduli@example.org')->first();

        $this->assertNotNull($donor);
        $this->assertMatchesRegularExpression('/^DNR-\d{8}-\d{4}$/', $donor->code);
        $this->assertSame('organization', $donor->type);
        $this->assertSame($this->userId('admin'), (int) $donor->created_by);

        $audit = $this->latestAudit('donor.created');
        $this->assertNotNull($audit);
        $this->assertSame((string) $donor->id, (string) $audit->entity_id);
        $this->assertSame($this->userId('admin'), (int) $audit->user_id);
    }

    public function testKeuanganCanCreateDonorThroughForm(): void
    {
        $result = $this->asUser('pengurus')->post('donatur', [
            'csrf_token'   => $this->csrfToken(),
            'name'         => 'Donatur dari Keuangan',
            'type'         => 'individual',
            'email'        => 'keuangan@example.test',
            'phone'        => '081255555555',
            'address'      => 'Jl. Keuangan No. 5',
            'notes'        => '',
            'is_anonymous' => '0',
            'status'       => 'active',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('success');

        $this->assertNotNull(model(DonaturModel::class)->where('email', 'keuangan@example.test')->first());
    }

    public function testCreateDonorWithInvalidEmailShowsValidationErrors(): void
    {
        $result = $this->asUser('admin')->post('donatur', [
            'csrf_token'   => $this->csrfToken(),
            'name'         => 'Donatur Salah',
            'type'         => 'individual',
            'email'        => 'bukan-email',
            'phone'        => '081200000000',
            'address'      => '',
            'notes'        => '',
            'is_anonymous' => '0',
            'status'       => 'active',
        ]);

        $result->assertRedirectTo(site_url('donatur/new'));
        $result->assertSessionHas('errors');
        $this->assertSame(0, model(DonaturModel::class)->where('name', 'Donatur Salah')->countAllResults());
    }

    public function testDuplicateEmailShowsErrorNotification(): void
    {
        $this->createDonor('Donatur Asli', [
            'email' => 'duplikat@example.test',
            'phone' => '081244444444',
        ]);

        $result = $this->asUser('admin')->post('donatur', [
            'csrf_token'   => $this->csrfToken(),
            'name'         => 'Donatur Duplikat',
            'type'         => 'individual',
            'email'        => 'DUPLIKAT@EXAMPLE.TEST',
            'phone'        => '081266666666',
            'address'      => '',
            'notes'        => '',
            'is_anonymous' => '0',
            'status'       => 'active',
        ]);

        $result->assertRedirectTo(site_url('donatur/new'));
        $result->assertSessionHas('error');
        $this->assertSame(1, model(DonaturModel::class)->where('email', 'duplikat@example.test')->countAllResults());
    }

    public function testAdministratorCanUpdateDonorThroughForm(): void
    {
        $id = $this->createDonor('Donatur Awal');

        $result = $this->asUser('admin')->post('donatur/' . $id, [
            'csrf_token'   => $this->csrfToken(),
            'name'         => 'Donatur Diperbarui',
            'type'         => 'organization',
            'email'        => 'diperbarui@example.test',
            'phone'        => '081298765432',
            'address'      => 'Jl. Baru No. 2',
            'notes'        => '',
            'is_anonymous' => '1',
            'status'       => 'inactive',
        ]);

        $result->assertRedirectTo(site_url('donatur/' . $id));
        $result->assertSessionHas('success');

        $donor = model(DonaturModel::class)->find($id);

        $this->assertSame('Donatur Diperbarui', $donor->name);
        $this->assertSame('organization', $donor->type);
        $this->assertSame('inactive', $donor->status);
        $this->assertSame(1, (int) $donor->is_anonymous);
        $this->assertNotNull($this->latestAudit('donor.updated'));
    }

    public function testAdministratorCanDeactivateAndActivateDonor(): void
    {
        $id = $this->createDonor('Donatur Status');

        $result = $this->asUser('admin')->post('donatur/' . $id . '/status', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $result->assertRedirectTo(site_url('donatur'));
        $this->assertSame('inactive', model(DonaturModel::class)->find($id)->status);
        $this->assertNotNull($this->latestAudit('donor.deactivated'));

        $result = $this->asUser('admin')->post('donatur/' . $id . '/status', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $result->assertRedirectTo(site_url('donatur'));
        $this->assertSame('active', model(DonaturModel::class)->find($id)->status);
        $this->assertNotNull($this->latestAudit('donor.activated'));
    }

    public function testAdministratorCanArchiveDonor(): void
    {
        $id = $this->createDonor('Donatur Arsip');

        $result = $this->asUser('admin')->post('donatur/' . $id . '/delete', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $result->assertRedirectTo(site_url('donatur'));
        $result->assertSessionHas('success');

        $this->assertNull(model(DonaturModel::class)->find($id));

        $row = $this->db->table('donatur')->where('id', $id)->get()->getRow();
        $this->assertNotNull($row->deleted_at);
        $this->assertNotNull($this->latestAudit('donor.archived'));
    }

    public function testPetugasCanViewDonorList(): void
    {
        $this->createDonor('Donatur Terlihat');

        $result = $this->asUser('petugas')->get('donatur');

        $result->assertOK();
        $result->assertSee('Daftar Donatur');
        $result->assertSee('Donatur Terlihat');
    }

    public function testPetugasCannotCreateDonor(): void
    {
        $result = $this->asUser('petugas')->post('donatur', [
            'csrf_token'   => $this->csrfToken(),
            'name'         => 'Donatur Ilegal',
            'type'         => 'individual',
            'email'        => 'ilegal@example.test',
            'phone'        => '',
            'address'      => '',
            'notes'        => '',
            'is_anonymous' => '0',
            'status'       => 'active',
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('error');
        $this->assertSame(0, model(DonaturModel::class)->where('name', 'Donatur Ilegal')->countAllResults());
    }

    public function testAuditorCannotViewDonorList(): void
    {
        $result = $this->asUser('auditor')->get('donatur');

        $result->assertRedirectTo(site_url('profile'));
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        $result = $this->withSession([])->get('donatur');

        $result->assertRedirectTo(site_url('login'));
    }

    public function testDonorDetailPageShowsDonorInformation(): void
    {
        $id = $this->createDonor('Hamba Allah');

        $result = $this->asUser('admin')->get('donatur/' . $id);

        $result->assertOK();
        $result->assertSee('Hamba Allah');
        $result->assertSee('DNR-TEST-');
        $result->assertSee('Riwayat Donasi');
    }

    public function testDonorListCanBeSearchedByNamePhoneAndEmail(): void
    {
        $this->createDonor('Alpha Satu', ['phone' => '081111111111', 'email' => 'alpha@example.test']);
        $this->createDonor('Beta Dua', ['phone' => '082222222222', 'email' => 'beta@example.test']);
        $this->createDonor('Gamma Tiga', ['phone' => '083333333333', 'email' => 'gamma@example.test']);

        $byName = $this->asUser('admin')->get('donatur?search=Alpha');
        $byName->assertOK();
        $byName->assertSee('Alpha Satu');
        $byName->assertDontSee('Beta Dua');

        $byPhone = $this->asUser('admin')->get('donatur?search=082222222222');
        $byPhone->assertOK();
        $byPhone->assertSee('Beta Dua');
        $byPhone->assertDontSee('Alpha Satu');

        $byEmail = $this->asUser('admin')->get('donatur?search=gamma%40example.test');
        $byEmail->assertOK();
        $byEmail->assertSee('Gamma Tiga');
        $byEmail->assertDontSee('Beta Dua');
    }

    public function testDonorListIsPaginated(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->createDonor(sprintf('Donatur Halaman %02d', $i));
        }

        $firstPage = $this->asUser('admin')->get('donatur');
        $firstPage->assertOK();
        $firstPage->assertSee('Donatur Halaman 01');
        $firstPage->assertSee('Donatur Halaman 10');
        $firstPage->assertDontSee('Donatur Halaman 11');
        $firstPage->assertSee('dari 12 donatur');

        $secondPage = $this->asUser('admin')->get('donatur?page_donatur=2');
        $secondPage->assertOK();
        $secondPage->assertSee('Donatur Halaman 11');
        $secondPage->assertSee('Donatur Halaman 12');
        $secondPage->assertDontSee('Donatur Halaman 01');
    }

    public function testPetugasCannotOpenEditFormOrSubmitUpdateOrArchive(): void
    {
        $id = $this->createDonor('Donatur Terkunci', [
            'phone' => '081299999999',
            'email' => 'terkunci@example.test',
        ]);

        $this->asUser('petugas')->get('donatur/' . $id . '/edit')
            ->assertRedirectTo(site_url('profile'));

        $this->asUser('petugas')->post('donatur/' . $id, [
            'csrf_token'   => $this->csrfToken(),
            'name'         => 'Diubah Tanpa Izin',
            'type'         => 'individual',
            'email'        => 'terkunci@example.test',
            'phone'        => '081299999999',
            'address'      => '',
            'notes'        => '',
            'is_anonymous' => '0',
            'status'       => 'active',
        ])->assertRedirectTo(site_url('profile'));

        $this->asUser('petugas')->post('donatur/' . $id . '/delete', [
            'csrf_token' => $this->csrfToken(),
        ])->assertRedirectTo(site_url('profile'));

        $donor = model(DonaturModel::class)->find($id);

        $this->assertNotNull($donor);
        $this->assertSame('Donatur Terkunci', $donor->name);
    }

    public function testDonorOutputIsEscaped(): void
    {
        $name = '<script>alert(1)</script>';
        $id   = $this->createDonor($name, [
            'phone' => '081277777777',
            'email' => 'xss@example.test',
        ]);

        $list = $this->asUser('admin')->get('donatur');
        $list->assertOK();
        $list->assertSee(esc($name));
        $list->assertDontSee($name);

        $detail = $this->asUser('admin')->get('donatur/' . $id);
        $detail->assertOK();
        $detail->assertSee(esc($name));
        $detail->assertDontSee($name);
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

    private function createDonor(string $name, array $overrides = []): int
    {
        $this->donorSequence++;

        return (int) model(DonaturModel::class)->insert(array_merge([
            'code'         => 'DNR-TEST-' . str_pad((string) $this->donorSequence, 4, '0', STR_PAD_LEFT),
            'name'         => $name,
            'type'         => 'individual',
            'phone'        => '081200000000',
            'is_anonymous' => 0,
            'status'       => 'active',
        ], $overrides), true);
    }

    private function userId(string $username): int
    {
        $row = $this->db->table('users')->select('id')->where('username', $username)->get()->getRow();

        $this->assertNotNull($row, 'Pengguna uji tidak ditemukan: ' . $username);

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
