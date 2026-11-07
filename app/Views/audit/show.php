<?php
$this->extend('layouts/main');

$decode = static function (?string $json): ?array {
    if ($json === null || $json === '') {
        return null;
    }

    $decoded = json_decode($json, true);

    return is_array($decoded) ? $decoded : null;
};

$oldValues = $decode($log->old_values);
$newValues = $decode($log->new_values);

$pretty = static function (?array $values): string {
    if ($values === null) {
        return '—';
    }

    return (string) json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
};
?>

<?php $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 mb-0">Detail Aktivitas</h2>
                    <span class="text-muted small">Audit #<?= esc((string) $log->id) ?></span>
                </div>
                <a href="<?= url_to('audit.index') ?>" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3 text-muted fw-normal">Waktu</dt>
                    <dd class="col-sm-9"><?= esc(date('d/m/Y H:i:s', strtotime($log->created_at))) ?></dd>

                    <dt class="col-sm-3 text-muted fw-normal">Aksi</dt>
                    <dd class="col-sm-9"><code><?= esc($log->action) ?></code></dd>

                    <dt class="col-sm-3 text-muted fw-normal">Aktor</dt>
                    <dd class="col-sm-9">
                        <?php if ($actor !== null) : ?>
                            <?= esc($actor->full_name) ?> <span class="text-muted">(@<?= esc($actor->username) ?>)</span>
                        <?php else : ?>
                            <span class="text-muted">Sistem / tamu</span>
                        <?php endif ?>
                    </dd>

                    <dt class="col-sm-3 text-muted fw-normal">Entitas</dt>
                    <dd class="col-sm-9">
                        <?php if ($log->entity_type !== null) : ?>
                            <code><?= esc($log->entity_type) ?></code>
                            <?= $log->entity_id !== null ? '#' . esc((string) $log->entity_id) : '' ?>
                        <?php else : ?>
                            <span class="text-muted">—</span>
                        <?php endif ?>
                    </dd>

                    <dt class="col-sm-3 text-muted fw-normal">Deskripsi</dt>
                    <dd class="col-sm-9"><?= esc($log->description) ?></dd>

                    <dt class="col-sm-3 text-muted fw-normal">Alamat IP</dt>
                    <dd class="col-sm-9"><?= esc($log->ip_address ?? '—') ?></dd>

                    <dt class="col-sm-3 text-muted fw-normal">User agent</dt>
                    <dd class="col-sm-9 small text-break"><?= esc($log->user_agent ?? '—') ?></dd>
                </dl>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="h6 mb-0">Nilai Sebelum</h3></div>
                    <div class="card-body p-0">
                        <pre class="audit-json mb-0"><?= esc($pretty($oldValues)) ?></pre>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="h6 mb-0">Nilai Sesudah</h3></div>
                    <div class="card-body p-0">
                        <pre class="audit-json mb-0"><?= esc($pretty($newValues)) ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h2 class="h6 mb-0">Catatan</h2></div>
            <div class="card-body small text-muted">
                <p>Audit log bersifat <strong class="text-body">append-only</strong>. Tidak ada fitur untuk mengubah atau menghapus catatan melalui aplikasi.</p>
                <p class="mb-0">Nilai sensitif seperti kata sandi, token, dan hash tidak pernah disimpan pada kolom nilai sebelum/sesudah.</p>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
