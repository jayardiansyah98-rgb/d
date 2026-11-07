<?php
$this->extend('layouts/main');

$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];

$statusMap = [
    'draft'       => ['label' => 'Draf — belum mengurangi saldo', 'class' => 'text-bg-secondary'],
    'approved'    => ['label' => 'Disetujui — saldo terkunci', 'class' => 'text-bg-info'],
    'distributed' => ['label' => 'Tersalurkan', 'class' => 'text-bg-success'],
    'cancelled'   => ['label' => 'Dibatalkan — tidak dihitung', 'class' => 'text-bg-dark'],
];

$recipientLabels = [
    'individual'   => 'Individu',
    'organization' => 'Lembaga/organisasi',
    'community'    => 'Komunitas',
];

$status        = $statusMap[$distribution->status] ?? ['label' => $distribution->status, 'class' => 'text-bg-secondary'];
$isGoods       = $distribution->distribution_type === 'goods';
$isDraft       = $distribution->status === 'draft';
$isApproved    = $distribution->status === 'approved';
$isCancellable = $distribution->status !== 'cancelled';
$cancelError   = $errors['cancelled_reason'] ?? null;
$receiptLabel  = $distribution->status === 'cancelled' ? 'Cetak Arsip' : 'Bukti Penyaluran';
$totalQuantity = 0.0;

foreach ($items as $item) {
    $totalQuantity += (float) $item->quantity;
}
?>

<?php $this->section('content') ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <div class="text-muted small mb-1">Nomor distribusi</div>
                <h2 class="h5 mb-2"><code><?= esc($distribution->distribution_number) ?></code></h2>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge <?= esc($status['class'], 'attr') ?>"><?= esc($status['label']) ?></span>
                    <span class="badge <?= $isGoods ? 'text-bg-info' : 'text-bg-success' ?>">
                        <?= $isGoods ? 'Bantuan barang' : 'Bantuan uang' ?>
                    </span>
                </div>
                <div class="text-muted small mt-2">
                    <i class="bi bi-calendar3 me-1"></i><?= esc(date('d/m/Y', strtotime($distribution->distribution_date))) ?>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-start justify-content-lg-end">
                <a href="<?= url_to('distribusi.index') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
                <a href="<?= url_to('distribusi.receipt', $distribution->id) ?>" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">
                    <i class="bi bi-printer me-1"></i><?= esc($receiptLabel) ?>
                </a>
                <?php if (can('distributions.manage') && $isDraft) : ?>
                    <a href="<?= url_to('distribusi.edit', $distribution->id) ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil-square me-1"></i>Ubah
                    </a>
                    <form action="<?= url_to('distribusi.approve', $distribution->id) ?>" method="post" data-confirm="Setujui distribusi ini? Saldo akan diperiksa dan dikunci.">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-check2-circle me-1"></i>Setujui
                        </button>
                    </form>
                <?php endif ?>
                <?php if (can('distributions.manage') && $isApproved) : ?>
                    <form action="<?= url_to('distribusi.distribute', $distribution->id) ?>" method="post" data-confirm="Tandai distribusi ini sudah tersalurkan?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-truck me-1"></i>Tandai Tersalurkan
                        </button>
                    </form>
                <?php endif ?>
                <?php if (can('distributions.manage') && $isCancellable) : ?>
                    <a href="#cancel-distribution" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-x-circle me-1"></i>Batalkan
                    </a>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h3 class="h6 mb-0">Rincian Penyaluran</h3></div>
            <div class="card-body">
                <?php if ($distribution->status === 'cancelled') : ?>
                    <div class="alert alert-secondary">
                        <div class="fw-semibold mb-1"><i class="bi bi-x-circle me-1"></i>Distribusi dibatalkan</div>
                        <div class="small"><?= $distribution->cancelled_reason !== null ? nl2br(esc($distribution->cancelled_reason)) : 'Alasan tidak tercatat.' ?></div>
                        <div class="small mt-1">Transaksi ini tidak dihitung sebagai penyaluran aktif dan tidak mengurangi saldo.</div>
                    </div>
                <?php elseif ($isDraft) : ?>
                    <div class="alert alert-warning">
                        <div class="fw-semibold mb-1"><i class="bi bi-hourglass-split me-1"></i>Masih draf</div>
                        <div class="small">Draf belum mengurangi saldo. Saldo baru diperiksa dan dikunci saat distribusi disetujui.</div>
                    </div>
                <?php endif ?>

                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted fw-normal">Jenis bantuan</dt>
                    <dd class="col-sm-8"><?= $isGoods ? 'Barang' : 'Uang' ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Tanggal penyaluran</dt>
                    <dd class="col-sm-8"><?= esc(date('d/m/Y', strtotime($distribution->distribution_date))) ?></dd>

                    <?php if ($isGoods) : ?>
                        <dt class="col-sm-4 text-muted fw-normal">Jumlah jenis barang</dt>
                        <dd class="col-sm-8"><?= esc((string) count($items)) ?> jenis</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Total kuantitas</dt>
                        <dd class="col-sm-8"><?= esc(number_format($totalQuantity, 2, ',', '.')) ?></dd>
                    <?php else : ?>
                        <dt class="col-sm-4 text-muted fw-normal">Nominal</dt>
                        <dd class="col-sm-8 fw-semibold text-success">Rp <?= esc(number_format((float) $distribution->amount, 2, ',', '.')) ?></dd>
                    <?php endif ?>

                    <dt class="col-sm-4 text-muted fw-normal">Tujuan penyaluran</dt>
                    <dd class="col-sm-8"><?= $distribution->description !== null && $distribution->description !== '' ? nl2br(esc($distribution->description)) : '—' ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Keterangan</dt>
                    <dd class="col-sm-8 mb-0"><?= $distribution->notes !== null && $distribution->notes !== '' ? nl2br(esc($distribution->notes)) : '—' ?></dd>
                </dl>

                <?php if ($isGoods) : ?>
                    <h4 class="h6 mt-4 mb-2">Daftar Barang</h4>
                    <?php if ($items === []) : ?>
                        <p class="text-muted small mb-0">Belum ada rincian barang.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama barang</th>
                                        <th class="text-end">Kuantitas</th>
                                        <th>Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item) : ?>
                                        <tr>
                                            <td class="small"><?= esc($item->item_name) ?></td>
                                            <td class="text-end small"><?= esc(number_format((float) $item->quantity, 2, ',', '.')) ?></td>
                                            <td class="small"><?= esc($item->unit) ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><h3 class="h6 mb-0">Penerima Manfaat</h3></div>
            <div class="card-body small">
                <div class="fw-semibold"><?= esc($distribution->recipient_name) ?></div>
                <div class="text-muted mb-2"><?= esc($recipientLabels[$distribution->recipient_type] ?? $distribution->recipient_type) ?></div>
                <div class="text-muted">
                    <?= $distribution->recipient_address !== null && $distribution->recipient_address !== '' ? nl2br(esc($distribution->recipient_address)) : 'Alamat tidak dicatat.' ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="h6 mb-0">Metadata</h3></div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Dibuat oleh</dt>
                    <dd class="col-7"><?= $creator !== null ? esc($creator->full_name) : '—' ?></dd>

                    <dt class="col-5 text-muted fw-normal">Dibuat</dt>
                    <dd class="col-7"><?= esc(date('d/m/Y H:i', strtotime($distribution->created_at))) ?></dd>

                    <dt class="col-5 text-muted fw-normal">Disetujui oleh</dt>
                    <dd class="col-7"><?= $approver !== null ? esc($approver->full_name) : '—' ?></dd>

                    <dt class="col-5 text-muted fw-normal">Disetujui</dt>
                    <dd class="col-7"><?= $distribution->approved_at !== null ? esc(date('d/m/Y H:i', strtotime($distribution->approved_at))) : '—' ?></dd>

                    <dt class="col-5 text-muted fw-normal">Tersalurkan</dt>
                    <dd class="col-7"><?= $distribution->distributed_at !== null ? esc(date('d/m/Y H:i', strtotime($distribution->distributed_at))) : '—' ?></dd>

                    <dt class="col-5 text-muted fw-normal">Diperbarui</dt>
                    <dd class="col-7 mb-0"><?= $distribution->updated_at !== null ? esc(date('d/m/Y H:i', strtotime($distribution->updated_at))) : '—' ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php if (can('distributions.manage') && $isCancellable) : ?>
    <div class="card border-danger-subtle" id="cancel-distribution">
        <div class="card-header">
            <h3 class="h6 mb-0 text-danger">Batalkan Distribusi</h3>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                Pembatalan tidak menghapus data. Saldo aktif otomatis dilepas karena status dibatalkan tidak dihitung sebagai penyaluran aktif.
                Perubahan ini tercatat pada audit log.
                <?php if (in_array($distribution->status, ['approved', 'distributed'], true)) : ?>
                    <strong class="text-body">Distribusi ini sudah mengurangi saldo</strong>; pembatalan akan mengembalikan saldo tersebut. Pastikan pembatalan sesuai dengan koreksi resmi.
                <?php endif ?>
            </p>
            <form action="<?= url_to('distribusi.cancel', $distribution->id) ?>" method="post" data-confirm="Batalkan distribusi ini? Data tidak akan dihapus dan alasan pembatalan akan tercatat.">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="cancelled_reason" class="form-label">Alasan pembatalan <span class="text-danger">*</span></label>
                    <textarea class="form-control <?= $cancelError ? 'is-invalid' : '' ?>" id="cancelled_reason" name="cancelled_reason" rows="3" minlength="5" maxlength="255" required><?= esc((string) old('cancelled_reason', '')) ?></textarea>
                    <?php if ($cancelError) : ?><div class="invalid-feedback"><?= esc($cancelError) ?></div><?php endif ?>
                    <div class="form-text">Minimal 5 dan maksimal 255 karakter. Jelaskan alasan koreksi atau pembatalan.</div>
                </div>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x-circle me-1"></i>Batalkan Distribusi
                </button>
                <a href="#" class="btn btn-outline-secondary">Kembali ke atas</a>
            </form>
        </div>
    </div>
<?php endif ?>
<?php $this->endSection() ?>
