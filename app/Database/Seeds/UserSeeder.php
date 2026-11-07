<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

/**
 * Membuat akun awal.
 *
 * Tidak ada password yang ditulis di source code. Isi salah satu variabel
 * environment berikut sebelum menjalankan seeder:
 * - seed.adminPassword       (plaintext, dibaca sekali lalu di-hash)
 * - seed.adminPasswordHash   (hash bcrypt yang sudah jadi)
 *
 * Akun demo hanya dibuat jika seed.demoUsers = true dan password masing-masing
 * diatur melalui environment.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminHash     = trim((string) env('seed.adminPasswordHash', ''));
        $adminPassword = (string) env('seed.adminPassword', '');

        if ($adminHash !== '' && (password_get_info($adminHash)['algo'] ?? 0) === 0) {
            log_message('warning', 'UserSeeder: seed.adminPasswordHash bukan hash password yang valid; nilai diabaikan.');
            $adminHash = '';
        }

        if ($adminHash === '' && $adminPassword === '') {
            log_message('warning', 'UserSeeder: administrator dilewati karena seed.adminPassword/seed.adminPasswordHash belum diatur.');
        } else {
            $this->createUserIfMissing(
                roleSlug: 'admin',
                username: 'admin',
                email: 'admin@yayasan.test',
                fullName: 'Administrator Yayasan',
                password: $adminPassword,
                passwordHash: $adminHash,
            );
        }

        if (! filter_var(env('seed.demoUsers', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $demoUsers = [
            [
                'roleSlug'    => 'keuangan',
                'username'    => 'pengurus',
                'email'       => 'pengurus@yayasan.test',
                'fullName'    => 'Bagian Keuangan',
                'passwordEnv' => 'seed.pengurusPassword',
            ],
            [
                'roleSlug'    => 'distribusi',
                'username'    => 'petugas',
                'email'       => 'petugas@yayasan.test',
                'fullName'    => 'Bagian Distribusi',
                'passwordEnv' => 'seed.petugasPassword',
            ],
            [
                'roleSlug'    => 'pimpinan',
                'username'    => 'auditor',
                'email'       => 'auditor@yayasan.test',
                'fullName'    => 'Pimpinan Yayasan',
                'passwordEnv' => 'seed.auditorPassword',
            ],
        ];

        foreach ($demoUsers as $user) {
            $password = (string) env($user['passwordEnv'], '');

            if ($password === '') {
                log_message('warning', 'UserSeeder: ' . $user['username'] . ' dilewati karena ' . $user['passwordEnv'] . ' belum diatur.');

                continue;
            }

            $this->createUserIfMissing(
                roleSlug: $user['roleSlug'],
                username: $user['username'],
                email: $user['email'],
                fullName: $user['fullName'],
                password: $password,
                passwordHash: '',
            );
        }
    }

    private function createUserIfMissing(
        string $roleSlug,
        string $username,
        string $email,
        string $fullName,
        string $password,
        string $passwordHash,
    ): void {
        $existing = $this->db->table('users')
            ->select('id')
            ->where('username', $username)
            ->get()
            ->getRow();

        if ($existing !== null) {
            return;
        }

        $role = $this->db->table('roles')
            ->select('id')
            ->where('slug', $roleSlug)
            ->get()
            ->getRow();

        if ($role === null) {
            return;
        }

        if ($passwordHash === '' && $password === '') {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'       => (int) $role->id,
            'username'      => $username,
            'email'         => $email,
            'password_hash' => $passwordHash !== '' ? $passwordHash : UserModel::hashPassword($password),
            'full_name'     => $fullName,
            'phone'         => null,
            'status'        => 'active',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}
