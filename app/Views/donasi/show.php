<?php
$this->extend('layouts/main');

$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];

$statusMap = [
    'pending'   => ['label' => 'Menunggu verifikasi', 'class' => 'text-bg-warning'],
    'verified'  => ['label' => 'Terverifikasi', 'class' => 'text-bg-success'],
    'cancelled' => ['label' => 'Dibatalkan', 'class' => 'text-bg-secondary'],
];

$paymentLabels = [
    'cash'          => 'Tunai',
    'bank_transfer' => 'Transfer bank',
    'other'         => 'Lainnya',
];

$status       = $statusMap[$donation->status] ?? ['label' => $donation->status, 'class' => 'text-bg-secondary'];
$isGoods      = $donation->donation_type === 'goods';
$isAnonymous  = (int) $donation->is_anonymous === 1;
$isPending    = $donation->status === 'pending';
$isCancellable = in_array($donation->status, ['pending', 'verified'], true);
$cancelError  = $errors['cancelled_reason'] ?? null;
$receiptLabel = $donation->status === 'cancelled' ? 'Cetak Arsip' : 'Bukti Penerimaan';
?>

<?php $this->section('content') ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <div class="text-muted small mb-1">Nomor transaksi</div>
                <h2 class="h5 mb-2"><code><?= esc($donation->transaction_number) ?></code></h2>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge <?= esc($status['class'], 'attr') ?>"><?= esc($status['label']) ?></span>
                    <span class="badge <?= $isGoods ? 'text-bg-info' : 'text-bg-success' ?>">
                        <?= $isGoods ? 'Donasi barang' : 'Donasi uang' ?>
                    </span>
                    <?php if ($isAnonymous) : ?>
                        <span class="badge text-bg-dark"><i class="bi bi-incognito me-1"></i>Anonim</span>
                    <?php endif ?>
                </div>
                <div class="text-muted small mt-2">
                    <i class="bi bi-calendar3 me-1"></i><?= esc(date('d/m/Y', strtotime($donation->donation_date))) ?>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-start justify-content-lg-end">
                <a href="<?= url_to('donasi.index') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
                <a href="<?= url_to('donasi.receipt', $donation->id) ?>" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">
                    <i class="bi bi-printer me-1"></i><?= esc($receiptLabel) ?>
                </a>
                <?php if (can('donations.manage') && $isPending) : ?>
                    <a href="<?= url_to('donasi.edit', $donation->id) ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil-square me-1"></i>Ubah
                    </a>
                    <form action="<?= url_to('donasi.verify', $donation->id) ?>" method="post" data-confirm="Verifikasi donasi ini? Pastikan uang atau barang sudah diterima.">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-check2-circle me-1"></i>Verifikasi
                        </button>
                    </form>
                <?php endif ?>
                <?php if (can('donations.manage') && $isCancellable) : ?>
                    <a href="#cancel-donation" class="btn btn-sm btn-outline-danger">
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
            <div class="card-header"><h3 class="h6 mb-0">Rincian Donasi</h3></div>
            <div class="card-body">
                <?php if ($donation->status === 'cancelled') : ?>
                    <div class="alert alert-secondary">
                        <div class="fw-semibold mb-1"><i class="bi bi-x-circle me-1"></i>Donasi dibatalkan</div>
                        <div class="small"><?= $donation->cancelled_reason !== null ? nl2br(esc($donation->cancelled_reason)) : 'Alasan tidak tercatat.' ?></div>
                    </div>
                <?php endif ?>

                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted fw-normal">Jenis donasi</dt>
                    <dd class="col-sm-8"><?= $isGoods ? 'Barang' : 'Uang' ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Tanggal donasi</dt>
                    <dd class="col-sm-8"><?= esc(date('d/m/Y', strtotime($donation->donation_date))) ?></dd>

                    <?php if ($isGoods) : ?>
                        <dt class="col-sm-4 text-muted fw-normal">Nama barang</dt>
                        <dd class="col-sm-8"><?= $donation->goods_name !== null ? esc($donation->goods_name) : '—' ?></dd>

                        <dt class="col-sm-4 text-muted fw-normal">Kuantitas</dt>
                        <dd class="col-sm-8">
                            <?php if ($donation->goods_quantity !== null) : ?>
                                <?= esc(number_format((float) $donation->goods_quantity, 2, ',', '.')) ?> <?= esc($donation->goods_unit ?? '') ?>
                            <?php else : ?>
                                —
                            <?php endif ?>
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Nilai estimasi</dt>
                        <dd class="col-sm-8">
                            <?= $donation->estimated_value !== null ? 'Rp ' . esc(number_format((float) $donation->estimated_value, 2, ',', '.')) : '—' ?>
                        </dd>
                    <?php else : ?>
                        <dt class="col-sm-4 text-muted fw-normal">Nominal</dt>
                        <dd class="col-sm-8 fw-semibold text-success">Rp <?= esc(number_format((float) $donation->amount, 2, ',', '.')) ?></dd>

                        <dt class="col-sm-4 text-muted fw-normal">Metode pembayaran</dt>
                        <dd class="col-sm-8"><?= esc($paymentLabels[$donation->payment_method] ?? '—') ?></dd>

                        <dt class="col-sm-4 text-muted fw-normal">Nomor referensi</dt>
                        <dd class="col-sm-8"><?= $donation->reference_number !== null && $donation->reference_number !== '' ? esc($donation->reference_number) : '—' ?></dd>
                    <?php endif ?>

                    <dt class="col-sm-4 text-muted fw-normal">Catatan</dt>
                    <dd class="col-sm-8 mb-0"><?= $donation->notes !== null && $donation->notes !== '' ? nl2br(esc($donation->notes)) : '—' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><h3 class="h6 mb-0">Donatur</h3></div>
            <div class="card-body small">
                <?php if ($isAnonymous) : ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar"><i class="bi bi-incognito"></i></span>
                        <div>
                            <div class="fw-semibold">Donasi anonim</div>
                            <div class="text-muted">Identitas donatur tidak ditampilkan pada transaksi ini.</div>
                        </div>
                    </div>
                <?php elseif ($donation->donor_id !== null && $donation->donor_name !== null) : ?>
                    <div class="mb-2">
                        <div class="fw-semibold">
                            <?php if ($donation->donor_deleted_at === null) : ?>
                                <a href="<?= url_to('donatur.show', $donation->donor_id) ?>" class="text-reset text-decoration-none">
                                    <?= esc($donation->donor_name) ?>
                                </a>
                            <?php else : ?>
                                <?= esc($donation->donor_name) ?>
                                <span class="badge text-bg-secondary ms-1">Arsip</span>
                            <?php endif ?>
                        </div>
                        <div class="text-muted"><code><?= esc((string) $donation->donor_code) ?></code></div>
                    </div>
                    <div class="text-muted">
                        Status donatur:
                        <?= $donation->donor_status === 'active' ? 'Aktif' : 'Nonaktif' ?>
                    </div>
                <?php else : ?>
                    <span class="text-muted">Tidak ada data donatur pada transaksi ini.</span>
                <?php endif ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="h6 mb-0">Metadata</h3></div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Dibuat oleh</dt>
                    <dd class="col-7"><?= $creator !== null ? esc($creator->full_name) : '—' ?></dd>

                    <dt class="col-5 text-muted fw-normal">Dibuat</dt>
                    <dd class="col-7"><?= esc(date('d/m/Y H:i', strtotime($donation->created_at))) ?></dd>

                    <dt class="col-5 text-muted fw-normal">Diverifikasi oleh</dt>
                    <dd class="col-7"><?= $verifier !== null ? esc($verifier->full_name) : '—' ?></dd>

                    <dt class="col-5 text-muted fw-normal">Diverifikasi</dt>
                    <dd class="col-7"><?= $donation->verified_at !== null ? esc(date('d/m/Y H:i', strtotime($donation->verified_at))) : '—' ?></dd>

                    <dt class="col-5 text-muted fw-normal">Diperbarui</dt>
                    <dd class="col-7 mb-0"><?= $donation->updated_at !== null ? esc(date('d/m/Y H:i', strtotime($donation->updated_at))) : '—' ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php if (can('donations.manage') && $isCancellable) : ?>
    <div class="card border-danger-subtle" id="cancel-donation">
        <div class="card-header">
            <h3 class="h6 mb-0 text-danger">Batalkan Donasi</h3>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                Pembatalan tidak menghapus data. Donasi tetap tersimpan dengan status dibatalkan dan alasan yang tercatat pada audit log.
                <?php if ($donation->status === 'verified') : ?>
                    <strong class="text-body">Donasi ini sudah terverifikasi</strong>, pastikan pembatalan sesuai dengan koreksi resmi.
                <?php endif ?>
            </p>
            <form action="<?= url_to('donasi.cancel', $donation->id) ?>" method="post" data-confirm="Batalkan donasi ini? Data tidak akan dihapus dan alasan pembatalan akan tercatat.">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="cancelled_reason" class="form-label">Alasan pembatalan <span class="text-danger">*</span></label>
                    <textarea class="form-control <?= $cancelError ? 'is-invalid' : '' ?>" id="cancelled_reason" name="cancelled_reason" rows="3" minlength="5" maxlength="255" required><?= esc((string) old('cancelled_reason', '')) ?></textarea>
                    <?php if ($cancelError) : ?><div class="invalid-feedback"><?= esc($cancelError) ?></div><?php endif ?>
                    <div class="form-text">Minimal 5 dan maksimal 255 karakter. Jelaskan alasan koreksi atau pembatalan.</div>
                </div>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x-circle me-1"></i>Batalkan Donasi
                </button>
                <a href="#" class="btn btn-outline-secondary">Kembali ke atas</a>
            </form>
        </div>
    </div>
<?php endif ?>
<?php $this->endSection() ?>
