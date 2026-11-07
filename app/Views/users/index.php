<?php
$this->extend('layouts/main');

$details     = $pager->getDetails('users');
$total       = $details['total'];
$perPage     = $details['perPage'];
$currentPage = max(1, $details['currentPage']);
$from        = $total > 0 ? ($currentPage - 1) * $perPage + 1 : 0;
$to          = min($total, $currentPage * $perPage);
$actorId     = (int) (current_user()?->id ?? 0);
?>

<?php $this->section('content') ?>
<div class="card">
    <div class="card-header flex-wrap gap-2">
        <div>
            <h2 class="h6 mb-0">Daftar Pengguna</h2>
            <span class="text-muted small">Kelola akun, role, dan status akses.</span>
        </div>
        <?php if (can('users.create')) : ?>
            <a href="<?= url_to('users.create') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i>Tambah Pengguna
            </a>
        <?php endif ?>
    </div>

    <div class="card-body border-bottom bg-light-subtle">
        <form action="<?= url_to('users.index') ?>" method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label small">Cari</label>
                <input type="search" class="form-control form-control-sm" id="search" name="search" value="<?= esc($search) ?>" placeholder="Nama, username, atau email" maxlength="100">
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="form-label small">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">Semua</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="role_id" class="form-label small">Role</label>
                <select class="form-select form-select-sm" id="role_id" name="role_id">
                    <option value="0">Semua role</option>
                    <?php foreach ($roles as $role) : ?>
                        <option value="<?= esc((string) $role->id, 'attr') ?>" <?= $roleId === (int) $role->id ? 'selected' : '' ?>>
                            <?= esc($role->name) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="<?= url_to('users.index') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users === []) : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-people fs-3 d-block mb-2"></i>
                            Tidak ada pengguna yang sesuai dengan filter.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar"><?= esc(user_initials($user->full_name)) ?></span>
                                    <div>
                                        <div class="fw-semibold">
                                            <?= esc($user->full_name) ?>
                                            <?php if ((int) $user->id === $actorId) : ?>
                                                <span class="badge text-bg-light border ms-1">Anda</span>
                                            <?php endif ?>
                                        </div>
                                        <div class="text-muted small">@<?= esc($user->username) ?> · <?= esc($user->email) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= esc(role_badge_class($user->role_slug), 'attr') ?>"><?= esc($user->role_name) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $user->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $user->status === 'active' ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="text-muted small">
                                <?= $user->last_login_at !== null ? esc(date('d/m/Y H:i', strtotime($user->last_login_at))) : 'Belum pernah' ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if (can('users.update')) : ?>
                                        <a href="<?= url_to('users.edit', $user->id) ?>" class="btn btn-sm btn-light" title="Ubah pengguna">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <?php if ((int) $user->id !== $actorId) : ?>
                                            <form action="<?= url_to('users.status', $user->id) ?>" method="post" data-confirm="Ubah status pengguna ini?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm <?= $user->status === 'active' ? 'btn-outline-secondary' : 'btn-outline-success' ?>" title="<?= $user->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                    <i class="bi <?= $user->status === 'active' ? 'bi-person-dash' : 'bi-person-check' ?>"></i>
                                                </button>
                                            </form>
                                        <?php endif ?>
                                    <?php endif ?>

                                    <?php if (can('users.delete') && (int) $user->id !== $actorId) : ?>
                                        <form action="<?= url_to('users.delete', $user->id) ?>" method="post" data-confirm="Arsipkan pengguna ini? Pengguna tidak akan bisa login, tetapi riwayatnya tetap tersimpan.">
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
                Menampilkan <?= esc((string) $from) ?>–<?= esc((string) $to) ?> dari <?= esc((string) $total) ?> pengguna
            <?php else : ?>
                Tidak ada data
            <?php endif ?>
        </span>
        <?= $pager->links('users', 'bootstrap5') ?>
    </div>
</div>
<?php $this->endSection() ?>
