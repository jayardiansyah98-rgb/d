<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\DonasiModel;
use App\Models\DonaturModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class DonasiServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

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
        $this->loginAs('admin');
    }

    public function testCreateMoneyDonationGeneratesNumberAndWritesAudit(): void
    {
        $id = service('donasiService')->create($this->moneyData());

        $donation = model(DonasiModel::class)->find($id);

        $this->assertNotNull($donation);
        $this->assertMatchesRegularExpression('/^DON-\d{8}-\d{4}$/', $donation->transaction_number);
        $this->assertSame(DonasiModel::STATUS_PENDING, $donation->status);
        $this->assertSame(DonasiModel::TYPE_MONEY, $donation->donation_type);
        $this->assertSame('1500000.00', (string) $donation->amount);
        $this->assertSame('bank_transfer', $donation->payment_method);
        $this->assertSame('TRX-12345', $donation->reference_number);
        $this->assertSame($this->adminId(), (int) $donation->created_by);

        $audit = $this->latestAudit('donation.created');

        $this->assertNotNull($audit);
        $this->assertSame('donasi', $audit->entity_type);
        $this->assertSame((string) $id, (string) $audit->entity_id);
        $this->assertSame($this->adminId(), (int) $audit->user_id);
        $this->assertStringContainsString($donation->transaction_number, (string) $audit->new_values);
    }

    public function testCreateGoodsDonationStoresGoodsFieldsOnly(): void
    {
        $id = service('donasiService')->create($this->goodsData());

        $donation = model(DonasiModel::class)->find($id);

        $this->assertNotNull($donation);
        $this->assertSame(DonasiModel::TYPE_GOODS, $donation->donation_type);
        $this->assertNull($donation->amount);
        $this->assertNull($donation->payment_method);
        $this->assertSame('Beras premium', $donation->goods_name);
        $this->assertSame('25.00', (string) $donation->goods_quantity);
        $this->assertSame('karung', $donation->goods_unit);
        $this->assertSame('3750000.00', (string) $donation->estimated_value);
    }

    public function testCreateAnonymousDonationWithoutDonorIsAllowed(): void
    {
        $id = service('donasiService')->create($this->moneyData([
            'donor_id'     => 0,
            'is_anonymous' => 1,
        ]));

        $donation = model(DonasiModel::class)->find($id);

        $this->assertNotNull($donation);
        $this->assertNull($donation->donor_id);
        $this->assertSame(1, (int) $donation->is_anonymous);
    }

    public function testCreateRequiresDonorWhenNotAnonymous(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Donatur wajib dipilih');

        service('donasiService')->create($this->moneyData(['donor_id' => 0]));
    }

    public function testMoneyDonationRequiresAmount(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nominal donasi uang wajib lebih dari 0');

        service('donasiService')->create($this->moneyData(['amount' => '']));
    }

    public function testBankTransferRequiresReferenceNumber(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nomor referensi wajib diisi');

        service('donasiService')->create($this->moneyData(['reference_number' => '']));
    }

    public function testDonationDateCannotBeInTheFuture(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tidak boleh melebihi hari ini');

        service('donasiService')->create($this->moneyData([
            'donation_date' => date('Y-m-d', strtotime('+1 day')),
        ]));
    }

    public function testGoodsDonationRequiresNameQuantityAndUnit(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Satuan barang wajib diisi');

        service('donasiService')->create($this->goodsData(['goods_unit' => '']));
    }

    public function testUpdateOnlyAllowedWhilePending(): void
    {
        $id = service('donasiService')->create($this->moneyData());

        service('donasiService')->update($id, $this->moneyData([
            'amount' => '2000000',
            'notes'  => 'Nominal dikoreksi.',
        ]));

        $donation = model(DonasiModel::class)->find($id);

        $this->assertSame('2000000.00', (string) $donation->amount);
        $this->assertSame('Nominal dikoreksi.', $donation->notes);
        $this->assertNotNull($this->latestAudit('donation.updated'));

        service('donasiService')->verify($id);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya donasi berstatus menunggu');

        service('donasiService')->update($id, $this->moneyData(['amount' => '3000000']));
    }

    public function testVerifySetsVerifierAndWritesAudit(): void
    {
        $id = service('donasiService')->create($this->moneyData());

        service('donasiService')->verify($id);

        $donation = model(DonasiModel::class)->find($id);

        $this->assertSame(DonasiModel::STATUS_VERIFIED, $donation->status);
        $this->assertSame($this->adminId(), (int) $donation->verified_by);
        $this->assertNotNull($donation->verified_at);
        $this->assertNotNull($this->latestAudit('donation.verified'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya donasi berstatus menunggu');

        service('donasiService')->verify($id);
    }

    public function testCancelVerifiedDonationRequiresReasonAndKeepsRecord(): void
    {
        $id = service('donasiService')->create($this->moneyData());
        service('donasiService')->verify($id);

        service('donasiService')->cancel($id, 'Salah input nominal donasi.');

        $donation = model(DonasiModel::class)->find($id);

        $this->assertNotNull($donation);
        $this->assertSame(DonasiModel::STATUS_CANCELLED, $donation->status);
        $this->assertSame('Salah input nominal donasi.', $donation->cancelled_reason);
        $this->assertNotNull($this->latestAudit('donation.cancelled'));
    }

    public function testCancelRejectsShortReasonAndKeepsStatus(): void
    {
        $id = service('donasiService')->create($this->moneyData());

        try {
            service('donasiService')->cancel($id, 'sala');
            $this->fail('Pembatalan dengan alasan terlalu pendek seharusnya ditolak.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('minimal 5 karakter', $exception->getMessage());
        }

        $this->assertSame(DonasiModel::STATUS_PENDING, model(DonasiModel::class)->find($id)->status);
    }

    public function testCreateRejectsArchivedDonor(): void
    {
        $donorId = $this->createDonor('Donatur Sudah Arsip');
        service('donaturService')->archive($donorId);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah diarsipkan');

        service('donasiService')->create($this->moneyData(['donor_id' => $donorId]));
    }

    public function testUpdateKeepsArchivedDonorOnExistingDonation(): void
    {
        $donorId = $this->createDonor('Donatur Arsip Pending');
        $id      = service('donasiService')->create($this->moneyData(['donor_id' => $donorId]));

        service('donaturService')->archive($donorId);

        service('donasiService')->update($id, $this->moneyData([
            'donor_id' => $donorId,
            'notes'    => 'Koreksi setelah donatur diarsipkan.',
        ]));

        $donation = model(DonasiModel::class)->find($id);

        $this->assertSame($donorId, (int) $donation->donor_id);
        $this->assertSame('Koreksi setelah donatur diarsipkan.', $donation->notes);
    }

    public function testMoneyDonationRejectsZeroAndNegativeAmount(): void
    {
        foreach (['0', '-1500'] as $amount) {
            try {
                service('donasiService')->create($this->moneyData(['amount' => $amount]));
                $this->fail('Nominal tidak sah seharusnya ditolak: ' . $amount);
            } catch (DomainException $exception) {
                $this->assertStringContainsString('wajib lebih dari 0', $exception->getMessage());
            }
        }

        $this->assertSame(0, model(DonasiModel::class)->countAllResults());
    }

    public function testGoodsDonationRejectsZeroQuantity(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Kuantitas barang wajib lebih dari 0');

        service('donasiService')->create($this->goodsData(['goods_quantity' => '0']));
    }

    public function testGeneratedNumbersAreUniqueAndIncrement(): void
    {
        $first  = model(DonasiModel::class)->find(service('donasiService')->create($this->moneyData()));
        $second = model(DonasiModel::class)->find(service('donasiService')->create($this->moneyData()));

        $this->assertNotSame($first->transaction_number, $second->transaction_number);

        $firstSequence  = (int) substr($first->transaction_number, -4);
        $secondSequence = (int) substr($second->transaction_number, -4);

        $this->assertSame($firstSequence + 1, $secondSequence);
    }

    public function testCreateRetriesWithNextNumberWhenConcurrentDuplicateOccurs(): void
    {
        $donations = new DuplicateOnceDonasiModel();
        $service   = new \App\Libraries\DonasiService(
            $donations,
            model(DonaturModel::class),
            service('auditLogger'),
        );

        $id = $service->create($this->moneyData());

        $this->assertSame(2, count($donations->attemptedNumbers));
        $this->assertNotSame($donations->attemptedNumbers[0], $donations->attemptedNumbers[1]);

        $created = model(DonasiModel::class)->find($id);

        $this->assertNotNull($created);
        $this->assertSame($donations->attemptedNumbers[1], $created->transaction_number);

        $numbers = array_column(
            $this->db->table('donasi')->select('transaction_number')->get()->getResultArray(),
            'transaction_number',
        );

        $this->assertSame(count($numbers), count(array_unique($numbers)));
        $this->assertNotNull($this->latestAudit('donation.created'));
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function moneyData(array $overrides = []): array
    {
        return array_merge([
            'donation_type'    => 'money',
            'donor_id'         => $this->createDonor(),
            'is_anonymous'     => 0,
            'donation_date'    => date('Y-m-d'),
            'amount'           => '1500000',
            'payment_method'   => 'bank_transfer',
            'reference_number' => 'TRX-12345',
            'goods_name'       => '',
            'goods_quantity'   => '',
            'goods_unit'       => '',
            'estimated_value'  => '',
            'notes'            => '',
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function goodsData(array $overrides = []): array
    {
        return array_merge([
            'donation_type'    => 'goods',
            'donor_id'         => $this->createDonor(),
            'is_anonymous'     => 0,
            'donation_date'    => date('Y-m-d'),
            'amount'           => '',
            'payment_method'   => '',
            'reference_number' => '',
            'goods_name'       => 'Beras premium',
            'goods_quantity'   => '25',
            'goods_unit'       => 'karung',
            'estimated_value'  => '3750000',
            'notes'            => '',
        ], $overrides);
    }

    private function createDonor(): int
    {
        $this->donorSequence++;

        return (int) model(DonaturModel::class)->insert([
            'code'         => 'DNR-TEST-D' . str_pad((string) $this->donorSequence, 4, '0', STR_PAD_LEFT),
            'name'         => 'Donatur Donasi ' . $this->donorSequence,
            'type'         => 'individual',
            'phone'        => '081200000000',
            'is_anonymous' => 0,
            'status'       => 'active',
        ], true);
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

/**
 * Simulasi permintaan bersamaan: percobaan pertama gagal karena nomor
 * sudah dipakai baris lain, percobaan kedua harus memakai nomor berikutnya.
 */
final class DuplicateOnceDonasiModel extends DonasiModel
{
    /** @var list<string> */
    public array $attemptedNumbers = [];

    private bool $throwOnce = true;

    public function insert($row = null, bool $returnID = true)
    {
        $number = (string) ($row['transaction_number'] ?? '');
        $this->attemptedNumbers[] = $number;

        if ($this->throwOnce) {
            $this->throwOnce = false;

            // Tiru transaksi lain yang sudah commit lebih dahulu.
            $this->db->table($this->table)->insert($row);

            throw new \CodeIgniter\Database\Exceptions\DatabaseException(
                "Duplicate entry '" . $number . "' for key 'transaction_number'",
            );
        }

        return parent::insert($row, $returnID);
    }
}
