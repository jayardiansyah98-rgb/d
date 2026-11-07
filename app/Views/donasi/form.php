<?php
$this->extend('layouts/main');

$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];

$isEdit     = $donation !== null;
$action     = $isEdit ? url_to('donasi.update', $donation->id) : url_to('donasi.store');
$cancelUrl  = $isEdit ? url_to('donasi.show', $donation->id) : url_to('donasi.index');
$fieldError = static fn (string $field): ?string => $errors[$field] ?? null;

$type        = (string) old('donation_type', $isEdit ? $donation->donation_type : 'money');
$type        = in_array($type, ['money', 'goods'], true) ? $type : 'money';
$isAnonymous = (string) old('is_anonymous', $isEdit ? (string) $donation->is_anonymous : '0');
$donorId     = (string) old('donor_id', $isEdit ? (string) ($donation->donor_id ?? '') : '');
$date        = (string) old('donation_date', $isEdit ? $donation->donation_date : $defaultDate);
$payment     = (string) old('payment_method', $isEdit ? (string) ($donation->payment_method ?? 'cash') : 'cash');
$payment     = in_array($payment, ['cash', 'bank_transfer', 'other'], true) ? $payment : 'cash';

$moneyHidden = $type === 'money' ? '' : ' d-none';
$goodsHidden = $type === 'goods' ? '' : ' d-none';
?>

<?php $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-8">
        <form action="<?= esc($action, 'attr') ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Informasi Donasi</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="donation_type" class="form-label">Jenis donasi <span class="text-danger">*</span></label>
                            <select class="form-select <?= $fieldError('donation_type') ? 'is-invalid' : '' ?>" id="donation_type" name="donation_type" required>
                                <option value="money" <?= $type === 'money' ? 'selected' : '' ?>>Uang</option>
                                <option value="goods" <?= $type === 'goods' ? 'selected' : '' ?>>Barang</option>
                            </select>
                            <?php if ($fieldError('donation_type')) : ?><div class="invalid-feedback"><?= esc($fieldError('donation_type')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label for="donation_date" class="form-label">Tanggal donasi <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?= $fieldError('donation_date') ? 'is-invalid' : '' ?>" id="donation_date" name="donation_date" value="<?= esc($date, 'attr') ?>" max="<?= esc(date('Y-m-d'), 'attr') ?>" required>
                            <?php if ($fieldError('donation_date')) : ?><div class="invalid-feedback"><?= esc($fieldError('donation_date')) ?></div><?php endif ?>
                            <div class="form-text">Tanggal penerimaan donasi, tidak boleh melebihi hari ini.</div>
                        </div>

                        <div class="col-md-8">
                            <label for="donor_id" class="form-label">Donatur</label>
                            <select class="form-select <?= $fieldError('donor_id') ? 'is-invalid' : '' ?>" id="donor_id" name="donor_id">
                                <option value="">— Tanpa donatur (khusus donasi anonim) —</option>
                                <?php foreach ($donors as $donor) : ?>
                                    <?php
                                    $isArchived = $donor->deleted_at !== null;
                                    $label      = $donor->name . ' · ' . $donor->code;
                                    if ($isArchived) {
                                        $label .= ' (diarsipkan)';
                                    } elseif ($donor->status === 'inactive') {
                                        $label .= ' (nonaktif)';
                                    }
                                    ?>
                                    <option value="<?= esc((string) $donor->id, 'attr') ?>" <?= (string) $donor->id === $donorId ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <?php if ($fieldError('donor_id')) : ?><div class="invalid-feedback"><?= esc($fieldError('donor_id')) ?></div><?php endif ?>
                            <div class="form-text">
                                Wajib dipilih untuk donasi yang tidak anonim.
                                <?php if (can('donors.manage')) : ?>
                                    <a href="<?= url_to('donatur.create') ?>" class="text-decoration-none">Tambah donatur baru</a>.
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-block">Anonimitas</label>
                            <div class="form-check form-switch mt-2">
                                <input type="hidden" name="is_anonymous" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_anonymous" name="is_anonymous" value="1" <?= $isAnonymous === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_anonymous">Donasi anonim</label>
                            </div>
                            <?php if ($fieldError('is_anonymous')) : ?><div class="text-danger small"><?= esc($fieldError('is_anonymous')) ?></div><?php endif ?>
                            <div class="form-text">Identitas donatur tidak ditampilkan pada daftar dan detail donasi.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4<?= $moneyHidden ?>" id="moneyFields">
                <div class="card-header">
                    <h2 class="h6 mb-0">Rincian Donasi Uang</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Nominal <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control <?= $fieldError('amount') ? 'is-invalid' : '' ?>" id="amount" name="amount" value="<?= esc((string) old('amount', $isEdit ? (string) ($donation->amount ?? '') : ''), 'attr') ?>" min="0.01" step="0.01" inputmode="decimal" <?= $type === 'money' ? 'required' : '' ?>>
                                <?php if ($fieldError('amount')) : ?><div class="invalid-feedback"><?= esc($fieldError('amount')) ?></div><?php endif ?>
                            </div>
                            <div class="form-text">Gunakan titik untuk desimal, tanpa pemisah ribuan.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Metode pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select <?= $fieldError('payment_method') ? 'is-invalid' : '' ?>" id="payment_method" name="payment_method" <?= $type === 'money' ? 'required' : '' ?>>
                                <option value="cash" <?= $payment === 'cash' ? 'selected' : '' ?>>Tunai</option>
                                <option value="bank_transfer" <?= $payment === 'bank_transfer' ? 'selected' : '' ?>>Transfer bank</option>
                                <option value="other" <?= $payment === 'other' ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                            <?php if ($fieldError('payment_method')) : ?><div class="invalid-feedback"><?= esc($fieldError('payment_method')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label for="reference_number" class="form-label">Nomor referensi</label>
                            <input type="text" class="form-control <?= $fieldError('reference_number') ? 'is-invalid' : '' ?>" id="reference_number" name="reference_number" value="<?= esc((string) old('reference_number', $isEdit ? (string) ($donation->reference_number ?? '') : ''), 'attr') ?>" maxlength="100">
                            <?php if ($fieldError('reference_number')) : ?><div class="invalid-feedback"><?= esc($fieldError('reference_number')) ?></div><?php endif ?>
                            <div class="form-text">Wajib untuk transfer bank, misalnya nomor transaksi atau bukti transfer.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4<?= $goodsHidden ?>" id="goodsFields">
                <div class="card-header">
                    <h2 class="h6 mb-0">Rincian Donasi Barang</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="goods_name" class="form-label">Nama barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $fieldError('goods_name') ? 'is-invalid' : '' ?>" id="goods_name" name="goods_name" value="<?= esc((string) old('goods_name', $isEdit ? (string) ($donation->goods_name ?? '') : ''), 'attr') ?>" maxlength="150" <?= $type === 'goods' ? 'required' : '' ?>>
                            <?php if ($fieldError('goods_name')) : ?><div class="invalid-feedback"><?= esc($fieldError('goods_name')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-3">
                            <label for="goods_quantity" class="form-label">Kuantitas <span class="text-danger">*</span></label>
                            <input type="number" class="form-control <?= $fieldError('goods_quantity') ? 'is-invalid' : '' ?>" id="goods_quantity" name="goods_quantity" value="<?= esc((string) old('goods_quantity', $isEdit ? (string) ($donation->goods_quantity ?? '') : ''), 'attr') ?>" min="0.01" step="0.01" inputmode="decimal" <?= $type === 'goods' ? 'required' : '' ?>>
                            <?php if ($fieldError('goods_quantity')) : ?><div class="invalid-feedback"><?= esc($fieldError('goods_quantity')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-3">
                            <label for="goods_unit" class="form-label">Satuan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $fieldError('goods_unit') ? 'is-invalid' : '' ?>" id="goods_unit" name="goods_unit" value="<?= esc((string) old('goods_unit', $isEdit ? (string) ($donation->goods_unit ?? '') : ''), 'attr') ?>" maxlength="30" placeholder="mis. karung, dus, pcs" <?= $type === 'goods' ? 'required' : '' ?>>
                            <?php if ($fieldError('goods_unit')) : ?><div class="invalid-feedback"><?= esc($fieldError('goods_unit')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label for="estimated_value" class="form-label">Nilai estimasi</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control <?= $fieldError('estimated_value') ? 'is-invalid' : '' ?>" id="estimated_value" name="estimated_value" value="<?= esc((string) old('estimated_value', $isEdit ? (string) ($donation->estimated_value ?? '') : ''), 'attr') ?>" min="0" step="0.01" inputmode="decimal">
                                <?php if ($fieldError('estimated_value')) : ?><div class="invalid-feedback"><?= esc($fieldError('estimated_value')) ?></div><?php endif ?>
                            </div>
                            <div class="form-text">Opsional. Nilai estimasi tidak dijumlahkan sebagai penerimaan uang.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Catatan</h2>
                </div>
                <div class="card-body">
                    <label for="notes" class="form-label">Catatan tambahan</label>
                    <textarea class="form-control <?= $fieldError('notes') ? 'is-invalid' : '' ?>" id="notes" name="notes" rows="3" maxlength="2000"><?= esc((string) old('notes', $isEdit ? (string) ($donation->notes ?? '') : '')) ?></textarea>
                    <?php if ($fieldError('notes')) : ?><div class="invalid-feedback"><?= esc($fieldError('notes')) ?></div><?php endif ?>
                    <div class="form-text">Misalnya kondisi barang, keterangan transfer, atau informasi pendukung lain.</div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Donasi' ?>
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
                <p><strong class="text-body">Nomor transaksi</strong> dibuat otomatis dengan format <code>DON-YYYYMMDD-NNNN</code> setelah donasi disimpan.</p>
                <p><strong class="text-body">Donasi baru</strong> selalu berstatus menunggu. Verifikasi dilakukan setelah uang atau barang benar-benar diterima.</p>
                <p><strong class="text-body">Donasi uang</strong> memerlukan nominal dan metode pembayaran. Transfer bank wajib disertai nomor referensi.</p>
                <p><strong class="text-body">Donasi barang</strong> memerlukan nama barang, kuantitas, dan satuan. Nilai estimasi bersifat opsional.</p>
                <p class="mb-0"><strong class="text-body">Pembatalan</strong> wajib beralasan dan tidak menghapus data. Riwayat tetap tersimpan untuk audit.</p>
            </div>
        </div>

        <?php if ($isEdit) : ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Metadata</h2>
                </div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted fw-normal">Nomor</dt>
                        <dd class="col-7 mb-2"><code><?= esc($donation->transaction_number) ?></code></dd>
                        <dt class="col-5 text-muted fw-normal">Status</dt>
                        <dd class="col-7 mb-2"><?= $donation->status === 'pending' ? 'Menunggu verifikasi' : esc($donation->status) ?></dd>
                        <dt class="col-5 text-muted fw-normal">Dibuat</dt>
                        <dd class="col-7 mb-2"><?= esc(date('d/m/Y H:i', strtotime($donation->created_at))) ?></dd>
                        <dt class="col-5 text-muted fw-normal">Diperbarui</dt>
                        <dd class="col-7 mb-0"><?= $donation->updated_at !== null ? esc(date('d/m/Y H:i', strtotime($donation->updated_at))) : '—' ?></dd>
                    </dl>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
(() => {
    'use strict';

    const type = document.getElementById('donation_type');
    const moneyFields = document.getElementById('moneyFields');
    const goodsFields = document.getElementById('goodsFields');
    const amount = document.getElementById('amount');
    const payment = document.getElementById('payment_method');
    const reference = document.getElementById('reference_number');
    const goodsName = document.getElementById('goods_name');
    const goodsQuantity = document.getElementById('goods_quantity');
    const goodsUnit = document.getElementById('goods_unit');
    const anonymous = document.getElementById('is_anonymous');
    const donor = document.getElementById('donor_id');

    if (!type || !moneyFields || !goodsFields) {
        return;
    }

    const toggleReference = () => {
        if (reference) {
            reference.required = type.value === 'money' && payment && payment.value === 'bank_transfer';
        }
    };

    const toggleType = () => {
        const isMoney = type.value === 'money';

        moneyFields.classList.toggle('d-none', !isMoney);
        goodsFields.classList.toggle('d-none', isMoney);

        if (amount) {
            amount.required = isMoney;
        }
        if (payment) {
            payment.required = isMoney;
        }
        if (goodsName) {
            goodsName.required = !isMoney;
        }
        if (goodsQuantity) {
            goodsQuantity.required = !isMoney;
        }
        if (goodsUnit) {
            goodsUnit.required = !isMoney;
        }

        toggleReference();
    };

    const toggleAnonymous = () => {
        if (anonymous && donor) {
            donor.required = !anonymous.checked;
        }
    };

    type.addEventListener('change', toggleType);

    if (payment) {
        payment.addEventListener('change', toggleReference);
    }

    if (anonymous) {
        anonymous.addEventListener('change', toggleAnonymous);
    }

    toggleType();
    toggleAnonymous();
})();
</script>
<?php $this->endSection() ?>
