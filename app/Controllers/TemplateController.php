<?php

namespace App\Controllers;

class TemplateController extends BaseController
{
    /**
     * Pratinjau kerangka UI laporan.
     *
     * Halaman ini sengaja tidak memuat data transaksi. Modul laporan
     * sesungguhnya akan diimplementasikan pada tahap berikutnya.
     */
    public function report()
    {
        return view('templates/report', [
            'title' => 'Template Laporan',
        ]);
    }
}
