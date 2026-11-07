<?php
$this->extend('layouts/main');

$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];

$isEdit     = $distribution !== null;
$action     = $isEdit ? url_to('distribusi.update', $distribution->id) : url_to('distribusi.store');
$cancelUrl  = $isEdit ? url_to('distribusi.show', $distribution->id) : url_to('distribusi.index');
$fieldError = static fn (string $field): ?string => $errors[$field] ?? null;

$type = (string) old('distribution_type', $isEdit ? $distribution->distribution_type : 'money');
$type = in_array($type, ['money', 'goods'], true) ? $type : 'money';

$date = (string) old('distribution_date', $isEdit ? $distribution->distribution_date : $defaultDate);

$recipientType = (string) old('recipient_type', $isEdit ? $distribution->recipient_type : 'individual');
$recipientType = in_array($recipientType, ['individual', 'organization', 'community'], true) ? $recipientType : 'individual';

$moneyHidden = $type === 'money' ? '' : ' d-none';
$goodsHidden = $type === 'goods' ? '' : ' d-none';

$oldItems = old('items', []);

if (! is_array($oldItems) || $oldItems === []) {
    $oldItems = [];

    foreach ($items as $item) {
        $oldItems[] = [
            'item_name' => (string) $item->item_name,
            'quantity'  => (string) $item->quantity,
            'unit'      => (string) $item->unit,
        ];
    }
}

if ($oldItems === []) {
    $oldItems[] = ['item_name' => '', 'quantity' => '', 'unit' => ''];
}

$availableGoods = array_values(array_filter(
    $goodsBalances,
    static fn (array $balance): bool => $balance['available_cents'] > 0,
));

$itemError = static function (int $index, string $field) use ($errors): ?string {
    return $errors['items.' . $index . '.' . $field] ?? null;
};
?>

<?php $this->section('content') ?>
<div class="row g-4">
    <div class="col-xl-8">
        <form action="<?= esc($action, 'attr') ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Informasi Penyaluran</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="distribution_type" class="form-label">Jenis bantuan <span class="text-danger">*</span></label>
                            <select class="form-select <?= $fieldError('distribution_type') ? 'is-invalid' : '' ?>" id="distribution_type" name="distribution_type" required>
                                <option value="money" <?= $type === 'money' ? 'selected' : '' ?>>Uang</option>
                                <option value="goods" <?= $type === 'goods' ? 'selected' : '' ?>>Barang</option>
                            </select>
                            <?php if ($fieldError('distribution_type')) : ?><div class="invalid-feedback"><?= esc($fieldError('distribution_type')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label for="distribution_date" class="form-label">Tanggal penyaluran <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?= $fieldError('distribution_date') ? 'is-invalid' : '' ?>" id="distribution_date" name="distribution_date" value="<?= esc($date, 'attr') ?>" max="<?= esc(date('Y-m-d'), 'attr') ?>" required>
                            <?php if ($fieldError('distribution_date')) : ?><div class="invalid-feedback"><?= esc($fieldError('distribution_date')) ?></div><?php endif ?>
                            <div class="form-text">Tanggal penyaluran, tidak boleh melebihi hari ini.</div>
                        </div>

                        <div class="col-md-8">
                            <label for="recipient_name" class="form-label">Nama penerima manfaat / lembaga <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $fieldError('recipient_name') ? 'is-invalid' : '' ?>" id="recipient_name" name="recipient_name" value="<?= esc((string) old('recipient_name', $isEdit ? (string) $distribution->recipient_name : ''), 'attr') ?>" maxlength="150" required>
                            <?php if ($fieldError('recipient_name')) : ?><div class="invalid-feedback"><?= esc($fieldError('recipient_name')) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-4">
                            <label for="recipient_type" class="form-label">Jenis penerima <span class="text-danger">*</span></label>
                            <select class="form-select <?= $fieldError('recipient_type') ? 'is-invalid' : '' ?>" id="recipient_type" name="recipient_type" required>
                                <option value="individual" <?= $recipientType === 'individual' ? 'selected' : '' ?>>Individu</option>
                                <option value="organization" <?= $recipientType === 'organization' ? 'selected' : '' ?>>Lembaga/organisasi</option>
                                <option value="community" <?= $recipientType === 'community' ? 'selected' : '' ?>>Komunitas</option>
                            </select>
                            <?php if ($fieldError('recipient_type')) : ?><div class="invalid-feedback"><?= esc($fieldError('recipient_type')) ?></div><?php endif ?>
                        </div>

                        <div class="col-12">
                            <label for="recipient_address" class="form-label">Alamat penerima</label>
                            <textarea class="form-control <?= $fieldError('recipient_address') ? 'is-invalid' : '' ?>" id="recipient_address" name="recipient_address" rows="2" maxlength="1000"><?= esc((string) old('recipient_address', $isEdit ? (string) ($distribution->recipient_address ?? '') : '')) ?></textarea>
                            <?php if ($fieldError('recipient_address')) : ?><div class="invalid-feedback"><?= esc($fieldError('recipient_address')) ?></div><?php endif ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4<?= $moneyHidden ?>" id="moneyFields">
                <div class="card-header">
                    <h2 class="h6 mb-0">Rincian Bantuan Uang</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Nominal penyaluran <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control <?= $fieldError('amount') ? 'is-invalid' : '' ?>" id="amount" name="amount" value="<?= esc((string) old('amount', $isEdit ? (string) ($distribution->amount ?? '') : ''), 'attr') ?>" min="0.01" step="0.01" inputmode="decimal" <?= $type === 'money' ? 'required' : '' ?>>
                                <?php if ($fieldError('amount')) : ?><div class="invalid-feedback"><?= esc($fieldError('amount')) ?></div><?php endif ?>
                            </div>
                            <div class="form-text">
                                Saldo dana tersedia saat ini:
                                <strong>Rp <?= esc($moneyBalance['available']) ?></strong>.
                                Nominal tidak boleh melebihi saldo saat distribusi disetujui.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4<?= $goodsHidden ?>" id="goodsFields">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Rincian Bantuan Barang</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Barang
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($fieldError('items')) : ?>
                        <div class="alert alert-danger py-2 px-3 small"><?= esc($fieldError('items')) ?></div>
                    <?php endif ?>

                    <div id="itemsContainer">
                        <?php foreach ($oldItems as $index => $item) : ?>
                            <?php
                            $nameError = $itemError((int) $index, 'item_name');
                            $qtyError  = $itemError((int) $index, 'quantity');
                            $unitError = $itemError((int) $index, 'unit');
                            ?>
                            <div class="row g-2 align-items-start item-row mb-2">
                                <div class="col-md-5">
                                    <label class="form-label small">Nama barang <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm <?= $nameError ? 'is-invalid' : '' ?>" name="items[<?= esc((string) $index, 'attr') ?>][item_name]" list="goodsOptions" value="<?= esc((string) ($item['item_name'] ?? ''), 'attr') ?>" maxlength="150">
                                    <?php if ($nameError) : ?><div class="invalid-feedback"><?= esc($nameError) ?></div><?php endif ?>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small">Kuantitas <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-sm <?= $qtyError ? 'is-invalid' : '' ?>" name="items[<?= esc((string) $index, 'attr') ?>][quantity]" value="<?= esc((string) ($item['quantity'] ?? ''), 'attr') ?>" min="0.01" step="0.01" inputmode="decimal">
                                    <?php if ($qtyError) : ?><div class="invalid-feedback"><?= esc($qtyError) ?></div><?php endif ?>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small">Satuan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm <?= $unitError ? 'is-invalid' : '' ?>" name="items[<?= esc((string) $index, 'attr') ?>][unit]" value="<?= esc((string) ($item['unit'] ?? ''), 'attr') ?>" maxlength="30" placeholder="mis. kg, karung, dus">
                                    <?php if ($unitError) : ?><div class="invalid-feedback"><?= esc($unitError) ?></div><?php endif ?>
                                </div>
                                <div class="col-md-2 d-flex align-items-end" style="min-height: 3.4rem;">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-item" title="Hapus baris">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>

                    <div class="form-text mt-2">
                        Maksimal <?= esc((string) \App\Libraries\DistribusiService::MAX_ITEMS) ?> jenis barang.
                        Nama barang dan satuan harus sama persis dengan stok terverifikasi; tidak ada konversi satuan.
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Tujuan dan Keterangan</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="description" class="form-label">Tujuan penyaluran <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= $fieldError('description') ? 'is-invalid' : '' ?>" id="description" name="description" rows="3" maxlength="2000" required><?= esc((string) old('description', $isEdit ? (string) ($distribution->description ?? '') : '')) ?></textarea>
                        <?php if ($fieldError('description')) : ?><div class="invalid-feedback"><?= esc($fieldError('description')) ?></div><?php endif ?>
                        <div class="form-text">Jelaskan peruntukan penyaluran, misalnya bantuan pendidikan atau tanggap bencana.</div>
                    </div>

                    <div>
                        <label for="notes" class="form-label">Keterangan tambahan</label>
                        <textarea class="form-control <?= $fieldError('notes') ? 'is-invalid' : '' ?>" id="notes" name="notes" rows="3" maxlength="2000"><?= esc((string) old('notes', $isEdit ? (string) ($distribution->notes ?? '') : '')) ?></textarea>
                        <?php if ($fieldError('notes')) : ?><div class="invalid-feedback"><?= esc($fieldError('notes')) ?></div><?php endif ?>
                        <div class="form-text">Opsional. Misalnya nama pendamping, nomor berita acara, atau kondisi barang.</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Distribusi' ?>
                </button>
                <a href="<?= esc($cancelUrl, 'attr') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Saldo Tersedia</h2>
            </div>
            <div class="card-body small">
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="text-muted">Dana global</span>
                    <strong class="text-success">Rp <?= esc($moneyBalance['available']) ?></strong>
                </div>
                <div class="text-muted mb-3">
                    Diterima Rp <?= esc($moneyBalance['received']) ?>,
                    tersalurkan Rp <?= esc($moneyBalance['distributed']) ?>.
                </div>

                <div class="fw-semibold mb-2">Stok barang tersedia</div>
                <?php if ($availableGoods === []) : ?>
                    <p class="text-muted mb-0">Belum ada stok barang terverifikasi yang tersedia.</p>
                <?php else : ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($availableGoods as $balance) : ?>
                            <li class="d-flex justify-content-between gap-2 border-bottom py-1">
                                <span><?= esc($balance['item_name']) ?></span>
                                <span class="text-nowrap fw-semibold"><?= esc($balance['available']) ?> <?= esc($balance['unit']) ?></span>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h2 class="h6 mb-0">Panduan</h2>
            </div>
            <div class="card-body small text-muted">
                <p><strong class="text-body">Nomor distribusi</strong> dibuat otomatis dengan format <code>DSR-YYYYMMDD-NNNN</code> setelah distribusi disimpan.</p>
                <p><strong class="text-body">Alur status:</strong> draf → disetujui → tersalurkan. Draf belum mengurangi saldo; persetujuan memeriksa dan mengunci saldo.</p>
                <p><strong class="text-body">Saldo dana</strong> dihitung dari donasi uang terverifikasi dikurangi distribusi uang yang disetujui/tersalurkan.</p>
                <p><strong class="text-body">Stok barang</strong> dihitung per nama + satuan yang sama. Kuantitas berlebih akan ditolak saat persetujuan.</p>
                <p class="mb-0"><strong class="text-body">Transaksi final</strong> tidak dapat diubah langsung. Gunakan pembatalan beralasan lalu catat ulang; semuanya tercatat di audit log.</p>
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
                        <dd class="col-7 mb-2"><code><?= esc($distribution->distribution_number) ?></code></dd>
                        <dt class="col-5 text-muted fw-normal">Status</dt>
                        <dd class="col-7 mb-2"><?= $distribution->status === 'draft' ? 'Draf' : esc($distribution->status) ?></dd>
                        <dt class="col-5 text-muted fw-normal">Dibuat</dt>
                        <dd class="col-7 mb-2"><?= esc(date('d/m/Y H:i', strtotime($distribution->created_at))) ?></dd>
                        <dt class="col-5 text-muted fw-normal">Diperbarui</dt>
                        <dd class="col-7 mb-0"><?= $distribution->updated_at !== null ? esc(date('d/m/Y H:i', strtotime($distribution->updated_at))) : '—' ?></dd>
                    </dl>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>

<datalist id="goodsOptions">
    <?php foreach ($availableGoods as $balance) : ?>
        <option value="<?= esc($balance['item_name'], 'attr') ?>"><?= esc($balance['available']) ?> <?= esc($balance['unit']) ?></option>
    <?php endforeach ?>
</datalist>

<template id="itemRowTemplate">
    <div class="row g-2 align-items-start item-row mb-2">
        <div class="col-md-5">
            <label class="form-label small">Nama barang <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm" data-field="item_name" list="goodsOptions" maxlength="150">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small">Kuantitas <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-sm" data-field="quantity" min="0.01" step="0.01" inputmode="decimal">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small">Satuan <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm" data-field="unit" maxlength="30" placeholder="mis. kg, karung, dus">
        </div>
        <div class="col-md-2 d-flex align-items-end" style="min-height: 3.4rem;">
            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-item" title="Hapus baris">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
(() => {
    'use strict';

    const type = document.getElementById('distribution_type');
    const moneyFields = document.getElementById('moneyFields');
    const goodsFields = document.getElementById('goodsFields');
    const amount = document.getElementById('amount');
    const container = document.getElementById('itemsContainer');
    const addButton = document.getElementById('addItem');
    const template = document.getElementById('itemRowTemplate');
    const maxItems = <?= (int) \App\Libraries\DistribusiService::MAX_ITEMS ?>;

    let nextIndex = container ? container.querySelectorAll('.item-row').length : 0;

    const bindRemove = (row) => {
        const button = row.querySelector('.remove-item');

        if (button) {
            button.addEventListener('click', () => {
                if (container.querySelectorAll('.item-row').length > 1) {
                    row.remove();
                } else {
                    row.querySelectorAll('input').forEach((input) => {
                        input.value = '';
                    });
                }
            });
        }
    };

    const toggleType = () => {
        if (!type || !moneyFields || !goodsFields) {
            return;
        }

        const isMoney = type.value === 'money';

        moneyFields.classList.toggle('d-none', !isMoney);
        goodsFields.classList.toggle('d-none', isMoney);

        if (amount) {
            amount.required = isMoney;
        }
    };

    if (type) {
        type.addEventListener('change', toggleType);
        toggleType();
    }

    if (container) {
        container.querySelectorAll('.item-row').forEach(bindRemove);
    }

    if (addButton && container && template) {
        addButton.addEventListener('click', () => {
            if (container.querySelectorAll('.item-row').length >= maxItems) {
                return;
            }

            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.item-row');
            const index = nextIndex++;
            const indexName = 'items[' + index + ']';

            row.querySelectorAll('[data-field]').forEach((input) => {
                input.name = indexName + '[' + input.dataset.field + ']';

                if (input.dataset.field === 'item_name' || input.dataset.field === 'unit') {
                    input.required = type ? type.value === 'goods' : true;
                }
            });

            bindRemove(row);
            container.appendChild(fragment);
        });
    }
})();
</script>
<?php $this->endSection() ?>
