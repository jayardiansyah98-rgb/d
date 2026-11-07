<?php
$this->extend('layouts/main');
?>

<?php $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Laporan Rekapitulasi Donasi</h1>
        <p class="text-muted mb-0">Ringkasan kondisi finansial, saldo kas, penerimaan, dan penyaluran donasi yayasan.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url_to('laporan.rekapitulasi.print') . '?' . http_build_query($filters) ?>" target="_blank" class="btn btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Cetak Laporan
        </a>
        <a href="<?= url_to('laporan.rekapitulasi.excel') . '?' . http_build_query($filters) ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel
        </a>
    </div>
</div>

<!-- Filter Periode Rekapitulasi -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= url_to('laporan.rekapitulasi') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="start_date" class="form-label small fw-semibold">Tanggal Awal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= esc($filters['start_date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label small fw-semibold">Tanggal Akhir</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= esc($filters['end_date'] ?? '') ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i>Filter Periode
                </button>
                <a href="<?= url_to('laporan.rekapitulasi') ?>" class="btn btn-outline-secondary">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- 4 KARTU SALDO & REKAP UTAMA -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 bg-primary-subtle border-start border-primary border-4">
            <div class="card-body">
                <span class="text-primary-emphasis small fw-bold text-uppercase">Penerimaan (Periode)</span>
                <h4 class="fw-bold text-primary mt-2 mb-1">
                    Rp <?= number_format($rekap['period_received'], 2, ',', '.') ?>
                </h4>
                <div class="text-muted small">
                    <?= esc((string) $rekap['receipt_summary']['verified_count']) ?> transaksi terverifikasi
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 bg-danger-subtle border-start border-danger border-4">
            <div class="card-body">
                <span class="text-danger-emphasis small fw-bold text-uppercase">Penyaluran (Periode)</span>
                <h4 class="fw-bold text-danger mt-2 mb-1">
                    Rp <?= number_format($rekap['period_distributed'], 2, ',', '.') ?>
                </h4>
                <div class="text-muted small">
                    <?= esc((string) $rekap['dist_summary']['active_count']) ?> penyaluran aktif
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 bg-info-subtle border-start border-info border-4">
            <div class="card-body">
                <span class="text-info-emphasis small fw-bold text-uppercase">Surplus / Defisit Periode</span>
                <h4 class="fw-bold <?= $rekap['period_net'] >= 0 ? 'text-success' : 'text-danger' ?> mt-2 mb-1">
                    Rp <?= number_format($rekap['period_net'], 2, ',', '.') ?>
                </h4>
                <div class="text-muted small">
                    Selisih penerimaan vs penyaluran
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 bg-success-subtle border-start border-success border-4">
            <div class="card-body">
                <span class="text-success-emphasis small fw-bold text-uppercase">Saldo Kas Kumulatif</span>
                <h4 class="fw-bold text-success mt-2 mb-1">
                    Rp <?= number_format($rekap['global_kpi']['money_balance'], 2, ',', '.') ?>
                </h4>
                <div class="text-muted small">
                    Saldo kas riil siap pakai
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TABEL REKAPITULASI DETAIL & BULANAN -->
<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h2 class="h6 fw-bold mb-0">Rincian Posisi Finansial Yayasan</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Komponen Keuangan</th>
                            <th class="text-end">Nilai (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Penerimaan Donasi Uang (Sah & Terverifikasi)</td>
                            <td class="text-end fw-semibold text-success">Rp <?= number_format($rekap['global_kpi']['total_received_money'], 2, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Total Donasi Uang yang Telah Disalurkan</td>
                            <td class="text-end fw-semibold text-danger">Rp <?= number_format($rekap['global_kpi']['total_distributed_money'], 2, ',', '.') ?></td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold">Saldo Akhir Donasi Uang Tersedia</td>
                            <td class="text-end fw-bold text-success">Rp <?= number_format($rekap['global_kpi']['money_balance'], 2, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Total Donasi Pending (Menunggu Verifikasi Bank)</td>
                            <td class="text-end text-warning">Rp <?= number_format((float) ($rekap['receipt_summary']['pending_money'] ?? 0), 2, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Total Donasi Dibatalkan</td>
                            <td class="text-end text-muted">Rp <?= number_format((float) ($rekap['receipt_summary']['cancelled_money'] ?? 0), 2, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Total Rencana Distribusi (Status Draf)</td>
                            <td class="text-end text-secondary">Rp <?= number_format((float) ($rekap['dist_summary']['draft_money'] ?? 0), 2, ',', '.') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h2 class="h6 fw-bold mb-0">Ringkasan Donatur & Operasional</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Parameter Operasional</th>
                            <th class="text-end">Kuantitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Jumlah Donatur Terdaftar</td>
                            <td class="text-end fw-semibold"><?= esc((string) $rekap['global_kpi']['total_donors']) ?> Donatur</td>
                        </tr>
                        <tr>
                            <td>Jumlah Donatur Aktif</td>
                            <td class="text-end fw-semibold text-success"><?= esc((string) $rekap['global_kpi']['active_donors']) ?> Donatur</td>
                        </tr>
                        <tr>
                            <td>Donatur Baru Terdaftar Pada Periode Ini</td>
                            <td class="text-end fw-semibold text-primary"><?= esc((string) $rekap['new_donors']) ?> Donatur</td>
                        </tr>
                        <tr>
                            <td>Total Transaksi Donasi Diterima</td>
                            <td class="text-end fw-semibold"><?= esc((string) $rekap['global_kpi']['total_donation_count']) ?> Transaksi</td>
                        </tr>
                        <tr>
                            <td>Total Penyaluran Donasi Dilaksanakan</td>
                            <td class="text-end fw-semibold text-danger"><?= esc((string) $rekap['global_kpi']['active_distribution_count']) ?> Penyaluran</td>
                        </tr>
                        <tr>
                            <td>Donasi Barang Masuk Terverifikasi</td>
                            <td class="text-end fw-semibold"><?= esc((string) $rekap['global_kpi']['goods_received_count']) ?> Transaksi</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- TABEL REKAP BULANAN -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h2 class="h6 fw-bold mb-0">Tabel Arus Kas Bulanan (Tahun <?= esc((string) $rekap['chart_data']['year']) ?>)</h2>
        <span class="text-muted small">Rincian pemasukan, penyaluran, dan saldo kumulatif per bulan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Bulan</th>
                    <th class="text-end">Penerimaan (Rp)</th>
                    <th class="text-end">Penyaluran (Rp)</th>
                    <th class="text-end">Selisih Bersih (Rp)</th>
                    <th class="text-end">Saldo Berjalan (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rekap['chart_data']['labels'] as $idx => $label) : ?>
                    <?php
                    $rec = $rekap['chart_data']['receipts'][$idx];
                    $dis = $rekap['chart_data']['distributions'][$idx];
                    $net = $rekap['chart_data']['net_monthly'][$idx];
                    $cum = $rekap['chart_data']['cumulative'][$idx];
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($label) ?></td>
                        <td class="text-end text-success">Rp <?= number_format($rec, 2, ',', '.') ?></td>
                        <td class="text-end text-danger">Rp <?= number_format($dis, 2, ',', '.') ?></td>
                        <td class="text-end <?= $net >= 0 ? 'text-success fw-semibold' : 'text-danger fw-semibold' ?>">
                            <?= $net >= 0 ? '+' : '' ?>Rp <?= number_format($net, 2, ',', '.') ?>
                        </td>
                        <td class="text-end fw-bold text-dark">Rp <?= number_format($cum, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td>Total Tahun <?= esc((string) $rekap['chart_data']['year']) ?></td>
                    <td class="text-end text-success">Rp <?= number_format(array_sum($rekap['chart_data']['receipts']), 2, ',', '.') ?></td>
                    <td class="text-end text-danger">Rp <?= number_format(array_sum($rekap['chart_data']['distributions']), 2, ',', '.') ?></td>
                    <td class="text-end <?= (array_sum($rekap['chart_data']['receipts']) - array_sum($rekap['chart_data']['distributions'])) >= 0 ? 'text-success' : 'text-danger' ?>">
                        Rp <?= number_format(array_sum($rekap['chart_data']['receipts']) - array_sum($rekap['chart_data']['distributions']), 2, ',', '.') ?>
                    </td>
                    <td class="text-end text-primary">Rp <?= number_format(end($rekap['chart_data']['cumulative']), 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
