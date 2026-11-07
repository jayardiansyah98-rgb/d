<?php

namespace App\Libraries;

use App\Models\PermissionModel;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;
use DomainException;
use RuntimeException;

class RoleService
{
    private RoleModel $roles;
    private RolePermissionModel $rolePermissions;
    private PermissionModel $permissions;
    private AuditLogger $audit;

    public function __construct(
        ?RoleModel $roles = null,
        ?RolePermissionModel $rolePermissions = null,
        ?PermissionModel $permissions = null,
        ?AuditLogger $audit = null,
    ) {
        $this->roles           = $roles ?? model(RoleModel::class);
        $this->rolePermissions = $rolePermissions ?? model(RolePermissionModel::class);
        $this->permissions     = $permissions ?? model(PermissionModel::class);
        $this->audit           = $audit ?? service('auditLogger');
    }

    /**
     * @param array<string, mixed> $data
     * @param list<int>            $permissionIds
     */
    public function create(array $data, array $permissionIds): int
    {
        $permissionIds = $this->validPermissionIds($permissionIds);

        $payload = [
            'name'        => trim((string) $data['name']),
            'slug'        => strtolower(trim((string) $data['slug'])),
            'description' => $this->nullableTrim($data['description'] ?? null),
            'is_system'   => 0,
        ];

        $db = db_connect();
        $db->transBegin();

        try {
            $id = $this->roles->insert($payload, true);

            if ($id === false) {
                throw new RuntimeException('Role gagal dibuat.');
            }

            $this->rolePermissions->replaceForRole((int) $id, $permissionIds);

            if (! $db->transStatus()) {
                throw new RuntimeException('Role gagal dibuat.');
            }

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }

        $this->audit->log(
            'role.created',
            'Membuat role baru: ' . $payload['name'],
            'role',
            (int) $id,
            null,
            $payload + ['permission_ids' => $permissionIds],
        );

        return (int) $id;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<int>            $permissionIds
     */
    public function update(int $id, array $data, array $permissionIds): void
    {
        $role = $this->roles->find($id);

        if ($role === null) {
            throw new DomainException('Role tidak ditemukan.');
        }

        if ((int) $role->is_system === 1) {
            throw new DomainException('Role sistem tidak dapat diubah.');
        }

        $permissionIds = $this->validPermissionIds($permissionIds);

        $oldValues = [
            'name'        => $role->name,
            'slug'        => $role->slug,
            'description' => $role->description,
            'permissions' => $this->roles->getPermissionIds($id),
        ];

        $payload = [
            'name'        => trim((string) $data['name']),
            'slug'        => strtolower(trim((string) $data['slug'])),
            'description' => $this->nullableTrim($data['description'] ?? null),
        ];

        $db = db_connect();
        $db->transBegin();

        try {
            if ($this->roles->update($id, $payload) === false) {
                throw new RuntimeException('Role gagal diperbarui.');
            }

            $this->rolePermissions->replaceForRole($id, $permissionIds);

            if (! $db->transStatus()) {
                throw new RuntimeException('Role gagal diperbarui.');
            }

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }

        $this->audit->log(
            'role.updated',
            'Mengubah role: ' . $payload['name'],
            'role',
            $id,
            $oldValues,
            $payload + ['permissions' => $permissionIds],
        );
    }

    public function delete(int $id): void
    {
        $role = $this->roles->find($id);

        if ($role === null) {
            throw new DomainException('Role tidak ditemukan.');
        }

        if ((int) $role->is_system === 1) {
            throw new DomainException('Role sistem tidak dapat dihapus.');
        }

        if ($this->roles->countUsers($id) > 0) {
            throw new DomainException('Role masih digunakan oleh pengguna dan tidak dapat dihapus.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $this->dbDeleteRolePermissions($id);

            if ($this->roles->delete($id) === false) {
                throw new RuntimeException('Role gagal dihapus.');
            }

            if (! $db->transStatus()) {
                throw new RuntimeException('Role gagal dihapus.');
            }

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }

        $this->audit->log(
            'role.deleted',
            'Menghapus role: ' . $role->name,
            'role',
            $id,
            $this->safeRoleValues((array) $role),
            null,
        );
    }

    private function dbDeleteRolePermissions(int $roleId): void
    {
        db_connect()->table('role_permissions')->where('role_id', $roleId)->delete();
    }

    /**
     * @param list<int> $permissionIds
     *
     * @return list<int>
     */
    private function validPermissionIds(array $permissionIds): array
    {
        $validIds = $this->permissions->getAllIds();

        $filtered = array_values(array_unique(array_filter(
            array_map('intval', $permissionIds),
            static fn (int $id): bool => in_array($id, $validIds, true),
        )));

        if ($filtered === []) {
            throw new DomainException('Pilih minimal satu hak akses yang valid.');
        }

        sort($filtered);

        return $filtered;
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
    private function safeRoleValues(array $values): array
    {
        return array_intersect_key($values, array_flip([
            'id',
            'name',
            'slug',
            'description',
            'is_system',
        ]));
    }
}
