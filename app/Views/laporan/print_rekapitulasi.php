<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a1a;
            background: #fff;
            font-size: 11pt;
        }
        .header-kop {
            border-bottom: 3px double #2c3e50;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-kop h1 {
            font-size: 16pt;
            font-weight: 800;
            margin: 0;
            color: #0d6efd;
            letter-spacing: 0.5px;
        }
        .header-kop p {
            margin: 2px 0;
            font-size: 9pt;
            color: #555;
        }
        .doc-title {
            text-align: center;
            margin: 20px 0;
        }
        .doc-title h2 {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0;
            text-decoration: underline;
        }
        .doc-title p {
            font-size: 9pt;
            color: #666;
            margin-top: 4px;
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }
        .table-custom th, .table-custom td {
            border: 1px solid #dee2e6;
            padding: 6px 10px;
        }
        .table-custom th {
            background-color: #f8f9fa !important;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container py-3">
    <!-- Tombol Aksi di Layar -->
    <div class="no-print mb-4 d-flex justify-content-between align-items-center bg-light p-3 rounded">
        <div>
            <strong>Mode Pratinjau Cetak / Unduh PDF</strong>
            <p class="text-muted small mb-0">Gunakan opsi browser <em>Print to PDF</em> atau <em>Simpan sebagai PDF</em> untuk menyimpan dokumen ini.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Cetak / Simpan PDF</button>
            <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
        </div>
    </div>

    <!-- KOP RESMI YAYASAN -->
    <div class="header-kop d-flex align-items-center gap-3">
        <div style="font-size: 38pt; color: #0d6efd; line-height: 1;">
            &#10084;&#65039;
        </div>
        <div>
            <h1>YAYASAN AMAL & SOSIAL PEDULI UMMAT</h1>
            <p>Jl. Jenderal Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10220</p>
            <p>Telepon: (021) 555-0199 · Email: info@yayasan.test · Website: www.yayasan.test</p>
        </div>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title">
        <h2>Laporan Rekapitulasi Pengelolaan Donasi</h2>
        <p>
            <?php if (! empty($filters['start_date']) || ! empty($filters['end_date'])) : ?>
                Periode: <?= esc($filters['start_date'] ?? 'Awal') ?> s/d <?= esc($filters['end_date'] ?? 'Sekarang') ?>
            <?php else : ?>
                Periode: Seluruh Data Hingga <?= date('d F Y') ?>
            <?php endif ?>
        </p>
    </div>

    <!-- METADATA LAPORAN -->
    <table class="table table-borderless table-sm mb-3" style="font-size: 9.5pt;">
        <tr>
            <td style="width: 15%;"><strong>Tanggal Cetak</strong></td>
            <td style="width: 35%;">: <?= esc($print_date) ?> WIB</td>
            <td style="width: 15%;"><strong>Dicetak Oleh</strong></td>
            <td style="width: 35%;">: <?= esc($printed_by) ?></td>
        </tr>
    </table>

    <!-- RINGKASAN POSISI KEUANGAN -->
    <h5 class="fw-bold mb-2" style="font-size: 11pt;">I. POSISI SALDO & FINANSIAL</h5>
    <table class="table-custom">
        <thead>
            <tr>
                <th>Uraian Komponen Finansial</th>
                <th class="text-end" style="width: 35%;">Jumlah (Rupiah)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Seluruh Penerimaan Donasi Uang (Terverifikasi)</td>
                <td class="text-end fw-semibold">Rp <?= number_format($rekap['global_kpi']['total_received_money'], 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Total Seluruh Penyaluran Donasi Uang (Tersalurkan)</td>
                <td class="text-end fw-semibold">Rp <?= number_format($rekap['global_kpi']['total_distributed_money'], 2, ',', '.') ?></td>
            </tr>
            <tr style="background-color: #f1f8ff; font-weight: bold;">
                <td>SALDO KAS DONASI TERSEDIA</td>
                <td class="text-end">Rp <?= number_format($rekap['global_kpi']['money_balance'], 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Penerimaan Donasi Khusus Periode Laporan</td>
                <td class="text-end">Rp <?= number_format($rekap['period_received'], 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Penyaluran Donasi Khusus Periode Laporan</td>
                <td class="text-end">Rp <?= number_format($rekap['period_distributed'], 2, ',', '.') ?></td>
            </tr>
            <tr style="font-weight: bold;">
                <td>Surplus / (Defisit) Bersih Periode Laporan</td>
                <td class="text-end">Rp <?= number_format($rekap['period_net'], 2, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>

    <!-- RINGKASAN ARUS KAS BULANAN -->
    <h5 class="fw-bold mb-2 mt-4" style="font-size: 11pt;">II. REKAPITULASI ARUS KAS BULANAN (TAHUN <?= esc((string) $rekap['chart_data']['year']) ?>)</h5>
    <table class="table-custom">
        <thead>
            <tr>
                <th>Bulan</th>
                <th class="text-end">Penerimaan (Rp)</th>
                <th class="text-end">Penyaluran (Rp)</th>
                <th class="text-end">Selisih Bersih (Rp)</th>
                <th class="text-end">Saldo Berjalan (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rekap['chart_data']['labels'] as $idx => $label) : ?>
                <tr>
                    <td><?= esc($label) ?></td>
                    <td class="text-end">Rp <?= number_format($rekap['chart_data']['receipts'][$idx], 2, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($rekap['chart_data']['distributions'][$idx], 2, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($rekap['chart_data']['net_monthly'][$idx], 2, ',', '.') ?></td>
                    <td class="text-end fw-semibold">Rp <?= number_format($rekap['chart_data']['cumulative'][$idx], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f8f9fa;">
                <td>TOTAL</td>
                <td class="text-end">Rp <?= number_format(array_sum($rekap['chart_data']['receipts']), 2, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format(array_sum($rekap['chart_data']['distributions']), 2, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format(array_sum($rekap['chart_data']['receipts']) - array_sum($rekap['chart_data']['distributions']), 2, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format(end($rekap['chart_data']['cumulative']), 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- TANDA TANGAN -->
    <div class="row signature-section">
        <div class="col-6 text-center">
            <p class="mb-5">Mengetahui,<br><strong>Pimpinan Yayasan</strong></p>
            <br><br>
            <p class="mb-0"><strong>( ............................................ )</strong></p>
        </div>
        <div class="col-6 text-center">
            <p class="mb-5">Jakarta, <?= date('d F Y') ?><br><strong>Bagian Keuangan / Bendahara</strong></p>
            <br><br>
            <p class="mb-0"><strong>( <?= esc($printed_by) ?> )</strong></p>
        </div>
    </div>
</div>

</body>
</html>
