<?php

namespace App\Validation;

/**
 * Aturan validasi data donatur.
 *
 * Kode donatur dibuat otomatis oleh DonaturService sehingga tidak
 * divalidasi dari input pengguna. Semua data tetap divalidasi di server.
 */
class DonaturRules
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function store(): array
    {
        return [
            'name' => [
                'label' => 'Nama donatur',
                'rules' => 'required|min_length[2]|max_length[150]',
            ],
            'type' => [
                'label' => 'Jenis donatur',
                'rules' => 'required|in_list[individual,organization]',
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'permit_empty|valid_email|max_length[254]',
            ],
            'phone' => [
                'label' => 'Nomor telepon',
                'rules' => 'permit_empty|max_length[30]|regex_match[/^[0-9+\-\s().]+$/]',
                'errors' => [
                    'regex_match' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, titik, dan tanda kurung.',
                ],
            ],
            'address' => [
                'label' => 'Alamat',
                'rules' => 'permit_empty|max_length[1000]',
            ],
            'notes' => [
                'label' => 'Catatan',
                'rules' => 'permit_empty|max_length[2000]',
            ],
            'is_anonymous' => [
                'label' => 'Donatur anonim',
                'rules' => 'permit_empty|in_list[0,1]',
            ],
            'status' => [
                'label' => 'Status',
                'rules' => 'required|in_list[active,inactive]',
            ],
        ];
    }
}
