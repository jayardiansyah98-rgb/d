<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\UserModel;
use App\Validation\UserRules;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;
use RuntimeException;

class UserController extends BaseController
{
    public function index()
    {
        $search = trim((string) $this->request->getGet('search'));
        $search = mb_substr($search, 0, 100);

        $status = (string) $this->request->getGet('status');
        if (! in_array($status, ['', 'active', 'inactive'], true)) {
            $status = '';
        }

        $roleId = (int) $this->request->getGet('role_id');

        $model = model(UserModel::class);
        $model->select('users.id, users.role_id, users.username, users.email, users.full_name, users.phone, users.status, users.last_login_at, roles.name AS role_name, roles.slug AS role_slug')
            ->join('roles', 'roles.id = users.role_id')
            ->orderBy('users.full_name', 'ASC');

        if ($search !== '') {
            $model->groupStart()
                ->like('users.full_name', $search)
                ->orLike('users.username', $search)
                ->orLike('users.email', $search)
                ->groupEnd();
        }

        if ($status !== '') {
            $model->where('users.status', $status);
        }

        if ($roleId > 0) {
            $model->where('users.role_id', $roleId);
        }

        $users = $model->paginate(10, 'users');

        return view('users/index', [
            'title'   => 'Manajemen Pengguna',
            'users'   => $users,
            'pager'   => $model->pager,
            'roles'   => model(RoleModel::class)->orderBy('name', 'ASC')->findAll(),
            'search'  => $search,
            'status'  => $status,
            'roleId'  => $roleId,
        ]);
    }

    public function create()
    {
        return view('users/form', [
            'title' => 'Tambah Pengguna',
            'user'  => null,
            'roles' => model(RoleModel::class)->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function store()
    {
        $rules = (new UserRules())->store();

        if (! $this->validate($rules)) {
            return redirect()->to(url_to('users.create'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            service('userService')->create($this->formData());
        } catch (DomainException $exception) {
            return redirect()->to(url_to('users.create'))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal membuat pengguna: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('users.create'))
                ->withInput()
                ->with('error', 'Pengguna gagal dibuat. Silakan coba lagi.');
        }

        return redirect()->to(url_to('users.index'))
            ->with('success', 'Pengguna baru berhasil dibuat.');
    }

    public function edit($id)
    {
        $user = $this->findUserOrFail((int) $id);

        return view('users/form', [
            'title' => 'Ubah Pengguna',
            'user'  => $user,
            'roles' => model(RoleModel::class)->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function update($id)
    {
        $id = (int) $id;

        $this->findUserOrFail($id);

        $rules = (new UserRules())->update($id);

        if (! $this->validate($rules)) {
            return redirect()->to(url_to('users.edit', $id))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            service('userService')->update($id, $this->formData());
        } catch (DomainException $exception) {
            return redirect()->to(url_to('users.edit', $id))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengubah pengguna: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('users.edit', $id))
                ->withInput()
                ->with('error', 'Perubahan pengguna gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(url_to('users.index'))
            ->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function toggleStatus($id)
    {
        try {
            $newStatus = service('userService')->toggleStatus((int) $id);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('users.index'))->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengubah status pengguna: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('users.index'))->with('error', 'Status pengguna gagal diubah.');
        }

        return redirect()->to(url_to('users.index'))
            ->with('success', $newStatus === 'active' ? 'Pengguna berhasil diaktifkan.' : 'Pengguna berhasil dinonaktifkan.');
    }

    public function archive($id)
    {
        try {
            service('userService')->archive((int) $id);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('users.index'))->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengarsipkan pengguna: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('users.index'))->with('error', 'Pengguna gagal diarsipkan.');
        }

        return redirect()->to(url_to('users.index'))
            ->with('success', 'Pengguna berhasil diarsipkan.');
    }

    private function findUserOrFail(int $id): object
    {
        $user = model(UserModel::class)->findWithRole($id);

        if ($user === null) {
            throw PageNotFoundException::forPageNotFound('Pengguna tidak ditemukan.');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'full_name'        => trim((string) $this->request->getPost('full_name')),
            'username'         => trim((string) $this->request->getPost('username')),
            'email'            => trim((string) $this->request->getPost('email')),
            'phone'            => trim((string) $this->request->getPost('phone')),
            'role_id'          => (int) $this->request->getPost('role_id'),
            'status'           => (string) $this->request->getPost('status'),
            'password'         => (string) $this->request->getPost('password'),
            'password_confirm' => (string) $this->request->getPost('password_confirm'),
        ];
    }
}
