<?php
$this->extend('layouts/main');

$details     = $pager->getDetails('donasi');
$total       = $details['total'];
$perPage     = $details['perPage'];
$currentPage = max(1, $details['currentPage']);
$from        = $total > 0 ? ($currentPage - 1) * $perPage + 1 : 0;
$to          = min($total, $currentPage * $perPage);

$statusMap = [
    'pending'   => ['label' => 'Menunggu', 'class' => 'text-bg-warning'],
    'verified'  => ['label' => 'Terverifikasi', 'class' => 'text-bg-success'],
    'cancelled' => ['label' => 'Dibatalkan', 'class' => 'text-bg-secondary'],
];

$paymentLabels = [
    'cash'          => 'Tunai',
    'bank_transfer' => 'Transfer bank',
    'other'         => 'Lainnya',
];

$totalTransactions     = (int) ($summary->total_transactions ?? 0);
$cancelledTransactions = (int) ($summary->cancelled_transactions ?? 0);
$activeTransactions    = max(0, $totalTransactions - $cancelledTransactions);
$activeMoney           = (float) ($summary->active_money ?? 0);
$verifiedMoney         = (float) ($summary->verified_money ?? 0);
?>

<?php $this->section('content') ?>
<div class="card">
    <div class="card-header flex-wrap gap-2">
        <div>
            <h2 class="h6 mb-0">Penerimaan Donasi</h2>
            <span class="text-muted small">Catatan donasi uang dan barang beserta status verifikasinya.</span>
        </div>
        <?php if (can('donations.manage')) : ?>
            <a href="<?= url_to('donasi.create') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Catat Donasi
            </a>
        <?php endif ?>
    </div>

    <div class="card-body border-bottom bg-light-subtle">
        <form action="<?= url_to('donasi.index') ?>" method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label small">Cari</label>
                <input type="search" class="form-control form-control-sm" id="search" name="search" value="<?= esc($search) ?>" placeholder="Nomor transaksi, donatur, atau catatan" maxlength="100">
            </div>
            <div class="col-6 col-md-2">
                <label for="type" class="form-label small">Jenis</label>
                <select class="form-select form-select-sm" id="type" name="type">
                    <option value="">Semua</option>
                    <option value="money" <?= $type === 'money' ? 'selected' : '' ?>>Uang</option>
                    <option value="goods" <?= $type === 'goods' ? 'selected' : '' ?>>Barang</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="form-label small">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">Semua</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="verified" <?= $status === 'verified' ? 'selected' : '' ?>>Terverifikasi</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="date_from" class="form-label small">Dari tanggal</label>
                <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?= esc($dateFrom) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label for="date_to" class="form-label small">Sampai tanggal</label>
                <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?= esc($dateTo) ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="<?= url_to('donasi.index') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body border-bottom">
        <div class="row g-3 text-center text-sm-start">
            <div class="col-6 col-lg-3">
                <div class="text-muted small">Transaksi sesuai filter</div>
                <div class="fs-5 fw-semibold" id="summary-total"><?= esc((string) $totalTransactions) ?></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="text-muted small">Aktif (belum batal)</div>
                <div class="fs-5 fw-semibold text-success" id="summary-active"><?= esc((string) $activeTransactions) ?></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="text-muted small">Dibatalkan</div>
                <div class="fs-5 fw-semibold text-secondary" id="summary-cancelled"><?= esc((string) $cancelledTransactions) ?></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="text-muted small">Nilai uang aktif</div>
                <div class="fs-5 fw-semibold" id="summary-active-money">Rp <?= esc(number_format($activeMoney, 2, ',', '.')) ?></div>
                <div class="text-muted small" id="summary-verified-money">Terverifikasi: Rp <?= esc(number_format($verifiedMoney, 2, ',', '.')) ?></div>
            </div>
        </div>
        <div class="text-muted small mt-2">
            Donasi berstatus dibatalkan tidak dihitung sebagai penerimaan aktif maupun nilai uang aktif.
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Transaksi</th>
                    <th>Donatur</th>
                    <th>Rincian</th>
                    <th class="text-end">Nilai</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($donations === []) : ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                            Tidak ada donasi yang sesuai dengan filter.
                            <?php if (can('donations.manage')) : ?>
                                <div class="mt-3">
                                    <a href="<?= url_to('donasi.create') ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i>Catat Donasi
                                    </a>
                                </div>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($donations as $donation) : ?>
                        <?php
                        $status     = $statusMap[$donation->status] ?? ['label' => $donation->status, 'class' => 'text-bg-secondary'];
                        $isGoods    = $donation->donation_type === 'goods';
                        $isAnonymous = (int) $donation->is_anonymous === 1;
                        ?>
                        <tr>
                            <td>
                                <a href="<?= url_to('donasi.show', $donation->id) ?>" class="text-reset text-decoration-none">
                                    <code class="d-block"><?= esc($donation->transaction_number) ?></code>
                                </a>
                                <span class="text-muted small"><?= esc(date('d/m/Y', strtotime($donation->donation_date))) ?></span>
                            </td>
                            <td class="small">
                                <?php if ($isAnonymous) : ?>
                                    <span class="badge text-bg-dark"><i class="bi bi-incognito me-1"></i>Anonim</span>
                                <?php elseif ($donation->donor_name !== null) : ?>
                                    <div class="fw-semibold"><?= esc($donation->donor_name) ?></div>
                                    <div class="text-muted"><code><?= esc((string) $donation->donor_code) ?></code></div>
                                    <?php if ($donation->donor_deleted_at !== null) : ?>
                                        <span class="badge text-bg-secondary mt-1">Arsip</span>
                                    <?php elseif ($donation->donor_status === 'inactive') : ?>
                                        <span class="badge text-bg-secondary mt-1">Nonaktif</span>
                                    <?php endif ?>
                                <?php else : ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </td>
                            <td class="small">
                                <span class="badge <?= $isGoods ? 'text-bg-info' : 'text-bg-success' ?>">
                                    <?= $isGoods ? 'Barang' : 'Uang' ?>
                                </span>
                                <?php if ($isGoods) : ?>
                                    <div class="mt-1">
                                        <?= esc($donation->goods_name ?? '—') ?>
                                        <?php if ($donation->goods_quantity !== null) : ?>
                                            <span class="text-muted">· <?= esc(number_format((float) $donation->goods_quantity, 2, ',', '.')) ?> <?= esc($donation->goods_unit ?? '') ?></span>
                                        <?php endif ?>
                                    </div>
                                <?php else : ?>
                                    <div class="mt-1 text-muted"><?= esc($paymentLabels[$donation->payment_method] ?? '—') ?></div>
                                <?php endif ?>
                            </td>
                            <td class="text-end small">
                                <?php if ($isGoods) : ?>
                                    <?php if ($donation->estimated_value !== null) : ?>
                                        <div class="fw-semibold">Rp <?= esc(number_format((float) $donation->estimated_value, 2, ',', '.')) ?></div>
                                        <div class="text-muted">estimasi</div>
                                    <?php else : ?>
                                        <span class="text-muted">—</span>
                                    <?php endif ?>
                                <?php else : ?>
                                    <span class="fw-semibold">Rp <?= esc(number_format((float) $donation->amount, 2, ',', '.')) ?></span>
                                <?php endif ?>
                            </td>
                            <td>
                                <span class="badge <?= esc($status['class'], 'attr') ?>"><?= esc($status['label']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= url_to('donasi.show', $donation->id) ?>" class="btn btn-sm btn-light" title="Detail donasi">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <?php if (can('donations.manage') && $donation->status === 'pending') : ?>
                                        <a href="<?= url_to('donasi.edit', $donation->id) ?>" class="btn btn-sm btn-light" title="Ubah donasi">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form action="<?= url_to('donasi.verify', $donation->id) ?>" method="post" data-confirm="Verifikasi donasi ini? Pastikan uang atau barang sudah diterima.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Verifikasi">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <span class="text-muted small">
            <?php if ($total > 0) : ?>
                Menampilkan <?= esc((string) $from) ?>–<?= esc((string) $to) ?> dari <?= esc((string) $total) ?> donasi
            <?php else : ?>
                Tidak ada data
            <?php endif ?>
        </span>
        <?= $pager->links('donasi', 'bootstrap5') ?>
    </div>
</div>
<?php $this->endSection() ?>
