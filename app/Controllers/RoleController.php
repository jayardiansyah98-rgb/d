<?php

namespace App\Controllers;

use App\Models\PermissionModel;
use App\Models\RoleModel;
use App\Validation\RoleRules;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;
use RuntimeException;

class RoleController extends BaseController
{
    public function index()
    {
        return view('roles/index', [
            'title' => 'Role & Hak Akses',
            'roles' => model(RoleModel::class)->getWithStats(),
        ]);
    }

    public function create()
    {
        return view('roles/form', [
            'title'              => 'Tambah Role',
            'role'               => null,
            'permissionGroups'   => model(PermissionModel::class)->getGrouped(),
            'rolePermissionIds'  => [],
        ]);
    }

    public function store()
    {
        $rules = (new RoleRules())->store();

        if (! $this->validate($rules)) {
            return redirect()->to(url_to('roles.create'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            service('roleService')->create($this->formData(), $this->permissionIdsFromRequest());
        } catch (DomainException $exception) {
            return redirect()->to(url_to('roles.create'))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal membuat role: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('roles.create'))
                ->withInput()
                ->with('error', 'Role gagal dibuat. Silakan coba lagi.');
        }

        return redirect()->to(url_to('roles.index'))
            ->with('success', 'Role baru berhasil dibuat.');
    }

    public function edit($id)
    {
        $role = $this->findRoleOrFail((int) $id);

        if ((int) $role->is_system === 1) {
            return redirect()->to(url_to('roles.index'))
                ->with('error', 'Role sistem tidak dapat diubah.');
        }

        return view('roles/form', [
            'title'             => 'Ubah Role',
            'role'              => $role,
            'permissionGroups'  => model(PermissionModel::class)->getGrouped(),
            'rolePermissionIds' => model(RoleModel::class)->getPermissionIds((int) $role->id),
        ]);
    }

    public function update($id)
    {
        $id   = (int) $id;
        $role = $this->findRoleOrFail($id);

        if ((int) $role->is_system === 1) {
            return redirect()->to(url_to('roles.index'))
                ->with('error', 'Role sistem tidak dapat diubah.');
        }

        $rules = (new RoleRules())->update($id);

        if (! $this->validate($rules)) {
            return redirect()->to(url_to('roles.edit', $id))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            service('roleService')->update($id, $this->formData(), $this->permissionIdsFromRequest());
        } catch (DomainException $exception) {
            return redirect()->to(url_to('roles.edit', $id))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengubah role: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('roles.edit', $id))
                ->withInput()
                ->with('error', 'Role gagal diperbarui. Silakan coba lagi.');
        }

        return redirect()->to(url_to('roles.index'))
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function delete($id)
    {
        try {
            service('roleService')->delete((int) $id);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('roles.index'))->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal menghapus role: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('roles.index'))->with('error', 'Role gagal dihapus.');
        }

        return redirect()->to(url_to('roles.index'))
            ->with('success', 'Role berhasil dihapus.');
    }

    private function findRoleOrFail(int $id): object
    {
        $role = model(RoleModel::class)->find($id);

        if ($role === null) {
            throw PageNotFoundException::forPageNotFound('Role tidak ditemukan.');
        }

        return $role;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'name'        => trim((string) $this->request->getPost('name')),
            'slug'        => trim((string) $this->request->getPost('slug')),
            'description' => trim((string) $this->request->getPost('description')),
        ];
    }

    /**
     * @return list<int>
     */
    private function permissionIdsFromRequest(): array
    {
        $raw = $this->request->getPost('permissions');

        if (! is_array($raw)) {
            return [];
        }

        return array_map('intval', array_filter($raw, 'is_scalar'));
    }
}
