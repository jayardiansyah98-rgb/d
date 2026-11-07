<?php

namespace App\Validation;

/**
 * Aturan validasi pengguna.
 *
 * Aturan unik dibuat dinamis karena harus mengecualikan baris yang
 * sedang diubah. Semua data tetap divalidasi di server.
 */
class UserRules
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function store(): array
    {
        return [
            'full_name' => [
                'label' => 'Nama lengkap',
                'rules' => 'required|min_length[3]|max_length[100]',
            ],
            'username' => [
                'label' => 'Username',
                'rules' => 'required|alpha_dash|min_length[3]|max_length[50]|is_unique[users.username]',
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[254]|is_unique[users.email]',
            ],
            'phone' => [
                'label' => 'Nomor telepon',
                'rules' => 'permit_empty|max_length[30]|regex_match[/^[0-9+\-\s().]+$/]',
                'errors' => [
                    'regex_match' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, titik, dan tanda kurung.',
                ],
            ],
            'role_id' => [
                'label' => 'Role',
                'rules' => 'required|is_natural_no_zero|is_not_unique[roles.id]',
            ],
            'status' => [
                'label' => 'Status',
                'rules' => 'required|in_list[active,inactive]',
            ],
            'password' => [
                'label' => 'Kata sandi',
                'rules' => 'required|min_length[8]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]',
                'errors' => [
                    'regex_match' => 'Kata sandi harus mengandung huruf besar, huruf kecil, dan angka.',
                ],
            ],
            'password_confirm' => [
                'label' => 'Konfirmasi kata sandi',
                'rules' => 'required|matches[password]',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function update(int $id): array
    {
        $rules = $this->store();

        $rules['username']['rules'] = 'required|alpha_dash|min_length[3]|max_length[50]|is_unique[users.username,id,' . $id . ']';
        $rules['email']['rules']    = 'required|valid_email|max_length[254]|is_unique[users.email,id,' . $id . ']';

        $rules['password']['rules'] = 'permit_empty|min_length[8]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]';
        $rules['password_confirm']['rules'] = 'required_with[password]|matches[password]';

        return $rules;
    }
}
