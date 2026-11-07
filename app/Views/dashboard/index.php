<?php
$this->extend('layouts/main');

$totalAccounts = $activeUsers + $inactiveUsers;
?>

<?php $this->section('content') ?>
<!-- Header Sambutan & Status Finansial Global -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Dashboard Pengelolaan Donasi</h1>
        <p class="text-muted mb-0">Selamat datang, <strong><?= esc($me?->full_name) ?></strong> (<?= esc($me?->role_name) ?>). Pantau seluruh data penerimaan, penyaluran, dan saldo donasi terkini.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canViewReports) : ?>
            <a href="<?= url_to('laporan.rekapitulasi') ?>" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-bar-graph me-1"></i>Rekapitulasi
            </a>
        <?php endif ?>
        <?php if (can('donations.manage')) : ?>
            <a href="<?= url_to('donasi.create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Catat Donasi
            </a>
        <?php endif ?>
        <?php if (can('distributions.manage')) : ?>
            <a href="<?= url_to('distribusi.create') ?>" class="btn btn-success">
                <i class="bi bi-send-plus me-1"></i>Catat Penyaluran
            </a>
        <?php endif ?>
    </div>
</div>

<!-- 6 KPI CARDS UTAMA -->
<div class="row g-3 mb-4">
    <!-- 1. Total Seluruh Penerimaan Donasi -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="stat-label text-muted fw-semibold">Total Penerimaan Uang</span>
                    <div class="stat-icon text-bg-primary rounded-3 p-2"><i class="bi bi-cash-stack fs-5"></i></div>
                </div>
                <h3 class="stat-value fw-bold text-primary mb-1">
                    Rp <?= number_format($kpi['total_received_money'], 2, ',', '.') ?>
                </h3>
                <div class="stat-meta text-muted small">
                    <i class="bi bi-check2-circle text-success me-1"></i><?= esc((string) $kpi['verified_donation_count']) ?> transaksi terverifikasi
                    <?php if ($kpi['goods_received_count'] > 0) : ?>
                        · <?= esc((string) $kpi['goods_received_count']) ?> donasi barang
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Total Donasi yang Sudah Didistribusikan -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="stat-label text-muted fw-semibold">Total Donasi Tersalurkan</span>
                    <div class="stat-icon text-bg-danger rounded-3 p-2"><i class="bi bi-send-check-fill fs-5"></i></div>
                </div>
                <h3 class="stat-value fw-bold text-danger mb-1">
                    Rp <?= number_format($kpi['total_distributed_money'], 2, ',', '.') ?>
                </h3>
                <div class="stat-meta text-muted small">
                    <i class="bi bi-arrow-up-right-circle text-danger me-1"></i><?= esc((string) $kpi['active_distribution_count']) ?> transaksi disetujui / tersalur
                    <?php if ($kpi['goods_distributed_count'] > 0) : ?>
                        · <?= esc((string) $kpi['goods_distributed_count']) ?> distribusi barang
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Saldo Donasi yang Belum Didistribusikan -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm h-100 bg-success-subtle border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="stat-label text-success-emphasis fw-bold">Saldo Donasi Tersedia</span>
                    <div class="stat-icon text-bg-success rounded-3 p-2"><i class="bi bi-wallet2 fs-5"></i></div>
                </div>
                <h3 class="stat-value fw-bold text-success mb-1">
                    Rp <?= number_format($kpi['money_balance'], 2, ',', '.') ?>
                </h3>
                <div class="stat-meta text-success-emphasis small">
                    <i class="bi bi-shield-check me-1"></i>Dana siap dialokasikan untuk program yayasan
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Jumlah Seluruh Donatur -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="stat-label text-muted fw-semibold">Jumlah Seluruh Donatur</span>
                    <div class="stat-icon text-bg-info rounded-3 p-2 text-white"><i class="bi bi-heart-fill fs-5"></i></div>
                </div>
                <h3 class="stat-value fw-bold text-dark mb-1">
                    <?= esc((string) $kpi['total_donors']) ?> <small class="fs-6 fw-normal text-muted">Donatur</small>
                </h3>
                <div class="stat-meta text-muted small">
                    <span class="text-success fw-semibold"><i class="bi bi-person-check-fill me-1"></i><?= esc((string) $kpi['active_donors']) ?> donatur aktif</span> terdaftar di sistem
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Jumlah Transaksi Penerimaan Donasi -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="stat-label text-muted fw-semibold">Jumlah Transaksi Donasi</span>
                    <div class="stat-icon text-bg-secondary rounded-3 p-2"><i class="bi bi-receipt fs-5"></i></div>
                </div>
                <h3 class="stat-value fw-bold text-dark mb-1">
                    <?= esc((string) $kpi['total_donation_count']) ?> <small class="fs-6 fw-normal text-muted">Transaksi</small>
                </h3>
                <div class="stat-meta text-muted small">
                    <?= esc((string) $kpi['verified_donation_count']) ?> terverifikasi · <?= esc((string) ($kpi['total_donation_count'] - $kpi['verified_donation_count'])) ?> pending/dibatalkan
                </div>
            </div>
        </div>
    </div>

    <!-- 6. Jumlah Transaksi Distribusi Donasi -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="stat-label text-muted fw-semibold">Jumlah Transaksi Distribusi</span>
                    <div class="stat-icon text-bg-warning rounded-3 p-2 text-dark"><i class="bi bi-box-seam-fill fs-5"></i></div>
                </div>
                <h3 class="stat-value fw-bold text-dark mb-1">
                    <?= esc((string) $kpi['total_distribution_count']) ?> <small class="fs-6 fw-normal text-muted">Penyaluran</small>
                </h3>
                <div class="stat-meta text-muted small">
                    <?= esc((string) $kpi['active_distribution_count']) ?> disetujui & tersalurkan ke penerima
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4 GRAFIK DASHBOARD (CHART.JS) DENGAN DATA AKTUAL -->
<div class="row g-4 mb-4">
    <!-- Grafik 1 & 2: Penerimaan & Distribusi Bulanan -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 fw-bold mb-0 text-dark">Grafik Penerimaan & Distribusi Donasi (<?= esc((string) $chartData['year']) ?>)</h2>
                    <span class="text-muted small">Perbandingan nominal dana masuk dan penyaluran bulanan</span>
                </div>
                <span class="badge bg-primary-subtle text-primary">Bulanan</span>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="chartComparison"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik 3: Perbandingan Penerimaan vs Distribusi -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 fw-bold mb-0 text-dark">Perbandingan Akumulasi Donasi vs Penyaluran</h2>
                    <span class="text-muted small">Total dana yang dihimpun berbanding dana tersalurkan</span>
                </div>
                <span class="badge bg-success-subtle text-success">Rasio</span>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="chartRatio"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik 4: Perkembangan Donasi & Trend Saldo Berjalan -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 fw-bold mb-0 text-dark">Grafik Perkembangan Donasi & Trend Saldo Kumulatif</h2>
                    <span class="text-muted small">Pergerakan saldo kas yayasan sepanjang tahun <?= esc((string) $chartData['year']) ?></span>
                </div>
                <span class="badge bg-info-subtle text-info-emphasis">Trend Tahunan</span>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="chartTrend"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TRANSAKSI TERBARU & AKTIVITAS -->
<div class="row g-4">
    <!-- Penerimaan Donasi Terbaru -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 fw-bold mb-0">Donasi Masuk Terbaru</h2>
                    <span class="text-muted small">5 transaksi terakhir yang tercatat</span>
                </div>
                <?php if ($canViewDonations) : ?>
                    <a href="<?= url_to('donasi.index') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                <?php endif ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Donatur</th>
                            <th>Jumlah / Barang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentDonations === []) : ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada transaksi donasi tercatat.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($recentDonations as $d) : ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold small"><?= esc($d->transaction_number) ?></div>
                                        <div class="text-muted small"><?= esc(date('d/m/Y', strtotime($d->donation_date))) ?></div>
                                    </td>
                                    <td class="small">
                                        <?= (int) $d->is_anonymous === 1 ? '<span class="text-muted font-italic">Hamba Allah (Anonim)</span>' : esc($d->donor_name ?? 'Umum') ?>
                                    </td>
                                    <td>
                                        <?php if ($d->donation_type === 'money') : ?>
                                            <span class="fw-bold text-success small">Rp <?= number_format((float) $d->amount, 2, ',', '.') ?></span>
                                        <?php else : ?>
                                            <span class="fw-bold text-primary small"><?= esc((string) $d->goods_quantity) ?> <?= esc($d->goods_unit ?? '') ?></span>
                                            <div class="text-muted small"><?= esc($d->goods_name ?? '') ?></div>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?php if ($d->status === 'verified') : ?>
                                            <span class="badge text-bg-success">Terverifikasi</span>
                                        <?php elseif ($d->status === 'pending') : ?>
                                            <span class="badge text-bg-warning">Pending</span>
                                        <?php else : ?>
                                            <span class="badge text-bg-danger">Batal</span>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Penyaluran Donasi Terbaru -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 fw-bold mb-0">Distribusi Donasi Terbaru</h2>
                    <span class="text-muted small">5 penyaluran terakhir yang dicatat</span>
                </div>
                <?php if ($canViewDistributions) : ?>
                    <a href="<?= url_to('distribusi.index') ?>" class="btn btn-sm btn-outline-success">Lihat Semua</a>
                <?php endif ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Penyaluran</th>
                            <th>Penerima</th>
                            <th>Bantuan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentDistributions === []) : ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada data penyaluran donasi.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($recentDistributions as $dist) : ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold small"><?= esc($dist->distribution_number) ?></div>
                                        <div class="text-muted small"><?= esc(date('d/m/Y', strtotime($dist->distribution_date))) ?></div>
                                    </td>
                                    <td class="small">
                                        <strong><?= esc($dist->recipient_name) ?></strong>
                                        <div class="text-muted small"><?= esc($dist->description) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($dist->distribution_type === 'money') : ?>
                                            <span class="fw-bold text-danger small">Rp <?= number_format((float) $dist->amount, 2, ',', '.') ?></span>
                                        <?php else : ?>
                                            <span class="badge bg-secondary">Barang / Logistik</span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?php if ($dist->status === 'distributed') : ?>
                                            <span class="badge text-bg-success">Tersalurkan</span>
                                        <?php elseif ($dist->status === 'approved') : ?>
                                            <span class="badge text-bg-primary">Disetujui</span>
                                        <?php elseif ($dist->status === 'draft') : ?>
                                            <span class="badge text-bg-secondary">Draf</span>
                                        <?php else : ?>
                                            <span class="badge text-bg-danger">Batal</span>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartLabels = <?= json_encode($chartData['labels']) ?>;
    const receiptsData = <?= json_encode($chartData['receipts']) ?>;
    const distributionsData = <?= json_encode($chartData['distributions']) ?>;
    const cumulativeData = <?= json_encode($chartData['cumulative']) ?>;
    const totalReceipts = <?= json_encode($chartData['total_receipts']) ?>;
    const totalDist = <?= json_encode($chartData['total_dist']) ?>;

    const formatRupiah = (val) => 'Rp ' + Number(val).toLocaleString('id-ID');

    // 1. Grafik Perbandingan Bulanan (Penerimaan vs Distribusi)
    const ctxComp = document.getElementById('chartComparison');
    if (ctxComp) {
        new Chart(ctxComp, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Penerimaan Donasi (Rp)',
                        data: receiptsData,
                        backgroundColor: 'rgba(13, 110, 253, 0.75)',
                        borderColor: '#0d6efd',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Distribusi Donasi (Rp)',
                        data: distributionsData,
                        backgroundColor: 'rgba(220, 53, 69, 0.75)',
                        borderColor: '#dc3545',
                        borderWidth: 1,
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatRupiah(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' Jt';
                                if (value >= 1000) return (value / 1000) + ' Rb';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Grafik Rasio / Komposisi Donasi Masuk vs Tersalurkan
    const ctxRatio = document.getElementById('chartRatio');
    if (ctxRatio) {
        new Chart(ctxRatio, {
            type: 'doughnut',
            data: {
                labels: ['Total Donasi Diterima', 'Total Donasi Tersalurkan', 'Saldo Belum Tersalurkan'],
                datasets: [{
                    data: [
                        receiptsData.reduce((a,b)=>a+b, 0),
                        distributionsData.reduce((a,b)=>a+b, 0),
                        Math.max(0, receiptsData.reduce((a,b)=>a+b, 0) - distributionsData.reduce((a,b)=>a+b, 0))
                    ],
                    backgroundColor: [
                        '#0d6efd',
                        '#dc3545',
                        '#198754'
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + formatRupiah(context.raw);
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Grafik Trend Perkembangan Saldo Kumulatif
    const ctxTrend = document.getElementById('chartTrend');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Trend Saldo Kas Kumulatif (Rp)',
                    data: cumulativeData,
                    fill: true,
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderColor: '#198754',
                    tension: 0.3,
                    borderWidth: 2,
                    pointBackgroundColor: '#198754',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatRupiah(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' Jt';
                                if (value >= 1000) return (value / 1000) + ' Rb';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php $this->endSection() ?>
