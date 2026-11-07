<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\DonasiModel;
use App\Models\DonaturModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class DonaturServiceTest extends CIUnitTestCase
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

    public function testCreateGeneratesCodeAndWritesAudit(): void
    {
        $id = service('donaturService')->create($this->donorData([
            'name'  => 'Donatur Pertama',
            'email' => 'PERTAMA@EXAMPLE.TEST',
        ]));

        $donor = model(DonaturModel::class)->find($id);

        $this->assertNotNull($donor);
        $this->assertMatchesRegularExpression('/^DNR-\d{8}-\d{4}$/', $donor->code);
        $this->assertSame('pertama@example.test', $donor->email);
        $this->assertSame('Donatur Pertama', $donor->name);

        $audit = $this->latestAudit('donor.created');

        $this->assertNotNull($audit);
        $this->assertSame('donatur', $audit->entity_type);
        $this->assertSame((string) $id, (string) $audit->entity_id);
        $this->assertStringContainsString('Donatur Pertama', (string) $audit->new_values);
        $this->assertSame($this->adminId(), (int) $audit->user_id);
    }

    public function testGeneratedCodeDoesNotCollideWithArchivedDonor(): void
    {
        $firstId   = service('donaturService')->create($this->donorData(['name' => 'Donatur Pertama']));
        $firstCode = model(DonaturModel::class)->find($firstId)->code;

        service('donaturService')->archive($firstId);

        $secondId   = service('donaturService')->create($this->donorData(['name' => 'Donatur Kedua']));
        $secondCode = model(DonaturModel::class)->find($secondId)->code;

        $this->assertNotSame($firstCode, $secondCode);
        $this->assertNotNull(model(DonaturModel::class)->withDeleted()->find($firstId));
    }

    public function testUpdateWritesOldAndNewValuesToAudit(): void
    {
        $id = service('donaturService')->create($this->donorData(['name' => 'Nama Lama']));

        service('donaturService')->update($id, $this->donorData([
            'name'   => 'Nama Baru',
            'email'  => 'baru@example.test',
            'status' => 'inactive',
        ]));

        $donor = model(DonaturModel::class)->find($id);

        $this->assertSame('Nama Baru', $donor->name);
        $this->assertSame('baru@example.test', $donor->email);
        $this->assertSame('inactive', $donor->status);

        $audit = $this->latestAudit('donor.updated');

        $this->assertNotNull($audit);
        $this->assertStringContainsString('Nama Lama', (string) $audit->old_values);
        $this->assertStringContainsString('Nama Baru', (string) $audit->new_values);
    }

    public function testToggleStatusWritesAudit(): void
    {
        $id = service('donaturService')->create($this->donorData());

        $this->assertSame('inactive', service('donaturService')->toggleStatus($id));
        $this->assertSame('inactive', model(DonaturModel::class)->find($id)->status);
        $this->assertNotNull($this->latestAudit('donor.deactivated'));

        $this->assertSame('active', service('donaturService')->toggleStatus($id));
        $this->assertSame('active', model(DonaturModel::class)->find($id)->status);
        $this->assertNotNull($this->latestAudit('donor.activated'));
    }

    public function testArchiveSoftDeletesDonorAndWritesAudit(): void
    {
        $id = service('donaturService')->create($this->donorData(['name' => 'Donatur Arsip']));

        service('donaturService')->archive($id);

        $this->assertNull(model(DonaturModel::class)->find($id));
        $this->assertNotNull(model(DonaturModel::class)->withDeleted()->find($id));

        $row = $this->db->table('donatur')->where('id', $id)->get()->getRow();
        $this->assertNotNull($row->deleted_at);

        $audit = $this->latestAudit('donor.archived');
        $this->assertNotNull($audit);
        $this->assertStringContainsString('Donatur Arsip', (string) $audit->old_values);
    }

    public function testArchiveKeepsDonationHistory(): void
    {
        $donorId = service('donaturService')->create($this->donorData());

        $donationId = (int) model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-TEST-0001',
            'donor_id'           => $donorId,
            'is_anonymous'       => 0,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_VERIFIED,
            'donation_date'      => '2026-10-28',
            'amount'             => '750000.00',
        ], true);

        service('donaturService')->archive($donorId);

        $donation = model(DonasiModel::class)->find($donationId);

        $this->assertNotNull($donation);
        $this->assertSame($donorId, (int) $donation->donor_id);

        $donor = model(DonasiModel::class)->donor($donorId);
        $this->assertNotNull($donor);
        $this->assertSame($donorId, (int) $donor->id);

        $summary = model(DonaturModel::class)->summary($donorId);
        $this->assertSame(1, (int) $summary->donation_count);
        $this->assertSame('750000.00', (string) $summary->total_amount);
    }

    public function testUpdateRejectsUnknownDonor(): void
    {
        $this->expectException(DomainException::class);

        service('donaturService')->update(999999, $this->donorData());
    }

    public function testCreateRejectsInvalidEmail(): void
    {
        $this->expectException(DomainException::class);

        service('donaturService')->create($this->donorData(['email' => 'bukan-email']));
    }

    public function testCreateAllowsDonorWithoutEmailAndPhone(): void
    {
        $id = service('donaturService')->create($this->donorData([
            'name'  => 'Donatur Tanpa Kontak',
            'email' => '',
            'phone' => '',
        ]));

        $donor = model(DonaturModel::class)->find($id);

        $this->assertNotNull($donor);
        $this->assertNull($donor->email);
        $this->assertNull($donor->phone);
    }

    public function testCreateRejectsDuplicateEmailIgnoringCase(): void
    {
        service('donaturService')->create($this->donorData([
            'name'  => 'Donatur Pertama',
            'email' => 'sama@example.test',
        ]));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Email sudah digunakan oleh donatur lain');

        service('donaturService')->create($this->donorData([
            'name'  => 'Donatur Kedua',
            'email' => 'SAMA@EXAMPLE.TEST',
            'phone' => '089900000000',
        ]));
    }

    public function testCreateRejectsDuplicateNameAndPhoneWithDifferentFormatting(): void
    {
        service('donaturService')->create($this->donorData([
            'name'  => 'Budi Santoso',
            'email' => 'budi@example.test',
            'phone' => '0812-3456-7890',
        ]));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('nama dan nomor telepon yang sama');

        service('donaturService')->create($this->donorData([
            'name'  => '  budi   santoso ',
            'email' => 'budi.lain@example.test',
            'phone' => '081234567890',
        ]));
    }

    public function testUpdateAllowsOwnEmailAndRejectsAnotherDonorsEmail(): void
    {
        $firstId = service('donaturService')->create($this->donorData([
            'name'  => 'Donatur Pertama',
            'email' => 'pertama@example.test',
            'phone' => '081100000001',
        ]));

        $secondId = service('donaturService')->create($this->donorData([
            'name'  => 'Donatur Kedua',
            'email' => 'kedua@example.test',
            'phone' => '081100000002',
        ]));

        service('donaturService')->update($secondId, $this->donorData([
            'name'  => 'Donatur Kedua',
            'email' => 'kedua@example.test',
            'phone' => '081100000002',
        ]));

        $this->assertSame('kedua@example.test', model(DonaturModel::class)->find($secondId)->email);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Email sudah digunakan oleh donatur lain');

        service('donaturService')->update($secondId, $this->donorData([
            'name'  => 'Donatur Kedua',
            'email' => 'pertama@example.test',
            'phone' => '081100000002',
        ]));
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function donorData(array $overrides = []): array
    {
        return array_merge([
            'name'         => 'Donatur Uji',
            'type'         => 'individual',
            'email'        => 'donatur@example.test',
            'phone'        => '081200000000',
            'address'      => 'Jl. Uji No. 1',
            'notes'        => '',
            'is_anonymous' => 0,
            'status'       => 'active',
        ], $overrides);
    }

    private function loginAs(string $username): void
    {
        $row = $this->db->table('users')->select('id')->where('username', $username)->get()->getRow();

        $this->assertNotNull($row, 'Pengguna uji tidak ditemukan: ' . $username);

        session()->set('auth_user_id', (int) $row->id);
    }

    private function adminId(): int
    {
        return (int) $this->db->table('users')->select('id')->where('username', 'admin')->get()->getRow()->id;
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
