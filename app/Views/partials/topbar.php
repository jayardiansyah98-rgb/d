<?php
$me = current_user();
?>
<header class="app-topbar">
    <button type="button" class="btn btn-light btn-icon d-lg-none" id="sidebarToggle" aria-label="Buka menu">
        <i class="bi bi-list"></i>
    </button>

    <div class="app-topbar-title">
        <span class="text-muted small d-none d-sm-block">Sistem Informasi Pengelolaan Donasi</span>
        <h1><?= esc($title ?? 'Dashboard') ?></h1>
    </div>

    <div class="dropdown ms-auto">
        <button class="btn btn-light d-flex align-items-center gap-2 user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar avatar-sm"><?= esc(user_initials($me?->full_name)) ?></span>
            <span class="text-start d-none d-md-block">
                <span class="d-block fw-semibold small"><?= esc($me?->full_name ?? 'Pengguna') ?></span>
                <span class="d-block text-muted user-role-label"><?= esc($me?->role_name ?? '-') ?></span>
            </span>
            <i class="bi bi-chevron-down small text-muted"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li>
                <a class="dropdown-item" href="<?= url_to('profile.edit') ?>">
                    <i class="bi bi-person me-2"></i>Profil Saya
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form action="<?= url_to('logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Keluar
                    </button>
                </form>
            </li>
        </ul>
    </div>
</header>
