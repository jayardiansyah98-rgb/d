<?php

namespace App\Models;

/**
 * Program/alokasi donasi.
 */
class ProgramDonasiModel extends BaseModel
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_INACTIVE  = 'inactive';
    public const STATUS_COMPLETED = 'completed';
    public const STATUSES         = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_COMPLETED];

    protected $table         = 'program_donasi';
    protected $allowedFields = [
        'code',
        'name',
        'description',
        'target_amount',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $validationRules = [
        'code'          => 'required|max_length[30]|regex_match[/^[A-Z0-9-]+$/]',
        'name'          => 'required|max_length[150]',
        'description'   => 'permit_empty|max_length[5000]',
        'target_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'start_date'    => 'permit_empty|valid_date[Y-m-d]',
        'end_date'      => 'permit_empty|valid_date[Y-m-d]',
        'status'        => 'required|in_list[active,inactive,completed]',
        'created_by'    => 'permit_empty|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'code' => [
            'required'    => 'Kode program wajib diisi.',
            'max_length'  => 'Kode program maksimal 30 karakter.',
            'regex_match' => 'Kode program hanya boleh berisi huruf besar, angka, dan tanda hubung.',
        ],
        'name' => [
            'required'   => 'Nama program wajib diisi.',
            'max_length' => 'Nama program maksimal 150 karakter.',
        ],
        'target_amount' => [
            'decimal'                => 'Target nominal harus berupa angka desimal.',
            'greater_than_equal_to'  => 'Target nominal tidak boleh negatif.',
        ],
        'start_date' => [
            'valid_date' => 'Tanggal mulai tidak valid.',
        ],
        'end_date' => [
            'valid_date' => 'Tanggal selesai tidak valid.',
        ],
        'status' => [
            'in_list' => 'Status program tidak valid.',
        ],
    ];

    protected function uniqueRules(?int $ignoreId = null): array
    {
        $ignore = $ignoreId === null ? '' : ',id,' . $ignoreId;

        return [
            'code' => 'is_unique[program_donasi.code' . $ignore . ']',
        ];
    }

    /**
     * @return list<object>
     */
    public function donations(int $programId): array
    {
        return model(DonasiModel::class)
            ->where('program_id', $programId)
            ->orderBy('donation_date', 'DESC')
            ->findAll();
    }

    /**
     * @return list<object>
     */
    public function distributions(int $programId): array
    {
        return model(DistribusiModel::class)
            ->where('program_id', $programId)
            ->orderBy('distribution_date', 'DESC')
            ->findAll();
    }

    public function creator(int $userId): ?object
    {
        return $this->db->table('users')
            ->select('id, username, full_name')
            ->where('id', $userId)
            ->get()
            ->getRow();
    }
}
