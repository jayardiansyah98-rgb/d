<?php

namespace App\Validation;

/**
 * Aturan validasi dasar donasi.
 *
 * Aturan silang (mis. donasi uang wajib punya nominal dan metode bayar)
 * diperiksa oleh DonasiService agar pesan dapat ditampilkan per field.
 */
class DonasiRules
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function store(): array
    {
        return [
            'donation_type' => [
                'label' => 'Jenis donasi',
                'rules' => 'required|in_list[money,goods]',
            ],
            'donor_id' => [
                'label' => 'Donatur',
                'rules' => 'permit_empty|is_natural_no_zero',
            ],
            'is_anonymous' => [
                'label' => 'Donasi anonim',
                'rules' => 'permit_empty|in_list[0,1]',
            ],
            'donation_date' => [
                'label' => 'Tanggal donasi',
                'rules' => 'required|valid_date[Y-m-d]',
            ],
            'amount' => [
                'label' => 'Nominal donasi',
                'rules' => 'permit_empty|decimal|greater_than[0]',
            ],
            'payment_method' => [
                'label' => 'Metode pembayaran',
                'rules' => 'permit_empty|in_list[cash,bank_transfer,other]',
            ],
            'reference_number' => [
                'label' => 'Nomor referensi',
                'rules' => 'permit_empty|max_length[100]',
            ],
            'goods_name' => [
                'label' => 'Nama barang',
                'rules' => 'permit_empty|max_length[150]',
            ],
            'goods_quantity' => [
                'label' => 'Kuantitas barang',
                'rules' => 'permit_empty|decimal|greater_than[0]',
            ],
            'goods_unit' => [
                'label' => 'Satuan barang',
                'rules' => 'permit_empty|max_length[30]',
            ],
            'estimated_value' => [
                'label' => 'Nilai estimasi',
                'rules' => 'permit_empty|decimal|greater_than_equal_to[0]',
            ],
            'notes' => [
                'label' => 'Catatan',
                'rules' => 'permit_empty|max_length[2000]',
            ],
        ];
    }

    /**
     * Aturan pembatalan donasi.
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
