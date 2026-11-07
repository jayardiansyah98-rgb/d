<?php

namespace App\Validation;

class ProfileRules
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function update(int $id): array
    {
        return [
            'full_name' => [
                'label' => 'Nama lengkap',
                'rules' => 'required|min_length[3]|max_length[100]',
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[254]|is_unique[users.email,id,' . $id . ']',
            ],
            'phone' => [
                'label' => 'Nomor telepon',
                'rules' => 'permit_empty|max_length[30]|regex_match[/^[0-9+\-\s().]+$/]',
                'errors' => [
                    'regex_match' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, titik, dan tanda kurung.',
                ],
            ],
        ];
    }
}
