<?php

namespace App\Models;

/**
 * Rincian barang pada satu distribusi.
 */
class DetailDistribusiModel extends BaseModel
{
    protected $useSoftDeletes = false;

    protected $table         = 'detail_distribusi';
    protected $allowedFields = [
        'distribution_id',
        'item_name',
        'quantity',
        'unit',
        'unit_value',
        'notes',
    ];

    protected $validationRules = [
        'distribution_id' => 'required|is_natural_no_zero',
        'item_name'       => 'required|max_length[150]',
        'quantity'        => 'required|decimal|greater_than[0]',
        'unit'            => 'required|max_length[30]',
        'unit_value'      => 'permit_empty|decimal|greater_than_equal_to[0]',
        'notes'           => 'permit_empty|max_length[1000]',
    ];

    protected $validationMessages = [
        'distribution_id' => [
            'required'            => 'Distribusi wajib dipilih.',
            'is_natural_no_zero'  => 'Distribusi tidak valid.',
        ],
        'item_name' => [
            'required'   => 'Jenis/nama barang wajib diisi.',
            'max_length' => 'Nama barang maksimal 150 karakter.',
        ],
        'quantity' => [
            'required'     => 'Kuantitas wajib diisi.',
            'decimal'      => 'Kuantitas harus berupa angka.',
            'greater_than' => 'Kuantitas harus lebih dari 0.',
        ],
        'unit' => [
            'required'   => 'Satuan wajib diisi.',
            'max_length' => 'Satuan maksimal 30 karakter.',
        ],
        'unit_value' => [
            'decimal'               => 'Nilai satuan harus berupa angka.',
            'greater_than_equal_to' => 'Nilai satuan tidak boleh negatif.',
        ],
    ];

    public function distribution(int $distributionId): ?object
    {
        return model(DistribusiModel::class)->withDeleted()->find($distributionId);
    }
}
