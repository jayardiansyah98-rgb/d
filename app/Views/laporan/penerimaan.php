<?php
$this->extend('layouts/main');
?>

<?php $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Laporan Penerimaan Donasi</h1>
        <p class="text-muted mb-0">Rincian seluruh transaksi donasi uang dan barang yang masuk ke yayasan.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url_to('laporan.penerimaan.print') . '?' . http_build_query($filters) ?>" target="_blank" class="btn btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Cetak Laporan
        </a>
        <a href="<?= url_to('laporan.penerimaan.excel') . '?' . http_build_query($filters) ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel
        </a>
    </div>
</div>

<!-- FORM FILTER & PENCARIAN -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= url_to('laporan.penerimaan') ?>" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label small fw-semibold">Tanggal Awal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= esc($filters['start_date'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label small fw-semibold">Tanggal Akhir</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= esc($filters['end_date'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="donor_id" class="form-label small fw-semibold">Donatur</label>
                <select class="form-select" id="donor_id" name="donor_id">
                    <option value="">-- Semua Donatur --</option>
                    <?php foreach ($donorOptions as $d) : ?>
                        <option value="<?= esc((string) $d['id'], 'attr') ?>" <?= ($filters['donor_id'] ?? '') === (string) $d['id'] ? 'selected' : '' ?>>
                            <?= esc($d['name']) ?> (<?= esc($d['code']) ?>)
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="donation_type" class="form-label small fw-semibold">Jenis Donasi</label>
                <select class="form-select" id="donation_type" name="donation_type">
                    <option value="">-- Semua Jenis --</option>
                    <option value="money" <?= ($filters['donation_type'] ?? '') === 'money' ? 'selected' : '' ?>>Uang</option>
                    <option value="goods" <?= ($filters['donation_type'] ?? '') === 'goods' ? 'selected' : '' ?>>Barang</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small fw-semibold">Status Transaksi</label>
                <select class="form-select" id="status" name="status">
                    <option value="">-- Semua Status --</option>
                    <option value="verified" <?= ($filters['status'] ?? '') === 'verified' ? 'selected' : '' ?>>Terverifikasi</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Menunggu Verifikasi (Pending)</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="payment_method" class="form-label small fw-semibold">Metode Pembayaran</label>
                <select class="form-select" id="payment_method" name="payment_method">
                    <option value="">-- Semua Metode --</option>
                    <option value="bank_transfer" <?= ($filters['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Transfer Bank</option>
                    <option value="cash" <?= ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Tunai</option>
                    <option value="qris" <?= ($filters['payment_method'] ?? '') === 'qris' ? 'selected' : '' ?>>QRIS</option>
                    <option value="other" <?= ($filters['payment_method'] ?? '') === 'other' ? 'selected' : '' ?>>Lainnya</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="q" class="form-label small fw-semibold">Pencarian Kata Kunci</label>
                <input type="text" class="form-control" id="q" name="q" placeholder="No transaksi, catatan, barang..." value="<?= esc($filters['q'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Terapkan
                </button>
                <a href="<?= url_to('laporan.penerimaan') ?>" class="btn btn-outline-secondary">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- KARTU RINGKASAN DATA SESUAI FILTER -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <span class="text-muted small fw-semibold">Transaksi Terfilter</span>
                <h4 class="fw-bold text-dark mt-2 mb-0"><?= esc((string) $report['total_rows']) ?> <small class="fs-6 text-muted">Data</small></h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <span class="text-muted small fw-semibold">Total Uang (Semua Status)</span>
                <h4 class="fw-bold text-primary mt-2 mb-0">Rp <?= number_format($report['total_money'], 2, ',', '.') ?></h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 bg-success-subtle border-start border-success border-4">
            <div class="card-body">
                <span class="text-success-emphasis small fw-bold">Uang Sah Terverifikasi</span>
                <h4 class="fw-bold text-success mt-2 mb-0">Rp <?= number_format($report['verified_money'], 2, ',', '.') ?></h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <span class="text-muted small fw-semibold">Total Donasi Barang</span>
                <h4 class="fw-bold text-dark mt-2 mb-0"><?= number_format($report['total_goods_qty'], 2, ',', '.') ?> <small class="fs-6 text-muted">Kuantitas</small></h4>
            </div>
        </div>
    </div>
</div>

<!-- TABEL LAPORAN PENERIMAAN -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Donatur</th>
                    <th>Jenis</th>
                    <th>Nominal / Barang</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Verifikator</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($report['data'] === []) : ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                            Tidak ada transaksi penerimaan donasi yang sesuai dengan filter.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($report['data'] as $d) : ?>
                        <?php
                        $isArr   = is_array($d);
                        $isAnon  = (int) ($isArr ? ($d['is_anonymous'] ?? 0) : ($d->is_anonymous ?? 0)) === 1;
                        $trxNum  = $isArr ? $d['transaction_number'] : $d->transaction_number;
                        $dDate   = $isArr ? $d['donation_date'] : $d->donation_date;
                        $dName   = $isArr ? ($d['donor_name'] ?? 'Umum') : ($d->donor_name ?? 'Umum');
                        $dCode   = $isArr ? ($d['donor_code'] ?? '') : ($d->donor_code ?? '');
                        $dType   = $isArr ? $d['donation_type'] : $d->donation_type;
                        $amt     = $isArr ? (float) $d['amount'] : (float) $d->amount;
                        $gQty    = $isArr ? $d['goods_quantity'] : $d->goods_quantity;
                        $gUnit   = $isArr ? ($d['goods_unit'] ?? '') : ($d->goods_unit ?? '');
                        $gName   = $isArr ? ($d['goods_name'] ?? '') : ($d->goods_name ?? '');
                        $pMethod = $isArr ? ($d['payment_method'] ?? '-') : ($d->payment_method ?? '-');
                        $refNum  = $isArr ? ($d['reference_number'] ?? '') : ($d->reference_number ?? '');
                        $st      = $isArr ? $d['status'] : $d->status;
                        $vName   = $isArr ? ($d['verified_by_name'] ?? '-') : ($d->verified_by_name ?? '-');
                        ?>
                        <tr>
                            <td>
                                <strong class="small"><?= esc($trxNum) ?></strong>
                            </td>
                            <td class="small text-muted"><?= esc(date('d/m/Y', strtotime($dDate))) ?></td>
                            <td class="small">
                                <?php if ($isAnon) : ?>
                                    <span class="text-muted fst-italic">Hamba Allah (Anonim)</span>
                                <?php else : ?>
                                    <strong><?= esc($dName) ?></strong>
                                    <?php if (! empty($dCode)) : ?>
                                        <div class="text-muted" style="font-size: 0.8rem;"><?= esc($dCode) ?></div>
                                    <?php endif ?>
                                <?php endif ?>
                            </td>
                            <td>
                                <span class="badge <?= $dType === 'money' ? 'text-bg-primary' : 'text-bg-info text-white' ?>">
                                    <?= $dType === 'money' ? 'Uang' : 'Barang' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($dType === 'money') : ?>
                                    <span class="fw-bold text-success small">Rp <?= number_format($amt, 2, ',', '.') ?></span>
                                <?php else : ?>
                                    <span class="fw-bold small"><?= esc((string) $gQty) ?> <?= esc($gUnit) ?></span>
                                    <div class="text-muted small"><?= esc($gName) ?></div>
                                <?php endif ?>
                            </td>
                            <td class="small">
                                <?= esc(ucfirst(str_replace('_', ' ', $pMethod))) ?>
                                <?php if (! empty($refNum)) : ?>
                                    <div class="text-muted" style="font-size: 0.75rem;">Ref: <?= esc($refNum) ?></div>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if ($st === 'verified') : ?>
                                    <span class="badge text-bg-success">Terverifikasi</span>
                                <?php elseif ($st === 'pending') : ?>
                                    <span class="badge text-bg-warning">Pending</span>
                                <?php else : ?>
                                    <span class="badge text-bg-danger">Batal</span>
                                <?php endif ?>
                            </td>
                            <td class="small text-muted">
                                <?= esc($vName) ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
    <?php if ($report['pager'] !== null) : ?>
        <div class="card-footer bg-white border-0 py-3">
            <?= $report['pager']->links('donasi', 'default_full') ?>
        </div>
    <?php endif ?>
</div>
<?php $this->endSection() ?>
