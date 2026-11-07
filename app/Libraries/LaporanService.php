<?php

namespace App\Libraries;

use App\Models\DetailDistribusiModel;
use App\Models\DistribusiModel;
use App\Models\DonasiModel;
use App\Models\DonaturModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

/**
 * Service untuk pelaporan donasi, distribusi, rekapitulasi, dan visualisasi grafik.
 * Seluruh kalkulasi saldo finansial konsisten dengan aturan bisnis DistribusiService.
 */
class LaporanService
{
    private BaseConnection $db;
    private DonasiModel $donasiModel;
    private DistribusiModel $distribusiModel;
    private DonaturModel $donaturModel;
    private DetailDistribusiModel $detailDistribusiModel;

    public function __construct(
        ?DonasiModel $donasiModel = null,
        ?DistribusiModel $distribusiModel = null,
        ?DonaturModel $donaturModel = null,
        ?DetailDistribusiModel $detailDistribusiModel = null,
        ?BaseConnection $db = null,
    ) {
        $this->db                    = $db ?? db_connect();
        $this->donasiModel           = $donasiModel ?? model(DonasiModel::class);
        $this->distribusiModel       = $distribusiModel ?? model(DistribusiModel::class);
        $this->donaturModel          = $donaturModel ?? model(DonaturModel::class);
        $this->detailDistribusiModel = $detailDistribusiModel ?? model(DetailDistribusiModel::class);
    }

    /**
     * Ringkasan saldo dan KPI utama yayasan (untuk Dashboard & Rekapitulasi).
     *
     * @return array<string, mixed>
     */
    public function getKpiSummary(): array
    {
        // 1. Total seluruh penerimaan donasi uang (verified)
        $moneyReceived = (float) ($this->db->table('donasi')
            ->select('COALESCE(SUM(amount), 0) AS total')
            ->where('donation_type', 'money')
            ->where('status', 'verified')
            ->where('deleted_at', null)
            ->get()->getRow()->total ?? 0);

        // 2. Total seluruh donasi uang yang didistribusikan (approved / distributed)
        $moneyDistributed = (float) ($this->db->table('distribusi')
            ->select('COALESCE(SUM(amount), 0) AS total')
            ->where('distribution_type', 'money')
            ->whereIn('status', ['approved', 'distributed'])
            ->where('deleted_at', null)
            ->get()->getRow()->total ?? 0);

        // 3. Saldo donasi uang yang belum didistribusikan
        $moneyBalance = $moneyReceived - $moneyDistributed;

        // 4. Jumlah donatur (total tidak terhapus & aktif)
        $totalDonatur = $this->db->table('donatur')
            ->where('deleted_at', null)
            ->countAllResults();

        $activeDonatur = $this->db->table('donatur')
            ->where('deleted_at', null)
            ->where('status', 'active')
            ->countAllResults();

        // 5. Jumlah transaksi penerimaan donasi
        $totalDonasiTx = $this->db->table('donasi')
            ->where('deleted_at', null)
            ->countAllResults();

        $verifiedDonasiTx = $this->db->table('donasi')
            ->where('deleted_at', null)
            ->where('status', 'verified')
            ->countAllResults();

        // 6. Jumlah transaksi distribusi donasi
        $totalDistribusiTx = $this->db->table('distribusi')
            ->where('deleted_at', null)
            ->countAllResults();

        $activeDistribusiTx = $this->db->table('distribusi')
            ->where('deleted_at', null)
            ->whereIn('status', ['approved', 'distributed'])
            ->countAllResults();

        // Rincian barang masuk & tersalurkan
        $goodsReceivedCount = (int) ($this->db->table('donasi')
            ->where('donation_type', 'goods')
            ->where('status', 'verified')
            ->where('deleted_at', null)
            ->countAllResults());

        $goodsDistributedCount = (int) ($this->db->table('distribusi')
            ->where('distribution_type', 'goods')
            ->whereIn('status', ['approved', 'distributed'])
            ->where('deleted_at', null)
            ->countAllResults());

        return [
            'total_received_money'     => $moneyReceived,
            'total_distributed_money'  => $moneyDistributed,
            'money_balance'            => $moneyBalance,
            'total_donors'             => $totalDonatur,
            'active_donors'            => $activeDonatur,
            'total_donation_count'     => $totalDonasiTx,
            'verified_donation_count'  => $verifiedDonasiTx,
            'total_distribution_count' => $totalDistribusiTx,
            'active_distribution_count'=> $activeDistribusiTx,
            'goods_received_count'     => $goodsReceivedCount,
            'goods_distributed_count'  => $goodsDistributedCount,
        ];
    }

    /**
     * Data grafik bulanan untuk Chart.js (12 bulan terakhir atau tahun tertentu).
     *
     * @param int|null $year
     * @return array<string, mixed>
     */
    public function getMonthlyChartData(?int $year = null): array
    {
        $year = $year ?? (int) date('Y');

        $labels        = [];
        $receipts      = [];
        $distributions = [];
        $netMonthly    = [];
        $cumulative    = [];

        $runningBalance = 0;

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        // Query penerimaan uang per bulan pada tahun target
        $receiptRows = $this->db->table('donasi')
            ->select('MONTH(donation_date) AS bln, SUM(amount) AS total')
            ->where('donation_type', 'money')
            ->where('status', 'verified')
            ->where('YEAR(donation_date)', $year)
            ->where('deleted_at', null)
            ->groupBy('MONTH(donation_date)')
            ->get()->getResultArray();

        $receiptsMap = [];
        foreach ($receiptRows as $r) {
            $receiptsMap[(int) $r['bln']] = (float) $r['total'];
        }

        // Query distribusi uang per bulan pada tahun target
        $distRows = $this->db->table('distribusi')
            ->select('MONTH(distribution_date) AS bln, SUM(amount) AS total')
            ->where('distribution_type', 'money')
            ->whereIn('status', ['approved', 'distributed'])
            ->where('YEAR(distribution_date)', $year)
            ->where('deleted_at', null)
            ->groupBy('MONTH(distribution_date)')
            ->get()->getResultArray();

        $distMap = [];
        foreach ($distRows as $d) {
            $distMap[(int) $d['bln']] = (float) $d['total'];
        }

        for ($m = 1; $m <= 12; $m++) {
            $in  = $receiptsMap[$m] ?? 0.0;
            $out = $distMap[$m] ?? 0.0;
            $net = $in - $out;
            $runningBalance += $net;

            $labels[]        = $monthNames[$m] . ' ' . $year;
            $receipts[]      = $in;
            $distributions[] = $out;
            $netMonthly[]    = $net;
            $cumulative[]    = $runningBalance;
        }

        return [
            'year'          => $year,
            'labels'        => $labels,
            'receipts'      => $receipts,
            'distributions' => $distributions,
            'net_monthly'   => $netMonthly,
            'cumulative'    => $cumulative,
            'total_receipts'=> array_sum($receipts),
            'total_dist'    => array_sum($distributions),
        ];
    }

    /**
     * Mengambil data laporan penerimaan donasi dengan filter, pencarian, dan paginasi.
     *
     * @param array<string, mixed> $filters
     * @param int                  $perPage
     * @return array<string, mixed>
     */
    public function getLaporanPenerimaan(array $filters = [], int $perPage = 20, bool $all = false): array
    {
        $builder = $this->buildPenerimaanQuery($filters);

        // Hitung total ringkasan untuk set yang terfilter
        $summaryBuilder = clone $builder;
        $summaryRows = $summaryBuilder->select('
            COUNT(donasi.id) AS total_rows,
            COALESCE(SUM(CASE WHEN donasi.donation_type = "money" THEN donasi.amount ELSE 0 END), 0) AS total_money,
            COALESCE(SUM(CASE WHEN donasi.donation_type = "money" AND donasi.status = "verified" THEN donasi.amount ELSE 0 END), 0) AS verified_money,
            COALESCE(SUM(CASE WHEN donasi.donation_type = "goods" THEN donasi.goods_quantity ELSE 0 END), 0) AS total_goods_qty
        ')->get()->getRowArray();

        // Ambil data detail
        $builder->select('
            donasi.*,
            donatur.name AS donor_name,
            donatur.code AS donor_code,
            donatur.phone AS donor_phone,
            donatur.email AS donor_email,
            users.full_name AS verified_by_name
        ')
        ->join('donatur', 'donatur.id = donasi.donor_id', 'left')
        ->join('users', 'users.id = donasi.verified_by', 'left')
        ->orderBy('donasi.donation_date', 'DESC')
        ->orderBy('donasi.id', 'DESC');

        if ($all) {
            $data  = $builder->get()->getResultArray();
            $pager = null;
        } else {
            $data  = $this->donasiModel->select('
                donasi.*,
                donatur.name AS donor_name,
                donatur.code AS donor_code,
                donatur.phone AS donor_phone,
                donatur.email AS donor_email,
                users.full_name AS verified_by_name
            ')
            ->join('donatur', 'donatur.id = donasi.donor_id', 'left')
            ->join('users', 'users.id = donasi.verified_by', 'left')
            ->orderBy('donasi.donation_date', 'DESC')
            ->orderBy('donasi.id', 'DESC');

            $this->applyPenerimaanFiltersToModel($this->donasiModel, $filters);
            $data  = $this->donasiModel->paginate($perPage, 'donasi');
            $pager = $this->donasiModel->pager;
        }

        return [
            'data'             => $data,
            'pager'            => $pager,
            'total_rows'       => (int) ($summaryRows['total_rows'] ?? 0),
            'total_money'      => (float) ($summaryRows['total_money'] ?? 0),
            'verified_money'   => (float) ($summaryRows['verified_money'] ?? 0),
            'total_goods_qty'  => (float) ($summaryRows['total_goods_qty'] ?? 0),
        ];
    }

    /**
     * Mengambil data laporan distribusi donasi dengan filter, pencarian, dan paginasi.
     *
     * @param array<string, mixed> $filters
     * @param int                  $perPage
     * @return array<string, mixed>
     */
    public function getLaporanDistribusi(array $filters = [], int $perPage = 20, bool $all = false): array
    {
        $builder = $this->buildDistribusiQuery($filters);

        // Hitung total ringkasan untuk set yang terfilter
        $summaryBuilder = clone $builder;
        $summaryRows = $summaryBuilder->select('
            COUNT(distribusi.id) AS total_rows,
            COALESCE(SUM(CASE WHEN distribusi.distribution_type = "money" THEN distribusi.amount ELSE 0 END), 0) AS total_money,
            COALESCE(SUM(CASE WHEN distribusi.distribution_type = "money" AND distribusi.status IN ("approved", "distributed") THEN distribusi.amount ELSE 0 END), 0) AS active_money,
            COALESCE(SUM(CASE WHEN distribusi.distribution_type = "goods" THEN 1 ELSE 0 END), 0) AS total_goods_trans
        ')->get()->getRowArray();

        // Ambil data detail
        $builder->select('
            distribusi.*,
            u_create.full_name AS creator_name,
            u_app.full_name AS approver_name
        ')
        ->join('users u_create', 'u_create.id = distribusi.created_by', 'left')
        ->join('users u_app', 'u_app.id = distribusi.approved_by', 'left')
        ->orderBy('distribusi.distribution_date', 'DESC')
        ->orderBy('distribusi.id', 'DESC');

        if ($all) {
            $data  = $builder->get()->getResultArray();
            $pager = null;
        } else {
            $this->applyDistribusiFiltersToModel($this->distribusiModel, $filters);
            $data  = $this->distribusiModel->select('
                distribusi.*,
                u_create.full_name AS creator_name,
                u_app.full_name AS approver_name
            ')
            ->join('users u_create', 'u_create.id = distribusi.created_by', 'left')
            ->join('users u_app', 'u_app.id = distribusi.approved_by', 'left')
            ->orderBy('distribusi.distribution_date', 'DESC')
            ->orderBy('distribusi.id', 'DESC')
            ->paginate($perPage, 'distribusi');

            $pager = $this->distribusiModel->pager;
        }

        // Ambil rincian barang untuk distribusi bertipe goods
        $distributionIds = array_column($data, 'id');
        $goodsItemsMap = [];

        if ($distributionIds !== []) {
            $items = $this->detailDistribusiModel
                ->whereIn('distribution_id', $distributionIds)
                ->findAll();

            foreach ($items as $item) {
                $goodsItemsMap[$item->distribution_id][] = $item;
            }
        }

        foreach ($data as &$row) {
            $id = is_array($row) ? $row['id'] : $row->id;
            if (is_array($row)) {
                $row['items'] = $goodsItemsMap[$id] ?? [];
            } else {
                $row->items = $goodsItemsMap[$id] ?? [];
            }
        }
        unset($row);

        return [
            'data'              => $data,
            'pager'             => $pager,
            'total_rows'        => (int) ($summaryRows['total_rows'] ?? 0),
            'total_money'       => (float) ($summaryRows['total_money'] ?? 0),
            'active_money'      => (float) ($summaryRows['active_money'] ?? 0),
            'total_goods_trans' => (int) ($summaryRows['total_goods_trans'] ?? 0),
        ];
    }

    /**
     * Laporan Rekapitulasi Komprehensif dalam rentang waktu tertentu.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getRekapitulasi(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate   = $filters['end_date'] ?? null;

        // Penerimaan dalam periode
        $recBuilder = $this->db->table('donasi')
            ->where('deleted_at', null);

        if (! empty($startDate)) {
            $recBuilder->where('donation_date >=', $startDate);
        }
        if (! empty($endDate)) {
            $recBuilder->where('donation_date <=', $endDate);
        }

        $recSummary = $recBuilder->select('
            COUNT(id) AS total_count,
            COALESCE(SUM(CASE WHEN status = "verified" THEN 1 ELSE 0 END), 0) AS verified_count,
            COALESCE(SUM(CASE WHEN donation_type = "money" AND status = "verified" THEN amount ELSE 0 END), 0) AS verified_money,
            COALESCE(SUM(CASE WHEN donation_type = "money" AND status = "pending" THEN amount ELSE 0 END), 0) AS pending_money,
            COALESCE(SUM(CASE WHEN donation_type = "money" AND status = "cancelled" THEN amount ELSE 0 END), 0) AS cancelled_money,
            COALESCE(SUM(CASE WHEN donation_type = "goods" AND status = "verified" THEN goods_quantity ELSE 0 END), 0) AS verified_goods_qty
        ')->get()->getRowArray();

        // Penyaluran dalam periode
        $distBuilder = $this->db->table('distribusi')
            ->where('deleted_at', null);

        if (! empty($startDate)) {
            $distBuilder->where('distribution_date >=', $startDate);
        }
        if (! empty($endDate)) {
            $distBuilder->where('distribution_date <=', $endDate);
        }

        $distSummary = $distBuilder->select('
            COUNT(id) AS total_count,
            COALESCE(SUM(CASE WHEN status IN ("approved", "distributed") THEN 1 ELSE 0 END), 0) AS active_count,
            COALESCE(SUM(CASE WHEN distribution_type = "money" AND status IN ("approved", "distributed") THEN amount ELSE 0 END), 0) AS active_money,
            COALESCE(SUM(CASE WHEN distribution_type = "money" AND status = "draft" THEN amount ELSE 0 END), 0) AS draft_money,
            COALESCE(SUM(CASE WHEN distribution_type = "money" AND status = "cancelled" THEN amount ELSE 0 END), 0) AS cancelled_money
        ')->get()->getRowArray();

        // Saldo global kumulatif yayasan (selalu dihitung dari seluruh data valid tak terhapus)
        $globalKpi = $this->getKpiSummary();

        // Saldo bersih khusus periode terpilih
        $periodMoneyIn  = (float) ($recSummary['verified_money'] ?? 0);
        $periodMoneyOut = (float) ($distSummary['active_money'] ?? 0);
        $periodNetMoney = $periodMoneyIn - $periodMoneyOut;

        // Donatur baru dalam periode
        $donaturBuilder = $this->db->table('donatur')->where('deleted_at', null);
        if (! empty($startDate)) {
            $donaturBuilder->where('created_at >=', $startDate . ' 00:00:00');
        }
        if (! empty($endDate)) {
            $donaturBuilder->where('created_at <=', $endDate . ' 23:59:59');
        }
        $newDonors = $donaturBuilder->countAllResults();

        // Breakdown bulanan dalam periode atau tahun berjalan
        $chartData = $this->getMonthlyChartData(
            ! empty($startDate) ? (int) date('Y', strtotime($startDate)) : (int) date('Y')
        );

        return [
            'filters'           => $filters,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'global_kpi'        => $globalKpi,
            'period_received'   => $periodMoneyIn,
            'period_distributed'=> $periodMoneyOut,
            'period_net'        => $periodNetMoney,
            'new_donors'        => $newDonors,
            'total_donors'      => $globalKpi['total_donors'],
            'receipt_summary'   => $recSummary,
            'dist_summary'      => $distSummary,
            'chart_data'        => $chartData,
        ];
    }

    /**
     * Opsi donatur untuk dropdown filter.
     *
     * @return array<int, array{id: int, name: string, code: string}>
     */
    public function getDonorOptions(): array
    {
        return $this->db->table('donatur')
            ->select('id, name, code')
            ->where('deleted_at', null)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Helper query builder penerimaan donasi.
     */
    private function buildPenerimaanQuery(array $filters): BaseBuilder
    {
        $builder = $this->db->table('donasi')
            ->where('donasi.deleted_at', null);

        if (! empty($filters['start_date'])) {
            $builder->where('donasi.donation_date >=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $builder->where('donasi.donation_date <=', $filters['end_date']);
        }
        if (! empty($filters['donor_id'])) {
            $builder->where('donasi.donor_id', $filters['donor_id']);
        }
        if (! empty($filters['payment_method'])) {
            $builder->where('donasi.payment_method', $filters['payment_method']);
        }
        if (! empty($filters['donation_type'])) {
            $builder->where('donasi.donation_type', $filters['donation_type']);
        }
        if (! empty($filters['status'])) {
            $builder->where('donasi.status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $builder->groupStart()
                ->like('donasi.transaction_number', $q)
                ->orLike('donasi.reference_number', $q)
                ->orLike('donasi.goods_name', $q)
                ->orLike('donasi.notes', $q)
                ->groupEnd();
        }

        return $builder;
    }

    /**
     * Helper query builder distribusi donasi.
     */
    private function buildDistribusiQuery(array $filters): BaseBuilder
    {
        $builder = $this->db->table('distribusi')
            ->where('distribusi.deleted_at', null);

        if (! empty($filters['start_date'])) {
            $builder->where('distribusi.distribution_date >=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $builder->where('distribusi.distribution_date <=', $filters['end_date']);
        }
        if (! empty($filters['recipient_type'])) {
            $builder->where('distribusi.recipient_type', $filters['recipient_type']);
        }
        if (! empty($filters['distribution_type'])) {
            $builder->where('distribusi.distribution_type', $filters['distribution_type']);
        }
        if (! empty($filters['status'])) {
            $builder->where('distribusi.status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $builder->groupStart()
                ->like('distribusi.distribution_number', $q)
                ->orLike('distribusi.recipient_name', $q)
                ->orLike('distribusi.description', $q)
                ->orLike('distribusi.notes', $q)
                ->groupEnd();
        }

        return $builder;
    }

    private function applyPenerimaanFiltersToModel(DonasiModel $model, array $filters): void
    {
        if (! empty($filters['start_date'])) {
            $model->where('donasi.donation_date >=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $model->where('donasi.donation_date <=', $filters['end_date']);
        }
        if (! empty($filters['donor_id'])) {
            $model->where('donasi.donor_id', $filters['donor_id']);
        }
        if (! empty($filters['payment_method'])) {
            $model->where('donasi.payment_method', $filters['payment_method']);
        }
        if (! empty($filters['donation_type'])) {
            $model->where('donasi.donation_type', $filters['donation_type']);
        }
        if (! empty($filters['status'])) {
            $model->where('donasi.status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $model->groupStart()
                ->like('donasi.transaction_number', $q)
                ->orLike('donasi.reference_number', $q)
                ->orLike('donasi.goods_name', $q)
                ->orLike('donasi.notes', $q)
                ->groupEnd();
        }
    }

    private function applyDistribusiFiltersToModel(DistribusiModel $model, array $filters): void
    {
        if (! empty($filters['start_date'])) {
            $model->where('distribusi.distribution_date >=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $model->where('distribusi.distribution_date <=', $filters['end_date']);
        }
        if (! empty($filters['recipient_type'])) {
            $model->where('distribusi.recipient_type', $filters['recipient_type']);
        }
        if (! empty($filters['distribution_type'])) {
            $model->where('distribusi.distribution_type', $filters['distribution_type']);
        }
        if (! empty($filters['status'])) {
            $model->where('distribusi.status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $model->groupStart()
                ->like('distribusi.distribution_number', $q)
                ->orLike('distribusi.recipient_name', $q)
                ->orLike('distribusi.description', $q)
                ->orLike('distribusi.notes', $q)
                ->groupEnd();
        }
    }
}
