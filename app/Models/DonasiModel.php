<?php

namespace App\Models;

/**
 * Penerimaan donasi uang atau barang.
 *
 * Aturan silang (jenis donasi vs kolom nominal/barang dan anonymitas)
 * diperiksa oleh businessRuleErrors() serta check constraint database.
 */
class DonasiModel extends BaseModel
{
    public const TYPE_MONEY = 'money';
    public const TYPE_GOODS = 'goods';
    public const TYPES      = [self::TYPE_MONEY, self::TYPE_GOODS];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_VERIFIED  = 'verified';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUSES         = [self::STATUS_PENDING, self::STATUS_VERIFIED, self::STATUS_CANCELLED];

    public const PAYMENT_CASH          = 'cash';
    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    public const PAYMENT_OTHER         = 'other';
    public const PAYMENT_METHODS       = [self::PAYMENT_CASH, self::PAYMENT_BANK_TRANSFER, self::PAYMENT_OTHER];

    protected $table         = 'donasi';
    protected $allowedFields = [
        'transaction_number',
        'donor_id',
        'is_anonymous',
        'program_id',
        'donation_type',
        'status',
        'donation_date',
        'amount',
        'payment_method',
        'reference_number',
        'goods_name',
        'goods_quantity',
        'goods_unit',
        'estimated_value',
        'notes',
        'created_by',
        'verified_by',
        'verified_at',
        'cancelled_reason',
    ];

    protected $validationRules = [
        'transaction_number' => 'required|max_length[30]|regex_match[/^[A-Z0-9-]+$/]',
        'donor_id'           => 'permit_empty|is_natural_no_zero',
        'is_anonymous'       => 'permit_empty|in_list[0,1]',
        'program_id'         => 'permit_empty|is_natural_no_zero',
        'donation_type'      => 'required|in_list[money,goods]',
        'status'             => 'required|in_list[pending,verified,cancelled]',
        'donation_date'      => 'required|valid_date[Y-m-d]',
        'amount'             => 'permit_empty|decimal|greater_than[0]',
        'payment_method'     => 'permit_empty|in_list[cash,bank_transfer,other]',
        'reference_number'   => 'permit_empty|max_length[100]',
        'goods_name'         => 'permit_empty|max_length[150]',
        'goods_quantity'     => 'permit_empty|decimal|greater_than[0]',
        'goods_unit'         => 'permit_empty|max_length[30]',
        'estimated_value'    => 'permit_empty|decimal|greater_than_equal_to[0]',
        'notes'              => 'permit_empty|max_length[2000]',
        'created_by'         => 'permit_empty|is_natural_no_zero',
        'verified_by'        => 'permit_empty|is_natural_no_zero',
        'verified_at'        => 'permit_empty',
        'cancelled_reason'   => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'transaction_number' => [
            'required'    => 'Nomor transaksi wajib diisi.',
            'max_length'  => 'Nomor transaksi maksimal 30 karakter.',
            'regex_match' => 'Nomor transaksi hanya boleh berisi huruf besar, angka, dan tanda hubung.',
        ],
        'donation_type' => [
            'required' => 'Jenis donasi wajib dipilih.',
            'in_list'  => 'Jenis donasi tidak valid.',
        ],
        'status' => [
            'in_list' => 'Status donasi tidak valid.',
        ],
        'donation_date' => [
            'required'   => 'Tanggal donasi wajib diisi.',
            'valid_date' => 'Tanggal donasi tidak valid.',
        ],
        'amount' => [
            'decimal'      => 'Nominal donasi harus berupa angka.',
            'greater_than' => 'Nominal donasi harus lebih dari 0.',
        ],
        'goods_quantity' => [
            'decimal'      => 'Kuantitas barang harus berupa angka.',
            'greater_than' => 'Kuantitas barang harus lebih dari 0.',
        ],
    ];

    protected function uniqueRules(?int $ignoreId = null): array
    {
        $ignore = $ignoreId === null ? '' : ',id,' . $ignoreId;

        return [
            'transaction_number' => 'is_unique[donasi.transaction_number' . $ignore . ']',
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
        $type   = $data['donation_type'] ?? null;
        $anonymous = filter_var($data['is_anonymous'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $anonymous && empty($data['donor_id'])) {
            $errors['donor_id'] = 'Donatur wajib dipilih untuk donasi yang tidak anonim.';
        }

        if ($type === self::TYPE_MONEY) {
            if (! isset($data['amount']) || ! is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
                $errors['amount'] = 'Nominal donasi uang wajib lebih dari 0.';
            }

            if (! empty($data['goods_name']) || ! empty($data['goods_quantity']) || ! empty($data['goods_unit'])) {
                $errors['goods_name'] = 'Donasi uang tidak boleh memiliki rincian barang.';
            }
        } elseif ($type === self::TYPE_GOODS) {
            if (empty($data['goods_name'])) {
                $errors['goods_name'] = 'Jenis/nama barang wajib diisi untuk donasi barang.';
            }

            if (! isset($data['goods_quantity']) || ! is_numeric($data['goods_quantity']) || (float) $data['goods_quantity'] <= 0) {
                $errors['goods_quantity'] = 'Kuantitas barang wajib lebih dari 0.';
            }

            if (empty($data['goods_unit'])) {
                $errors['goods_unit'] = 'Satuan barang wajib diisi.';
            }

            if (! empty($data['amount'])) {
                $errors['amount'] = 'Donasi barang tidak boleh memiliki nominal uang.';
            }
        } else {
            $errors['donation_type'] = 'Jenis donasi wajib dipilih.';
        }

        return $errors;
    }

    /**
     * Query dasar daftar/detail donasi dengan nama donatur dan program.
     * Kolom diambil eksplisit agar tidak menimpa kolom donasi.
     */
    public function withRelations(): self
    {
        return $this
            ->select('donasi.*, donatur.name AS donor_name, donatur.code AS donor_code, donatur.status AS donor_status, donatur.deleted_at AS donor_deleted_at, program_donasi.name AS program_name')
            ->join('donatur', 'donatur.id = donasi.donor_id', 'left')
            ->join('program_donasi', 'program_donasi.id = donasi.program_id', 'left');
    }

    public function findWithRelations(int $id): ?object
    {
        return $this->withRelations()
            ->where('donasi.id', $id)
            ->first();
    }

    public function donor(int $donorId): ?object
    {
        return model(DonaturModel::class)->withDeleted()->find($donorId);
    }

    public function program(int $programId): ?object
    {
        return model(ProgramDonasiModel::class)->withDeleted()->find($programId);
    }

    public function creator(int $userId): ?object
    {
        return $this->findUser($userId);
    }

    public function verifier(int $userId): ?object
    {
        return $this->findUser($userId);
    }

    /**
     * @return list<object>
     */
    public function distributions(int $donationId): array
    {
        return model(DistribusiModel::class)
            ->where('donation_id', $donationId)
            ->orderBy('distribution_date', 'DESC')
            ->findAll();
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
