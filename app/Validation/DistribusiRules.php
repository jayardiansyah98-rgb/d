<?php

namespace App\Validation;

/**
 * Aturan validasi dasar distribusi.
 *
 * Aturan silang (mis. distribusi uang wajib punya nominal, distribusi
 * barang wajib punya minimal satu rincian) diperiksa oleh DistribusiService
 * agar pesan dapat ditampilkan per field, termasuk untuk baris barang
 * dinamis (`items.*`).
 */
class DistribusiRules
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function store(): array
    {
        return [
            'distribution_type' => [
                'label' => 'Jenis bantuan',
                'rules' => 'required|in_list[money,goods]',
            ],
            'distribution_date' => [
                'label' => 'Tanggal penyaluran',
                'rules' => 'required|valid_date[Y-m-d]',
            ],
            'recipient_name' => [
                'label' => 'Nama penerima manfaat',
                'rules' => 'required|max_length[150]',
            ],
            'recipient_type' => [
                'label' => 'Jenis penerima',
                'rules' => 'required|in_list[individual,organization,community]',
            ],
            'recipient_address' => [
                'label' => 'Alamat penerima',
                'rules' => 'permit_empty|max_length[1000]',
            ],
            'amount' => [
                'label' => 'Nominal penyaluran',
                'rules' => 'permit_empty|decimal|greater_than[0]',
            ],
            'description' => [
                'label' => 'Tujuan penyaluran',
                'rules' => 'required|max_length[2000]',
            ],
            'notes' => [
                'label' => 'Keterangan',
                'rules' => 'permit_empty|max_length[2000]',
            ],
        ];
    }

    /**
     * Aturan pembatalan distribusi.
     *
     * @return array<string, array<string, mixed>>
     */
    public function cancel(): array
    {
        return [
            'cancelled_reason' => [
                'label' => 'Alasan pembatalan',
                'rules' => 'required|min_length[5]|max_length[255]',
            ],
        ];
    }
}
