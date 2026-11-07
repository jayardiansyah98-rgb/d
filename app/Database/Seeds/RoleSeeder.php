<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Role awal aplikasi.
     *
     * `legacy_slug` dipakai untuk mengganti nama role dari versi sebelumnya
     * secara in-place. Dengan begitu pengguna yang sudah ada tetap terhubung
     * ke role yang benar dan tidak ada role duplikat.
     *
     * @var list<array{name: string, slug: string, legacy_slug: string|null, description: string, is_system: int}>
     */
    private array $roles = [
        [
            'name'        => 'Admin',
            'slug'        => 'admin',
            'legacy_slug' => 'administrator',
            'description' => 'Mengelola pengguna, role, dan seluruh modul aplikasi.',
            'is_system'   => 1,
        ],
        [
            'name'        => 'Keuangan',
            'slug'        => 'keuangan',
            'legacy_slug' => 'pengurus',
            'description' => 'Mengelola data donatur dan penerimaan donasi, serta melihat laporan.',
            'is_system'   => 0,
        ],
        [
            'name'        => 'Distribusi',
            'slug'        => 'distribusi',
            'legacy_slug' => 'petugas',
            'description' => 'Mengelola penyaluran donasi kepada penerima manfaat.',
            'is_system'   => 0,
        ],
        [
            'name'        => 'Pimpinan',
            'slug'        => 'pimpinan',
            'legacy_slug' => 'auditor',
            'description' => 'Melihat dashboard, laporan, dan audit tanpa mengubah transaksi.',
            'is_system'   => 0,
        ],
    ];

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($this->roles as $role) {
            $legacySlug = $role['legacy_slug'];
            unset($role['legacy_slug']);

            $existing = $this->findBySlug($role['slug']);

            if ($existing === null && $legacySlug !== null) {
                $legacy = $this->findBySlug($legacySlug);

                if ($legacy !== null) {
                    $this->db->table('roles')
                        ->where('id', $legacy->id)
                        ->update($role + ['updated_at' => $now]);

                    continue;
                }
            }

            if ($existing !== null) {
                $this->db->table('roles')
                    ->where('id', $existing->id)
                    ->update($role + ['updated_at' => $now]);

                continue;
            }

            $this->db->table('roles')->insert($role + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function findBySlug(string $slug): ?object
    {
        return $this->db->table('roles')
            ->select('id')
            ->where('slug', $slug)
            ->get()
            ->getRow();
    }
}
