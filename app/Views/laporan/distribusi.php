<?php
$this->extend('layouts/main');
?>

<?php $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Laporan Distribusi Donasi</h1>
        <p class="text-muted mb-0">Rincian seluruh penyaluran bantuan dana dan logistik kepada penerima manfaat.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url_to('laporan.distribusi.print') . '?' . http_build_query($filters) ?>" target="_blank" class="btn btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Cetak Laporan
        </a>
        <a href="<?= url_to('laporan.distribusi.excel') . '?' . http_build_query($filters) ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel
        </a>
    </div>
</div>

<!-- FORM FILTER & PENCARIAN -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= url_to('laporan.distribusi') ?>" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label small fw-semibold">Tanggal Awal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= esc($filters['start_date'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label small fw-semibold">Tanggal Akhir</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= esc($filters['end_date'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="recipient_type" class="form-label small fw-semibold">Tipe Penerima</label>
                <select class="form-select" id="recipient_type" name="recipient_type">
                    <option value="">-- Semua Tipe --</option>
                    <option value="individual" <?= ($filters['recipient_type'] ?? '') === 'individual' ? 'selected' : '' ?>>Individu / Perorangan</option>
                    <option value="organization" <?= ($filters['recipient_type'] ?? '') === 'organization' ? 'selected' : '' ?>>Lembaga / Yayasan</option>
                    <option value="community" <?= ($filters['recipient_type'] ?? '') === 'community' ? 'selected' : '' ?>>Komunitas / Kelompok</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="distribution_type" class="form-label small fw-semibold">Jenis Bantuan</label>
                <select class="form-select" id="distribution_type" name="distribution_type">
                    <option value="">-- Semua Jenis --</option>
                    <option value="money" <?= ($filters['distribution_type'] ?? '') === 'money' ? 'selected' : '' ?>>Uang Tunai</option>
                    <option value="goods" <?= ($filters['distribution_type'] ?? '') === 'goods' ? 'selected' : '' ?>>Barang / Logistik</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label small fw-semibold">Status Penyaluran</label>
                <select class="form-select" id="status" name="status">
                    <option value="">-- Semua Status --</option>
                    <option value="distributed" <?= ($filters['status'] ?? '') === 'distributed' ? 'selected' : '' ?>>Tersalurkan</option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                    <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draf</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Pencarian Kata Kunci</label>
                <input type="text" class="form-control" id="q" name="q" placeholder="No distribusi, penerima, tujuan, catatan..." value="<?= esc($filters['q'] ?? '') ?>">
            </div>
            <div class="col-md-3 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Terapkan
                </button>
                <a href="<?= url_to('laporan.distribusi') ?>" class="btn btn-outline-secondary">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- KARTU RINGKASAN DATA SESUAI FILTER -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <span class="text-muted small fw-semibold">Distribusi Terfilter</span>
                <h4 class="fw-bold text-dark mt-2 mb-0"><?= esc((string) $report['total_rows']) ?> <small class="fs-6 text-muted">Transaksi</small></h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100 bg-danger-subtle border-start border-danger border-4">
            <div class="card-body">
                <span class="text-danger-emphasis small fw-bold">Dana Tersalurkan (Aktif)</span>
                <h4 class="fw-bold text-danger mt-2 mb-0">Rp <?= number_format($report['active_money'], 2, ',', '.') ?></h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <span class="text-muted small fw-semibold">Penyaluran Barang / Logistik</span>
                <h4 class="fw-bold text-dark mt-2 mb-0"><?= esc((string) $report['total_goods_trans']) ?> <small class="fs-6 text-muted">Kegiatan</small></h4>
            </div>
        </div>
    </div>
</div>

<!-- TABEL LAPORAN DISTRIBUSI -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Penyaluran</th>
                    <th>Tanggal</th>
                    <th>Penerima Manfaat</th>
                    <th>Jenis</th>
                    <th>Bantuan / Nilai</th>
                    <th>Tujuan Penyaluran</th>
                    <th>Status</th>
                    <th>Penyetuju</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($report['data'] === []) : ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                            Tidak ada data distribusi donasi yang sesuai dengan filter.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($report['data'] as $dist) : ?>
                        <?php
                        $isArr = is_array($dist);
                        $dNum  = $isArr ? $dist['distribution_number'] : $dist->distribution_number;
                        $dDate = $isArr ? $dist['distribution_date'] : $dist->distribution_date;
                        $rName = $isArr ? $dist['recipient_name'] : $dist->recipient_name;
                        $rType = $isArr ? $dist['recipient_type'] : $dist->recipient_type;
                        $dType = $isArr ? $dist['distribution_type'] : $dist->distribution_type;
                        $amt   = $isArr ? (float) $dist['amount'] : (float) $dist->amount;
                        $desc  = $isArr ? $dist['description'] : $dist->description;
                        $st    = $isArr ? $dist['status'] : $dist->status;
                        $appN  = $isArr ? ($dist['approver_name'] ?? null) : ($dist->approver_name ?? null);
                        $items = $isArr ? ($dist['items'] ?? []) : ($dist->items ?? []);
                        ?>
                        <tr>
                            <td><strong class="small"><?= esc($dNum) ?></strong></td>
                            <td class="small text-muted"><?= esc(date('d/m/Y', strtotime($dDate))) ?></td>
                            <td class="small">
                                <strong><?= esc($rName) ?></strong>
                                <span class="badge text-bg-light border text-secondary" style="font-size: 0.75rem;">
                                    <?= esc(ucfirst($rType)) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $dType === 'money' ? 'text-bg-danger' : 'text-bg-warning text-dark' ?>">
                                    <?= $dType === 'money' ? 'Uang' : 'Barang' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($dType === 'money') : ?>
                                    <span class="fw-bold text-danger small">Rp <?= number_format($amt, 2, ',', '.') ?></span>
                                <?php else : ?>
                                    <?php if ($items !== []) : ?>
                                        <ul class="list-unstyled mb-0 small">
                                            <?php foreach ($items as $it) : ?>
                                                <li>• <?= esc((string) $it->quantity) ?> <?= esc($it->unit) ?> <?= esc($it->item_name) ?></li>
                                            <?php endforeach ?>
                                        </ul>
                                    <?php else : ?>
                                        <span class="text-muted small">Logistik Barang</span>
                                    <?php endif ?>
                                <?php endif ?>
                            </td>
                            <td class="small text-muted" style="max-width: 220px;">
                                <?= esc($desc) ?>
                            </td>
                            <td>
                                <?php if ($st === 'distributed') : ?>
                                    <span class="badge text-bg-success">Tersalurkan</span>
                                <?php elseif ($st === 'approved') : ?>
                                    <span class="badge text-bg-primary">Disetujui</span>
                                <?php elseif ($st === 'draft') : ?>
                                    <span class="badge text-bg-secondary">Draf</span>
                                <?php else : ?>
                                    <span class="badge text-bg-danger">Batal</span>
                                <?php endif ?>
                            </td>
                            <td class="small text-muted"><?= esc($appN ?? '-') ?></td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
    <?php if ($report['pager'] !== null) : ?>
        <div class="card-footer bg-white border-0 py-3">
            <?= $report['pager']->links('distribusi', 'default_full') ?>
        </div>
    <?php endif ?>
</div>
<?php $this->endSection() ?>
