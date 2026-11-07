<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Donasi</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .num { text-align: right; }
        .kop { font-size: 14pt; font-weight: bold; text-align: center; }
        .subkop { font-size: 10pt; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="5" class="kop">YAYASAN AMAL &amp; SOSIAL PEDULI UMMAT</td>
        </tr>
        <tr>
            <td colspan="5" class="subkop">LAPORAN REKAPITULASI PENGELOLAAN DONASI</td>
        </tr>
        <tr>
            <td colspan="5" class="subkop">
                Periode: <?= esc($filters['start_date'] ?? 'Awal') ?> s/d <?= esc($filters['end_date'] ?? 'Sekarang') ?> | Dicetak: <?= esc($print_date) ?> oleh <?= esc($printed_by) ?>
            </td>
        </tr>
        <tr><td colspan="5"></td></tr>
        <tr style="background-color: #e9ecef; font-weight: bold;">
            <th colspan="4">I. POSISI SALDO &amp; KEUANGAN GLOBAL</th>
            <th class="num">NILAI (RP)</th>
        </tr>
        <tr>
            <td colspan="4">Total Seluruh Penerimaan Donasi Uang (Terverifikasi)</td>
            <td class="num"><?= number_format($rekap['global_kpi']['total_received_money'], 2, '.', '') ?></td>
        </tr>
        <tr>
            <td colspan="4">Total Seluruh Penyaluran Donasi Uang (Tersalurkan)</td>
            <td class="num"><?= number_format($rekap['global_kpi']['total_distributed_money'], 2, '.', '') ?></td>
        </tr>
        <tr style="font-weight: bold; background-color: #d1e7dd;">
            <td colspan="4">SALDO DONASI UANG TERSEDIA</td>
            <td class="num"><?= number_format($rekap['global_kpi']['money_balance'], 2, '.', '') ?></td>
        </tr>
        <tr>
            <td colspan="4">Penerimaan Donasi Periode Laporan</td>
            <td class="num"><?= number_format($rekap['period_received'], 2, '.', '') ?></td>
        </tr>
        <tr>
            <td colspan="4">Penyaluran Donasi Periode Laporan</td>
            <td class="num"><?= number_format($rekap['period_distributed'], 2, '.', '') ?></td>
        </tr>
        <tr style="font-weight: bold;">
            <td colspan="4">Surplus / (Defisit) Bersih Periode Laporan</td>
            <td class="num"><?= number_format($rekap['period_net'], 2, '.', '') ?></td>
        </tr>
        <tr><td colspan="5"></td></tr>
        <tr style="background-color: #e9ecef; font-weight: bold;">
            <th>BULAN</th>
            <th class="num">PENERIMAAN (RP)</th>
            <th class="num">PENYALURAN (RP)</th>
            <th class="num">SELISIH BERSIH (RP)</th>
            <th class="num">SALDO BERJALAN (RP)</th>
        </tr>
        <?php foreach ($rekap['chart_data']['labels'] as $idx => $label) : ?>
            <tr>
                <td><?= esc($label) ?></td>
                <td class="num"><?= number_format($rekap['chart_data']['receipts'][$idx], 2, '.', '') ?></td>
                <td class="num"><?= number_format($rekap['chart_data']['distributions'][$idx], 2, '.', '') ?></td>
                <td class="num"><?= number_format($rekap['chart_data']['net_monthly'][$idx], 2, '.', '') ?></td>
                <td class="num"><?= number_format($rekap['chart_data']['cumulative'][$idx], 2, '.', '') ?></td>
            </tr>
        <?php endforeach ?>
    </table>
</body>
</html>
