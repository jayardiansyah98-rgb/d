<?php
$this->extend('layouts/main');

$errors     = session()->getFlashdata('errors');
$errors     = is_array($errors) ? $errors : [];
$fieldError = static fn (string $field): ?string => $errors[$field] ?? null;
?>

<?php $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <span class="avatar avatar-xl mx-auto mb-3"><?= esc(user_initials($user->full_name)) ?></span>
                <h2 class="h5 mb-1"><?= esc($user->full_name) ?></h2>
                <p class="text-muted small mb-2">@<?= esc($user->username) ?></p>
                <span class="badge <?= esc(role_badge_class($user->role_slug), 'attr') ?>"><?= esc($user->role_name) ?></span>

                <hr class="my-4">

                <dl class="row text-start small mb-0">
                    <dt class="col-5 text-muted fw-normal">Email</dt>
                    <dd class="col-7 mb-2 text-break"><?= esc($user->email) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Telepon</dt>
                    <dd class="col-7 mb-2"><?= esc($user->phone ?? '—') ?></dd>
                    <dt class="col-5 text-muted fw-normal">Login terakhir</dt>
                    <dd class="col-7 mb-0">
                        <?= $user->last_login_at !== null ? esc(date('d/m/Y H:i', strtotime($user->last_login_at))) : '—' ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card mb-4">
            <div class="card-header">
                <div>
                    <h2 class="h6 mb-0">Data Profil</h2>
                    <span class="text-muted small">Perbarui informasi kontak akun Anda.</span>
                </div>
            </div>
            <div class="card-body">
                <form action="<?= url_to('profile.update') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Nama lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $fieldError('full_name') ? 'is-invalid' : '' ?>" id="full_name" name="full_name" value="<?= esc(old('full_name', $user->full_name)) ?>" maxlength="100" required>
                            <?php if ($fieldError('full_name')) : ?><div class="invalid-feedback"><?= esc($fieldError('full_name')) ?></div><?php endif ?>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?= $fieldError('email') ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= esc(old('email', $user->email)) ?>" maxlength="254" required>
                            <?php if ($fieldError('email')) : ?><div class="invalid-feedback"><?= esc($fieldError('email')) ?></div><?php endif ?>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Nomor telepon</label>
                            <input type="tel" class="form-control <?= $fieldError('phone') ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= esc(old('phone', $user->phone ?? '')) ?>" maxlength="30">
                            <?php if ($fieldError('phone')) : ?><div class="invalid-feedback"><?= esc($fieldError('phone')) ?></div><?php endif ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= esc($user->username) ?>" disabled>
                            <div class="form-text">Username hanya dapat diubah oleh administrator.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="bi bi-save me-1"></i>Simpan Profil
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="h6 mb-0">Ubah Kata Sandi</h2>
                    <span class="text-muted small">Gunakan kata sandi yang kuat dan unik.</span>
                </div>
            </div>
            <div class="card-body">
                <form action="<?= url_to('profile.password') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="current_password" class="form-label">Kata sandi saat ini <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?= $fieldError('current_password') ? 'is-invalid' : '' ?>" id="current_password" name="current_password" autocomplete="current-password" maxlength="72" required>
                            <?php if ($fieldError('current_password')) : ?><div class="invalid-feedback"><?= esc($fieldError('current_password')) ?></div><?php endif ?>
                        </div>

                        <div class="col-md-6">
                            <label for="new_password" class="form-label">Kata sandi baru <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?= $fieldError('new_password') ? 'is-invalid' : '' ?>" id="new_password" name="new_password" autocomplete="new-password" maxlength="72" required>
                            <?php if ($fieldError('new_password')) : ?><div class="invalid-feedback"><?= esc($fieldError('new_password')) ?></div><?php endif ?>
                            <div class="form-text">Minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="new_password_confirm" class="form-label">Konfirmasi kata sandi baru <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?= $fieldError('new_password_confirm') ? 'is-invalid' : '' ?>" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" maxlength="72" required>
                            <?php if ($fieldError('new_password_confirm')) : ?><div class="invalid-feedback"><?= esc($fieldError('new_password_confirm')) ?></div><?php endif ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning mt-4">
                        <i class="bi bi-key me-1"></i>Perbarui Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
