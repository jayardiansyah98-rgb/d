<?php

namespace App\Controllers;

use App\Models\DonaturModel;
use App\Validation\DonaturRules;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;
use RuntimeException;

class DonaturController extends BaseController
{
    public function index()
    {
        $search = trim((string) $this->request->getGet('search'));
        $search = mb_substr($search, 0, 100);

        $status = (string) $this->request->getGet('status');
        if (! in_array($status, ['', ...DonaturModel::STATUSES], true)) {
            $status = '';
        }

        $type = (string) $this->request->getGet('type');
        if (! in_array($type, ['', ...DonaturModel::TYPES], true)) {
            $type = '';
        }

        $model = model(DonaturModel::class)->orderBy('name', 'ASC');

        if ($search !== '') {
            $model->groupStart()
                ->like('name', $search)
                ->orLike('code', $search)
                ->orLike('email', $search)
                ->orLike('phone', $search)
                ->groupEnd();
        }

        if ($status !== '') {
            $model->where('status', $status);
        }

        if ($type !== '') {
            $model->where('type', $type);
        }

        $donors = $model->paginate(10, 'donatur');

        return view('donatur/index', [
            'title'  => 'Data Donatur',
            'donors' => $donors,
            'pager'  => $model->pager,
            'search' => $search,
            'status' => $status,
            'type'   => $type,
        ]);
    }

    public function show($id)
    {
        $donor = $this->findDonorOrFail((int) $id);
        $model = model(DonaturModel::class);

        return view('donatur/show', [
            'title'     => 'Detail Donatur',
            'donor'     => $donor,
            'donations' => $model->donations((int) $donor->id),
            'summary'   => $model->summary((int) $donor->id),
            'creator'   => $donor->created_by !== null ? $model->creator((int) $donor->created_by) : null,
        ]);
    }

    public function create()
    {
        return view('donatur/form', [
            'title' => 'Tambah Donatur',
            'donor' => null,
        ]);
    }

    public function store()
    {
        $rules = (new DonaturRules())->store();

        if (! $this->validate($rules)) {
            return redirect()->to(url_to('donatur.create'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $id = service('donaturService')->create($this->formData());
        } catch (DomainException $exception) {
            return redirect()->to(url_to('donatur.create'))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal membuat donatur: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('donatur.create'))
                ->withInput()
                ->with('error', 'Donatur gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(url_to('donatur.show', $id))
            ->with('success', 'Donatur baru berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $donor = $this->findDonorOrFail((int) $id);

        return view('donatur/form', [
            'title' => 'Ubah Donatur',
            'donor' => $donor,
        ]);
    }

    public function update($id)
    {
        $id = (int) $id;

        $this->findDonorOrFail($id);

        $rules = (new DonaturRules())->store();

        if (! $this->validate($rules)) {
            return redirect()->to(url_to('donatur.edit', $id))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            service('donaturService')->update($id, $this->formData());
        } catch (DomainException $exception) {
            return redirect()->to(url_to('donatur.edit', $id))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengubah donatur: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('donatur.edit', $id))
                ->withInput()
                ->with('error', 'Perubahan data donatur gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(url_to('donatur.show', $id))
            ->with('success', 'Data donatur berhasil diperbarui.');
    }

    public function toggleStatus($id)
    {
        try {
            $newStatus = service('donaturService')->toggleStatus((int) $id);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('donatur.index'))->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengubah status donatur: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('donatur.index'))->with('error', 'Status donatur gagal diubah.');
        }

        return redirect()->to(url_to('donatur.index'))
            ->with('success', $newStatus === DonaturModel::STATUS_ACTIVE
                ? 'Donatur berhasil diaktifkan.'
                : 'Donatur berhasil dinonaktifkan.');
    }

    public function archive($id)
    {
        try {
            service('donaturService')->archive((int) $id);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('donatur.index'))->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengarsipkan donatur: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('donatur.index'))->with('error', 'Donatur gagal diarsipkan.');
        }

        return redirect()->to(url_to('donatur.index'))
            ->with('success', 'Donatur berhasil diarsipkan.');
    }

    private function findDonorOrFail(int $id): object
    {
        $donor = model(DonaturModel::class)->find($id);

        if ($donor === null) {
            throw PageNotFoundException::forPageNotFound('Donatur tidak ditemukan.');
        }

        return $donor;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'name'         => trim((string) $this->request->getPost('name')),
            'type'         => (string) $this->request->getPost('type'),
            'email'        => trim((string) $this->request->getPost('email')),
            'phone'        => trim((string) $this->request->getPost('phone')),
            'address'      => trim((string) $this->request->getPost('address')),
            'notes'        => trim((string) $this->request->getPost('notes')),
            'is_anonymous' => $this->request->getPost('is_anonymous') ? 1 : 0,
            'status'       => (string) $this->request->getPost('status'),
        ];
    }
}
