<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table            = 'role_permissions';
    protected $primaryKey       = 'role_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'role_id',
        'permission_id',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Ganti seluruh hak akses sebuah role.
     *
     * @param list<int> $permissionIds
     */
    public function replaceForRole(int $roleId, array $permissionIds): void
    {
        $this->where('role_id', $roleId)->delete();

        if ($permissionIds === []) {
            return;
        }

        $now  = date('Y-m-d H:i:s');
        $rows = array_map(static fn (int $permissionId): array => [
            'role_id'       => $roleId,
            'permission_id' => $permissionId,
            'created_at'    => $now,
        ], $permissionIds);

        $this->insertBatch($rows);
    }
}
