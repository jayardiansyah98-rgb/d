<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $skipValidation     = true;
    protected $validationRules    = [];
    protected $validationMessages = [];

    public function findBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->first();
    }

    /**
     * Daftar role beserta jumlah pengguna dan jumlah hak akses.
     *
     * @return list<object>
     */
    public function getWithStats(): array
    {
        return $this->db->table('roles')
            ->select('roles.*, COUNT(DISTINCT users.id) AS user_count, COUNT(DISTINCT role_permissions.permission_id) AS permission_count')
            ->join('users', 'users.role_id = roles.id AND users.deleted_at IS NULL', 'left')
            ->join('role_permissions', 'role_permissions.role_id = roles.id', 'left')
            ->groupBy('roles.id')
            ->orderBy('roles.is_system', 'DESC')
            ->orderBy('roles.name', 'ASC')
            ->get()
            ->getResultObject();
    }

    /**
     * @return list<int>
     */
    public function getPermissionIds(int $roleId): array
    {
        $rows = $this->db->table('role_permissions')
            ->select('permission_id')
            ->where('role_id', $roleId)
            ->get()
            ->getResultObject();

        return array_map(static fn (object $row): int => (int) $row->permission_id, $rows);
    }

    /**
     * Menghitung seluruh pengguna (termasuk yang diarsipkan) karena
     * foreign key tetap mengacu pada role tersebut.
     */
    public function countUsers(int $roleId): int
    {
        return $this->db->table('users')
            ->where('role_id', $roleId)
            ->countAllResults();
    }
}
