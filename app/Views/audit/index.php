<?php
$this->extend('layouts/main');

$details     = $pager->getDetails('audit');
$total       = $details['total'];
$perPage     = $details['perPage'];
$currentPage = max(1, $details['currentPage']);
$from        = $total > 0 ? ($currentPage - 1) * $perPage + 1 : 0;
$to          = min($total, $currentPage * $perPage);
?>

<?php $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="h6 mb-0">Jejak Audit</h2>
            <span class="text-muted small">Catatan append-only untuk aktivitas penting aplikasi.</span>
        </div>
    </div>

    <div class="card-body border-bottom bg-light-subtle">
        <form action="<?= url_to('audit.index') ?>" method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label for="search" class="form-label small">Cari</label>
                <input type="search" class="form-control form-control-sm" id="search" name="search" value="<?= esc($search) ?>" placeholder="Deskripsi, nama pengguna, atau IP" maxlength="100">
            </div>
            <div class="col-8 col-md-4">
                <label for="action" class="form-label small">Aksi</label>
                <select class="form-select form-select-sm" id="action" name="action">
                    <option value="">Semua aksi</option>
                    <?php foreach ($actions as $option) : ?>
                        <option value="<?= esc($option, 'attr') ?>" <?= $action === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-4 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-grow-1"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="<?= url_to('audit.index') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 150px">Waktu</th>
                    <th>Aksi</th>
                    <th>Aktor</th>
                    <th>Deskripsi</th>
                    <th>IP</th>
                    <th class="text-end">Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs === []) : ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-clipboard-data fs-3 d-block mb-2"></i>
                            Tidak ada audit log yang sesuai.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <?php
                        $badge = 'text-bg-secondary';
                        if (str_contains($log->action, 'failed') || str_contains($log->action, 'locked')) {
                            $badge = 'text-bg-danger';
                        } elseif (str_contains($log->action, 'login') || str_contains($log->action, 'logout')) {
                            $badge = 'text-bg-success';
                        } elseif (str_starts_with($log->action, 'user.') || str_starts_with($log->action, 'profile.')) {
                            $badge = 'text-bg-primary';
                        } elseif (str_starts_with($log->action, 'role.')) {
                            $badge = 'text-bg-info';
                        }
                        ?>
                        <tr>
                            <td class="text-muted small text-nowrap"><?= esc(date('d/m/Y H:i:s', strtotime($log->created_at))) ?></td>
                            <td><span class="badge <?= esc($badge, 'attr') ?>"><?= esc($log->action) ?></span></td>
                            <td class="small">
                                <?php if ($log->actor_name !== null) : ?>
                                    <span class="fw-semibold"><?= esc($log->actor_name) ?></span>
                                    <span class="text-muted d-block">@<?= esc($log->actor_username) ?></span>
                                <?php else : ?>
                                    <span class="text-muted">Sistem / tamu</span>
                                <?php endif ?>
                            </td>
                            <td class="small"><?= esc($log->description) ?></td>
                            <td class="text-muted small text-nowrap"><?= esc($log->ip_address ?? '—') ?></td>
                            <td class="text-end">
                                <a href="<?= url_to('audit.show', $log->id) ?>" class="btn btn-sm btn-light" title="Lihat detail">
                                    <i class="bi bi-eye"></i>
                                </a>
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
                Menampilkan <?= esc((string) $from) ?>–<?= esc((string) $to) ?> dari <?= esc((string) $total) ?> catatan
            <?php else : ?>
                Tidak ada data
            <?php endif ?>
        </span>
        <?= $pager->links('audit', 'bootstrap5') ?>
    </div>
</div>
<?php $this->endSection() ?>
