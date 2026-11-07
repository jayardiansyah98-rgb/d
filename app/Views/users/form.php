<?php
$this->extend('layouts/main');

$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];

$isEdit      = $user !== null;
$action      = $isEdit ? url_to('users.update', $user->id) : url_to('users.store');
$cancelUrl   = url_to('users.index');
$fieldError  = static fn (string $field): ?string => $errors[$field] ?? null;

$roleId  = old('role_id', $isEdit ? (string) $user->role_id : '');
$status  = old('status', $isEdit ? $user->status : 'active');
?>

<?php $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-8">
        <form action="<?= esc($action, 'attr') ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Informasi Pengguna</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Nama lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $fieldError('full_name') ? 'is-invalid' : '' ?>" id="full_name" name="full_name" value="<?= esc(old('full_name', $user->full_name ?? '')) ?>" maxlength="100" required>
                            <?php if ($fieldError('full_name')) : ?><div class="invalid-feedback"><?= esc($fieldError('full_name')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?= $fieldError('email') ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= esc(old('email', $user->email ?? '')) ?>" maxlength="254" required>
                            <?php if ($fieldError('email')) : ?><div class="invalid-feedback"><?= esc($fieldError('email')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" class="form-control <?= $fieldError('username') ? 'is-invalid' : '' ?>" id="username" name="username" value="<?= esc(old('username', $user->username ?? '')) ?>" maxlength="50" pattern="[A-Za-z0-9_-]+" required>
                                <?php if ($fieldError('username')) : ?><div class="invalid-feedback"><?= esc($fieldError('username')) ?></div><?php endif ?>
                            </div>
                            <div class="form-text">Huruf, angka, garis bawah, atau tanda hubung.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Nomor telepon</label>
                            <input type="tel" class="form-control <?= $fieldError('phone') ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= esc(old('phone', $user->phone ?? '')) ?>" maxlength="30">
                            <?php if ($fieldError('phone')) : ?><div class="invalid-feedback"><?= esc($fieldError('phone')) ?></div><?php endif ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Hak Akses & Status</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select <?= $fieldError('role_id') ? 'is-invalid' : '' ?>" id="role_id" name="role_id" required>
                                <option value="">Pilih role</option>
                                <?php foreach ($roles as $role) : ?>
                                    <option value="<?= esc((string) $role->id, 'attr') ?>" <?= (string) $roleId === (string) $role->id ? 'selected' : '' ?>>
                                        <?= esc($role->name) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <?php if ($fieldError('role_id')) : ?><div class="invalid-feedback"><?= esc($fieldError('role_id')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select <?= $fieldError('status') ? 'is-invalid' : '' ?>" id="status" name="status" required>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                            <?php if ($fieldError('status')) : ?><div class="invalid-feedback"><?= esc($fieldError('status')) ?></div><?php endif ?>
                            <div class="form-text">Pengguna nonaktif tidak dapat masuk ke aplikasi.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0"><?= $isEdit ? 'Reset Kata Sandi' : 'Kata Sandi' ?></h2>
                </div>
                <div class="card-body">
                    <?php if ($isEdit) : ?>
                        <div class="alert alert-light border small py-2">
                            <i class="bi bi-info-circle me-1"></i>Kosongkan kedua kolom jika kata sandi tidak diubah.
                        </div>
                    <?php endif ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Kata sandi <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control <?= $fieldError('password') ? 'is-invalid' : '' ?>" id="password" name="password" autocomplete="new-password" maxlength="72" <?= $isEdit ? '' : 'required' ?>>
                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
                                <?php if ($fieldError('password')) : ?><div class="invalid-feedback"><?= esc($fieldError('password')) ?></div><?php endif ?>
                            </div>
                            <div class="form-text">Minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirm" class="form-label">Konfirmasi kata sandi <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                            <input type="password" class="form-control <?= $fieldError('password_confirm') ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" autocomplete="new-password" maxlength="72" <?= $isEdit ? '' : 'required' ?>>
                            <?php if ($fieldError('password_confirm')) : ?><div class="invalid-feedback"><?= esc($fieldError('password_confirm')) ?></div><?php endif ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Buat Pengguna' ?>
                </button>
                <a href="<?= esc($cancelUrl, 'attr') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Panduan</h2>
            </div>
            <div class="card-body small text-muted">
                <p><strong class="text-body">Role</strong> menentukan menu dan tindakan yang dapat diakses pengguna.</p>
                <p><strong class="text-body">Status nonaktif</strong> mempertahankan data pengguna, tetapi menolak semua login.</p>
                <p class="mb-0"><strong class="text-body">Arsip</strong> menggunakan soft delete. Data tidak dihapus permanen dan tetap dapat ditelusuri melalui audit log.</p>
            </div>
        </div>

        <?php if ($isEdit) : ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Metadata</h2>
                </div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted fw-normal">Dibuat</dt>
                        <dd class="col-7 mb-2"><?= esc(date('d/m/Y H:i', strtotime($user->created_at))) ?></dd>
                        <dt class="col-5 text-muted fw-normal">Diperbarui</dt>
                        <dd class="col-7 mb-2"><?= $user->updated_at !== null ? esc(date('d/m/Y H:i', strtotime($user->updated_at))) : '—' ?></dd>
                        <dt class="col-5 text-muted fw-normal">Login terakhir</dt>
                        <dd class="col-7 mb-0"><?= $user->last_login_at !== null ? esc(date('d/m/Y H:i', strtotime($user->last_login_at))) : 'Belum pernah' ?></dd>
                    </dl>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>
<?php $this->endSection() ?>
