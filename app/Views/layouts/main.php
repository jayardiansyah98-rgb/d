<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= csrf_meta() ?>
    <title><?= esc(($title ?? 'Dashboard') . ' · Sistem Informasi Donasi Yayasan') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?= $this->include('partials/sidebar') ?>

    <div class="app-main">
        <?= $this->include('partials/topbar') ?>

        <main class="app-content">
            <?= $this->include('partials/alerts') ?>
            <?= $this->renderSection('content') ?>
        </main>

        <?= $this->include('partials/footer') ?>
    </div>
</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
