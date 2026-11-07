<?php
/**
 * Kerangka UI laporan.
 *
 * File ini hanya template tampilan. Tidak ada query atau data transaksi.
 * Modul laporan yang sesungguhnya akan diimplementasikan pada tahap berikutnya.
 */
$this->extend('layouts/main');
?>

<?php $this->section('content') ?>
<div class="alert alert-info d-flex gap-2 align-items-start" role="note">
    <i class="bi bi-info-circle-fill mt-1"></i>
    <div>
        <strong>Pratinjau template laporan.</strong>
        Halaman ini hanya menunjukkan struktur tampilan (filter, ringkasan, tabel, dan pagination).
        Belum ada data donasi, distribusi, atau perhitungan laporan pada tahap ini.
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div>
            <h2 class="h6 mb-0">Filter Laporan</h2>
            <span class="text-muted small">Parameter laporan akan diaktifkan saat modul laporan dibuat.</span>
        </div>
        <i class="bi bi-funnel text-muted"></i>
    </div>
    <div class="card-body">
        <form class="row g-3" action="<?= url_to('templates.report') ?>" method="get">
            <div class="col-12 col-md-3">
                <label for="report_start" class="form-label">Tanggal awal</label>
                <input type="date" class="form-control" id="report_start" name="start" disabled>
            </div>
            <div class="col-12 col-md-3">
                <label for="report_end" class="form-label">Tanggal akhir</label>
                <input type="date" class="form-control" id="report_end" name="end" disabled>
            </div>
            <div class="col-12 col-md-3">
                <label for="report_type" class="form-label">Jenis laporan</label>
                <select class="form-select" id="report_type" name="type" disabled>
                    <option>Penerimaan donasi</option>
                    <option>Distribusi donasi</option>
                    <option>Rekap donatur</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" disabled>
                    <i class="bi bi-search me-1"></i>Tampilkan
                </button>
                <button type="button" class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-download me-1"></i>Ekspor
                </button>
            </div>
        </form>
        <p class="form-text mb-0 mt-2">
            Kontrol dinonaktifkan karena belum ada sumber data laporan.
        </p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon text-bg-primary"><i class="bi bi-wallet2"></i></div>
                <span class="stat-label">Total Penerimaan</span>
                <strong class="stat-value">&mdash;</strong>
                <span class="stat-meta">menunggu data modul donasi</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon text-bg-success"><i class="bi bi-box-seam"></i></div>
                <span class="stat-label">Total Distribusi</span>
                <strong class="stat-value">&mdash;</strong>
                <span class="stat-meta">menunggu data modul distribusi</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon text-bg-warning"><i class="bi bi-people"></i></div>
                <span class="stat-label">Jumlah Donatur</span>
                <strong class="stat-value">&mdash;</strong>
                <span class="stat-meta">menunggu data modul donatur</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="h6 mb-0">Tabel Laporan</h2>
            <span class="text-muted small">Contoh struktur tabel dengan pencarian dan pagination.</span>
        </div>
        <span class="badge text-bg-light border">Belum ada data</span>
    </div>

    <div class="card-body border-bottom bg-light-subtle">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label for="report_search" class="form-label small">Cari</label>
                <input type="search" class="form-control form-control-sm" id="report_search" placeholder="Cari keterangan laporan" disabled>
            </div>
            <div class="col-8 col-md-4">
                <label for="report_status" class="form-label small">Status</label>
                <select class="form-select form-select-sm" id="report_status" disabled>
                    <option>Semua status</option>
                </select>
            </div>
            <div class="col-4 col-md-3">
                <button type="button" class="btn btn-sm btn-dark w-100" disabled>
                    <i class="bi bi-search me-1"></i>Filter
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 140px">Tanggal</th>
                    <th>Keterangan</th>
                    <th>Jenis</th>
                    <th class="text-end">Nominal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="bi bi-file-earmark-bar-graph fs-3 d-block mb-2"></i>
                        Template siap. Data laporan akan tampil setelah modul terkait tersedia.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="text-muted small">Menampilkan 0-0 dari 0 data</span>
        <nav aria-label="Pagination laporan">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><span class="page-link">Sebelumnya</span></li>
                <li class="page-item disabled"><span class="page-link">1</span></li>
                <li class="page-item disabled"><span class="page-link">Berikutnya</span></li>
            </ul>
        </nav>
    </div>
</div>
<?php $this->endSection() ?>
