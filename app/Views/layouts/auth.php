<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= csrf_meta() ?>
    <title><?= esc(($title ?? 'Masuk') . ' · Sistem Informasi Donasi Yayasan') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
<main class="auth-wrapper">
    <div class="auth-brand">
        <span class="sidebar-brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
        <div>
            <strong>Donasi Yayasan</strong>
            <small>Sistem Informasi Pengelolaan Donasi</small>
        </div>
    </div>

    <?= $this->include('partials/alerts') ?>

    <?= $this->renderSection('content') ?>

    <p class="text-center text-muted small mt-4 mb-0">
        &copy; <?= date('Y') ?> Yayasan · Akses terbatas untuk pengguna terdaftar.
    </p>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
