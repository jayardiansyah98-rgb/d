<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Dasar model tahap 2.
 *
 * Aturan unik tidak dimasukkan langsung ke properti $validationRules karena
 * aturan tersebut berbeda antara insert dan update. Gunakan validateForSave()
 * dari service/controller agar pengecekan unik ikut dijalankan.
 */
abstract class BaseModel extends Model
{
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $protectFields    = true;
    protected $useSoftDeletes   = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $cleanValidationRules = true;

    /**
     * Aturan unik tambahan. Nilai $ignoreId diabaikan saat update.
     *
     * @return array<string, string>
     */
    protected function uniqueRules(?int $ignoreId = null): array
    {
        return [];
    }

    /**
     * Gabungkan aturan dasar dengan aturan unik.
     *
     * @return array<string, string>
     */
    public function rulesForSave(?int $ignoreId = null): array
    {
        $rules = $this->validationRules;

        foreach ($this->uniqueRules($ignoreId) as $field => $rule) {
            $rules[$field] = trim(($rules[$field] ?? '') . '|' . $rule, '|');
        }

        return $rules;
    }

    /**
     * Validasi data sebelum insert/update, termasuk keunikan.
     */
    public function validateForSave(array $data, ?int $ignoreId = null): bool
    {
        $originalRules = $this->validationRules;
        $this->validationRules = $this->rulesForSave($ignoreId);

        try {
            return $this->validate($data);
        } finally {
            $this->validationRules = $originalRules;
        }
    }
}
