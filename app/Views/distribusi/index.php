<?php
$this->extend('layouts/main');

$details     = $pager->getDetails('distribusi');
$total       = $details['total'];
$perPage     = $details['perPage'];
$currentPage = max(1, $details['currentPage']);
$from        = $total > 0 ? ($currentPage - 1) * $perPage + 1 : 0;
$to          = min($total, $currentPage * $perPage);

$statusMap = [
    'draft'       => ['label' => 'Draf', 'class' => 'text-bg-secondary'],
    'approved'    => ['label' => 'Disetujui', 'class' => 'text-bg-info'],
    'distributed' => ['label' => 'Tersalurkan', 'class' => 'text-bg-success'],
    'cancelled'   => ['label' => 'Dibatalkan', 'class' => 'text-bg-dark'],
];

$recipientLabels = [
    'individual'   => 'Individu',
    'organization' => 'Lembaga',
    'community'    => 'Komunitas',
];

$totalTransactions     = (int) ($summary->total_transactions ?? 0);
$draftTransactions     = (int) ($summary->draft_transactions ?? 0);
$cancelledTransactions = (int) ($summary->cancelled_transactions ?? 0);
$activeTransactions    = max(0, $totalTransactions - $cancelledTransactions - $draftTransactions);
$activeMoney           = (float) ($summary->active_money ?? 0);

$goodsRows = array_filter(
    $goodsBalances,
    static fn (array $balance): bool => $balance['received_cents'] > 0 || $balance['distributed_cents'] > 0,
);
?>

<?php $this->section('content') ?>
<div class="card mb-4">
    <div class="card-header flex-wrap gap-2">
        <div>
            <h2 class="h6 mb-0">Distribusi Donasi</h2>
            <span class="text-muted small">Penyaluran dana dan barang kepada penerima manfaat.</span>
        </div>
        <?php if (can('distributions.manage')) : ?>
            <a href="<?= url_to('distribusi.create') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Catat Distribusi
            </a>
        <?php endif ?>
    </div>

    <div class="card-body border-bottom bg-light-subtle">
        <form action="<?= url_to('distribusi.index') ?>" method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label small">Cari</label>
                <input type="search" class="form-control form-control-sm" id="search" name="search" value="<?= esc($search) ?>" placeholder="Nomor distribusi, penerima, atau tujuan" maxlength="100">
            </div>
            <div class="col-6 col-md-2">
                <label for="type" class="form-label small">Jenis bantuan</label>
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
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draf</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                    <option value="distributed" <?= $status === 'distributed' ? 'selected' : '' ?>>Tersalurkan</option>
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
                <a href="<?= url_to('distribusi.index') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                <div class="text-muted small">Aktif (disetujui/tersalurkan)</div>
                <div class="fs-5 fw-semibold text-success" id="summary-active"><?= esc((string) $activeTransactions) ?></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="text-muted small">Draf / dibatalkan</div>
                <div class="fs-5 fw-semibold text-secondary" id="summary-inactive">
                    <?= esc((string) $draftTransactions) ?> / <?= esc((string) $cancelledTransactions) ?>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="text-muted small">Uang tersalurkan aktif</div>
                <div class="fs-5 fw-semibold" id="summary-active-money">Rp <?= esc(number_format($activeMoney, 2, ',', '.')) ?></div>
            </div>
        </div>
        <div class="text-muted small mt-2">
            Draf belum mengurangi saldo. Transaksi dibatalkan tidak dihitung sebagai penyaluran aktif.
        </div>
    </div>

    <div class="card-body border-bottom">
        <div class="row g-4">
            <div class="col-lg-5">
                <h3 class="h6 mb-3">Saldo Dana Global</h3>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted small">Diterima</div>
                            <div class="fw-semibold small">Rp <?= esc($moneyBalance['received']) ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted small">Tersalurkan</div>
                            <div class="fw-semibold small">Rp <?= esc($moneyBalance['distributed']) ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 h-100 border-success-subtle bg-success-subtle">
                            <div class="text-muted small">Tersedia</div>
                            <div class="fw-semibold small text-success" id="money-available">Rp <?= esc($moneyBalance['available']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="form-text">
                    Hanya donasi uang <strong>terverifikasi</strong> yang dihitung sebagai penerimaan. Draf belum mengurangi saldo.
                </div>
            </div>

            <div class="col-lg-7">
                <h3 class="h6 mb-3">Saldo Barang</h3>
                <?php if ($goodsRows === []) : ?>
                    <p class="text-muted small mb-0">Belum ada stok barang terverifikasi.</p>
                <?php else : ?>
                    <div class="table-responsive" style="max-height: 14rem;">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th>Satuan</th>
                                    <th class="text-end">Diterima</th>
                                    <th class="text-end">Tersalurkan</th>
                                    <th class="text-end">Tersedia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($goodsRows as $balance) : ?>
                                    <tr>
                                        <td class="small"><?= esc($balance['item_name']) ?></td>
                                        <td class="small"><?= esc($balance['unit']) ?></td>
                                        <td class="text-end small"><?= esc($balance['received']) ?></td>
                                        <td class="text-end small"><?= esc($balance['distributed']) ?></td>
                                        <td class="text-end small fw-semibold <?= $balance['available_cents'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= esc($balance['available']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text">
                        Stok dihitung per nama barang + satuan (tanpa konversi satuan). Nama dinormalisasi: huruf besar/kecil dan spasi berlebih diabaikan.
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Distribusi</th>
                    <th>Penerima Manfaat</th>
                    <th>Bantuan</th>
                    <th class="text-end">Nilai</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($distributions === []) : ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
                            Tidak ada distribusi yang sesuai dengan filter.
                            <?php if (can('distributions.manage')) : ?>
                                <div class="mt-3">
                                    <a href="<?= url_to('distribusi.create') ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i>Catat Distribusi
                                    </a>
                                </div>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($distributions as $distribution) : ?>
                        <?php
                        $statusMeta = $statusMap[$distribution->status] ?? ['label' => $distribution->status, 'class' => 'text-bg-secondary'];
                        $isGoods    = $distribution->distribution_type === 'goods';
                        ?>
                        <tr>
                            <td>
                                <a href="<?= url_to('distribusi.show', $distribution->id) ?>" class="text-reset text-decoration-none">
                                    <code class="d-block"><?= esc($distribution->distribution_number) ?></code>
                                </a>
                                <span class="text-muted small"><?= esc(date('d/m/Y', strtotime($distribution->distribution_date))) ?></span>
                            </td>
                            <td class="small">
                                <div class="fw-semibold"><?= esc($distribution->recipient_name) ?></div>
                                <div class="text-muted"><?= esc($recipientLabels[$distribution->recipient_type] ?? $distribution->recipient_type) ?></div>
                            </td>
                            <td class="small">
                                <span class="badge <?= $isGoods ? 'text-bg-info' : 'text-bg-success' ?>">
                                    <?= $isGoods ? 'Barang' : 'Uang' ?>
                                </span>
                                <?php if ($distribution->description !== null && $distribution->description !== '') : ?>
                                    <div class="mt-1 text-muted"><?= esc(mb_strimwidth($distribution->description, 0, 70, '…')) ?></div>
                                <?php endif ?>
                            </td>
                            <td class="text-end small">
                                <?php if ($isGoods) : ?>
                                    <span class="text-muted">Rincian barang</span>
                                <?php else : ?>
                                    <span class="fw-semibold">Rp <?= esc(number_format((float) $distribution->amount, 2, ',', '.')) ?></span>
                                <?php endif ?>
                            </td>
                            <td>
                                <span class="badge <?= esc($statusMeta['class'], 'attr') ?>"><?= esc($statusMeta['label']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= url_to('distribusi.show', $distribution->id) ?>" class="btn btn-sm btn-light" title="Detail distribusi">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <?php if (can('distributions.manage') && $distribution->status === 'draft') : ?>
                                        <a href="<?= url_to('distribusi.edit', $distribution->id) ?>" class="btn btn-sm btn-light" title="Ubah distribusi">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form action="<?= url_to('distribusi.approve', $distribution->id) ?>" method="post" data-confirm="Setujui distribusi ini? Saldo aktif akan dikurangi setelah saldo dipastikan cukup.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Setujui">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif ?>

                                    <?php if (can('distributions.manage') && $distribution->status === 'approved') : ?>
                                        <form action="<?= url_to('distribusi.distribute', $distribution->id) ?>" method="post" data-confirm="Tandai distribusi ini sudah tersalurkan?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Tandai tersalurkan">
                                                <i class="bi bi-truck"></i>
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
                Menampilkan <?= esc((string) $from) ?>–<?= esc((string) $to) ?> dari <?= esc((string) $total) ?> distribusi
            <?php else : ?>
                Tidak ada data
            <?php endif ?>
        </span>
        <?= $pager->links('distribusi', 'bootstrap5') ?>
    </div>
</div>
<?php $this->endSection() ?>
