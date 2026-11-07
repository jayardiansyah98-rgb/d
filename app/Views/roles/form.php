<?php
$this->extend('layouts/main');

$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];

$isEdit      = $role !== null;
$action      = $isEdit ? url_to('roles.update', $role->id) : url_to('roles.store');
$fieldError  = static fn (string $field): ?string => $errors[$field] ?? null;
$selected    = old('permissions', $rolePermissionIds);
$selected    = is_array($selected) ? array_map('intval', $selected) : [];
?>

<?php $this->section('content') ?>
<form action="<?= esc($action, 'attr') ?>" method="post" novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header">
                    <h2 class="h6 mb-0">Informasi Role</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama role <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= $fieldError('name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= esc(old('name', $role->name ?? '')) ?>" maxlength="80" required>
                        <?php if ($fieldError('name')) : ?><div class="invalid-feedback"><?= esc($fieldError('name')) ?></div><?php endif ?>
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= $fieldError('slug') ? 'is-invalid' : '' ?>" id="slug" name="slug" value="<?= esc(old('slug', $role->slug ?? '')) ?>" maxlength="50" pattern="[a-z0-9_-]+" required>
                        <?php if ($fieldError('slug')) : ?><div class="invalid-feedback"><?= esc($fieldError('slug')) ?></div><?php endif ?>
                        <div class="form-text">Huruf kecil, angka, garis bawah, atau tanda hubung. Contoh: <code>keuangan</code>.</div>
                    </div>

                    <div class="mb-0">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control <?= $fieldError('description') ? 'is-invalid' : '' ?>" id="description" name="description" rows="3" maxlength="255"><?= esc(old('description', $role->description ?? '')) ?></textarea>
                        <?php if ($fieldError('description')) : ?><div class="invalid-feedback"><?= esc($fieldError('description')) ?></div><?php endif ?>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Buat Role' ?>
                </button>
                <a href="<?= url_to('roles.index') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="h6 mb-0">Hak Akses</h2>
                        <span class="text-muted small">Pilih minimal satu hak akses untuk role ini.</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($permissionGroups === []) : ?>
                        <p class="text-muted mb-0">Belum ada hak akses yang tersedia.</p>
                    <?php else : ?>
                        <?php $groupIndex = 0; ?>
                        <?php foreach ($permissionGroups as $groupName => $permissions) : ?>
                            <?php $groupIndex++; ?>
                            <fieldset class="permission-group">
                                <legend class="permission-group-title">
                                    <span><?= esc($groupName) ?></span>
                                    <label class="form-check-label small text-primary" for="check-group-<?= esc((string) $groupIndex, 'attr') ?>">
                                        <input type="checkbox" class="form-check-input" id="check-group-<?= esc((string) $groupIndex, 'attr') ?>" data-check-group="group-<?= esc((string) $groupIndex, 'attr') ?>">
                                        Pilih semua
                                    </label>
                                </legend>

                                <?php foreach ($permissions as $permission) : ?>
                                    <div class="form-check permission-item">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="permissions[]"
                                            value="<?= esc((string) $permission->id, 'attr') ?>"
                                            id="permission-<?= esc((string) $permission->id, 'attr') ?>"
                                            data-group="group-<?= esc((string) $groupIndex, 'attr') ?>"
                                            <?= in_array((int) $permission->id, $selected, true) ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="permission-<?= esc((string) $permission->id, 'attr') ?>">
                                            <span class="d-block"><?= esc($permission->name) ?></span>
                                            <small class="text-muted"><?= esc($permission->slug) ?><?= $permission->description !== null ? ' · ' . esc($permission->description) : '' ?></small>
                                        </label>
                                    </div>
                                <?php endforeach ?>
                            </fieldset>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>

            <div class="alert alert-light border mt-4 mb-0 small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Perubahan hak akses berlaku pada permintaan berikutnya. Pengguna yang sedang masuk tidak perlu login ulang.
            </div>
        </div>
    </div>
</form>
<?php $this->endSection() ?>
