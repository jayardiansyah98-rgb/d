<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'slug',
        'group_name',
        'description',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $skipValidation     = true;
    protected $validationRules    = [];
    protected $validationMessages = [];

    /**
     * @return array<string, list<object>>
     */
    public function getGrouped(): array
    {
        $permissions = $this->orderBy('group_name', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        $grouped = [];

        foreach ($permissions as $permission) {
            $grouped[$permission->group_name][] = $permission;
        }

        return $grouped;
    }

    /**
     * @return list<int>
     */
    public function getAllIds(): array
    {
        $rows = $this->select('id')->findAll();

        return array_map(static fn (object $row): int => (int) $row->id, $rows);
    }
}
