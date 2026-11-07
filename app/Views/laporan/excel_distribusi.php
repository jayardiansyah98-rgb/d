<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Distribusi Donasi</title>
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
        <tr><td colspan="8" class="kop">YAYASAN AMAL &amp; SOSIAL PEDULI UMMAT</td></tr>
        <tr><td colspan="8" class="subkop">LAPORAN PENYALURAN / DISTRIBUSI DONASI</td></tr>
        <tr><td colspan="8" class="subkop">Periode: <?= esc($filters['start_date'] ?? 'Awal') ?> s/d <?= esc($filters['end_date'] ?? 'Sekarang') ?> | Dicetak: <?= esc($print_date) ?></td></tr>
        <tr><td colspan="8"></td></tr>
        <tr>
            <th>NO</th>
            <th>NO PENYALURAN</th>
            <th>TANGGAL</th>
            <th>PENERIMA MANFAAT</th>
            <th>TIPE</th>
            <th>JENIS</th>
            <th class="num">NILAI DANA (RP)</th>
            <th>RINCIAN BARANG / TUJUAN</th>
            <th>STATUS</th>
        </tr>
        <?php foreach ($report['data'] as $idx => $dist) : ?>
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
            $items = $isArr ? ($dist['items'] ?? []) : ($dist->items ?? []);
            ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= esc($dNum) ?></td>
                <td><?= esc($dDate) ?></td>
                <td><?= esc($rName) ?></td>
                <td><?= esc(ucfirst($rType)) ?></td>
                <td><?= $dType === 'money' ? 'Uang' : 'Barang' ?></td>
                <td class="num"><?= $dType === 'money' ? number_format($amt, 2, '.', '') : '0.00' ?></td>
                <td>
                    <?php if ($dType === 'goods' && $items !== []) : ?>
                        <?php
                        $itemStr = [];
                        foreach ($items as $it) {
                            $itemStr[] = $it->quantity . ' ' . $it->unit . ' ' . $it->item_name;
                        }
                        echo esc(implode('; ', $itemStr)) . ' | ' . esc($desc);
                        ?>
                    <?php else : ?>
                        <?= esc($desc) ?>
                    <?php endif ?>
                </td>
                <td><?= esc(ucfirst($st)) ?></td>
            </tr>
        <?php endforeach ?>
        <tr style="font-weight: bold; background-color: #e9ecef;">
            <td colspan="6" class="num">TOTAL DANA TERSALURKAN (AKTIF):</td>
            <td class="num"><?= number_format($report['active_money'], 2, '.', '') ?></td>
            <td colspan="2">TOTAL PENYALURAN: <?= esc((string) $report['total_rows']) ?></td>
        </tr>
    </table>
</body>
</html>
