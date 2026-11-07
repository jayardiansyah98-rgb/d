<?php
$statusMeta = [
    'draft' => [
        'label' => 'DRAF — BELUM SAH',
        'class' => 'receipt-status-pending',
        'icon'  => 'bi-hourglass-split',
        'note'  => 'Dokumen ini masih draf. Saldo belum dikurangi dan penyaluran belum disetujui.',
    ],
    'approved' => [
        'label' => 'DISETUJUI — SIAP DISALURKAN',
        'class' => 'receipt-status-approved',
        'icon'  => 'bi-check2-circle',
        'note'  => 'Distribusi sudah disetujui dan saldo aktif sudah dikurangi. Barang/uang masih perlu diserahkan kepada penerima.',
    ],
    'distributed' => [
        'label' => 'TERSALURKAN',
        'class' => 'receipt-status-distributed',
        'icon'  => 'bi-truck',
        'note'  => 'Distribusi sudah ditandai tersalurkan kepada penerima manfaat.',
    ],
    'cancelled' => [
        'label' => 'DIBATALKAN — TIDAK BERLAKU',
        'class' => 'receipt-status-cancelled',
        'icon'  => 'bi-x-circle',
        'note'  => 'Transaksi ini dibatalkan dan tidak dihitung sebagai penyaluran aktif.',
    ],
];

$status        = $statusMeta[$distribution->status] ?? $statusMeta['draft'];
$isGoods       = $distribution->distribution_type === 'goods';
$isCancelled   = $distribution->status === 'cancelled';
$isDistributed = $distribution->status === 'distributed';

$recipientLabels = [
    'individual'   => 'Individu',
    'organization' => 'Lembaga/organisasi',
    'community'    => 'Komunitas',
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?> · Sistem Informasi Donasi Yayasan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <style>
        body {
            background: #eef1f5;
        }
        .receipt-sheet {
            max-width: 820px;
            margin: 1.5rem auto;
            padding: 2.5rem;
            background: #fff;
            border-radius: .75rem;
            box-shadow: 0 1rem 3rem rgba(15, 23, 42, .12);
        }
        .receipt-org {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: .04em;
        }
        .receipt-title {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: .06em;
        }
        .receipt-status {
            border: 1px solid transparent;
            border-radius: .5rem;
            padding: .85rem 1rem;
        }
        .receipt-status-pending {
            color: #664d03;
            background: #fff3cd;
            border-color: #ffecb5;
        }
        .receipt-status-approved {
            color: #055160;
            background: #cff4fc;
            border-color: #b6effb;
        }
        .receipt-status-distributed {
            color: #0f5132;
            background: #d1e7dd;
            border-color: #badbcc;
        }
        .receipt-status-cancelled {
            color: #842029;
            background: #f8d7da;
            border-color: #f5c2c7;
        }
        .receipt-signature {
            min-height: 4.5rem;
            border-bottom: 1px solid #adb5bd;
        }
        .receipt-toolbar {
            max-width: 820px;
            margin: 1.5rem auto -.75rem;
        }
        @media (max-width: 575.98px) {
            .receipt-sheet {
                margin: 0;
                padding: 1.25rem;
                border-radius: 0;
                box-shadow: none;
            }
        }
        @media print {
            @page {
                margin: 12mm;
            }
            body {
                background: #fff;
            }
            .receipt-toolbar,
            .no-print {
                display: none !important;
            }
            .receipt-sheet {
                max-width: none;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }
            a[href]::after {
                content: none !important;
            }
        }
    </style>
</head>
<body>
<div class="receipt-toolbar d-flex flex-wrap justify-content-between gap-2 px-3">
    <a href="<?= url_to('distribusi.show', $distribution->id) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail
    </a>
    <button type="button" class="btn btn-dark" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Cetak
    </button>
</div>

<main class="receipt-sheet">
    <div class="row g-3 align-items-start">
        <div class="col-8">
            <div class="receipt-org">YAYASAN</div>
            <div class="text-muted small">Sistem Informasi Pengelolaan Donasi</div>
        </div>
        <div class="col-4 text-end">
            <div class="receipt-title">BUKTI PENYALURAN</div>
            <div class="text-muted small">Distribusi Donasi</div>
        </div>
    </div>

    <hr class="my-4">

    <div class="receipt-status <?= esc($status['class'], 'attr') ?> mb-4">
        <div class="fw-bold">
            <i class="bi <?= esc($status['icon'], 'attr') ?> me-1"></i><?= esc($status['label']) ?>
        </div>
        <div class="small mt-1"><?= esc($status['note']) ?></div>
        <?php if ($isCancelled && $distribution->cancelled_reason !== null && $distribution->cancelled_reason !== '') : ?>
            <div class="small mt-1"><strong>Alasan pembatalan:</strong> <?= nl2br(esc($distribution->cancelled_reason)) ?></div>
        <?php endif ?>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6">
            <div class="text-muted small">Nomor distribusi</div>
            <div class="fw-semibold"><code><?= esc($distribution->distribution_number) ?></code></div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">Tanggal penyaluran</div>
            <div class="fw-semibold"><?= esc(date('d/m/Y', strtotime($distribution->distribution_date))) ?></div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">Jenis bantuan</div>
            <div class="fw-semibold"><?= $isGoods ? 'Barang' : 'Uang' ?></div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">Status distribusi</div>
            <div class="fw-semibold"><?= esc($status['label']) ?></div>
        </div>
    </div>

    <h2 class="h6 border-bottom pb-2 mb-3">Penerima Manfaat</h2>
    <div class="mb-4">
        <div class="fw-semibold"><?= esc($distribution->recipient_name) ?></div>
        <div class="text-muted small"><?= esc($recipientLabels[$distribution->recipient_type] ?? $distribution->recipient_type) ?></div>
        <?php if ($distribution->recipient_address !== null && $distribution->recipient_address !== '') : ?>
            <div class="text-muted small mt-1"><?= nl2br(esc($distribution->recipient_address)) ?></div>
        <?php endif ?>
    </div>

    <h2 class="h6 border-bottom pb-2 mb-3">Rincian Penyaluran</h2>
    <div class="table-responsive mb-4">
        <table class="table table-sm mb-0">
            <tbody>
                <?php if ($isGoods) : ?>
                    <tr>
                        <th scope="row" style="width: 32%;">Daftar barang</th>
                        <td>
                            <?php if ($items === []) : ?>
                                <span class="text-muted">Belum ada rincian barang.</span>
                            <?php else : ?>
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nama barang</th>
                                            <th class="text-end">Kuantitas</th>
                                            <th>Satuan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item) : ?>
                                            <tr>
                                                <td><?= esc($item->item_name) ?></td>
                                                <td class="text-end"><?= esc(number_format((float) $item->quantity, 2, ',', '.')) ?></td>
                                                <td><?= esc($item->unit) ?></td>
                                            </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                </table>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <tr>
                        <th scope="row" style="width: 32%;">Nominal penyaluran</th>
                        <td class="fw-semibold">Rp <?= esc(number_format((float) $distribution->amount, 2, ',', '.')) ?></td>
                    </tr>
                <?php endif ?>
                <tr>
                    <th scope="row">Tujuan penyaluran</th>
                    <td><?= $distribution->description !== null && $distribution->description !== '' ? nl2br(esc($distribution->description)) : '—' ?></td>
                </tr>
                <tr>
                    <th scope="row">Keterangan</th>
                    <td><?= $distribution->notes !== null && $distribution->notes !== '' ? nl2br(esc($distribution->notes)) : '—' ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="row g-4 mt-5">
        <div class="col-6 col-md-4">
            <div class="text-muted small mb-2">Dibuat oleh</div>
            <div class="receipt-signature"></div>
            <div class="small fw-semibold mt-2"><?= $creator !== null ? esc($creator->full_name) : '—' ?></div>
            <div class="text-muted small"><?= esc(date('d/m/Y H:i', strtotime($distribution->created_at))) ?></div>
        </div>
        <div class="col-6 col-md-4">
            <div class="text-muted small mb-2">Disetujui oleh</div>
            <div class="receipt-signature"></div>
            <div class="small fw-semibold mt-2">
                <?= $approver !== null ? esc($approver->full_name) : 'Belum disetujui' ?>
            </div>
            <div class="text-muted small">
                <?= $distribution->approved_at !== null ? esc(date('d/m/Y H:i', strtotime($distribution->approved_at))) : '—' ?>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="text-muted small mb-2">Diterima oleh</div>
            <div class="receipt-signature"></div>
            <div class="small fw-semibold mt-2"><?= $isDistributed ? esc($distribution->recipient_name) : 'Nama &amp; tanda tangan' ?></div>
            <div class="text-muted small"><?= $isDistributed ? 'Diterima secara fisik' : 'Belum tersalurkan' ?></div>
        </div>
    </div>

    <hr class="my-4">

    <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 text-muted small">
        <span>Dicetak pada <?= esc($printedAt) ?> dari Sistem Informasi Pengelolaan Donasi.</span>
        <span>Nomor distribusi: <code><?= esc($distribution->distribution_number) ?></code></span>
    </div>
</main>
</body>
</html>
