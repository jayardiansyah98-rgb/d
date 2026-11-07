<?php
$this->extend('layouts/auth');

$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];
?>

<?php $this->section('content') ?>
<div class="card auth-card border-0 shadow-lg">
    <div class="card-body p-4 p-md-5">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary-emphasis mb-2">Area Terbatas</span>
            <h1 class="h3 fw-bold mb-1">Selamat datang kembali</h1>
            <p class="text-muted mb-0">Masuk untuk mengelola data donasi yayasan.</p>
        </div>

        <form action="<?= url_to('login.attempt') ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="login" class="form-label">Username atau email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input
                        type="text"
                        class="form-control <?= isset($errors['login']) ? 'is-invalid' : '' ?>"
                        id="login"
                        name="login"
                        value="<?= esc(old('login')) ?>"
                        autocomplete="username"
                        maxlength="254"
                        required
                        autofocus
                    >
                </div>
                <?php if (isset($errors['login'])) : ?>
                    <div class="invalid-feedback d-block"><?= esc($errors['login']) ?></div>
                <?php endif ?>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Kata sandi</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input
                        type="password"
                        class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        maxlength="72"
                        required
                    >
                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['password'])) : ?>
                    <div class="invalid-feedback d-block"><?= esc($errors['password']) ?></div>
                <?php endif ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </button>
        </form>

        <div class="alert alert-light border mt-4 mb-0 small text-muted">
            <i class="bi bi-shield-check me-1 text-success"></i>
            Jangan bagikan kredensial Anda. Aktivitas login dicatat pada audit log.
        </div>
    </div>
</div>
<?php $this->endSection() ?>
