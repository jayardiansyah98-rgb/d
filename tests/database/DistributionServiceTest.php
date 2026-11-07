<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\DistribusiModel;
use App\Models\DonasiModel;
use App\Models\DonaturModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Aturan saldo, stok barang, transisi status, kunci saldo, dan audit
 * modul distribusi.
 *
 * @internal
 */
final class DistributionServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';
    protected $DBGroup   = 'tests';
    protected $seed      = DatabaseSeeder::class;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetServices();
        $this->mockSession();
        $this->loginAs('admin');
    }

    public function testMoneyBalanceUsesOnlyVerifiedDonationsAndActiveDistributions(): void
    {
        $this->createMoneyDonation('1000000', 'verified');
        $this->createMoneyDonation('500000', 'pending');
        $this->createMoneyDonation('700000', 'cancelled');

        $service = service('distribusiService');

        $this->assertSame(
            100000000,
            $service->moneyBalance()['available_cents'],
            'Hanya donasi terverifikasi yang dihitung sebagai penerimaan.',
        );

        $id = $service->create($this->moneyData(['amount' => '200000']));

        $this->assertSame(
            100000000,
            $service->moneyBalance()['available_cents'],
            'Draf belum boleh mengurangi saldo.',
        );

        $service->approve($id);

        $this->assertSame(
            80000000,
            $service->moneyBalance()['available_cents'],
            'Persetujuan mengurangi saldo aktif.',
        );

        $service->cancel($id, 'Koreksi data distribusi');

        $this->assertSame(
            100000000,
            $service->moneyBalance()['available_cents'],
            'Pembatalan harus melepas saldo yang sebelumnya dipakai.',
        );
    }

    public function testApproveRejectsInsufficientMoneyBalanceAndKeepsDraft(): void
    {
        $this->createMoneyDonation('100000', 'verified');

        $id = service('distribusiService')->create($this->moneyData(['amount' => '150000']));

        try {
            service('distribusiService')->approve($id);
            $this->fail('Persetujuan seharusnya ditolak karena saldo tidak mencukupi.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Saldo dana tidak mencukupi', $exception->getMessage());
        }

        $distribution = model(DistribusiModel::class)->find($id);

        $this->assertNotNull($distribution);
        $this->assertSame(DistribusiModel::STATUS_DRAFT, $distribution->status);
        $this->assertSame(10000000, service('distribusiService')->moneyBalance()['available_cents']);
    }

    public function testPendingDonationCannotFundDistribution(): void
    {
        $this->createMoneyDonation('900000', 'pending');

        $id = service('distribusiService')->create($this->moneyData(['amount' => '100000']));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Saldo dana tidak mencukupi');

        service('distribusiService')->approve($id);
    }

    public function testGoodsBalanceNormalizesItemNameAndUnit(): void
    {
        $this->createGoodsDonation('Beras Premium', '25', 'kg');
        $this->createGoodsDonation('  beras   premium ', '5', 'KG');
        $this->createGoodsDonation('Beras Premium', '3', 'karung');

        $balances = service('distribusiService')->goodsBalances();

        $this->assertArrayHasKey('beras premium|kg', $balances);
        $this->assertSame(3000, $balances['beras premium|kg']['available_cents']);
        $this->assertArrayHasKey('beras premium|karung', $balances);
        $this->assertSame(300, $balances['beras premium|karung']['available_cents']);
    }

    public function testGoodsDistributionRequiresSufficientStock(): void
    {
        $this->createGoodsDonation('Beras Premium', '10', 'kg');

        $id = service('distribusiService')->create(
            $this->goodsData(),
            [['item_name' => 'Beras Premium', 'quantity' => '12', 'unit' => 'kg']],
        );

        try {
            service('distribusiService')->approve($id);
            $this->fail('Persetujuan seharusnya ditolak karena stok tidak mencukupi.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Stok barang', $exception->getMessage());
        }

        $this->assertSame(
            DistribusiModel::STATUS_DRAFT,
            model(DistribusiModel::class)->find($id)->status,
        );
    }

    public function testGoodsDistributionReducesStockWhenSufficient(): void
    {
        $this->createGoodsDonation('Beras Premium', '20', 'kg');

        $id = service('distribusiService')->create(
            $this->goodsData(),
            [
                ['item_name' => 'beras premium', 'quantity' => '12', 'unit' => 'KG'],
                ['item_name' => 'Beras Premium', 'quantity' => '3', 'unit' => 'kg'],
            ],
        );

        service('distribusiService')->approve($id);

        $balance = service('distribusiService')->goodsBalance('Beras Premium', 'kg');

        $this->assertSame(500, $balance['available_cents']);
        $this->assertSame(2, count(service('distribusiService')->detailsFor($id)));
    }

    public function testDifferentUnitIsTreatedAsSeparateStock(): void
    {
        $this->createGoodsDonation('Minyak Goreng', '20', 'liter');

        $id = service('distribusiService')->create(
            $this->goodsData(),
            [['item_name' => 'Minyak Goreng', 'quantity' => '5', 'unit' => 'kg']],
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stok barang');

        service('distribusiService')->approve($id);
    }

    public function testCancellingDistributedGoodsReleasesStock(): void
    {
        $this->createGoodsDonation('Sembako Paket', '10', 'dus');

        $id = service('distribusiService')->create(
            $this->goodsData(),
            [['item_name' => 'Sembako Paket', 'quantity' => '4', 'unit' => 'dus']],
        );

        service('distribusiService')->approve($id);
        service('distribusiService')->markDistributed($id);

        $this->assertSame(600, service('distribusiService')->goodsBalance('Sembako Paket', 'dus')['available_cents']);

        service('distribusiService')->cancel($id, 'Penerima mengundurkan diri');

        $this->assertSame(1000, service('distribusiService')->goodsBalance('Sembako Paket', 'dus')['available_cents']);
    }

    public function testOnlyDraftDistributionCanBeEdited(): void
    {
        $this->createMoneyDonation('1000000', 'verified');

        $id = service('distribusiService')->create($this->moneyData(['amount' => '100000']));
        service('distribusiService')->approve($id);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya distribusi berstatus draf');

        service('distribusiService')->update($id, $this->moneyData(['amount' => '200000']));
    }

    public function testDraftCanBeEditedAndRevalidated(): void
    {
        $this->createMoneyDonation('1000000', 'verified');

        $id = service('distribusiService')->create($this->moneyData(['amount' => '100000']));
        service('distribusiService')->update($id, $this->moneyData([
            'amount'       => '250000',
            'recipient_name' => 'Panti Asuhan Baru',
        ]));

        $distribution = model(DistribusiModel::class)->find($id);

        $this->assertSame('250000.00', (string) $distribution->amount);
        $this->assertSame('Panti Asuhan Baru', $distribution->recipient_name);
    }

    public function testLifecycleGuardsAreEnforced(): void
    {
        $this->createMoneyDonation('1000000', 'verified');

        $id = service('distribusiService')->create($this->moneyData(['amount' => '100000']));

        try {
            service('distribusiService')->markDistributed($id);
            $this->fail('Draf tidak boleh langsung ditandai tersalurkan.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('sudah disetujui', $exception->getMessage());
        }

        service('distribusiService')->approve($id);
        service('distribusiService')->markDistributed($id);

        try {
            service('distribusiService')->approve($id);
            $this->fail('Distribusi tersalurkan tidak boleh disetujui ulang.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Hanya distribusi berstatus draf', $exception->getMessage());
        }

        try {
            service('distribusiService')->cancel($id, 'x');
            $this->fail('Alasan pembatalan terlalu pendek seharusnya ditolak.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('minimal 5 karakter', $exception->getMessage());
        }

        service('distribusiService')->cancel($id, 'Koreksi administratif');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah dibatalkan');

        service('distribusiService')->cancel($id, 'Koreksi administratif kedua');
    }

    public function testDistributionNumberIsGeneratedAndUnique(): void
    {
        $this->createMoneyDonation('1000000', 'verified');

        $service = service('distribusiService');
        $first   = $service->create($this->moneyData(['amount' => '100000']));
        $second  = $service->create($this->moneyData(['amount' => '100000']));

        $numbers = [
            model(DistribusiModel::class)->find($first)->distribution_number,
            model(DistribusiModel::class)->find($second)->distribution_number,
        ];

        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^DSR-\d{8}-\d{4}$/', $number);
        }

        $this->assertCount(2, array_unique($numbers));
    }

    public function testAllStatusChangesAreWrittenToAuditLog(): void
    {
        $this->createMoneyDonation('1000000', 'verified');

        $service = service('distribusiService');
        $id      = $service->create($this->moneyData(['amount' => '100000']));
        $service->approve($id);
        $service->markDistributed($id);
        $service->cancel($id, 'Koreksi data penyaluran');

        foreach ([
            'distribution.created',
            'distribution.approved',
            'distribution.distributed',
            'distribution.cancelled',
        ] as $action) {
            $this->assertNotNull($this->latestAudit($action), 'Audit tidak ditemukan: ' . $action);
        }
    }

    public function testGoodsDistributionRequiresAtLeastOneItem(): void
    {
        $this->createGoodsDonation('Beras Premium', '10', 'kg');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Minimal satu barang');

        service('distribusiService')->create($this->goodsData(), []);
    }

    public function testBalanceRowLockBlocksConcurrentConnection(): void
    {
        $this->createMoneyDonation('1000000', 'verified');

        $dbA = $this->db;
        $dbB = \Config\Database::connect('tests', false);

        $dbA->transBegin();

        try {
            $dbA->query(
                'INSERT INTO distribution_balance_locks (balance_key, created_at, updated_at) '
                . "VALUES ('money', NOW(), NOW()) ON DUPLICATE KEY UPDATE updated_at = NOW()",
            );
            $dbA->query(
                "SELECT balance_key FROM distribution_balance_locks WHERE balance_key = 'money' FOR UPDATE",
            );

            $dbB->query('SET SESSION innodb_lock_wait_timeout = 1');

            $blocked = false;
            $message = '';

            try {
                $dbB->query(
                    'INSERT INTO distribution_balance_locks (balance_key, created_at, updated_at) '
                    . "VALUES ('money', NOW(), NOW()) ON DUPLICATE KEY UPDATE updated_at = NOW()",
                );
            } catch (\Throwable $exception) {
                $blocked = true;
                $message = $exception->getMessage();
            }

            $this->assertTrue($blocked, 'Koneksi kedua seharusnya menunggu kunci saldo yang sama.');
            $this->assertStringContainsStringIgnoringCase('lock wait timeout', $message);
        } finally {
            $dbA->transRollback();
            $dbB->close();
        }
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function moneyData(array $overrides = []): array
    {
        return array_merge([
            'distribution_type' => 'money',
            'distribution_date' => date('Y-m-d'),
            'recipient_name'    => 'Panti Asuhan Sejahtera',
            'recipient_type'    => 'organization',
            'recipient_address' => 'Jl. Contoh No. 1',
            'amount'            => '100000',
            'description'       => 'Bantuan biaya pendidikan',
            'notes'             => '',
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
            'distribution_type' => 'goods',
            'distribution_date' => date('Y-m-d'),
            'recipient_name'    => 'Posyandu Melati',
            'recipient_type'    => 'community',
            'recipient_address' => '',
            'amount'            => '',
            'description'       => 'Bantuan sembako',
            'notes'             => '',
        ], $overrides);
    }

    private function createMoneyDonation(string $amount, string $status): int
    {
        $this->sequence++;

        return (int) model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-DIST-' . str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'donor_id'           => $this->createDonor(),
            'is_anonymous'       => 0,
            'donation_type'      => 'money',
            'status'             => $status,
            'donation_date'      => date('Y-m-d'),
            'amount'             => $amount,
            'payment_method'     => 'cash',
            'notes'              => '',
            'created_by'         => $this->adminId(),
            'verified_by'        => $status === 'verified' ? $this->adminId() : null,
            'verified_at'        => $status === 'verified' ? date('Y-m-d H:i:s') : null,
            'cancelled_reason'   => $status === 'cancelled' ? 'Pembatalan uji' : null,
        ], true);
    }

    private function createGoodsDonation(string $name, string $quantity, string $unit): int
    {
        $this->sequence++;

        return (int) model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-DIST-' . str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'donor_id'           => $this->createDonor(),
            'is_anonymous'       => 0,
            'donation_type'      => 'goods',
            'status'             => 'verified',
            'donation_date'      => date('Y-m-d'),
            'goods_name'         => $name,
            'goods_quantity'     => $quantity,
            'goods_unit'         => $unit,
            'notes'              => '',
            'created_by'         => $this->adminId(),
            'verified_by'        => $this->adminId(),
            'verified_at'        => date('Y-m-d H:i:s'),
        ], true);
    }

    private function createDonor(): int
    {
        $this->sequence++;

        return (int) model(DonaturModel::class)->insert([
            'code'         => 'DNR-DIST-' . str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'name'         => 'Donatur Distribusi ' . $this->sequence,
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
