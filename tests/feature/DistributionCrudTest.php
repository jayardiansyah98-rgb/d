<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\DetailDistribusiModel;
use App\Models\DistribusiModel;
use App\Models\DonasiModel;
use App\Models\DonaturModel;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Pengujian HTTP modul distribusi: CRUD, alur status, filter, bukti
 * penyaluran, RBAC, CSRF, dan escaping output.
 *
 * @internal
 */
final class DistributionCrudTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

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
    }

    public function testAdministratorCreatesMoneyDistributionThroughForm(): void
    {
        $this->createMoneyDonation('1000000');

        $result = $this->asUser('admin')->post('distribusi', $this->moneyPayload([
            'amount' => '250000',
            'notes'  => 'Diserahkan langsung kepada pengurus panti.',
        ]));

        $result->assertRedirect();
        $result->assertSessionHas('success');
        $this->assertStringStartsWith(site_url('distribusi/'), (string) $result->getRedirectUrl());

        $distribution = model(DistribusiModel::class)->orderBy('id', 'DESC')->first();

        $this->assertNotNull($distribution);
        $this->assertMatchesRegularExpression('/^DSR-\d{8}-\d{4}$/', $distribution->distribution_number);
        $this->assertSame(DistribusiModel::STATUS_DRAFT, $distribution->status);
        $this->assertSame(DistribusiModel::TYPE_MONEY, $distribution->distribution_type);
        $this->assertSame('250000.00', (string) $distribution->amount);
        $this->assertSame($this->userId('admin'), (int) $distribution->created_by);

        $this->assertNotNull($this->latestAudit('distribution.created'));
    }

    public function testGoodsDistributionStoresItemsAndCanBeApproved(): void
    {
        $this->createGoodsDonation('Beras Premium', '20', 'kg');

        $result = $this->asUser('admin')->post('distribusi', $this->goodsPayload([
            'items' => [
                ['item_name' => 'Beras Premium', 'quantity' => '5', 'unit' => 'kg'],
            ],
        ]));

        $result->assertRedirect();
        $result->assertSessionHas('success');

        $distribution = model(DistribusiModel::class)->orderBy('id', 'DESC')->first();

        $this->assertNotNull($distribution);
        $this->assertSame('goods', $distribution->distribution_type);
        $this->assertNull($distribution->amount);

        $items = model(DetailDistribusiModel::class)
            ->where('distribution_id', $distribution->id)
            ->findAll();

        $this->assertCount(1, $items);
        $this->assertSame('Beras Premium', $items[0]->item_name);
        $this->assertSame('5.00', (string) $items[0]->quantity);
        $this->assertSame('kg', $items[0]->unit);

        $approve = $this->asUser('admin')->post('distribusi/' . $distribution->id . '/approve', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $approve->assertRedirectTo(site_url('distribusi/' . $distribution->id));

        $this->assertSame(
            DistribusiModel::STATUS_APPROVED,
            model(DistribusiModel::class)->find($distribution->id)->status,
        );
        $this->assertSame(
            1500,
            service('distribusiService')->goodsBalance('Beras Premium', 'kg')['available_cents'],
        );
    }

    public function testApprovalWithoutSufficientBalanceShowsError(): void
    {
        $create = $this->asUser('admin')->post('distribusi', $this->moneyPayload(['amount' => '250000']));
        $create->assertRedirect();

        $distribution = model(DistribusiModel::class)->orderBy('id', 'DESC')->first();
        $this->assertNotNull($distribution);

        $result = $this->asUser('admin')->post('distribusi/' . $distribution->id . '/approve', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $result->assertRedirectTo(site_url('distribusi/' . $distribution->id));
        $result->assertSessionHas('error');

        $this->assertSame(
            DistribusiModel::STATUS_DRAFT,
            model(DistribusiModel::class)->find($distribution->id)->status,
        );
    }

    public function testFullLifecycleApproveAndDistribute(): void
    {
        $this->createMoneyDonation('1000000');

        $this->asUser('admin')->post('distribusi', $this->moneyPayload(['amount' => '400000']))->assertRedirect();

        $distribution = model(DistribusiModel::class)->orderBy('id', 'DESC')->first();
        $this->assertNotNull($distribution);

        $this->asUser('admin')->post('distribusi/' . $distribution->id . '/approve', [
            'csrf_token' => $this->csrfToken(),
        ])->assertRedirectTo(site_url('distribusi/' . $distribution->id));

        $this->assertSame(60000000, service('distribusiService')->moneyBalance()['available_cents']);

        $this->asUser('admin')->post('distribusi/' . $distribution->id . '/distribute', [
            'csrf_token' => $this->csrfToken(),
        ])->assertRedirectTo(site_url('distribusi/' . $distribution->id));

        $updated = model(DistribusiModel::class)->find($distribution->id);

        $this->assertSame(DistribusiModel::STATUS_DISTRIBUTED, $updated->status);
        $this->assertNotNull($updated->distributed_at);
        $this->assertSame(60000000, service('distribusiService')->moneyBalance()['available_cents']);
        $this->assertNotNull($this->latestAudit('distribution.distributed'));
    }

    public function testCancelReleasesBalanceAndKeepsRecord(): void
    {
        $this->createMoneyDonation('1000000');

        $this->asUser('admin')->post('distribusi', $this->moneyPayload(['amount' => '400000']))->assertRedirect();

        $distribution = model(DistribusiModel::class)->orderBy('id', 'DESC')->first();
        $this->assertNotNull($distribution);

        $this->asUser('admin')->post('distribusi/' . $distribution->id . '/approve', [
            'csrf_token' => $this->csrfToken(),
        ])->assertRedirect();

        $result = $this->asUser('admin')->post('distribusi/' . $distribution->id . '/cancel', [
            'csrf_token'       => $this->csrfToken(),
            'cancelled_reason' => 'Salah penerima manfaat',
        ]);

        $result->assertRedirectTo(site_url('distribusi/' . $distribution->id));
        $result->assertSessionHas('success');

        $cancelled = model(DistribusiModel::class)->find($distribution->id);

        $this->assertNotNull($cancelled);
        $this->assertSame(DistribusiModel::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame('400000.00', (string) $cancelled->amount);
        $this->assertSame('Salah penerima manfaat', $cancelled->cancelled_reason);
        $this->assertSame(100000000, service('distribusiService')->moneyBalance()['available_cents']);
        $this->assertNotNull($this->latestAudit('distribution.cancelled'));
    }

    public function testCancelRequiresReasonLength(): void
    {
        $id = $this->createDistribution();

        $result = $this->asUser('admin')->post('distribusi/' . $id . '/cancel', [
            'csrf_token'       => $this->csrfToken(),
            'cancelled_reason' => 'x',
        ]);

        $result->assertRedirectTo(site_url('distribusi/' . $id));
        $result->assertSessionHas('errors');
        $this->assertSame(DistribusiModel::STATUS_DRAFT, model(DistribusiModel::class)->find($id)->status);
    }

    public function testEditAndUpdateAreLimitedToDraft(): void
    {
        $approvedId = $this->createDistribution(['status' => DistribusiModel::STATUS_APPROVED]);
        $draftId    = $this->createDistribution();

        $this->asUser('admin')->get('distribusi/' . $draftId . '/edit')->assertOK();

        $blockedEdit = $this->asUser('admin')->get('distribusi/' . $approvedId . '/edit');
        $blockedEdit->assertRedirectTo(site_url('distribusi/' . $approvedId));
        $blockedEdit->assertSessionHas('error');

        $blockedUpdate = $this->asUser('admin')->post('distribusi/' . $approvedId, $this->moneyPayload([
            'amount' => '999000',
        ]));

        $blockedUpdate->assertRedirectTo(site_url('distribusi/' . $approvedId));

        $this->assertSame('100000.00', (string) model(DistribusiModel::class)->find($approvedId)->amount);
    }

    public function testFiltersByTypePeriodAndSearch(): void
    {
        $january = $this->createDistribution([
            'distribution_number' => 'DSR-TEST-0001',
            'distribution_type'   => 'money',
            'distribution_date'   => '2026-01-10',
            'amount'              => '100000.00',
            'recipient_name'      => 'Panti Asuhan Alpha',
        ]);

        $february = $this->createDistribution([
            'distribution_number' => 'DSR-TEST-0002',
            'distribution_type'   => 'goods',
            'distribution_date'   => '2026-02-10',
            'amount'              => null,
            'recipient_name'      => 'Posyandu Beta',
        ]);

        $march = $this->createDistribution([
            'distribution_number' => 'DSR-TEST-0003',
            'distribution_type'   => 'money',
            'distribution_date'   => '2026-03-10',
            'amount'              => '300000.00',
            'recipient_name'      => 'Yayasan Gamma',
        ]);

        $byType = $this->asUser('admin')->get('distribusi?type=goods');
        $byType->assertOK();
        $byType->assertSee('DSR-TEST-0002');
        $byType->assertDontSee('DSR-TEST-0001');
        $byType->assertDontSee('DSR-TEST-0003');

        $byPeriod = $this->asUser('admin')->get('distribusi?date_from=2026-03-01&date_to=2026-03-31');
        $byPeriod->assertOK();
        $byPeriod->assertSee('DSR-TEST-0003');
        $byPeriod->assertDontSee('DSR-TEST-0001');

        $bySearch = $this->asUser('admin')->get('distribusi?search=Gamma');
        $bySearch->assertOK();
        $bySearch->assertSee('DSR-TEST-0003');
        $bySearch->assertDontSee('DSR-TEST-0002');

        $this->assertSame(3, model(DistribusiModel::class)->countAllResults());
        $this->assertNotSame($january, $march);
        $this->assertNotSame($february, $march);
    }

    public function testReceiptAndDetailShowCorrectStatusPerState(): void
    {
        $draft       = $this->createDistribution(['status' => 'draft']);
        $approved    = $this->createDistribution(['status' => 'approved']);
        $distributed = $this->createDistribution(['status' => 'distributed']);
        $cancelled   = $this->createDistribution([
            'status'           => 'cancelled',
            'cancelled_reason' => 'Penerima tidak memenuhi kriteria',
        ]);

        $cancelledReceipt = $this->asUser('admin')->get('distribusi/' . $cancelled . '/receipt');

        $this->asUser('admin')->get('distribusi/' . $draft . '/receipt')->assertSee('DRAF');
        $this->asUser('admin')->get('distribusi/' . $approved . '/receipt')->assertSee('DISETUJUI');
        $this->asUser('admin')->get('distribusi/' . $distributed . '/receipt')->assertSee('TERSALURKAN');

        $cancelledReceipt->assertOK();
        $cancelledReceipt->assertSee('DIBATALKAN');
        $cancelledReceipt->assertSee('Penerima tidak memenuhi kriteria');

        $show = $this->asUser('admin')->get('distribusi/' . $distributed);
        $show->assertOK();
        $show->assertSee('Detail Distribusi');
    }

    public function testGoodsReceiptListsItems(): void
    {
        $id = $this->createDistribution([
            'distribution_type' => 'goods',
            'amount'            => null,
            'status'            => 'distributed',
        ]);

        model(DetailDistribusiModel::class)->insert([
            'distribution_id' => $id,
            'item_name'       => 'Selimut Bayi',
            'quantity'        => '12',
            'unit'            => 'pcs',
        ]);

        $result = $this->asUser('admin')->get('distribusi/' . $id . '/receipt');

        $result->assertOK();
        $result->assertSee('Selimut Bayi');
        $result->assertSee('pcs');
    }

    public function testRecipientTextIsEscaped(): void
    {
        $payload = '<script>alert("xss")</script>';

        $id = $this->createDistribution([
            'recipient_name' => $payload,
            'description'    => $payload,
        ]);

        $show = $this->asUser('admin')->get('distribusi/' . $id);

        $show->assertOK();

        // DOMParser menormalkan entitas HTML, jadi escaping diperiksa pada
        // body mentah agar terbukti tag tidak dikirim sebagai markup aktif.
        $body = (string) $show->response()->getBody();

        $this->assertStringContainsString(esc($payload), $body);
        $this->assertStringNotContainsString($payload, $body);
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->withSession([])->get('distribusi')->assertRedirectTo(site_url('login'));
        $this->withSession([])->get('distribusi/new')->assertRedirectTo(site_url('login'));
        $this->withSession([])->get('distribusi/1/receipt')->assertRedirectTo(site_url('login'));
    }

    public function testKeuanganCannotAccessDistributionModule(): void
    {
        $this->asUser('pengurus')->get('distribusi')->assertRedirectTo(site_url('profile'));
        $this->asUser('pengurus')->get('distribusi/new')->assertRedirectTo(site_url('profile'));
        $this->asUser('pengurus')->post('distribusi', $this->moneyPayload())->assertRedirectTo(site_url('profile'));
    }

    public function testPimpinanCanViewButNotManage(): void
    {
        $id = $this->createDistribution();

        $this->asUser('auditor')->get('distribusi')->assertOK();
        $this->asUser('auditor')->get('distribusi/' . $id)->assertOK();
        $this->asUser('auditor')->get('distribusi/' . $id . '/receipt')->assertOK();

        $this->asUser('auditor')->get('distribusi/new')->assertRedirectTo(site_url('profile'));
        $this->asUser('auditor')->get('distribusi/' . $id . '/edit')->assertRedirectTo(site_url('profile'));

        $this->asUser('auditor')->post('distribusi/' . $id . '/approve', [
            'csrf_token' => $this->csrfToken(),
        ])->assertRedirectTo(site_url('profile'));

        $this->assertSame(DistribusiModel::STATUS_DRAFT, model(DistribusiModel::class)->find($id)->status);
    }

    public function testDistribusiRoleCanManage(): void
    {
        $this->createMoneyDonation('500000');

        $this->asUser('petugas')->get('distribusi')->assertOK();
        $this->asUser('petugas')->get('distribusi/new')->assertOK();

        $create = $this->asUser('petugas')->post('distribusi', $this->moneyPayload(['amount' => '100000']));
        $create->assertRedirect();
        $create->assertSessionHas('success');

        $distribution = model(DistribusiModel::class)->orderBy('id', 'DESC')->first();
        $this->assertNotNull($distribution);

        $this->asUser('petugas')->post('distribusi/' . $distribution->id . '/approve', [
            'csrf_token' => $this->csrfToken(),
        ])->assertRedirectTo(site_url('distribusi/' . $distribution->id));

        $this->assertSame(
            DistribusiModel::STATUS_APPROVED,
            model(DistribusiModel::class)->find($distribution->id)->status,
        );
    }

    public function testCsrfTokenIsRequiredForWrites(): void
    {
        $this->expectException(SecurityException::class);

        $this->asUser('admin')->post('distribusi', [
            'distribution_type' => 'money',
            'distribution_date' => date('Y-m-d'),
            'recipient_name'    => 'Tanpa CSRF',
            'recipient_type'    => 'individual',
            'amount'            => '100000',
            'description'       => 'Uji CSRF',
        ]);
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

    private function userId(string $username): int
    {
        $row = $this->db->table('users')->select('id')->where('username', $username)->get()->getRow();

        $this->assertNotNull($row, 'Pengguna uji tidak ditemukan: ' . $username);

        return (int) $row->id;
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function createDistribution(array $overrides = []): int
    {
        $this->sequence++;

        $isGoods = ($overrides['distribution_type'] ?? 'money') === 'goods';

        $data = array_merge([
            'distribution_number' => 'DSR-TEST-' . str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'distribution_type'   => 'money',
            'status'              => 'draft',
            'distribution_date'   => date('Y-m-d'),
            'recipient_name'      => 'Penerima Uji ' . $this->sequence,
            'recipient_type'      => 'individual',
            'recipient_address'   => 'Jl. Uji No. 1',
            'amount'              => '100000.00',
            'description'         => 'Tujuan penyaluran uji',
            'notes'               => '',
            'created_by'          => $this->userId('admin'),
        ], $overrides);

        if ($isGoods) {
            $data['amount'] = null;
        }

        return (int) model(DistribusiModel::class)->insert($data, true);
    }

    private function createMoneyDonation(string $amount): int
    {
        $this->sequence++;

        return (int) model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-DIST-' . str_pad((string) $this->sequence, 4, '0', STR_PAD_LEFT),
            'donor_id'           => $this->createDonor(),
            'is_anonymous'       => 0,
            'donation_type'      => 'money',
            'status'             => 'verified',
            'donation_date'      => date('Y-m-d'),
            'amount'             => $amount,
            'payment_method'     => 'cash',
            'notes'              => '',
            'created_by'         => $this->userId('admin'),
            'verified_by'        => $this->userId('admin'),
            'verified_at'        => date('Y-m-d H:i:s'),
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
            'created_by'         => $this->userId('admin'),
            'verified_by'        => $this->userId('admin'),
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

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function moneyPayload(array $overrides = []): array
    {
        return array_merge([
            'csrf_token'        => $this->csrfToken(),
            'distribution_type' => 'money',
            'distribution_date' => date('Y-m-d'),
            'recipient_name'    => 'Panti Asuhan Harapan',
            'recipient_type'    => 'organization',
            'recipient_address' => 'Jl. Melati No. 2',
            'amount'            => '250000',
            'description'       => 'Bantuan biaya pendidikan',
            'notes'             => '',
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function goodsPayload(array $overrides = []): array
    {
        return array_merge([
            'csrf_token'        => $this->csrfToken(),
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

    private function latestAudit(string $action): ?object
    {
        return $this->db->table('audit_logs')
            ->where('action', $action)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();
    }
}
