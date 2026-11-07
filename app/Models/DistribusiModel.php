<?php

namespace App\Models;

/**
 * Distribusi dana atau barang kepada penerima manfaat.
 */
class DistribusiModel extends BaseModel
{
    public const TYPE_MONEY = 'money';
    public const TYPE_GOODS = 'goods';
    public const TYPES      = [self::TYPE_MONEY, self::TYPE_GOODS];

    public const STATUS_DRAFT       = 'draft';
    public const STATUS_APPROVED    = 'approved';
    public const STATUS_DISTRIBUTED = 'distributed';
    public const STATUS_CANCELLED   = 'cancelled';
    public const STATUSES           = [
        self::STATUS_DRAFT,
        self::STATUS_APPROVED,
        self::STATUS_DISTRIBUTED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Status yang dihitung sebagai penyaluran aktif dan mengurangi saldo.
     * Draf belum mengurangi saldo; batal tidak pernah mengurangi saldo.
     */
    public const ACTIVE_STATUSES = [self::STATUS_APPROVED, self::STATUS_DISTRIBUTED];

    public const RECIPIENT_INDIVIDUAL   = 'individual';
    public const RECIPIENT_ORGANIZATION = 'organization';
    public const RECIPIENT_COMMUNITY    = 'community';
    public const RECIPIENT_TYPES        = [
        self::RECIPIENT_INDIVIDUAL,
        self::RECIPIENT_ORGANIZATION,
        self::RECIPIENT_COMMUNITY,
    ];

    protected $table         = 'distribusi';
    protected $allowedFields = [
        'distribution_number',
        'program_id',
        'donation_id',
        'distribution_type',
        'status',
        'distribution_date',
        'recipient_name',
        'recipient_type',
        'recipient_address',
        'amount',
        'description',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'distributed_at',
        'cancelled_reason',
    ];

    protected $validationRules = [
        'distribution_number' => 'required|max_length[30]|regex_match[/^[A-Z0-9-]+$/]',
        'program_id'          => 'permit_empty|is_natural_no_zero',
        'donation_id'         => 'permit_empty|is_natural_no_zero',
        'distribution_type'   => 'required|in_list[money,goods]',
        'status'              => 'required|in_list[draft,approved,distributed,cancelled]',
        'distribution_date'   => 'required|valid_date[Y-m-d]',
        'recipient_name'      => 'required|max_length[150]',
        'recipient_type'      => 'required|in_list[individual,organization,community]',
        'recipient_address'   => 'permit_empty|max_length[1000]',
        'amount'              => 'permit_empty|decimal|greater_than[0]',
        'description'         => 'permit_empty|max_length[2000]',
        'notes'               => 'permit_empty|max_length[2000]',
        'created_by'          => 'permit_empty|is_natural_no_zero',
        'approved_by'         => 'permit_empty|is_natural_no_zero',
        'approved_at'         => 'permit_empty',
        'distributed_at'      => 'permit_empty',
        'cancelled_reason'    => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'distribution_number' => [
            'required'    => 'Nomor distribusi wajib diisi.',
            'max_length'  => 'Nomor distribusi maksimal 30 karakter.',
            'regex_match' => 'Nomor distribusi hanya boleh berisi huruf besar, angka, dan tanda hubung.',
        ],
        'distribution_type' => [
            'required' => 'Jenis distribusi wajib dipilih.',
            'in_list'  => 'Jenis distribusi tidak valid.',
        ],
        'status' => [
            'in_list' => 'Status distribusi tidak valid.',
        ],
        'distribution_date' => [
            'required'   => 'Tanggal distribusi wajib diisi.',
            'valid_date' => 'Tanggal distribusi tidak valid.',
        ],
        'recipient_name' => [
            'required'   => 'Nama penerima wajib diisi.',
            'max_length' => 'Nama penerima maksimal 150 karakter.',
        ],
        'amount' => [
            'decimal'      => 'Nominal distribusi harus berupa angka.',
            'greater_than' => 'Nominal distribusi harus lebih dari 0.',
        ],
    ];

    protected function uniqueRules(?int $ignoreId = null): array
    {
        $ignore = $ignoreId === null ? '' : ',id,' . $ignoreId;

        return [
            'distribution_number' => 'is_unique[distribusi.distribution_number' . $ignore . ']',
        ];
    }

    /**
     * Pemeriksaan aturan silang. Kirim data lengkap (hasil merge saat update).
     *
     * @return array<string, string>
     */
    public function businessRuleErrors(array $data): array
    {
        $errors = [];
        $type   = $data['distribution_type'] ?? null;

        if ($type === self::TYPE_MONEY) {
            if (! isset($data['amount']) || ! is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
                $errors['amount'] = 'Nominal distribusi uang wajib lebih dari 0.';
            }
        } elseif ($type === self::TYPE_GOODS) {
            if (! empty($data['amount'])) {
                $errors['amount'] = 'Distribusi barang tidak boleh memiliki nominal uang.';
            }
        } else {
            $errors['distribution_type'] = 'Jenis distribusi wajib dipilih.';
        }

        return $errors;
    }

    public function program(int $programId): ?object
    {
        return model(ProgramDonasiModel::class)->withDeleted()->find($programId);
    }

    /**
     * Query dasar daftar/detail distribusi dengan nama program.
     * Kolom diambil eksplisit agar kolom distribusi tidak tertimpa.
     */
    public function withRelations(): self
    {
        return $this
            ->select('distribusi.*, program_donasi.name AS program_name')
            ->join('program_donasi', 'program_donasi.id = distribusi.program_id', 'left');
    }

    public function findWithRelations(int $id): ?object
    {
        return $this->withRelations()
            ->where('distribusi.id', $id)
            ->first();
    }

    public function donation(int $donationId): ?object
    {
        return model(DonasiModel::class)->withDeleted()->find($donationId);
    }

    /**
     * @return list<object>
     */
    public function details(int $distributionId): array
    {
        return model(DetailDistribusiModel::class)
            ->where('distribution_id', $distributionId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function creator(int $userId): ?object
    {
        return $this->findUser($userId);
    }

    public function approver(int $userId): ?object
    {
        return $this->findUser($userId);
    }

    private function findUser(int $userId): ?object
    {
        return $this->db->table('users')
            ->select('id, username, full_name')
            ->where('id', $userId)
            ->get()
            ->getRow();
    }
}
