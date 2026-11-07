<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\DetailDistribusiModel;
use App\Models\DonasiModel;
use App\Models\DonaturModel;
use App\Models\DistribusiModel;
use App\Models\ProgramDonasiModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class DonationSchemaTest extends CIUnitTestCase
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
    }

    public function testStageTwoTablesExist(): void
    {
        foreach (['program_donasi', 'donatur', 'donasi', 'distribusi', 'detail_distribusi'] as $table) {
            $this->assertTrue($this->db->tableExists($table), 'Tabel ' . $table . ' tidak ditemukan.');
        }
    }

    public function testMoneyDonationStoresDecimalAndAllowsAnonymousWithoutDonor(): void
    {
        $programId = $this->createProgram();

        $id = (int) model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261028-0001',
            'donor_id'           => null,
            'is_anonymous'       => 1,
            'program_id'         => $programId,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_VERIFIED,
            'donation_date'      => '2026-10-28',
            'amount'             => '2500000.75',
            'payment_method'     => DonasiModel::PAYMENT_BANK_TRANSFER,
            'created_by'         => $this->adminId(),
        ], true);

        $row = model(DonasiModel::class)->find($id);

        $this->assertNotNull($row);
        $this->assertSame('2500000.75', (string) $row->amount);
        $this->assertSame('1', (string) $row->is_anonymous);
        $this->assertNull($row->donor_id);

        $errors = model(DonasiModel::class)->businessRuleErrors((array) $row);
        $this->assertSame([], $errors);
    }

    public function testGoodsDonationStoresQuantityUnitAndEstimatedValue(): void
    {
        $donorId = $this->createDonor();

        $id = (int) model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261028-0002',
            'donor_id'           => $donorId,
            'is_anonymous'       => 0,
            'donation_type'      => DonasiModel::TYPE_GOODS,
            'status'             => DonasiModel::STATUS_PENDING,
            'donation_date'      => '2026-10-28',
            'goods_name'         => 'Beras premium',
            'goods_quantity'     => '12.50',
            'goods_unit'         => 'karung',
            'estimated_value'    => '750000.00',
            'created_by'         => $this->adminId(),
        ], true);

        $row = model(DonasiModel::class)->find($id);

        $this->assertNotNull($row);
        $this->assertSame('Beras premium', $row->goods_name);
        $this->assertSame('12.50', (string) $row->goods_quantity);
        $this->assertSame('karung', $row->goods_unit);
        $this->assertNull($row->amount);
    }

    public function testGoodsDonationCannotCarryMoneyAmount(): void
    {
        $donorId = $this->createDonor();

        $this->expectException(DatabaseException::class);

        model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261028-0003',
            'donor_id'           => $donorId,
            'is_anonymous'       => 0,
            'donation_type'      => DonasiModel::TYPE_GOODS,
            'status'             => DonasiModel::STATUS_PENDING,
            'donation_date'      => '2026-10-28',
            'amount'             => '100000.00',
            'goods_name'         => 'Selimut',
            'goods_quantity'     => '5.00',
            'goods_unit'         => 'pcs',
        ]);
    }

    public function testNonAnonymousDonationRequiresDonor(): void
    {
        $this->expectException(DatabaseException::class);

        model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261028-0004',
            'donor_id'           => null,
            'is_anonymous'       => 0,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_PENDING,
            'donation_date'      => '2026-10-28',
            'amount'             => '100000.00',
        ]);
    }

    public function testForeignKeysRejectUnknownDonor(): void
    {
        $this->expectException(DatabaseException::class);

        model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261028-0005',
            'donor_id'           => 999999,
            'is_anonymous'       => 0,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_PENDING,
            'donation_date'      => '2026-10-28',
            'amount'             => '100000.00',
        ]);
    }

    public function testTransactionNumberIsUnique(): void
    {
        $donorId = $this->createDonor();
        $data = [
            'transaction_number' => 'DON-20261028-0006',
            'donor_id'           => $donorId,
            'is_anonymous'       => 0,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_PENDING,
            'donation_date'      => '2026-10-28',
            'amount'             => '100000.00',
        ];

        model(DonasiModel::class)->insert($data);

        $this->expectException(DatabaseException::class);
        model(DonasiModel::class)->insert($data);
    }

    public function testDistributionNumberIsUnique(): void
    {
        $data = [
            'distribution_number' => 'DSR-20261028-0001',
            'distribution_type'   => DistribusiModel::TYPE_GOODS,
            'status'              => DistribusiModel::STATUS_DRAFT,
            'distribution_date'   => '2026-10-28',
            'recipient_name'      => 'Panti Asuhan Harapan',
            'recipient_type'      => DistribusiModel::RECIPIENT_ORGANIZATION,
        ];

        model(DistribusiModel::class)->insert($data);

        $this->expectException(DatabaseException::class);
        model(DistribusiModel::class)->insert($data);
    }

    public function testGoodsDistributionDetailsCascadeOnHardDelete(): void
    {
        $distributionId = (int) model(DistribusiModel::class)->insert([
            'distribution_number' => 'DSR-20261028-0002',
            'distribution_type'   => DistribusiModel::TYPE_GOODS,
            'status'              => DistribusiModel::STATUS_APPROVED,
            'distribution_date'   => '2026-10-28',
            'recipient_name'      => 'Posyandu Melati',
            'recipient_type'      => DistribusiModel::RECIPIENT_COMMUNITY,
            'created_by'          => $this->adminId(),
        ], true);

        model(DetailDistribusiModel::class)->insert([
            'distribution_id' => $distributionId,
            'item_name'       => 'Susu bubuk',
            'quantity'        => '24.00',
            'unit'            => 'kotak',
            'unit_value'      => '45000.00',
        ]);

        $this->assertCount(1, model(DistribusiModel::class)->details($distributionId));

        // Hard delete untuk menguji ON DELETE CASCADE pada detail.
        $this->db->table('distribusi')->where('id', $distributionId)->delete();

        $this->assertSame(
            0,
            $this->db->table('detail_distribusi')->where('distribution_id', $distributionId)->countAllResults()
        );
    }

    public function testSoftDeleteDonaturKeepsDonationHistory(): void
    {
        $donorId = $this->createDonor();

        $donationId = (int) model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261028-0007',
            'donor_id'           => $donorId,
            'is_anonymous'       => 0,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_PENDING,
            'donation_date'      => '2026-10-28',
            'amount'             => '500000.00',
        ], true);

        model(DonaturModel::class)->delete($donorId);

        $this->assertNull(model(DonaturModel::class)->find($donorId));
        $this->assertNotNull(model(DonaturModel::class)->withDeleted()->find($donorId));

        $donation = model(DonasiModel::class)->find($donationId);
        $this->assertNotNull($donation);
        $this->assertSame($donorId, (int) $donation->donor_id);
    }

    public function testDonorWithDonationsCannotBeHardDeleted(): void
    {
        $donorId = $this->createDonor();

        model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261028-0010',
            'donor_id'           => $donorId,
            'is_anonymous'       => 0,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_PENDING,
            'donation_date'      => '2026-10-28',
            'amount'             => '500000.00',
        ]);

        $this->expectException(DatabaseException::class);

        $this->db->table('donatur')->where('id', $donorId)->delete();
    }

    public function testUniqueValidationIgnoresCurrentRowOnUpdate(): void
    {
        $donorId = $this->createDonor();

        $id = (int) model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261028-0008',
            'donor_id'           => $donorId,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_PENDING,
            'donation_date'      => '2026-10-28',
            'amount'             => '125000.00',
        ], true);

        $model = model(DonasiModel::class);

        $this->assertTrue($model->validateForSave([
            'transaction_number' => 'DON-20261028-0008',
            'donor_id'           => $donorId,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_VERIFIED,
            'donation_date'      => '2026-10-28',
            'amount'             => '125000.00',
        ], $id));

        $this->assertFalse($model->validateForSave([
            'transaction_number' => 'DON-20261028-0008',
            'donor_id'           => $donorId,
            'donation_type'      => DonasiModel::TYPE_MONEY,
            'status'             => DonasiModel::STATUS_PENDING,
            'donation_date'      => '2026-10-28',
            'amount'             => '125000.00',
        ]));
    }

    private function createProgram(): int
    {
        return (int) model(ProgramDonasiModel::class)->insert([
            'code'          => 'PRG-PENDIDIKAN',
            'name'          => 'Beasiswa Anak Yatim',
            'target_amount' => '50000000.00',
            'start_date'    => '2026-01-01',
            'end_date'      => '2026-12-31',
            'status'        => ProgramDonasiModel::STATUS_ACTIVE,
            'created_by'    => $this->adminId(),
        ], true);
    }

    private function createDonor(): int
    {
        return (int) model(DonaturModel::class)->insert([
            'code'       => 'DNR-20261028-0001',
            'name'       => 'Hamba Allah',
            'type'       => DonaturModel::TYPE_INDIVIDUAL,
            'phone'      => '081200000001',
            'status'     => DonaturModel::STATUS_ACTIVE,
            'created_by' => $this->adminId(),
        ], true);
    }

    private function adminId(): int
    {
        return (int) $this->db->table('users')
            ->select('id')
            ->where('username', 'admin')
            ->get()
            ->getRow()
            ->id;
    }
}
