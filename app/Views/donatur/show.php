<?php
$this->extend('layouts/main');

$typeLabel  = $donor->type === 'organization' ? 'Organisasi' : 'Individu';
$statusMap  = [
    'pending'   => ['label' => 'Menunggu', 'class' => 'text-bg-warning'],
    'verified'  => ['label' => 'Terverifikasi', 'class' => 'text-bg-success'],
    'cancelled' => ['label' => 'Dibatalkan', 'class' => 'text-bg-secondary'],
];
?>

<?php $this->section('content') ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-sm-row justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-xl"><?= esc(user_initials($donor->name)) ?></span>
                <div>
                    <h2 class="h5 mb-1"><?= esc($donor->name) ?></h2>
                    <div class="text-muted small mb-2"><code><?= esc($donor->code) ?></code></div>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge <?= $donor->type === 'organization' ? 'text-bg-primary' : 'text-bg-info' ?>"><?= esc($typeLabel) ?></span>
                        <span class="badge <?= $donor->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= $donor->status === 'active' ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                        <?php if ((int) $donor->is_anonymous === 1) : ?>
                            <span class="badge text-bg-dark"><i class="bi bi-incognito me-1"></i>Anonim</span>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-start">
                <a href="<?= url_to('donatur.index') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
                <?php if (can('donors.manage')) : ?>
                    <a href="<?= url_to('donatur.edit', $donor->id) ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil-square me-1"></i>Ubah Donatur
                    </a>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="h6 mb-0">Ringkasan Donasi</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Jumlah transaksi</div>
                    <div class="fs-4 fw-semibold"><?= esc((string) (int) $summary->donation_count) ?></div>
                </div>
                <div class="mb-0">
                    <div class="text-muted small">Total donasi uang terverifikasi</div>
                    <div class="fs-5 fw-semibold text-success">
                        Rp <?= esc(number_format((float) $summary->total_amount, 2, ',', '.')) ?>
                    </div>
                </div>
                <div class="text-muted small mt-3">
                    Nilai estimasi donasi barang tidak dijumlahkan ke total uang.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><h3 class="h6 mb-0">Informasi Donatur</h3></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted fw-normal">ID donatur</dt>
                    <dd class="col-sm-8"><?= esc((string) $donor->id) ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Email</dt>
                    <dd class="col-sm-8"><?= $donor->email !== null && $donor->email !== '' ? esc($donor->email) : '—' ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Telepon</dt>
                    <dd class="col-sm-8"><?= $donor->phone !== null && $donor->phone !== '' ? esc($donor->phone) : '—' ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Alamat</dt>
                    <dd class="col-sm-8"><?= $donor->address !== null && $donor->address !== '' ? nl2br(esc($donor->address)) : '—' ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Catatan</dt>
                    <dd class="col-sm-8"><?= $donor->notes !== null && $donor->notes !== '' ? nl2br(esc($donor->notes)) : '—' ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Dibuat oleh</dt>
                    <dd class="col-sm-8"><?= $creator !== null ? esc($creator->full_name) : '—' ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Dibuat</dt>
                    <dd class="col-sm-8"><?= esc(date('d/m/Y H:i', strtotime($donor->created_at))) ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Diperbarui</dt>
                    <dd class="col-sm-8 mb-0"><?= $donor->updated_at !== null ? esc(date('d/m/Y H:i', strtotime($donor->updated_at))) : '—' ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h3 class="h6 mb-0">Riwayat Donasi</h3>
            <span class="text-muted small">Transaksi yang tercatat atas nama donatur ini.</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Rincian</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($donations === []) : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                            Belum ada donasi yang tercatat untuk donatur ini.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($donations as $donation) : ?>
                        <?php $status = $statusMap[$donation->status] ?? ['label' => $donation->status, 'class' => 'text-bg-secondary']; ?>
                        <tr>
                            <td><code><?= esc($donation->transaction_number) ?></code></td>
                            <td class="small"><?= esc(date('d/m/Y', strtotime($donation->donation_date))) ?></td>
                            <td>
                                <span class="badge <?= $donation->donation_type === 'goods' ? 'text-bg-info' : 'text-bg-success' ?>">
                                    <?= $donation->donation_type === 'goods' ? 'Barang' : 'Uang' ?>
                                </span>
                            </td>
                            <td class="small">
                                <?php if ($donation->donation_type === 'goods') : ?>
                                    <?= esc($donation->goods_name ?? '—') ?>
                                    <?php if ($donation->goods_quantity !== null) : ?>
                                        · <?= esc(number_format((float) $donation->goods_quantity, 2, ',', '.')) ?> <?= esc($donation->goods_unit ?? '') ?>
                                    <?php endif ?>
                                <?php else : ?>
                                    Rp <?= esc(number_format((float) $donation->amount, 2, ',', '.')) ?>
                                <?php endif ?>
                            </td>
                            <td>
                                <span class="badge <?= esc($status['class'], 'attr') ?>"><?= esc($status['label']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
