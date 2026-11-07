<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\DonasiModel;
use App\Models\DonaturModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class DonasiCrudTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';
    protected $DBGroup   = 'tests';
    protected $seed      = DatabaseSeeder::class;

    private int $donorSequence = 0;
    private int $donationSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetServices();
        $this->mockSession();
    }

    public function testAdministratorCanCreateMoneyDonationThroughForm(): void
    {
        $donorId = $this->createDonor('Donatur Uang');

        $result = $this->asUser('admin')->post('donasi', $this->moneyPayload([
            'donor_id' => (string) $donorId,
            'amount'   => '1250000',
            'notes'    => 'Transfer dari rekening yayasan.',
        ]));

        $result->assertRedirect();
        $this->assertStringStartsWith(site_url('donasi/'), (string) $result->getRedirectUrl());
        $result->assertSessionHas('success');

        $donation = model(DonasiModel::class)->orderBy('id', 'DESC')->first();

        $this->assertNotNull($donation);
        $this->assertMatchesRegularExpression('/^DON-\d{8}-\d{4}$/', $donation->transaction_number);
        $this->assertSame('money', $donation->donation_type);
        $this->assertSame('pending', $donation->status);
        $this->assertSame('1250000.00', (string) $donation->amount);
        $this->assertSame($donorId, (int) $donation->donor_id);
        $this->assertSame($this->userId('admin'), (int) $donation->created_by);

        $audit = $this->latestAudit('donation.created');
        $this->assertNotNull($audit);
        $this->assertSame((string) $donation->id, (string) $audit->entity_id);
        $this->assertSame($this->userId('admin'), (int) $audit->user_id);
    }

    public function testAdministratorCanCreateGoodsDonationThroughForm(): void
    {
        $donorId = $this->createDonor('Donatur Barang');

        $result = $this->asUser('admin')->post('donasi', $this->goodsPayload([
            'donor_id' => (string) $donorId,
        ]));

        $result->assertRedirect();
        $result->assertSessionHas('success');

        $donation = model(DonasiModel::class)->orderBy('id', 'DESC')->first();

        $this->assertNotNull($donation);
        $this->assertSame('goods', $donation->donation_type);
        $this->assertNull($donation->amount);
        $this->assertSame('Sembako paket', $donation->goods_name);
        $this->assertSame('12.00', (string) $donation->goods_quantity);
        $this->assertSame('dus', $donation->goods_unit);
    }

    public function testAnonymousDonationCanBeCreatedWithoutDonor(): void
    {
        $result = $this->asUser('pengurus')->post('donasi', $this->moneyPayload([
            'donor_id'     => '',
            'is_anonymous' => '1',
            'payment_method' => 'cash',
            'reference_number' => '',
        ]));

        $result->assertRedirect();
        $result->assertSessionHas('success');

        $donation = model(DonasiModel::class)->orderBy('id', 'DESC')->first();

        $this->assertNotNull($donation);
        $this->assertNull($donation->donor_id);
        $this->assertSame(1, (int) $donation->is_anonymous);
    }

    public function testMoneyDonationWithoutAmountShowsValidationErrors(): void
    {
        $donorId = $this->createDonor('Donatur Tanpa Nominal');

        $result = $this->asUser('admin')->post('donasi', $this->moneyPayload([
            'donor_id' => (string) $donorId,
            'amount'   => '',
        ]));

        $result->assertRedirectTo(site_url('donasi/new'));
        $result->assertSessionHas('errors');
        $this->assertSame(0, model(DonasiModel::class)->countAllResults());
    }

    public function testZeroOrNegativeAmountIsRejected(): void
    {
        $donorId = $this->createDonor('Donatur Nominal Tidak Sah');

        foreach (['0', '-1500'] as $amount) {
            $result = $this->asUser('admin')->post('donasi', $this->moneyPayload([
                'donor_id' => (string) $donorId,
                'amount'   => $amount,
            ]));

            $result->assertRedirectTo(site_url('donasi/new'));
            $result->assertSessionHas('errors');
        }

        $this->assertSame(0, model(DonasiModel::class)->countAllResults());
    }

    public function testBankTransferWithoutReferenceShowsValidationErrors(): void
    {
        $donorId = $this->createDonor('Donatur Transfer');

        $result = $this->asUser('admin')->post('donasi', $this->moneyPayload([
            'donor_id'         => (string) $donorId,
            'reference_number' => '',
        ]));

        $result->assertRedirectTo(site_url('donasi/new'));
        $result->assertSessionHas('errors');
        $this->assertSame(0, model(DonasiModel::class)->countAllResults());
    }

    public function testFutureDonationDateShowsValidationErrors(): void
    {
        $donorId = $this->createDonor('Donatur Tanggal');

        $result = $this->asUser('admin')->post('donasi', $this->moneyPayload([
            'donor_id'      => (string) $donorId,
            'donation_date' => date('Y-m-d', strtotime('+1 day')),
        ]));

        $result->assertRedirectTo(site_url('donasi/new'));
        $result->assertSessionHas('errors');
        $this->assertSame(0, model(DonasiModel::class)->countAllResults());
    }

    public function testAdministratorCanUpdatePendingDonation(): void
    {
        $donorId    = $this->createDonor('Donatur Edit');
        $donationId = $this->createDonation([
            'donor_id' => $donorId,
        ]);

        $result = $this->asUser('admin')->post('donasi/' . $donationId, $this->moneyPayload([
            'donor_id' => (string) $donorId,
            'amount'   => '1750000',
            'notes'    => 'Nominal diperbarui.',
        ]));

        $result->assertRedirectTo(site_url('donasi/' . $donationId));
        $result->assertSessionHas('success');

        $donation = model(DonasiModel::class)->find($donationId);

        $this->assertSame('1750000.00', (string) $donation->amount);
        $this->assertSame('Nominal diperbarui.', $donation->notes);
        $this->assertSame('pending', $donation->status);
        $this->assertNotNull($this->latestAudit('donation.updated'));
    }

    public function testVerifiedDonationCannotBeEdited(): void
    {
        $donationId = $this->createDonation([
            'donor_id' => $this->createDonor('Donatur Terverifikasi'),
            'status'   => 'verified',
            'amount'   => '900000.00',
        ]);

        $result = $this->asUser('admin')->post('donasi/' . $donationId, $this->moneyPayload([
            'amount' => '100000',
        ]));

        $result->assertRedirectTo(site_url('donasi/' . $donationId));
        $result->assertSessionHas('error');

        $this->assertSame('900000.00', (string) model(DonasiModel::class)->find($donationId)->amount);

        $edit = $this->asUser('admin')->get('donasi/' . $donationId . '/edit');
        $edit->assertRedirectTo(site_url('donasi/' . $donationId));
    }

    public function testAdministratorCanVerifyPendingDonation(): void
    {
        $donationId = $this->createDonation([
            'donor_id' => $this->createDonor('Donatur Verifikasi'),
        ]);

        $result = $this->asUser('admin')->post('donasi/' . $donationId . '/verify', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $result->assertRedirectTo(site_url('donasi/' . $donationId));
        $result->assertSessionHas('success');

        $donation = model(DonasiModel::class)->find($donationId);

        $this->assertSame('verified', $donation->status);
        $this->assertSame($this->userId('admin'), (int) $donation->verified_by);
        $this->assertNotNull($donation->verified_at);
        $this->assertNotNull($this->latestAudit('donation.verified'));
    }

    public function testAdministratorCanCancelDonationWithReason(): void
    {
        $donationId = $this->createDonation([
            'donor_id' => $this->createDonor('Donatur Batal'),
            'status'   => 'verified',
        ]);

        $result = $this->asUser('admin')->post('donasi/' . $donationId . '/cancel', [
            'csrf_token'       => $this->csrfToken(),
            'cancelled_reason' => 'Salah input nominal donasi.',
        ]);

        $result->assertRedirectTo(site_url('donasi/' . $donationId));
        $result->assertSessionHas('success');

        $donation = model(DonasiModel::class)->find($donationId);

        $this->assertSame('cancelled', $donation->status);
        $this->assertSame('Salah input nominal donasi.', $donation->cancelled_reason);
        $this->assertNotNull($this->latestAudit('donation.cancelled'));
    }

    public function testCancelWithShortReasonIsRejected(): void
    {
        $donationId = $this->createDonation([
            'donor_id' => $this->createDonor('Donatur Batal Salah'),
        ]);

        $result = $this->asUser('admin')->post('donasi/' . $donationId . '/cancel', [
            'csrf_token'       => $this->csrfToken(),
            'cancelled_reason' => 'sala',
        ]);

        $result->assertRedirectTo(site_url('donasi/' . $donationId));
        $result->assertSessionHas('errors');
        $this->assertSame('pending', model(DonasiModel::class)->find($donationId)->status);
    }

    public function testAuditorCanViewDonationListButCannotManage(): void
    {
        $donationId = $this->createDonation([
            'donor_id' => $this->createDonor('Donatur Audit'),
        ]);

        $list = $this->asUser('auditor')->get('donasi');

        $list->assertOK();
        $list->assertSee('Penerimaan Donasi');
        $list->assertDontSee('Catat Donasi');

        $create = $this->asUser('auditor')->post('donasi', $this->moneyPayload([
            'donor_id' => (string) $this->createDonor('Donatur Ilegal'),
        ]));

        $create->assertRedirectTo(site_url('profile'));
        $create->assertSessionHas('error');
        $this->assertSame(1, model(DonasiModel::class)->countAllResults());

        $verify = $this->asUser('auditor')->post('donasi/' . $donationId . '/verify', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $verify->assertRedirectTo(site_url('profile'));
        $this->assertSame('pending', model(DonasiModel::class)->find($donationId)->status);
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        $result = $this->withSession([])->get('donasi');

        $result->assertRedirectTo(site_url('login'));
    }

    public function testDonationDetailShowsInformationAndActions(): void
    {
        $donorId    = $this->createDonor('Hamba Allah Berkah');
        $donationId = $this->createDonation(['donor_id' => $donorId]);

        $result = $this->asUser('admin')->get('donasi/' . $donationId);

        $result->assertOK();
        $result->assertSee('DON-TEST-');
        $result->assertSee('Hamba Allah Berkah');
        $result->assertSee('Menunggu verifikasi');
        $result->assertSee('Verifikasi');
        $result->assertSee('Batalkan Donasi');
    }

    public function testAnonymousDonationDetailHidesDonorName(): void
    {
        $donorId    = $this->createDonor('Nama Donatur Rahasia');
        $donationId = $this->createDonation([
            'donor_id'     => $donorId,
            'is_anonymous' => 1,
        ]);

        $result = $this->asUser('admin')->get('donasi/' . $donationId);

        $result->assertOK();
        $result->assertSee('Donasi anonim');
        $result->assertDontSee('Nama Donatur Rahasia');
    }

    public function testDonationListCanBeFilteredByPeriodTypeAndStatus(): void
    {
        $from    = date('Y-m-d', strtotime('-20 days'));
        $to      = date('Y-m-d', strtotime('-5 days'));
        $inside  = date('Y-m-d', strtotime('-10 days'));
        $outside = date('Y-m-d', strtotime('-30 days'));

        $this->createDonation([
            'donor_id'      => $this->createDonor('Donatur Filter Uang'),
            'donation_type' => 'money',
            'status'        => 'verified',
            'donation_date' => $inside,
            'amount'        => '150000.00',
        ]);
        $this->createDonation([
            'donor_id'      => $this->createDonor('Donatur Filter Barang'),
            'donation_type' => 'goods',
            'status'        => 'verified',
            'donation_date' => $inside,
        ]);
        $this->createDonation([
            'donor_id'      => $this->createDonor('Donatur Filter Lama'),
            'donation_type' => 'money',
            'status'        => 'verified',
            'donation_date' => $outside,
            'amount'        => '250000.00',
        ]);
        $this->createDonation([
            'donor_id'      => $this->createDonor('Donatur Filter Pending'),
            'donation_type' => 'money',
            'status'        => 'pending',
            'donation_date' => $inside,
            'amount'        => '350000.00',
        ]);

        $result = $this->asUser('admin')->get(
            'donasi?type=money&status=verified&date_from=' . $from . '&date_to=' . $to
        );

        $result->assertOK();
        $result->assertSee('Donatur Filter Uang');
        $result->assertDontSee('Donatur Filter Barang');
        $result->assertDontSee('Donatur Filter Lama');
        $result->assertDontSee('Donatur Filter Pending');
    }

    public function testActiveSummaryExcludesCancelledDonations(): void
    {
        $today = date('Y-m-d');

        $this->createDonation([
            'donor_id'      => $this->createDonor('Donatur Ringkasan Pending'),
            'donation_type' => 'money',
            'status'        => 'pending',
            'donation_date' => $today,
            'amount'        => '100000.00',
        ]);
        $this->createDonation([
            'donor_id'      => $this->createDonor('Donatur Ringkasan Verifikasi'),
            'donation_type' => 'money',
            'status'        => 'verified',
            'donation_date' => $today,
            'amount'        => '200000.00',
            'verified_by'   => $this->userId('admin'),
            'verified_at'   => date('Y-m-d H:i:s'),
        ]);
        $this->createDonation([
            'donor_id'      => $this->createDonor('Donatur Ringkasan Batal'),
            'donation_type' => 'money',
            'status'        => 'cancelled',
            'donation_date' => $today,
            'amount'        => '300000.00',
            'cancelled_reason' => 'Pembatalan untuk pengujian ringkasan.',
        ]);
        $this->createDonation([
            'donor_id'      => $this->createDonor('Donatur Ringkasan Barang'),
            'donation_type' => 'goods',
            'status'        => 'verified',
            'donation_date' => $today,
        ]);

        $body = $this->asUser('admin')->get('donasi')->getBody();

        $this->assertMatchesRegularExpression('/id="summary-total"[^>]*>\s*4\s*</', $body);
        $this->assertMatchesRegularExpression('/id="summary-active"[^>]*>\s*3\s*</', $body);
        $this->assertMatchesRegularExpression('/id="summary-cancelled"[^>]*>\s*1\s*</', $body);
        $this->assertMatchesRegularExpression('/id="summary-active-money"[^>]*>.*?Rp 300\.000,00/s', $body);
        $this->assertMatchesRegularExpression('/id="summary-verified-money"[^>]*>.*?Rp 200\.000,00/s', $body);
    }

    public function testVerifiedDonationReceiptShowsVerificationAndCanBePrinted(): void
    {
        $donorId = $this->createDonor('Donatur Bukti Terverifikasi');
        $donationId = $this->createDonation([
            'donor_id'      => $donorId,
            'status'        => 'verified',
            'donation_date' => date('Y-m-d'),
            'amount'        => '750000.00',
            'notes'         => 'Transfer sesuai bukti bank.',
            'verified_by'   => $this->userId('admin'),
            'verified_at'   => date('Y-m-d H:i:s'),
        ]);

        $result = $this->asUser('admin')->get('donasi/' . $donationId . '/receipt');

        $result->assertOK();
        $result->assertSee('BUKTI PENERIMAAN');
        $result->assertSee('DON-TEST-');
        $result->assertSee('Donatur Bukti Terverifikasi');
        $result->assertSee('TERVERIFIKASI');
        $result->assertSee('Rp 750.000,00');
        $result->assertSee('Transfer sesuai bukti bank.');
        $result->assertSee('Administrator Yayasan');
        $result->assertSee('Cetak');
    }

    public function testPendingDonationReceiptDoesNotClaimVerification(): void
    {
        $donationId = $this->createDonation([
            'donor_id' => $this->createDonor('Donatur Bukti Pending'),
            'status'   => 'pending',
        ]);

        $result = $this->asUser('admin')->get('donasi/' . $donationId . '/receipt');

        $result->assertOK();
        $result->assertSee('MENUNGGU VERIFIKASI');
        $result->assertSee('belum merupakan bukti pembayaran yang sah');
        $result->assertSee('Belum diverifikasi');
        $result->assertDontSee('TERVERIFIKASI');
    }

    public function testCancelledDonationReceiptIsMarkedAsNotValid(): void
    {
        $donationId = $this->createDonation([
            'donor_id'         => $this->createDonor('Donatur Bukti Batal'),
            'status'           => 'cancelled',
            'cancelled_reason' => 'Salah input nominal donasi.',
        ]);

        $result = $this->asUser('admin')->get('donasi/' . $donationId . '/receipt');

        $result->assertOK();
        $result->assertSee('DIBATALKAN');
        $result->assertSee('Alasan pembatalan');
        $result->assertSee('Salah input nominal donasi.');
        $result->assertSee('tidak boleh dihitung sebagai penerimaan aktif');
    }

    public function testAnonymousDonationReceiptHidesDonorIdentity(): void
    {
        $donorId    = $this->createDonor('Nama Donatur Rahasia');
        $donationId = $this->createDonation([
            'donor_id'     => $donorId,
            'is_anonymous' => 1,
        ]);

        $result = $this->asUser('auditor')->get('donasi/' . $donationId . '/receipt');

        $result->assertOK();
        $result->assertSee('Donasi anonim');
        $result->assertDontSee('Nama Donatur Rahasia');
    }

    public function testReceiptEscapesDynamicOutput(): void
    {
        $payload    = '<script>alert(1)</script>';
        $donationId = $this->createDonation([
            'donor_id' => $this->createDonor($payload),
            'notes'    => $payload,
        ]);

        $result = $this->asUser('admin')->get('donasi/' . $donationId . '/receipt');

        $result->assertOK();
        $result->assertSee(esc($payload));
        $result->assertDontSee($payload);
    }

    public function testGuestCannotOpenReceipt(): void
    {
        $donationId = $this->createDonation([
            'donor_id' => $this->createDonor('Donatur Bukti Guest'),
        ]);

        $result = $this->withSession([])->get('donasi/' . $donationId . '/receipt');

        $result->assertRedirectTo(site_url('login'));
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

    private function createDonor(string $name): int
    {
        $this->donorSequence++;

        return (int) model(DonaturModel::class)->insert([
            'code'         => 'DNR-TEST-C' . str_pad((string) $this->donorSequence, 4, '0', STR_PAD_LEFT),
            'name'         => $name,
            'type'         => 'individual',
            'phone'        => '081200000000',
            'is_anonymous' => 0,
            'status'       => 'active',
        ], true);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createDonation(array $overrides = []): int
    {
        $this->donationSequence++;

        $isGoods = ($overrides['donation_type'] ?? 'money') === 'goods';

        $data = array_merge([
            'transaction_number' => 'DON-TEST-' . str_pad((string) $this->donationSequence, 4, '0', STR_PAD_LEFT),
            'donor_id'           => $this->createDonor('Donatur Default'),
            'is_anonymous'       => 0,
            'donation_type'      => 'money',
            'status'             => 'pending',
            'donation_date'      => date('Y-m-d'),
            'amount'             => '500000.00',
            'payment_method'     => 'cash',
            'reference_number'   => null,
            'goods_name'         => null,
            'goods_quantity'     => null,
            'goods_unit'         => null,
            'estimated_value'    => null,
            'notes'              => '',
            'created_by'         => $this->userId('admin'),
        ], $overrides);

        if ($isGoods) {
            $data['amount']          = null;
            $data['payment_method']  = null;
            $data['goods_name']      = $data['goods_name'] ?? 'Sembako paket';
            $data['goods_quantity']  = $data['goods_quantity'] ?? '5.00';
            $data['goods_unit']      = $data['goods_unit'] ?? 'dus';
        }

        return (int) model(DonasiModel::class)->insert($data, true);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function moneyPayload(array $overrides = []): array
    {
        return array_merge([
            'csrf_token'       => $this->csrfToken(),
            'donation_type'    => 'money',
            'donor_id'         => '',
            'is_anonymous'     => '0',
            'donation_date'    => date('Y-m-d'),
            'amount'           => '1250000',
            'payment_method'   => 'bank_transfer',
            'reference_number' => 'TRX-FORM-001',
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
    private function goodsPayload(array $overrides = []): array
    {
        return array_merge([
            'csrf_token'       => $this->csrfToken(),
            'donation_type'    => 'goods',
            'donor_id'         => '',
            'is_anonymous'     => '0',
            'donation_date'    => date('Y-m-d'),
            'amount'           => '',
            'payment_method'   => '',
            'reference_number' => '',
            'goods_name'       => 'Sembako paket',
            'goods_quantity'   => '12',
            'goods_unit'       => 'dus',
            'estimated_value'  => '2400000',
            'notes'            => '',
        ], $overrides);
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
