<?php

namespace App\Libraries;

use App\Models\UserModel;
use DomainException;
use RuntimeException;

class UserService
{
    private UserModel $users;
    private AuditLogger $audit;

    public function __construct(?UserModel $users = null, ?AuditLogger $audit = null)
    {
        $this->users = $users ?? model(UserModel::class);
        $this->audit = $audit ?? service('auditLogger');
    }

    /**
     * @param array<string, mixed> $data Data yang sudah lolos validasi.
     */
    public function create(array $data): int
    {
        $payload = [
            'role_id'       => (int) $data['role_id'],
            'full_name'     => trim((string) $data['full_name']),
            'username'      => trim((string) $data['username']),
            'email'         => strtolower(trim((string) $data['email'])),
            'phone'         => $this->nullableTrim($data['phone'] ?? null),
            'status'        => (string) $data['status'],
            'password_hash' => UserModel::hashPassword((string) $data['password']),
        ];

        $id = $this->users->insert($payload, true);

        if ($id === false) {
            throw new RuntimeException('Pengguna gagal dibuat.');
        }

        $this->audit->log(
            'user.created',
            'Membuat pengguna baru: ' . $payload['username'],
            'user',
            (int) $id,
            null,
            $this->safeValues($payload),
        );

        return (int) $id;
    }

    /**
     * @param array<string, mixed> $data Data yang sudah lolos validasi.
     */
    public function update(int $id, array $data): void
    {
        $target = $this->users->findWithRole($id);

        if ($target === null) {
            throw new DomainException('Pengguna tidak ditemukan.');
        }

        $actorId  = service('auth')->id();
        $newRole  = (int) $data['role_id'];
        $newStatus = (string) $data['status'];

        if ($actorId === $id && $newStatus !== 'active') {
            throw new DomainException('Anda tidak dapat menonaktifkan akun sendiri.');
        }

        if ($actorId === $id && $newRole !== (int) $target->role_id) {
            throw new DomainException('Anda tidak dapat mengubah role akun sendiri.');
        }

        $this->guardLastAdministrator($target, $newRole, $newStatus);

        $payload = [
            'role_id'   => $newRole,
            'full_name' => trim((string) $data['full_name']),
            'username'  => trim((string) $data['username']),
            'email'     => strtolower(trim((string) $data['email'])),
            'phone'     => $this->nullableTrim($data['phone'] ?? null),
            'status'    => $newStatus,
        ];

        $passwordChanged = isset($data['password']) && trim((string) $data['password']) !== '';

        if ($passwordChanged) {
            $payload['password_hash'] = UserModel::hashPassword((string) $data['password']);
        }

        if ($this->users->update($id, $payload) === false) {
            throw new RuntimeException('Perubahan pengguna gagal disimpan.');
        }

        $newValues            = $this->safeValues($payload);
        $newValues['password_changed'] = $passwordChanged;

        $this->audit->log(
            'user.updated',
            'Mengubah data pengguna: ' . $payload['username'],
            'user',
            $id,
            $this->safeValues((array) $target),
            $newValues,
        );
    }

    /**
     * @param array<string, mixed> $data Data yang sudah lolos validasi.
     */
    public function updateProfile(int $id, array $data): void
    {
        $target = $this->users->find($id);

        if ($target === null) {
            throw new DomainException('Pengguna tidak ditemukan.');
        }

        $payload = [
            'full_name' => trim((string) $data['full_name']),
            'email'     => strtolower(trim((string) $data['email'])),
            'phone'     => $this->nullableTrim($data['phone'] ?? null),
        ];

        if ($this->users->update($id, $payload) === false) {
            throw new RuntimeException('Profil gagal diperbarui.');
        }

        $this->audit->log(
            'profile.updated',
            'Memperbarui profil sendiri.',
            'user',
            $id,
            $this->safeValues((array) $target),
            $payload,
        );
    }

    public function updatePassword(int $id, string $newPassword): void
    {
        if ($this->users->update($id, [
            'password_hash' => UserModel::hashPassword($newPassword),
        ]) === false) {
            throw new RuntimeException('Kata sandi gagal diperbarui.');
        }

        $this->audit->log(
            'profile.password_changed',
            'Mengubah kata sandi sendiri.',
            'user',
            $id,
        );
    }

    public function toggleStatus(int $id): string
    {
        $target = $this->users->findWithRole($id);

        if ($target === null) {
            throw new DomainException('Pengguna tidak ditemukan.');
        }

        if (service('auth')->id() === $id) {
            throw new DomainException('Anda tidak dapat mengubah status akun sendiri.');
        }

        $newStatus = $target->status === 'active' ? 'inactive' : 'active';

        $this->guardLastAdministrator($target, (int) $target->role_id, $newStatus);

        if ($this->users->update($id, ['status' => $newStatus]) === false) {
            throw new RuntimeException('Status pengguna gagal diubah.');
        }

        $this->audit->log(
            $newStatus === 'active' ? 'user.activated' : 'user.deactivated',
            ($newStatus === 'active' ? 'Mengaktifkan' : 'Menonaktifkan') . ' pengguna: ' . $target->username,
            'user',
            $id,
            ['status' => $target->status],
            ['status' => $newStatus],
        );

        return $newStatus;
    }

    public function archive(int $id): void
    {
        $target = $this->users->findWithRole($id);

        if ($target === null) {
            throw new DomainException('Pengguna tidak ditemukan.');
        }

        if (service('auth')->id() === $id) {
            throw new DomainException('Anda tidak dapat mengarsipkan akun sendiri.');
        }

        $this->guardLastAdministrator($target, (int) $target->role_id, 'inactive');

        if ($this->users->delete($id) === false) {
            throw new RuntimeException('Pengguna gagal diarsipkan.');
        }

        $this->audit->log(
            'user.archived',
            'Mengarsipkan pengguna: ' . $target->username,
            'user',
            $id,
            $this->safeValues((array) $target),
            null,
        );
    }

    private function guardLastAdministrator(object $target, int $newRoleId, string $newStatus): void
    {
        $isActiveAdministrator = $target->status === 'active' && $target->role_slug === UserModel::ROLE_ADMIN_SLUG;
        $losesAdministrator    = $newStatus !== 'active' || $newRoleId !== (int) $target->role_id;

        if (! $isActiveAdministrator || ! $losesAdministrator) {
            return;
        }

        if ($this->users->countActiveAdministrators() <= 1) {
            throw new DomainException('Administrator aktif terakhir tidak dapat dinonaktifkan atau diubah role-nya.');
        }
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function safeValues(array $values): array
    {
        unset(
            $values['password'],
            $values['password_hash'],
            $values['password_confirm'],
            $values['current_password'],
            $values['new_password'],
            $values['new_password_confirm'],
        );

        return $values;
    }
}
