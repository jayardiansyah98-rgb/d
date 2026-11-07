<?php
$this->extend('layouts/main');

$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];

$isEdit     = $donor !== null;
$action     = $isEdit ? url_to('donatur.update', $donor->id) : url_to('donatur.store');
$cancelUrl  = $isEdit ? url_to('donatur.show', $donor->id) : url_to('donatur.index');
$fieldError = static fn (string $field): ?string => $errors[$field] ?? null;

$type        = old('type', $isEdit ? $donor->type : 'individual');
$status      = old('status', $isEdit ? $donor->status : 'active');
$isAnonymous = old('is_anonymous', $isEdit ? (string) $donor->is_anonymous : '0');
?>

<?php $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-8">
        <form action="<?= esc($action, 'attr') ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Identitas Donatur</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nama donatur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $fieldError('name') ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= esc(old('name', $donor->name ?? '')) ?>" maxlength="150" required>
                            <?php if ($fieldError('name')) : ?><div class="invalid-feedback"><?= esc($fieldError('name')) ?></div><?php endif ?>
                            <div class="form-text">Nama individu atau nama lembaga/organisasi.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Jenis donatur <span class="text-danger">*</span></label>
                            <select class="form-select <?= $fieldError('type') ? 'is-invalid' : '' ?>" id="type" name="type" required>
                                <option value="individual" <?= $type === 'individual' ? 'selected' : '' ?>>Individu</option>
                                <option value="organization" <?= $type === 'organization' ? 'selected' : '' ?>>Organisasi / Lembaga</option>
                            </select>
                            <?php if ($fieldError('type')) : ?><div class="invalid-feedback"><?= esc($fieldError('type')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?= $fieldError('email') ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= esc(old('email', $donor->email ?? '')) ?>" maxlength="254">
                            <?php if ($fieldError('email')) : ?><div class="invalid-feedback"><?= esc($fieldError('email')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Nomor telepon</label>
                            <input type="tel" class="form-control <?= $fieldError('phone') ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= esc(old('phone', $donor->phone ?? '')) ?>" maxlength="30">
                            <?php if ($fieldError('phone')) : ?><div class="invalid-feedback"><?= esc($fieldError('phone')) ?></div><?php endif ?>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea class="form-control <?= $fieldError('address') ? 'is-invalid' : '' ?>" id="address" name="address" rows="2" maxlength="1000"><?= esc(old('address', $donor->address ?? '')) ?></textarea>
                            <?php if ($fieldError('address')) : ?><div class="invalid-feedback"><?= esc($fieldError('address')) ?></div><?php endif ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Preferensi & Status</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select <?= $fieldError('status') ? 'is-invalid' : '' ?>" id="status" name="status" required>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                            <?php if ($fieldError('status')) : ?><div class="invalid-feedback"><?= esc($fieldError('status')) ?></div><?php endif ?>
                            <div class="form-text">Donatur nonaktif tetap tersimpan, tetapi tidak diprioritaskan pada pencatatan baru.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Anonimitas</label>
                            <div class="form-check form-switch mt-2">
                                <input type="hidden" name="is_anonymous" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_anonymous" name="is_anonymous" value="1" <?= $isAnonymous === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_anonymous">Tandai sebagai donatur anonim</label>
                            </div>
                            <?php if ($fieldError('is_anonymous')) : ?><div class="text-danger small"><?= esc($fieldError('is_anonymous')) ?></div><?php endif ?>
                            <div class="form-text">Anonimitas transaksi tetap ditentukan pada masing-masing donasi.</div>
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea class="form-control <?= $fieldError('notes') ? 'is-invalid' : '' ?>" id="notes" name="notes" rows="3" maxlength="2000"><?= esc(old('notes', $donor->notes ?? '')) ?></textarea>
                            <?php if ($fieldError('notes')) : ?><div class="invalid-feedback"><?= esc($fieldError('notes')) ?></div><?php endif ?>
                            <div class="form-text">Informasi tambahan, misalnya preferensi penyaluran atau kontak alternatif.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Donatur' ?>
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
                <p><strong class="text-body">Kode donatur</strong> dibuat otomatis dengan format <code>DNR-YYYYMMDD-NNNN</code> dan tidak dapat diubah.</p>
                <p><strong class="text-body">Email dan telepon</strong> bersifat opsional. Isi minimal salah satu agar donatur mudah dihubungi. Email tidak boleh sama dengan donatur lain yang belum diarsipkan, dan kombinasi nama + nomor telepon juga diperiksa untuk mencegah duplikasi.</p>
                <p class="mb-0"><strong class="text-body">Arsip</strong> menggunakan soft delete. Data donatur dan riwayat donasinya tidak dihapus permanen.</p>
            </div>
        </div>

        <?php if ($isEdit) : ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Metadata</h2>
                </div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted fw-normal">Kode</dt>
                        <dd class="col-7 mb-2"><code><?= esc($donor->code) ?></code></dd>
                        <dt class="col-5 text-muted fw-normal">Dibuat</dt>
                        <dd class="col-7 mb-2"><?= esc(date('d/m/Y H:i', strtotime($donor->created_at))) ?></dd>
                        <dt class="col-5 text-muted fw-normal">Diperbarui</dt>
                        <dd class="col-7 mb-0"><?= $donor->updated_at !== null ? esc(date('d/m/Y H:i', strtotime($donor->updated_at))) : '—' ?></dd>
                    </dl>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>
<?php $this->endSection() ?>
