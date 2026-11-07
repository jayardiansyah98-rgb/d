<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    /**
     * Slug role administrator utama. Dipakai untuk mencegah lockout total
     * dan sebagai bypass pemeriksaan permission.
     */
    public const ROLE_ADMIN_SLUG = 'admin';

    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'role_id',
        'username',
        'email',
        'password_hash',
        'full_name',
        'phone',
        'status',
        'last_login_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Validasi dilakukan pada lapisan App\Validation agar aturan
     * unik dapat mengecualikan ID pengguna yang sedang diubah.
     */
    protected $skipValidation       = true;
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $cleanValidationRules = false;

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Cari pengguna aktif berdasarkan username atau email.
     */
    public function findByLogin(string $login): ?object
    {
        return $this->groupStart()
            ->where('username', $login)
            ->orWhere('email', strtolower($login))
            ->groupEnd()
            ->first();
    }

    /**
     * Ambil pengguna beserta informasi role.
     */
    public function findWithRole(int $id): ?object
    {
        return $this->select('users.id, users.role_id, users.username, users.email, users.full_name, users.phone, users.status, users.last_login_at, users.created_at, users.updated_at, roles.name AS role_name, roles.slug AS role_slug')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $id)
            ->first();
    }

    public function countActiveAdministrators(): int
    {
        return $this->db->table('users')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.status', 'active')
            ->where('users.deleted_at', null)
            ->where('roles.slug', self::ROLE_ADMIN_SLUG)
            ->countAllResults();
    }
}
