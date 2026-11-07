<?php
$this->extend('layouts/main');

$details     = $pager->getDetails('donatur');
$total       = $details['total'];
$perPage     = $details['perPage'];
$currentPage = max(1, $details['currentPage']);
$from        = $total > 0 ? ($currentPage - 1) * $perPage + 1 : 0;
$to          = min($total, $currentPage * $perPage);
?>

<?php $this->section('content') ?>
<div class="card">
    <div class="card-header flex-wrap gap-2">
        <div>
            <h2 class="h6 mb-0">Daftar Donatur</h2>
            <span class="text-muted small">Kelola data donatur individu dan organisasi.</span>
        </div>
        <?php if (can('donors.manage')) : ?>
            <a href="<?= url_to('donatur.create') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i>Tambah Donatur
            </a>
        <?php endif ?>
    </div>

    <div class="card-body border-bottom bg-light-subtle">
        <form action="<?= url_to('donatur.index') ?>" method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label small">Cari</label>
                <input type="search" class="form-control form-control-sm" id="search" name="search" value="<?= esc($search) ?>" placeholder="Nama, kode, email, atau telepon" maxlength="100">
            </div>
            <div class="col-6 col-md-2">
                <label for="type" class="form-label small">Jenis</label>
                <select class="form-select form-select-sm" id="type" name="type">
                    <option value="">Semua</option>
                    <option value="individual" <?= $type === 'individual' ? 'selected' : '' ?>>Individu</option>
                    <option value="organization" <?= $type === 'organization' ? 'selected' : '' ?>>Organisasi</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="form-label small">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">Semua</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="<?= url_to('donatur.index') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Donatur</th>
                    <th>Jenis</th>
                    <th>Kontak</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($donors === []) : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-heart fs-3 d-block mb-2"></i>
                            Tidak ada donatur yang sesuai dengan filter.
                            <?php if (can('donors.manage')) : ?>
                                <div class="mt-3">
                                    <a href="<?= url_to('donatur.create') ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-person-plus me-1"></i>Tambah Donatur
                                    </a>
                                </div>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($donors as $donor) : ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar"><?= esc(user_initials($donor->name)) ?></span>
                                    <div>
                                        <div class="fw-semibold">
                                            <a href="<?= url_to('donatur.show', $donor->id) ?>" class="text-reset text-decoration-none">
                                                <?= esc($donor->name) ?>
                                            </a>
                                            <?php if ((int) $donor->is_anonymous === 1) : ?>
                                                <span class="badge text-bg-secondary ms-1" title="Donatur anonim">
                                                    <i class="bi bi-incognito"></i> Anonim
                                                </span>
                                            <?php endif ?>
                                        </div>
                                        <div class="text-muted small"><?= esc($donor->code) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $donor->type === 'organization' ? 'text-bg-primary' : 'text-bg-info' ?>">
                                    <?= $donor->type === 'organization' ? 'Organisasi' : 'Individu' ?>
                                </span>
                            </td>
                            <td class="small">
                                <?php if ($donor->phone !== null && $donor->phone !== '') : ?>
                                    <div><i class="bi bi-telephone me-1 text-muted"></i><?= esc($donor->phone) ?></div>
                                <?php endif ?>
                                <?php if ($donor->email !== null && $donor->email !== '') : ?>
                                    <div class="text-muted"><i class="bi bi-envelope me-1"></i><?= esc($donor->email) ?></div>
                                <?php endif ?>
                                <?php if (($donor->phone === null || $donor->phone === '') && ($donor->email === null || $donor->email === '')) : ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <span class="badge <?= $donor->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $donor->status === 'active' ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= url_to('donatur.show', $donor->id) ?>" class="btn btn-sm btn-light" title="Detail donatur">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <?php if (can('donors.manage')) : ?>
                                        <a href="<?= url_to('donatur.edit', $donor->id) ?>" class="btn btn-sm btn-light" title="Ubah donatur">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form action="<?= url_to('donatur.status', $donor->id) ?>" method="post" data-confirm="Ubah status donatur ini?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm <?= $donor->status === 'active' ? 'btn-outline-secondary' : 'btn-outline-success' ?>" title="<?= $donor->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="bi <?= $donor->status === 'active' ? 'bi-person-dash' : 'bi-person-check' ?>"></i>
                                            </button>
                                        </form>

                                        <form action="<?= url_to('donatur.delete', $donor->id) ?>" method="post" data-confirm="Arsipkan donatur ini? Riwayat donasi tetap tersimpan dan donatur tidak akan muncul pada pilihan baru.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Arsipkan">
                                                <i class="bi bi-archive"></i>
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
                Menampilkan <?= esc((string) $from) ?>–<?= esc((string) $to) ?> dari <?= esc((string) $total) ?> donatur
            <?php else : ?>
                Tidak ada data
            <?php endif ?>
        </span>
        <?= $pager->links('donatur', 'bootstrap5') ?>
    </div>
</div>
<?php $this->endSection() ?>
