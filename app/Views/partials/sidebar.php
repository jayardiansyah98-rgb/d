<?php
$me = current_user();
?>
<aside class="app-sidebar" id="appSidebar">
    <a class="sidebar-brand" href="<?= url_to('dashboard') ?>">
        <span class="sidebar-brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
        <span>
            <strong>Donasi Yayasan</strong>
            <small>Sistem Informasi</small>
        </span>
    </a>

    <nav class="sidebar-nav" aria-label="Navigasi utama">
        <span class="sidebar-heading">Utama</span>

        <a class="sidebar-link <?= url_is('dashboard') ? 'active' : '' ?>" href="<?= url_to('dashboard') ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <?php if (can('donors.view') || can('donations.view') || can('distributions.view')) : ?>
            <span class="sidebar-heading">Operasional</span>
        <?php endif ?>

        <?php if (can('donors.view')) : ?>
            <a class="sidebar-link <?= url_is('donatur*') ? 'active' : '' ?>" href="<?= url_to('donatur.index') ?>">
                <i class="bi bi-heart-fill"></i>
                <span>Donatur</span>
            </a>
        <?php endif ?>

        <?php if (can('donations.view')) : ?>
            <a class="sidebar-link <?= url_is('donasi*') ? 'active' : '' ?>" href="<?= url_to('donasi.index') ?>">
                <i class="bi bi-cash-coin"></i>
                <span>Penerimaan Donasi</span>
            </a>
        <?php endif ?>

        <?php if (can('distributions.view')) : ?>
            <a class="sidebar-link <?= url_is('distribusi*') ? 'active' : '' ?>" href="<?= url_to('distribusi.index') ?>">
                <i class="bi bi-box-seam"></i>
                <span>Distribusi Donasi</span>
            </a>
        <?php endif ?>

        <?php if (can('users.view')) : ?>
            <span class="sidebar-heading">Administrasi</span>
            <a class="sidebar-link <?= url_is('users*') ? 'active' : '' ?>" href="<?= url_to('users.index') ?>">
                <i class="bi bi-people-fill"></i>
                <span>Pengguna</span>
            </a>
        <?php endif ?>

        <?php if (can('roles.view')) : ?>
            <a class="sidebar-link <?= url_is('roles*') ? 'active' : '' ?>" href="<?= url_to('roles.index') ?>">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Role & Hak Akses</span>
            </a>
        <?php endif ?>

        <?php if (can('audit.view')) : ?>
            <a class="sidebar-link <?= url_is('audit-logs*') ? 'active' : '' ?>" href="<?= url_to('audit.index') ?>">
                <i class="bi bi-clipboard-data-fill"></i>
                <span>Audit Log</span>
            </a>
        <?php endif ?>

        <?php if (can('reports.view')) : ?>
            <span class="sidebar-heading">Laporan</span>
            <a class="sidebar-link <?= url_is('laporan/rekapitulasi*') || url_is('laporan') ? 'active' : '' ?>" href="<?= url_to('laporan.rekapitulasi') ?>">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>
                <span>Rekapitulasi Donasi</span>
            </a>
            <a class="sidebar-link <?= url_is('laporan/penerimaan*') ? 'active' : '' ?>" href="<?= url_to('laporan.penerimaan') ?>">
                <i class="bi bi-file-earmark-arrow-down-fill"></i>
                <span>Laporan Penerimaan</span>
            </a>
            <a class="sidebar-link <?= url_is('laporan/distribusi*') ? 'active' : '' ?>" href="<?= url_to('laporan.distribusi') ?>">
                <i class="bi bi-file-earmark-arrow-up-fill"></i>
                <span>Laporan Distribusi</span>
            </a>
        <?php endif ?>

        <span class="sidebar-heading">Akun</span>
        <a class="sidebar-link <?= url_is('profile*') ? 'active' : '' ?>" href="<?= url_to('profile.edit') ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profil Saya</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <span class="avatar avatar-sm"><?= esc(user_initials($me?->full_name)) ?></span>
        <div class="min-w-0">
            <strong><?= esc($me?->full_name ?? 'Pengguna') ?></strong>
            <small><?= esc($me?->role_name ?? '-') ?></small>
        </div>
    </div>
</aside>
