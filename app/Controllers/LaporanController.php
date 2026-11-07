<?php

namespace App\Controllers;

use App\Libraries\LaporanService;
use CodeIgniter\HTTP\ResponseInterface;

class LaporanController extends BaseController
{
    private LaporanService $service;

    public function __construct(?LaporanService $service = null)
    {
        $this->service = $service ?? service('laporanService');
    }

    /**
     * Halaman Laporan Rekapitulasi Umum.
     */
    public function rekapitulasi(): string
    {
        $filters = [
            'start_date' => $this->request->getGet('start_date') ?: null,
            'end_date'   => $this->request->getGet('end_date') ?: null,
        ];

        $rekap = $this->service->getRekapitulasi($filters);

        return view('laporan/rekapitulasi', [
            'title'   => 'Laporan Rekapitulasi Donasi',
            'rekap'   => $rekap,
            'filters' => $filters,
        ]);
    }

    /**
     * Cetak Laporan Rekapitulasi.
     */
    public function printRekapitulasi(): string
    {
        $filters = [
            'start_date' => $this->request->getGet('start_date') ?: null,
            'end_date'   => $this->request->getGet('end_date') ?: null,
        ];

        $rekap = $this->service->getRekapitulasi($filters);

        return view('laporan/print_rekapitulasi', [
            'title'      => 'Cetak Rekapitulasi Donasi',
            'rekap'      => $rekap,
            'filters'    => $filters,
            'printed_by' => current_user()?->full_name ?? 'Sistem',
            'print_date' => date('d/m/Y H:i'),
        ]);
    }

    /**
     * Ekspor Rekapitulasi ke Excel.
     */
    public function excelRekapitulasi(): ResponseInterface
    {
        $filters = [
            'start_date' => $this->request->getGet('start_date') ?: null,
            'end_date'   => $this->request->getGet('end_date') ?: null,
        ];

        $rekap    = $this->service->getRekapitulasi($filters);
        $filename = 'rekapitulasi_donasi_' . date('Ymd_His') . '.xls';

        $html = view('laporan/excel_rekapitulasi', [
            'rekap'      => $rekap,
            'filters'    => $filters,
            'printed_by' => current_user()?->full_name ?? 'Sistem',
            'print_date' => date('d/m/Y H:i'),
        ]);

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody("\xEF\xBB\xBF" . $html);
    }

    /**
     * Halaman Laporan Penerimaan Donasi.
     */
    public function penerimaan(): string
    {
        $filters = [
            'start_date'     => $this->request->getGet('start_date') ?: null,
            'end_date'       => $this->request->getGet('end_date') ?: null,
            'donor_id'       => $this->request->getGet('donor_id') ?: null,
            'payment_method' => $this->request->getGet('payment_method') ?: null,
            'donation_type'  => $this->request->getGet('donation_type') ?: null,
            'status'         => $this->request->getGet('status') ?: null,
            'q'              => $this->request->getGet('q') ?: null,
        ];

        $report = $this->service->getLaporanPenerimaan($filters, 20);

        return view('laporan/penerimaan', [
            'title'        => 'Laporan Penerimaan Donasi',
            'report'       => $report,
            'filters'      => $filters,
            'donorOptions' => $this->service->getDonorOptions(),
        ]);
    }

    /**
     * Cetak Laporan Penerimaan Donasi.
     */
    public function printPenerimaan(): string
    {
        $filters = [
            'start_date'     => $this->request->getGet('start_date') ?: null,
            'end_date'       => $this->request->getGet('end_date') ?: null,
            'donor_id'       => $this->request->getGet('donor_id') ?: null,
            'payment_method' => $this->request->getGet('payment_method') ?: null,
            'donation_type'  => $this->request->getGet('donation_type') ?: null,
            'status'         => $this->request->getGet('status') ?: null,
            'q'              => $this->request->getGet('q') ?: null,
        ];

        $report = $this->service->getLaporanPenerimaan($filters, 1000, true);

        return view('laporan/print_penerimaan', [
            'title'      => 'Cetak Laporan Penerimaan Donasi',
            'report'     => $report,
            'filters'    => $filters,
            'printed_by' => current_user()?->full_name ?? 'Sistem',
            'print_date' => date('d/m/Y H:i'),
        ]);
    }

    /**
     * Ekspor Laporan Penerimaan ke Excel.
     */
    public function excelPenerimaan(): ResponseInterface
    {
        $filters = [
            'start_date'     => $this->request->getGet('start_date') ?: null,
            'end_date'       => $this->request->getGet('end_date') ?: null,
            'donor_id'       => $this->request->getGet('donor_id') ?: null,
            'payment_method' => $this->request->getGet('payment_method') ?: null,
            'donation_type'  => $this->request->getGet('donation_type') ?: null,
            'status'         => $this->request->getGet('status') ?: null,
            'q'              => $this->request->getGet('q') ?: null,
        ];

        $report   = $this->service->getLaporanPenerimaan($filters, 1000, true);
        $filename = 'laporan_penerimaan_donasi_' . date('Ymd_His') . '.xls';

        $html = view('laporan/excel_penerimaan', [
            'report'     => $report,
            'filters'    => $filters,
            'printed_by' => current_user()?->full_name ?? 'Sistem',
            'print_date' => date('d/m/Y H:i'),
        ]);

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody("\xEF\xBB\xBF" . $html);
    }

    /**
     * Halaman Laporan Distribusi Donasi.
     */
    public function distribusi(): string
    {
        $filters = [
            'start_date'        => $this->request->getGet('start_date') ?: null,
            'end_date'          => $this->request->getGet('end_date') ?: null,
            'recipient_type'    => $this->request->getGet('recipient_type') ?: null,
            'distribution_type' => $this->request->getGet('distribution_type') ?: null,
            'status'            => $this->request->getGet('status') ?: null,
            'q'                 => $this->request->getGet('q') ?: null,
        ];

        $report = $this->service->getLaporanDistribusi($filters, 20);

        return view('laporan/distribusi', [
            'title'   => 'Laporan Distribusi Donasi',
            'report'  => $report,
            'filters' => $filters,
        ]);
    }

    /**
     * Cetak Laporan Distribusi Donasi.
     */
    public function printDistribusi(): string
    {
        $filters = [
            'start_date'        => $this->request->getGet('start_date') ?: null,
            'end_date'          => $this->request->getGet('end_date') ?: null,
            'recipient_type'    => $this->request->getGet('recipient_type') ?: null,
            'distribution_type' => $this->request->getGet('distribution_type') ?: null,
            'status'            => $this->request->getGet('status') ?: null,
            'q'                 => $this->request->getGet('q') ?: null,
        ];

        $report = $this->service->getLaporanDistribusi($filters, 1000, true);

        return view('laporan/print_distribusi', [
            'title'      => 'Cetak Laporan Distribusi Donasi',
            'report'     => $report,
            'filters'    => $filters,
            'printed_by' => current_user()?->full_name ?? 'Sistem',
            'print_date' => date('d/m/Y H:i'),
        ]);
    }

    /**
     * Ekspor Laporan Distribusi ke Excel.
     */
    public function excelDistribusi(): ResponseInterface
    {
        $filters = [
            'start_date'        => $this->request->getGet('start_date') ?: null,
            'end_date'          => $this->request->getGet('end_date') ?: null,
            'recipient_type'    => $this->request->getGet('recipient_type') ?: null,
            'distribution_type' => $this->request->getGet('distribution_type') ?: null,
            'status'            => $this->request->getGet('status') ?: null,
            'q'                 => $this->request->getGet('q') ?: null,
        ];

        $report   = $this->service->getLaporanDistribusi($filters, 1000, true);
        $filename = 'laporan_distribusi_donasi_' . date('Ymd_His') . '.xls';

        $html = view('laporan/excel_distribusi', [
            'report'     => $report,
            'filters'    => $filters,
            'printed_by' => current_user()?->full_name ?? 'Sistem',
            'print_date' => date('d/m/Y H:i'),
        ]);

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody("\xEF\xBB\xBF" . $html);
    }
}
