<?php

namespace App\Controllers;

use App\Libraries\DistribusiService;
use App\Models\DistribusiModel;
use App\Validation\DistribusiRules;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;
use RuntimeException;

/**
 * Modul distribusi: daftar, pencatatan, persetujuan, penyaluran,
 * pembatalan, dan bukti penyaluran.
 *
 * Controller tipis: seluruh aturan saldo dan transisi status berada di
 * DistribusiService.
 */
class DistribusiController extends BaseController
{
    private const PER_PAGE = 10;

    public function index()
    {
        $search = mb_substr(trim((string) $this->request->getGet('search')), 0, 100);

        $type = (string) $this->request->getGet('type');
        if (! in_array($type, ['', ...DistribusiModel::TYPES], true)) {
            $type = '';
        }

        $status = (string) $this->request->getGet('status');
        if (! in_array($status, ['', ...DistribusiModel::STATUSES], true)) {
            $status = '';
        }

        $dateFrom = $this->validDate($this->request->getGet('date_from'));
        $dateTo   = $this->validDate($this->request->getGet('date_to'));

        $filters = [
            'search'   => $search,
            'type'     => $type,
            'status'   => $status,
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ];

        $model = model(DistribusiModel::class);
        $this->applyListFilters($model, $filters);

        $distributions = $model->withRelations()
            ->orderBy('distribusi.distribution_date', 'DESC')
            ->orderBy('distribusi.id', 'DESC')
            ->paginate(self::PER_PAGE, 'distribusi');

        $summaryModel = model(DistribusiModel::class);
        $this->applyListFilters($summaryModel, $filters);

        // Ringkasan mengikuti filter. Draf belum mengurangi saldo, sedangkan
        // transaksi batal tidak dihitung sebagai penyaluran aktif.
        $summary = $summaryModel
            ->select(
                'COUNT(*) AS total_transactions,'
                . " COALESCE(SUM(CASE WHEN distribusi.status = 'draft' THEN 1 ELSE 0 END), 0) AS draft_transactions,"
                . " COALESCE(SUM(CASE WHEN distribusi.status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_transactions,"
                . " COALESCE(SUM(CASE WHEN distribusi.status IN ('approved', 'distributed') AND distribusi.distribution_type = 'money' THEN distribusi.amount ELSE 0 END), 0) AS active_money"
            )
            ->first();

        $service = service('distribusiService');

        return view('distribusi/index', [
            'title'         => 'Distribusi Donasi',
            'distributions' => $distributions,
            'pager'         => $model->pager,
            'summary'       => $summary,
            'moneyBalance'  => $service->moneyBalance(),
            'goodsBalances' => $service->goodsBalances(),
            'search'        => $search,
            'type'          => $type,
            'status'        => $status,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
        ]);
    }

    public function show($id)
    {
        $distribution = $this->findDistributionOrFail((int) $id);

        return view('distribusi/show', [
            'title'        => 'Detail Distribusi',
            'distribution' => $distribution,
            'items'        => service('distribusiService')->detailsFor((int) $distribution->id),
            'creator'      => $this->userOption($distribution->created_by),
            'approver'     => $this->userOption($distribution->approved_by),
        ]);
    }

    public function receipt($id)
    {
        $distribution = $this->findDistributionOrFail((int) $id);

        return view('distribusi/receipt', [
            'title'        => 'Bukti Penyaluran ' . $distribution->distribution_number,
            'distribution' => $distribution,
            'items'        => service('distribusiService')->detailsFor((int) $distribution->id),
            'creator'      => $this->userOption($distribution->created_by),
            'approver'     => $this->userOption($distribution->approved_by),
            'printedAt'    => date('d/m/Y H:i'),
        ]);
    }

    public function create()
    {
        $service = service('distribusiService');

        return view('distribusi/form', [
            'title'         => 'Catat Distribusi',
            'distribution'  => null,
            'items'         => [],
            'defaultDate'   => date('Y-m-d'),
            'moneyBalance'  => $service->moneyBalance(),
            'goodsBalances' => $service->goodsBalances(),
        ]);
    }

    public function store()
    {
        $data   = $this->formData();
        $items  = $this->itemData();
        $errors = $this->validationErrors($data, $items);

        if ($errors !== []) {
            return redirect()->to(url_to('distribusi.create'))
                ->withInput()
                ->with('errors', $errors);
        }

        try {
            $id = service('distribusiService')->create($data, $items);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('distribusi.create'))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mencatat distribusi: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('distribusi.create'))
                ->withInput()
                ->with('error', 'Distribusi gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(url_to('distribusi.show', $id))
            ->with('success', 'Distribusi baru berhasil dicatat sebagai draf.');
    }

    public function edit($id)
    {
        $distribution = $this->findDistributionOrFail((int) $id);

        if ($distribution->status !== DistribusiModel::STATUS_DRAFT) {
            return redirect()->to(url_to('distribusi.show', $distribution->id))
                ->with('error', 'Hanya distribusi berstatus draf yang dapat diubah. Transaksi final harus dibatalkan lalu dicatat ulang.');
        }

        $service = service('distribusiService');

        return view('distribusi/form', [
            'title'         => 'Ubah Distribusi',
            'distribution'  => $distribution,
            'items'         => $service->detailsFor((int) $distribution->id),
            'defaultDate'   => date('Y-m-d'),
            'moneyBalance'  => $service->moneyBalance(),
            'goodsBalances' => $service->goodsBalances(),
        ]);
    }

    public function update($id)
    {
        $id           = (int) $id;
        $distribution = $this->findDistributionOrFail($id);

        if ($distribution->status !== DistribusiModel::STATUS_DRAFT) {
            return redirect()->to(url_to('distribusi.show', $id))
                ->with('error', 'Hanya distribusi berstatus draf yang dapat diubah.');
        }

        $data   = $this->formData();
        $items  = $this->itemData();
        $errors = $this->validationErrors($data, $items);

        if ($errors !== []) {
            return redirect()->to(url_to('distribusi.edit', $id))
                ->withInput()
                ->with('errors', $errors);
        }

        try {
            service('distribusiService')->update($id, $data, $items);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('distribusi.edit', $id))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengubah distribusi: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('distribusi.edit', $id))
                ->withInput()
                ->with('error', 'Perubahan distribusi gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(url_to('distribusi.show', $id))
            ->with('success', 'Data distribusi berhasil diperbarui.');
    }

    public function approve($id)
    {
        $id = (int) $id;

        $this->findDistributionOrFail($id);

        try {
            service('distribusiService')->approve($id);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('distribusi.show', $id))->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal menyetujui distribusi: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('distribusi.show', $id))->with('error', 'Distribusi gagal disetujui.');
        }

        return redirect()->to(url_to('distribusi.show', $id))
            ->with('success', 'Distribusi disetujui. Saldo aktif sudah dikurangi.');
    }

    public function distribute($id)
    {
        $id = (int) $id;

        $this->findDistributionOrFail($id);

        try {
            service('distribusiService')->markDistributed($id);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('distribusi.show', $id))->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal menandai distribusi tersalurkan: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('distribusi.show', $id))->with('error', 'Distribusi gagal ditandai tersalurkan.');
        }

        return redirect()->to(url_to('distribusi.show', $id))
            ->with('success', 'Distribusi ditandai sudah tersalurkan.');
    }

    public function cancel($id)
    {
        $id = (int) $id;

        $this->findDistributionOrFail($id);

        $rules = (new DistribusiRules())->cancel();

        if (! $this->validate($rules)) {
            return redirect()->to(url_to('distribusi.show', $id))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            service('distribusiService')->cancel($id, (string) $this->request->getPost('cancelled_reason'));
        } catch (DomainException $exception) {
            return redirect()->to(url_to('distribusi.show', $id))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal membatalkan distribusi: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('distribusi.show', $id))
                ->withInput()
                ->with('error', 'Distribusi gagal dibatalkan.');
        }

        return redirect()->to(url_to('distribusi.show', $id))
            ->with('success', 'Distribusi dibatalkan. Saldo aktif dilepas dan data tetap tersimpan untuk audit.');
    }

    private function findDistributionOrFail(int $id): object
    {
        $distribution = model(DistribusiModel::class)->findWithRelations($id);

        if ($distribution === null) {
            throw PageNotFoundException::forPageNotFound('Distribusi tidak ditemukan.');
        }

        return $distribution;
    }

    private function userOption(mixed $userId): ?object
    {
        if ($userId === null) {
            return null;
        }

        return model(DistribusiModel::class)->creator((int) $userId);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, mixed>    $items
     *
     * @return array<string, string>
     */
    private function validationErrors(array $data, array $items): array
    {
        $valid  = $this->validate((new DistribusiRules())->store());
        $errors = $valid ? [] : $this->validator->getErrors();

        return array_merge($errors, service('distribusiService')->validationErrors($data, $items));
    }

    /**
     * @return array<string, string>
     */
    private function formData(): array
    {
        return [
            'distribution_type' => trim((string) $this->request->getPost('distribution_type')),
            'distribution_date' => trim((string) $this->request->getPost('distribution_date')),
            'recipient_name'    => trim((string) $this->request->getPost('recipient_name')),
            'recipient_type'    => trim((string) $this->request->getPost('recipient_type')),
            'recipient_address' => trim((string) $this->request->getPost('recipient_address')),
            'amount'            => trim((string) $this->request->getPost('amount')),
            'description'       => trim((string) $this->request->getPost('description')),
            'notes'             => trim((string) $this->request->getPost('notes')),
        ];
    }

    /**
     * Baris barang dinamis. Dibatasi satu baris di atas batas agar
     * service dapat menolak jumlah yang melebihi batas dengan pesan jelas.
     *
     * @return list<array<string, string>>
     */
    private function itemData(): array
    {
        $raw = $this->request->getPost('items');

        if (! is_array($raw)) {
            return [];
        }

        $items = [];

        foreach (array_slice($raw, 0, DistribusiService::MAX_ITEMS + 1) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $items[] = [
                'item_name' => trim((string) ($row['item_name'] ?? '')),
                'quantity'  => trim((string) ($row['quantity'] ?? '')),
                'unit'      => trim((string) ($row['unit'] ?? '')),
            ];
        }

        return $items;
    }

    /**
     * Terapkan filter daftar yang sama pada query daftar dan ringkasan.
     *
     * @param array{search: string, type: string, status: string, dateFrom: string, dateTo: string} $filters
     */
    private function applyListFilters(DistribusiModel $model, array $filters): void
    {
        if ($filters['search'] !== '') {
            $model->groupStart()
                ->like('distribusi.distribution_number', $filters['search'])
                ->orLike('distribusi.recipient_name', $filters['search'])
                ->orLike('distribusi.recipient_address', $filters['search'])
                ->orLike('distribusi.description', $filters['search'])
                ->orLike('distribusi.notes', $filters['search'])
                ->groupEnd();
        }

        if ($filters['type'] !== '') {
            $model->where('distribusi.distribution_type', $filters['type']);
        }

        if ($filters['status'] !== '') {
            $model->where('distribusi.status', $filters['status']);
        }

        if ($filters['dateFrom'] !== '') {
            $model->where('distribusi.distribution_date >=', $filters['dateFrom']);
        }

        if ($filters['dateTo'] !== '') {
            $model->where('distribusi.distribution_date <=', $filters['dateTo']);
        }
    }

    private function validDate(mixed $value): string
    {
        $value = trim((string) $value);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        [$year, $month, $day] = array_map(static fn (string $part): int => (int) $part, explode('-', $value));

        return checkdate($month, $day, $year) ? $value : '';
    }
}
