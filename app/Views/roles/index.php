<?php
$this->extend('layouts/main');
?>

<?php $this->section('content') ?>
<div class="card">
    <div class="card-header flex-wrap gap-2">
        <div>
            <h2 class="h6 mb-0">Daftar Role</h2>
            <span class="text-muted small">Atur kelompok pengguna dan hak aksesnya.</span>
        </div>
        <?php if (can('roles.manage')) : ?>
            <a href="<?= url_to('roles.create') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Role
            </a>
        <?php endif ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Deskripsi</th>
                    <th class="text-center">Pengguna</th>
                    <th class="text-center">Hak Akses</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($roles === []) : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">Belum ada role.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($roles as $role) : ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge <?= esc(role_badge_class($role->slug), 'attr') ?>"><?= esc($role->name) ?></span>
                                    <?php if ((int) $role->is_system === 1) : ?>
                                        <span class="badge text-bg-light border"><i class="bi bi-lock me-1"></i>Sistem</span>
                                    <?php endif ?>
                                </div>
                                <code class="small text-muted"><?= esc($role->slug) ?></code>
                            </td>
                            <td class="text-muted small"><?= esc($role->description ?? '—') ?></td>
                            <td class="text-center"><span class="badge text-bg-light border"><?= esc((string) $role->user_count) ?></span></td>
                            <td class="text-center"><span class="badge text-bg-light border"><?= esc((string) $role->permission_count) ?></span></td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if (can('roles.manage') && (int) $role->is_system !== 1) : ?>
                                        <a href="<?= url_to('roles.edit', $role->id) ?>" class="btn btn-sm btn-light" title="Ubah role">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="<?= url_to('roles.delete', $role->id) ?>" method="post" data-confirm="Hapus role ini? Role yang masih digunakan pengguna tidak dapat dihapus.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus role">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else : ?>
                                        <span class="text-muted small"><i class="bi bi-lock me-1"></i>Terkunci</span>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <span class="text-muted small">
            <i class="bi bi-shield-check me-1"></i>
            Role <strong>Administrator</strong> selalu memiliki seluruh hak akses dan tidak dapat diubah atau dihapus.
        </span>
    </div>
</div>
<?php $this->endSection() ?>
