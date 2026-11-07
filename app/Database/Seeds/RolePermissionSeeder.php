<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Pemetaan hak akses bawaan untuk setiap role.
     *
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        'admin' => [
            'dashboard.view',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.manage',
            'audit.view',
            'donors.view', 'donors.manage',
            'donations.view', 'donations.manage',
            'distributions.view', 'distributions.manage',
            'reports.view',
        ],
        'keuangan' => [
            'dashboard.view',
            'donors.view', 'donors.manage',
            'donations.view', 'donations.manage',
            'reports.view',
        ],
        'distribusi' => [
            'dashboard.view',
            'donors.view',
            'donations.view',
            'distributions.view', 'distributions.manage',
        ],
        'pimpinan' => [
            'dashboard.view',
            'donations.view',
            'distributions.view',
            'reports.view',
            'audit.view',
        ],
    ];

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($this->rolePermissions as $roleSlug => $permissionSlugs) {
            $role = $this->db->table('roles')
                ->select('id')
                ->where('slug', $roleSlug)
                ->get()
                ->getRow();

            if ($role === null) {
                continue;
            }

            $permissionRows = $this->db->table('permissions')
                ->select('id')
                ->whereIn('slug', $permissionSlugs)
                ->get()
                ->getResult();

            $this->db->table('role_permissions')
                ->where('role_id', $role->id)
                ->delete();

            if ($permissionRows === []) {
                continue;
            }

            $rows = array_map(static fn (object $permission): array => [
                'role_id'       => (int) $role->id,
                'permission_id' => (int) $permission->id,
                'created_at'    => $now,
            ], $permissionRows);

            $this->db->table('role_permissions')->insertBatch($rows);
        }
    }
}
