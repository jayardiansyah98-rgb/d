<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Daftar hak akses aplikasi.
     *
     * Modul donasi, distribusi, dan laporan sudah didaftarkan agar
     * role dapat disiapkan sebelum modulnya diimplementasikan pada tahap
     * berikutnya.
     *
     * @var list<array{name: string, slug: string, group_name: string, description: string}>
     */
    private array $permissions = [
        ['name' => 'Melihat dashboard', 'slug' => 'dashboard.view', 'group_name' => 'Dashboard', 'description' => 'Mengakses ringkasan dashboard.'],

        ['name' => 'Melihat pengguna', 'slug' => 'users.view', 'group_name' => 'Pengguna', 'description' => 'Melihat daftar dan detail pengguna.'],
        ['name' => 'Menambah pengguna', 'slug' => 'users.create', 'group_name' => 'Pengguna', 'description' => 'Membuat akun pengguna baru.'],
        ['name' => 'Mengubah pengguna', 'slug' => 'users.update', 'group_name' => 'Pengguna', 'description' => 'Mengubah profil, role, dan status pengguna.'],
        ['name' => 'Mengarsipkan pengguna', 'slug' => 'users.delete', 'group_name' => 'Pengguna', 'description' => 'Mengarsipkan pengguna dengan soft delete.'],

        ['name' => 'Melihat role', 'slug' => 'roles.view', 'group_name' => 'Role & Hak Akses', 'description' => 'Melihat daftar role dan hak aksesnya.'],
        ['name' => 'Mengelola role', 'slug' => 'roles.manage', 'group_name' => 'Role & Hak Akses', 'description' => 'Membuat, mengubah, dan menghapus role.'],

        ['name' => 'Melihat audit log', 'slug' => 'audit.view', 'group_name' => 'Audit', 'description' => 'Melihat jejak aktivitas penting aplikasi.'],

        ['name' => 'Melihat donatur', 'slug' => 'donors.view', 'group_name' => 'Donatur', 'description' => 'Melihat daftar dan detail donatur.'],
        ['name' => 'Mengelola donatur', 'slug' => 'donors.manage', 'group_name' => 'Donatur', 'description' => 'Menambah, mengubah, menonaktifkan, dan mengarsipkan donatur.'],

        ['name' => 'Melihat penerimaan donasi', 'slug' => 'donations.view', 'group_name' => 'Penerimaan Donasi', 'description' => 'Melihat daftar dan detail penerimaan donasi.'],
        ['name' => 'Mengelola penerimaan donasi', 'slug' => 'donations.manage', 'group_name' => 'Penerimaan Donasi', 'description' => 'Mencatat, mengubah, memverifikasi, dan membatalkan penerimaan donasi.'],

        ['name' => 'Melihat distribusi', 'slug' => 'distributions.view', 'group_name' => 'Distribusi Donasi', 'description' => 'Melihat distribusi donasi (tahap berikutnya).'],
        ['name' => 'Mengelola distribusi', 'slug' => 'distributions.manage', 'group_name' => 'Distribusi Donasi', 'description' => 'Mencatat distribusi donasi (tahap berikutnya).'],

        ['name' => 'Melihat laporan', 'slug' => 'reports.view', 'group_name' => 'Laporan', 'description' => 'Mengakses laporan penerimaan dan distribusi (tahap berikutnya).'],
    ];

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($this->permissions as $permission) {
            $existing = $this->db->table('permissions')
                ->select('id')
                ->where('slug', $permission['slug'])
                ->get()
                ->getRow();

            if ($existing !== null) {
                $this->db->table('permissions')
                    ->where('id', $existing->id)
                    ->update($permission + ['updated_at' => $now]);

                continue;
            }

            $this->db->table('permissions')->insert($permission + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
