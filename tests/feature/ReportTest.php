<?php

namespace Tests\Feature;

use App\Models\DistribusiModel;
use App\Models\DonasiModel;
use App\Models\DonaturModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ReportTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';
    protected $DBGroup   = 'tests';
    protected $seed      = \App\Database\Seeds\DatabaseSeeder::class;

    private function asUser(string $username): self
    {
        $this->resetServices();
        $this->mockSession();

        $row = $this->db->table('users')->select('id')->where('username', $username)->get()->getRow();
        $this->assertNotNull($row, 'User not found: ' . $username);

        return $this->withSession(['auth_user_id' => (int) $row->id]);
    }

    public function testGuestRedirectedToLogin(): void
    {
        $this->get('laporan')->assertRedirectTo('/login');
        $this->get('laporan/penerimaan')->assertRedirectTo('/login');
        $this->get('laporan/distribusi')->assertRedirectTo('/login');
        $this->get('laporan/rekapitulasi')->assertRedirectTo('/login');
    }

    public function testPetugasCannotAccessReports(): void
    {
        // Petugas (Distribusi) tidak memiliki reports.view
        $this->asUser('petugas')->get('laporan')->assertRedirectTo('/profile');
        $this->asUser('petugas')->get('laporan/penerimaan')->assertRedirectTo('/profile');
        $this->asUser('petugas')->get('laporan/distribusi')->assertRedirectTo('/profile');
        $this->asUser('petugas')->get('laporan/rekapitulasi')->assertRedirectTo('/profile');
    }

    public function testAdminCanAccessAllReportPagesAndExports(): void
    {
        $r1 = $this->asUser('admin')->get('laporan/rekapitulasi');
        $r1->assertOK();
        $r1->assertSee('Laporan Rekapitulasi Donasi');

        $r2 = $this->asUser('admin')->get('laporan/rekapitulasi/print');
        $r2->assertOK();
        $r2->assertSee('Laporan Rekapitulasi Pengelolaan Donasi');

        $excelRekap = $this->asUser('admin')->get('laporan/rekapitulasi/excel');
        $excelRekap->assertOK();
        $this->assertStringContainsString('application/vnd.ms-excel', $excelRekap->response()->getHeaderLine('Content-Type'));

        $r3 = $this->asUser('admin')->get('laporan/penerimaan');
        $r3->assertOK();
        $r3->assertSee('Laporan Penerimaan Donasi');

        $r4 = $this->asUser('admin')->get('laporan/penerimaan/print');
        $r4->assertOK();
        $r4->assertSee('Laporan Transaksi Penerimaan Donasi');

        $excelPenerimaan = $this->asUser('admin')->get('laporan/penerimaan/excel');
        $excelPenerimaan->assertOK();
        $this->assertStringContainsString('application/vnd.ms-excel', $excelPenerimaan->response()->getHeaderLine('Content-Type'));

        $r5 = $this->asUser('admin')->get('laporan/distribusi');
        $r5->assertOK();
        $r5->assertSee('Laporan Distribusi Donasi');

        $r6 = $this->asUser('admin')->get('laporan/distribusi/print');
        $r6->assertOK();
        $r6->assertSee('Laporan Penyaluran / Distribusi Donasi');

        $excelDistribusi = $this->asUser('admin')->get('laporan/distribusi/excel');
        $excelDistribusi->assertOK();
        $this->assertStringContainsString('application/vnd.ms-excel', $excelDistribusi->response()->getHeaderLine('Content-Type'));
    }

    public function testPengurusAndAuditorCanAccessReports(): void
    {
        // Keuangan (pengurus)
        $this->asUser('pengurus')->get('laporan/penerimaan')->assertOK();
        $this->asUser('pengurus')->get('laporan/rekapitulasi')->assertOK();

        // Pimpinan (auditor)
        $this->asUser('auditor')->get('laporan/penerimaan')->assertOK();
        $this->asUser('auditor')->get('laporan/distribusi')->assertOK();
        $this->asUser('auditor')->get('laporan/rekapitulasi')->assertOK();
    }

    public function testDashboardRendersKpiAndCharts(): void
    {
        $result = $this->asUser('admin')->get('dashboard');
        $result->assertOK();
        $result->assertSee('Total Penerimaan Uang');
        $result->assertSee('Total Donasi Tersalurkan');
        $result->assertSee('Saldo Donasi Tersedia');
        $result->assertSee('Jumlah Seluruh Donatur');
        $result->assertSee('chartComparison');
        $result->assertSee('chartRatio');
        $result->assertSee('chartTrend');
    }

    public function testReportCalculationWithRealTransactions(): void
    {
        $db = db_connect();

        // 1. Buat donatur
        $donaturId = model(DonaturModel::class)->insert([
            'code'       => 'DNR-20261101-0001',
            'name'       => 'Hamba Berkah',
            'type'       => 'individual',
            'status'     => 'active',
            'created_by' => 1,
        ]);

        // 2. Buat donasi uang verified (10.000.000)
        model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261101-0001',
            'donor_id'           => $donaturId,
            'is_anonymous'       => 0,
            'donation_type'      => 'money',
            'amount'             => 10000000.00,
            'payment_method'     => 'bank_transfer',
            'reference_number'   => 'TRX-123456',
            'status'             => 'verified',
            'donation_date'      => date('Y-m-d'),
            'created_by'         => 1,
            'verified_by'        => 1,
            'verified_at'        => date('Y-m-d H:i:s'),
        ]);

        // 3. Buat donasi pending (2.000.000) - tidak boleh masuk saldo
        model(DonasiModel::class)->insert([
            'transaction_number' => 'DON-20261101-0002',
            'donor_id'           => $donaturId,
            'is_anonymous'       => 0,
            'donation_type'      => 'money',
            'amount'             => 2000000.00,
            'payment_method'     => 'cash',
            'status'             => 'pending',
            'donation_date'      => date('Y-m-d'),
            'created_by'         => 1,
        ]);

        // 4. Buat distribusi uang approved (4.000.000)
        model(DistribusiModel::class)->insert([
            'distribution_number' => 'DSR-20261101-0001',
            'recipient_name'      => 'Panti Asuhan Kasih',
            'recipient_type'      => 'organization',
            'distribution_type'   => 'money',
            'amount'              => 4000000.00,
            'description'         => 'Bantuan operasional',
            'status'              => 'approved',
            'distribution_date'   => date('Y-m-d'),
            'created_by'          => 1,
            'approved_by'         => 1,
            'approved_at'         => date('Y-m-d H:i:s'),
        ]);

        // Saldo kas seharusnya: 10.000.000 - 4.000.000 = 6.000.000
        $kpi = service('laporanService')->getKpiSummary();
        $this->assertEquals(10000000.00, $kpi['total_received_money']);
        $this->assertEquals(4000000.00, $kpi['total_distributed_money']);
        $this->assertEquals(6000000.00, $kpi['money_balance']);

        // Uji halaman rekapitulasi memuat angka-angka ini
        $res = $this->asUser('admin')->get('laporan/rekapitulasi');
        $res->assertOK();
        $res->assertSee('10.000.000,00');
        $res->assertSee('4.000.000,00');
        $res->assertSee('6.000.000,00');

        // Uji filter laporan penerimaan
        $penerimaanRes = $this->asUser('admin')->get('laporan/penerimaan?status=verified');
        $penerimaanRes->assertOK();
        $penerimaanRes->assertSee('DON-20261101-0001');
        $penerimaanRes->assertDontSee('DON-20261101-0002');

        // Uji filter laporan distribusi
        $distRes = $this->asUser('admin')->get('laporan/distribusi?status=approved');
        $distRes->assertOK();
        $distRes->assertSee('DSR-20261101-0001');
        $distRes->assertSee('Panti Asuhan Kasih');
    }
}
