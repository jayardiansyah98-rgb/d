<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $skipValidation     = true;
    protected $validationRules    = [];
    protected $validationMessages = [];

    /**
     * @return list<string>
     */
    public function getActionOptions(): array
    {
        $rows = $this->distinct()
            ->select('action')
            ->orderBy('action', 'ASC')
            ->findAll();

        return array_map(static fn (object $row): string => $row->action, $rows);
    }
}
