<?php
$statusMeta = [
    'pending' => [
        'label' => 'MENUNGGU VERIFIKASI',
        'class' => 'receipt-status-pending',
        'icon'  => 'bi-hourglass-split',
        'note'  => 'Dokumen ini belum merupakan bukti pembayaran yang sah karena transaksi belum diverifikasi.',
    ],
    'verified' => [
        'label' => 'TERVERIFIKASI',
        'class' => 'receipt-status-verified',
        'icon'  => 'bi-check2-circle',
        'note'  => 'Donasi telah diverifikasi dan dicatat sebagai penerimaan.',
    ],
    'cancelled' => [
        'label' => 'DIBATALKAN — TIDAK BERLAKU',
        'class' => 'receipt-status-cancelled',
        'icon'  => 'bi-x-circle',
        'note'  => 'Transaksi ini dibatalkan dan tidak boleh dihitung sebagai penerimaan aktif.',
    ],
];

$status      = $statusMeta[$donation->status] ?? $statusMeta['pending'];
$isGoods     = $donation->donation_type === 'goods';
$isAnonymous = (int) $donation->is_anonymous === 1;
$isCancelled = $donation->status === 'cancelled';
$isVerified  = $donation->status === 'verified';

$paymentLabels = [
    'cash'          => 'Tunai',
    'bank_transfer' => 'Transfer bank',
    'other'         => 'Lainnya',
];

$hasDonor = ! $isAnonymous && $donation->donor_id !== null && $donation->donor_name !== null;
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
        .receipt-status-verified {
            color: #0f5132;
            background: #d1e7dd;
            border-color: #badbcc;
        }
        .receipt-status-cancelled {
            color: #842029;
            background: #f8d7da;
            border-color: #f5c2c7;
        }
        .receipt-table th {
            width: 42%;
            font-weight: 600;
            color: #495057;
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
    <a href="<?= url_to('donasi.show', $donation->id) ?>" class="btn btn-outline-secondary">
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
            <div class="receipt-title">BUKTI PENERIMAAN</div>
            <div class="text-muted small">Donasi</div>
        </div>
    </div>

    <hr class="my-4">

    <div class="receipt-status <?= esc($status['class'], 'attr') ?> mb-4">
        <div class="fw-bold">
            <i class="bi <?= esc($status['icon'], 'attr') ?> me-1"></i><?= esc($status['label']) ?>
        </div>
        <div class="small mt-1"><?= esc($status['note']) ?></div>
        <?php if ($isCancelled && $donation->cancelled_reason !== null && $donation->cancelled_reason !== '') : ?>
            <div class="small mt-1"><strong>Alasan pembatalan:</strong> <?= nl2br(esc($donation->cancelled_reason)) ?></div>
        <?php endif ?>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6">
            <div class="text-muted small">Nomor transaksi</div>
            <div class="fw-semibold"><code><?= esc($donation->transaction_number) ?></code></div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">Tanggal penerimaan</div>
            <div class="fw-semibold"><?= esc(date('d/m/Y', strtotime($donation->donation_date))) ?></div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">Jenis donasi</div>
            <div class="fw-semibold"><?= $isGoods ? 'Barang' : 'Uang' ?></div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">Status transaksi</div>
            <div class="fw-semibold"><?= esc($status['label']) ?></div>
        </div>
    </div>

    <h2 class="h6 border-bottom pb-2 mb-3">Donatur</h2>
    <div class="mb-4">
        <?php if ($isAnonymous) : ?>
            <div class="fw-semibold">Donasi anonim</div>
            <div class="text-muted small">Identitas donatur tidak ditampilkan pada transaksi ini.</div>
        <?php elseif ($hasDonor) : ?>
            <div class="fw-semibold">
                <?= esc($donation->donor_name) ?>
                <?php if ($donation->donor_deleted_at !== null) : ?>
                    <span class="badge text-bg-secondary ms-1">Arsip</span>
                <?php elseif ($donation->donor_status === 'inactive') : ?>
                    <span class="badge text-bg-secondary ms-1">Nonaktif</span>
                <?php endif ?>
            </div>
            <div class="text-muted small"><code><?= esc((string) $donation->donor_code) ?></code></div>
        <?php else : ?>
            <span class="text-muted">Tidak ada data donatur pada transaksi ini.</span>
        <?php endif ?>
    </div>

    <h2 class="h6 border-bottom pb-2 mb-3">Rincian Donasi</h2>
    <div class="table-responsive mb-4">
        <table class="table table-sm receipt-table mb-0">
            <tbody>
                <?php if ($isGoods) : ?>
                    <tr>
                        <th scope="row">Nama barang</th>
                        <td><?= $donation->goods_name !== null ? esc($donation->goods_name) : '—' ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Kuantitas</th>
                        <td>
                            <?php if ($donation->goods_quantity !== null) : ?>
                                <?= esc(number_format((float) $donation->goods_quantity, 2, ',', '.')) ?> <?= esc($donation->goods_unit ?? '') ?>
                            <?php else : ?>
                                —
                            <?php endif ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Nilai estimasi</th>
                        <td>
                            <?= $donation->estimated_value !== null ? 'Rp ' . esc(number_format((float) $donation->estimated_value, 2, ',', '.')) : '—' ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <tr>
                        <th scope="row">Nominal donasi</th>
                        <td class="fw-semibold">Rp <?= esc(number_format((float) $donation->amount, 2, ',', '.')) ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Metode pembayaran</th>
                        <td><?= esc($paymentLabels[$donation->payment_method] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Nomor referensi</th>
                        <td><?= $donation->reference_number !== null && $donation->reference_number !== '' ? esc($donation->reference_number) : '—' ?></td>
                    </tr>
                <?php endif ?>
                <tr>
                    <th scope="row">Catatan</th>
                    <td><?= $donation->notes !== null && $donation->notes !== '' ? nl2br(esc($donation->notes)) : '—' ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="row g-4 mt-5">
        <div class="col-6 col-md-4">
            <div class="text-muted small mb-2">Dibuat oleh</div>
            <div class="receipt-signature"></div>
            <div class="small fw-semibold mt-2"><?= $creator !== null ? esc($creator->full_name) : '—' ?></div>
            <div class="text-muted small"><?= esc(date('d/m/Y H:i', strtotime($donation->created_at))) ?></div>
        </div>
        <div class="col-6 col-md-4">
            <div class="text-muted small mb-2">Diverifikasi oleh</div>
            <div class="receipt-signature"></div>
            <div class="small fw-semibold mt-2">
                <?= $isVerified && $verifier !== null ? esc($verifier->full_name) : 'Belum diverifikasi' ?>
            </div>
            <div class="text-muted small">
                <?= $isVerified && $donation->verified_at !== null ? esc(date('d/m/Y H:i', strtotime($donation->verified_at))) : '—' ?>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="text-muted small mb-2">Diterima oleh</div>
            <div class="receipt-signature"></div>
            <div class="small fw-semibold mt-2">Nama &amp; tanda tangan</div>
            <div class="text-muted small">Diterima secara fisik</div>
        </div>
    </div>

    <hr class="my-4">

    <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 text-muted small">
        <span>Dicetak pada <?= esc($printedAt) ?> dari Sistem Informasi Pengelolaan Donasi.</span>
        <span>Nomor transaksi: <code><?= esc($donation->transaction_number) ?></code></span>
    </div>
</main>
</body>
</html>
