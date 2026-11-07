<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a1a;
            background: #fff;
            font-size: 9.5pt;
        }
        .header-kop {
            border-bottom: 3px double #2c3e50;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-kop h1 {
            font-size: 15pt;
            font-weight: 800;
            margin: 0;
            color: #0d6efd;
        }
        .header-kop p {
            margin: 2px 0;
            font-size: 8.5pt;
            color: #555;
        }
        .doc-title {
            text-align: center;
            margin: 15px 0;
        }
        .doc-title h2 {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0;
            text-decoration: underline;
        }
        .doc-title p {
            font-size: 8.5pt;
            color: #666;
            margin-top: 4px;
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }
        .table-custom th, .table-custom td {
            border: 1px solid #dee2e6;
            padding: 5px 8px;
        }
        .table-custom th {
            background-color: #f8f9fa !important;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container-fluid py-2">
    <!-- Tombol Aksi di Layar -->
    <div class="no-print mb-3 d-flex justify-content-between align-items-center bg-light p-2 rounded">
        <div>
            <strong>Mode Cetak Laporan Penerimaan Donasi</strong>
            <span class="text-muted small ms-2">Gunakan opsi <em>Print to PDF</em> jika ingin menyimpan sebagai file PDF.</span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-primary">Cetak / Simpan PDF</button>
            <button onclick="window.close()" class="btn btn-sm btn-secondary">Tutup</button>
        </div>
    </div>

    <!-- KOP RESMI YAYASAN -->
    <div class="header-kop d-flex align-items-center gap-3">
        <div style="font-size: 32pt; color: #0d6efd; line-height: 1;">
            &#10084;&#65039;
        </div>
        <div>
            <h1>YAYASAN AMAL &amp; SOSIAL PEDULI UMMAT</h1>
            <p>Jl. Jenderal Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10220 · Telp: (021) 555-0199 · Email: info@yayasan.test</p>
        </div>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title">
        <h2>Laporan Transaksi Penerimaan Donasi</h2>
        <p>
            Periode: <?= esc($filters['start_date'] ?? 'Awal') ?> s/d <?= esc($filters['end_date'] ?? 'Sekarang') ?>
            · Dicetak: <?= esc($print_date) ?> WIB oleh <?= esc($printed_by) ?>
        </p>
    </div>

    <!-- TABEL DATA -->
    <table class="table-custom">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 14%;">No. Transaksi</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 18%;">Nama Donatur</th>
                <th style="width: 7%;">Jenis</th>
                <th style="width: 18%;">Nominal / Barang</th>
                <th style="width: 12%;">Metode Pembayaran</th>
                <th style="width: 9%;">Status</th>
                <th style="width: 9%;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($report['data'] === []) : ?>
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">Tidak ada data transaksi penerimaan donasi.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($report['data'] as $idx => $d) : ?>
                    <?php $isAnon = (int) ($d['is_anonymous'] ?? 0) === 1; ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td><strong><?= esc($d['transaction_number']) ?></strong></td>
                        <td><?= esc(date('d/m/Y', strtotime($d['donation_date']))) ?></td>
                        <td>
                            <?php if ($isAnon) : ?>
                                <em>Hamba Allah (Anonim)</em>
                            <?php else : ?>
                                <?= esc($d['donor_name'] ?? 'Umum') ?>
                            <?php endif ?>
                        </td>
                        <td><?= $d['donation_type'] === 'money' ? 'Uang' : 'Barang' ?></td>
                        <td>
                            <?php if ($d['donation_type'] === 'money') : ?>
                                Rp <?= number_format((float) $d['amount'], 2, ',', '.') ?>
                            <?php else : ?>
                                <?= esc((string) $d['goods_quantity']) ?> <?= esc($d['goods_unit'] ?? '') ?> - <?= esc($d['goods_name'] ?? '') ?>
                            <?php endif ?>
                        </td>
                        <td><?= esc(ucfirst(str_replace('_', ' ', $d['payment_method'] ?? '-'))) ?></td>
                        <td>
                            <?= $d['status'] === 'verified' ? 'Terverifikasi' : ($d['status'] === 'pending' ? 'Pending' : 'Dibatalkan') ?>
                        </td>
                        <td><?= esc($d['verified_by_name'] ?? '-') ?></td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f8f9fa;">
                <td colspan="5" class="text-end">TOTAL TERVERIFIKASI:</td>
                <td colspan="4">Rp <?= number_format($report['verified_money'], 2, ',', '.') ?> (Uang) · <?= number_format($report['total_goods_qty'], 2, ',', '.') ?> (Barang)</td>
            </tr>
        </tfoot>
    </table>

    <!-- TANDA TANGAN -->
    <div class="row signature-section">
        <div class="col-6 text-center">
            <p class="mb-4">Mengetahui,<br><strong>Pimpinan Yayasan</strong></p>
            <br><br>
            <p class="mb-0"><strong>( ............................................ )</strong></p>
        </div>
        <div class="col-6 text-center">
            <p class="mb-4">Jakarta, <?= date('d F Y') ?><br><strong>Bagian Keuangan / Verifikator</strong></p>
            <br><br>
            <p class="mb-0"><strong>( <?= esc($printed_by) ?> )</strong></p>
        </div>
    </div>
</div>

</body>
</html>
