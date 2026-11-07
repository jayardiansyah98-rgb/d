<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penerimaan Donasi</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .num { text-align: right; }
        .kop { font-size: 13pt; font-weight: bold; text-align: center; }
        .subkop { font-size: 9pt; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="9" class="kop">YAYASAN AMAL &amp; SOSIAL PEDULI UMMAT</td></tr>
        <tr><td colspan="9" class="subkop">LAPORAN TRANSAKSI PENERIMAAN DONASI</td></tr>
        <tr><td colspan="9" class="subkop">Periode: <?= esc($filters['start_date'] ?? 'Awal') ?> s/d <?= esc($filters['end_date'] ?? 'Sekarang') ?> | Dicetak: <?= esc($print_date) ?></td></tr>
        <tr><td colspan="9"></td></tr>
        <tr>
            <th>NO</th>
            <th>NO TRANSAKSI</th>
            <th>TANGGAL</th>
            <th>DONATUR</th>
            <th>JENIS</th>
            <th class="num">NOMINAL (RP)</th>
            <th>BARANG / KUANTITAS</th>
            <th>METODE PEMBAYARAN</th>
            <th>STATUS</th>
        </tr>
        <?php foreach ($report['data'] as $idx => $d) : ?>
            <?php $isAnon = (int) ($d['is_anonymous'] ?? 0) === 1; ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= esc($d['transaction_number']) ?></td>
                <td><?= esc($d['donation_date']) ?></td>
                <td><?= $isAnon ? 'Hamba Allah (Anonim)' : esc($d['donor_name'] ?? 'Umum') ?></td>
                <td><?= $d['donation_type'] === 'money' ? 'Uang' : 'Barang' ?></td>
                <td class="num"><?= $d['donation_type'] === 'money' ? number_format((float) $d['amount'], 2, '.', '') : '0.00' ?></td>
                <td><?= $d['donation_type'] === 'goods' ? esc($d['goods_quantity'] . ' ' . ($d['goods_unit'] ?? '') . ' - ' . ($d['goods_name'] ?? '')) : '-' ?></td>
                <td><?= esc(ucfirst(str_replace('_', ' ', $d['payment_method'] ?? '-'))) ?></td>
                <td><?= esc(ucfirst($d['status'])) ?></td>
            </tr>
        <?php endforeach ?>
        <tr style="font-weight: bold; background-color: #e9ecef;">
            <td colspan="5" class="num">TOTAL UANG TERVERIFIKASI:</td>
            <td class="num"><?= number_format($report['verified_money'], 2, '.', '') ?></td>
            <td colspan="3">TOTAL BARANG: <?= number_format($report['total_goods_qty'], 2, '.', '') ?></td>
        </tr>
    </table>
</body>
</html>
