<?php

namespace App\Libraries;

use App\Models\UserModel;
use Config\Auth as AuthConfig;

/**
 * Mengelola sesi login dan pemeriksaan hak akses pengguna.
 */
class AuthService
{
    private AuthConfig $config;
    private UserModel $users;
    private ?object $currentUser = null;
    private bool $userLoaded      = false;
    private ?array $permissions   = null;
    private ?string $failureReason = null;

    public function __construct(?AuthConfig $config = null, ?UserModel $users = null)
    {
        $this->config = $config ?? config(AuthConfig::class);
        $this->users  = $users ?? model(UserModel::class);
    }

    /**
     * Coba masuk menggunakan username atau email.
     */
    public function attempt(string $login, string $password): bool
    {
        $this->failureReason = null;

        $user = $this->users->findByLogin($login);

        if ($user === null || ! password_verify($password, $user->password_hash)) {
            $this->failureReason = 'invalid';

            return false;
        }

        if ($user->status !== 'active') {
            $this->failureReason = 'inactive';

            return false;
        }

        if (password_needs_rehash($user->password_hash, PASSWORD_DEFAULT)) {
            $this->users->update($user->id, [
                'password_hash' => UserModel::hashPassword($password),
            ]);
        }

        $this->users->update($user->id, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $session = session();
        $session->regenerate(true);
        $session->set($this->config->sessionKey, (int) $user->id);

        $this->currentUser = $this->users->findWithRole((int) $user->id);
        $this->userLoaded  = true;
        $this->permissions = null;

        return true;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        return $this->user() !== null ? (int) $this->user()->id : null;
    }

    public function user(): ?object
    {
        if ($this->userLoaded) {
            return $this->currentUser;
        }

        $this->userLoaded = true;
        $userId           = session($this->config->sessionKey);

        if (! is_numeric($userId)) {
            return null;
        }

        $user = $this->users->findWithRole((int) $userId);

        if ($user === null || $user->status !== 'active') {
            return null;
        }

        return $this->currentUser = $user;
    }

    public function isAdministrator(): bool
    {
        $user = $this->user();

        return $user !== null && $user->role_slug === UserModel::ROLE_ADMIN_SLUG;
    }

    public function can(string $permission): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        // Administrator tidak dapat kehilangan akses karena perubahan
        // konfigurasi role, sehingga mencegah lockout total.
        if ($user->role_slug === UserModel::ROLE_ADMIN_SLUG) {
            return true;
        }

        return in_array($permission, $this->permissions(), true);
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }

        $user = $this->user();

        if ($user === null) {
            return $this->permissions = [];
        }

        $rows = db_connect()->table('role_permissions')
            ->select('permissions.slug')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $user->role_id)
            ->get()
            ->getResultObject();

        return $this->permissions = array_map(
            static fn (object $row): string => $row->slug,
            $rows,
        );
    }

    public function logout(): void
    {
        $session = session();
        $session->remove($this->config->sessionKey);
        $session->regenerate(true);

        $this->currentUser = null;
        $this->userLoaded  = true;
        $this->permissions = null;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }
}
