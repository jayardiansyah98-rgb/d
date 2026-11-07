<?php

namespace App\Models;

/**
 * Data donatur.
 *
 * Donatur dapat ditandai anonim. Pada level transaksi, anonymitas
 * tetap ditentukan oleh kolom donasi.is_anonymous.
 */
class DonaturModel extends BaseModel
{
    public const TYPE_INDIVIDUAL   = 'individual';
    public const TYPE_ORGANIZATION = 'organization';
    public const TYPES             = [self::TYPE_INDIVIDUAL, self::TYPE_ORGANIZATION];

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUSES        = [self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    protected $table         = 'donatur';
    protected $allowedFields = [
        'code',
        'name',
        'type',
        'email',
        'phone',
        'address',
        'notes',
        'is_anonymous',
        'status',
        'created_by',
    ];

    protected $validationRules = [
        'code'         => 'required|max_length[30]|regex_match[/^[A-Z0-9-]+$/]',
        'name'         => 'required|max_length[150]',
        'type'         => 'required|in_list[individual,organization]',
        'email'        => 'permit_empty|valid_email|max_length[254]',
        'phone'        => 'permit_empty|max_length[30]',
        'address'      => 'permit_empty|max_length[1000]',
        'notes'        => 'permit_empty|max_length[2000]',
        'is_anonymous' => 'permit_empty|in_list[0,1]',
        'status'       => 'required|in_list[active,inactive]',
        'created_by'   => 'permit_empty|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'code' => [
            'required'    => 'Kode donatur wajib diisi.',
            'max_length'  => 'Kode donatur maksimal 30 karakter.',
            'regex_match' => 'Kode donatur hanya boleh berisi huruf besar, angka, dan tanda hubung.',
        ],
        'name' => [
            'required'   => 'Nama donatur wajib diisi.',
            'max_length' => 'Nama donatur maksimal 150 karakter.',
        ],
        'type' => [
            'required' => 'Jenis donatur wajib dipilih.',
            'in_list'  => 'Jenis donatur tidak valid.',
        ],
        'email' => [
            'valid_email' => 'Format email tidak valid.',
            'max_length'  => 'Email maksimal 254 karakter.',
        ],
        'status' => [
            'in_list' => 'Status donatur tidak valid.',
        ],
    ];

    protected function uniqueRules(?int $ignoreId = null): array
    {
        $ignore = $ignoreId === null ? '' : ',id,' . $ignoreId;

        return [
            'code' => 'is_unique[donatur.code' . $ignore . ']',
        ];
    }

    /**
     * @return list<object>
     */
    public function donations(int $donorId): array
    {
        return model(DonasiModel::class)
            ->where('donor_id', $donorId)
            ->orderBy('donation_date', 'DESC')
            ->findAll();
    }

    /**
     * Ringkasan donasi aktif milik donatur.
     *
     * total_amount hanya menjumlahkan donasi uang berstatus verified,
     * sehingga nilai estimasi barang tidak tercampur dengan uang.
     */
    public function summary(int $donorId): object
    {
        $row = $this->db->table('donasi')
            ->select(
                'COUNT(*) AS donation_count, '
                . "COALESCE(SUM(CASE WHEN donation_type = 'money' AND status = 'verified' THEN amount ELSE 0 END), 0) AS total_amount",
                false,
            )
            ->where('donor_id', $donorId)
            ->where('deleted_at', null)
            ->get()
            ->getRow();

        return $row ?? (object) ['donation_count' => 0, 'total_amount' => '0.00'];
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
