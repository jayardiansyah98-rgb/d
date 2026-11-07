<?php
$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];

$alerts = [
    'success' => ['class' => 'alert-success', 'icon' => 'bi-check-circle-fill'],
    'error'   => ['class' => 'alert-danger', 'icon' => 'bi-exclamation-triangle-fill'],
    'warning' => ['class' => 'alert-warning', 'icon' => 'bi-exclamation-circle-fill'],
    'info'    => ['class' => 'alert-info', 'icon' => 'bi-info-circle-fill'],
];
?>
<?php foreach ($alerts as $key => $alert) : ?>
    <?php $message = session()->getFlashdata($key); ?>
    <?php if (is_string($message) && $message !== '') : ?>
        <div class="alert <?= esc($alert['class'], 'attr') ?> alert-dismissible fade show app-alert" role="alert">
            <i class="bi <?= esc($alert['icon'], 'attr') ?> me-2"></i><?= esc($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif ?>
<?php endforeach ?>

<?php if ($errors !== []) : ?>
    <div class="alert alert-danger alert-dismissible fade show app-alert" role="alert">
        <div class="d-flex gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Periksa kembali data yang Anda masukkan.</strong>
                <ul class="mb-0 mt-1 ps-3">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif ?>
