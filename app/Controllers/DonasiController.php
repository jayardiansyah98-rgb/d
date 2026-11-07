<?php

namespace App\Controllers;

use App\Models\DonasiModel;
use App\Models\DonaturModel;
use App\Validation\DonasiRules;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;
use RuntimeException;

class DonasiController extends BaseController
{
    private const PER_PAGE = 10;

    public function index()
    {
        $search = mb_substr(trim((string) $this->request->getGet('search')), 0, 100);

        $type = (string) $this->request->getGet('type');
        if (! in_array($type, ['', ...DonasiModel::TYPES], true)) {
            $type = '';
        }

        $status = (string) $this->request->getGet('status');
        if (! in_array($status, ['', ...DonasiModel::STATUSES], true)) {
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

        $model = model(DonasiModel::class);
        $this->applyListFilters($model, $filters);

        $donations = $model->withRelations()
            ->orderBy('donasi.donation_date', 'DESC')
            ->orderBy('donasi.id', 'DESC')
            ->paginate(self::PER_PAGE, 'donasi');

        $summaryModel = model(DonasiModel::class);
        $this->applyListFilters($summaryModel, $filters);

        // Ringkasan mengikuti filter aktif. Donasi berstatus batal tidak
        // dihitung sebagai penerimaan aktif, sedangkan nilai terverifikasi
        // dihitung terpisah agar donasi pending tidak diklaim sudah sah.
        $summary = $summaryModel
            ->select(
                'COUNT(*) AS total_transactions,'
                . " COALESCE(SUM(CASE WHEN donasi.status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_transactions,"
                . " COALESCE(SUM(CASE WHEN donasi.status <> 'cancelled' AND donasi.donation_type = 'money' THEN donasi.amount ELSE 0 END), 0) AS active_money,"
                . " COALESCE(SUM(CASE WHEN donasi.status = 'verified' AND donasi.donation_type = 'money' THEN donasi.amount ELSE 0 END), 0) AS verified_money"
            )
            ->join('donatur', 'donatur.id = donasi.donor_id', 'left')
            ->first();

        return view('donasi/index', [
            'title'     => 'Penerimaan Donasi',
            'donations' => $donations,
            'pager'     => $model->pager,
            'summary'   => $summary,
            'search'   => $search,
            'type'     => $type,
            'status'   => $status,
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    public function show($id)
    {
        $donation = $this->findDonationOrFail((int) $id);
        $model    = model(DonasiModel::class);

        return view('donasi/show', [
            'title'      => 'Detail Donasi',
            'donation'   => $donation,
            'creator'    => $donation->created_by !== null ? $model->creator((int) $donation->created_by) : null,
            'verifier'   => $donation->verified_by !== null ? $model->verifier((int) $donation->verified_by) : null,
        ]);
    }

    public function receipt($id)
    {
        $donation = $this->findDonationOrFail((int) $id);
        $model    = model(DonasiModel::class);

        return view('donasi/receipt', [
            'title'     => 'Bukti Penerimaan ' . $donation->transaction_number,
            'donation'  => $donation,
            'creator'   => $donation->created_by !== null ? $model->creator((int) $donation->created_by) : null,
            'verifier'  => $donation->verified_by !== null ? $model->verifier((int) $donation->verified_by) : null,
            'printedAt' => date('d/m/Y H:i'),
        ]);
    }

    public function create()
    {
        return view('donasi/form', [
            'title'       => 'Catat Donasi',
            'donation'    => null,
            'donors'      => $this->donorOptions(),
            'defaultDate' => date('Y-m-d'),
        ]);
    }

    public function store()
    {
        $data   = $this->formData();
        $errors = $this->validationErrors($data);

        if ($errors !== []) {
            return redirect()->to(url_to('donasi.create'))
                ->withInput()
                ->with('errors', $errors);
        }

        try {
            $id = service('donasiService')->create($data);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('donasi.create'))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mencatat donasi: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('donasi.create'))
                ->withInput()
                ->with('error', 'Donasi gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(url_to('donasi.show', $id))
            ->with('success', 'Donasi baru berhasil dicatat dan menunggu verifikasi.');
    }

    public function edit($id)
    {
        $donation = $this->findDonationOrFail((int) $id);

        if ($donation->status !== DonasiModel::STATUS_PENDING) {
            return redirect()->to(url_to('donasi.show', $donation->id))
                ->with('error', 'Hanya donasi berstatus menunggu yang dapat diubah.');
        }

        return view('donasi/form', [
            'title'       => 'Ubah Donasi',
            'donation'    => $donation,
            'donors'      => $this->donorOptions($donation),
            'defaultDate' => date('Y-m-d'),
        ]);
    }

    public function update($id)
    {
        $id       = (int) $id;
        $donation = $this->findDonationOrFail($id);

        if ($donation->status !== DonasiModel::STATUS_PENDING) {
            return redirect()->to(url_to('donasi.show', $id))
                ->with('error', 'Hanya donasi berstatus menunggu yang dapat diubah.');
        }

        $data   = $this->formData();
        $errors = $this->validationErrors(
            $data,
            $donation->donor_id !== null ? (int) $donation->donor_id : null,
        );

        if ($errors !== []) {
            return redirect()->to(url_to('donasi.edit', $id))
                ->withInput()
                ->with('errors', $errors);
        }

        try {
            service('donasiService')->update($id, $data);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('donasi.edit', $id))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengubah donasi: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('donasi.edit', $id))
                ->withInput()
                ->with('error', 'Perubahan donasi gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(url_to('donasi.show', $id))
            ->with('success', 'Data donasi berhasil diperbarui.');
    }

    public function verify($id)
    {
        $id = (int) $id;

        $this->findDonationOrFail($id);

        try {
            service('donasiService')->verify($id);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('donasi.show', $id))->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal memverifikasi donasi: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('donasi.show', $id))->with('error', 'Donasi gagal diverifikasi.');
        }

        return redirect()->to(url_to('donasi.show', $id))
            ->with('success', 'Donasi berhasil diverifikasi.');
    }

    public function cancel($id)
    {
        $id = (int) $id;

        $this->findDonationOrFail($id);

        $rules = (new DonasiRules())->cancel();

        if (! $this->validate($rules)) {
            return redirect()->to(url_to('donasi.show', $id))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            service('donasiService')->cancel($id, (string) $this->request->getPost('cancelled_reason'));
        } catch (DomainException $exception) {
            return redirect()->to(url_to('donasi.show', $id))
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal membatalkan donasi: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('donasi.show', $id))
                ->withInput()
                ->with('error', 'Donasi gagal dibatalkan.');
        }

        return redirect()->to(url_to('donasi.show', $id))
            ->with('success', 'Donasi berhasil dibatalkan. Data tetap tersimpan untuk audit.');
    }

    private function findDonationOrFail(int $id): object
    {
        $donation = model(DonasiModel::class)->findWithRelations($id);

        if ($donation === null) {
            throw PageNotFoundException::forPageNotFound('Donasi tidak ditemukan.');
        }

        return $donation;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    private function validationErrors(array $data, ?int $existingDonorId = null): array
    {
        $valid  = $this->validate((new DonasiRules())->store());
        $errors = $valid ? [] : $this->validator->getErrors();

        return array_merge($errors, service('donasiService')->validationErrors($data, $existingDonorId));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'donation_type'    => trim((string) $this->request->getPost('donation_type')),
            'donor_id'         => (int) $this->request->getPost('donor_id'),
            'is_anonymous'     => $this->request->getPost('is_anonymous') ? 1 : 0,
            'donation_date'    => trim((string) $this->request->getPost('donation_date')),
            'amount'           => trim((string) $this->request->getPost('amount')),
            'payment_method'   => trim((string) $this->request->getPost('payment_method')),
            'reference_number' => trim((string) $this->request->getPost('reference_number')),
            'goods_name'       => trim((string) $this->request->getPost('goods_name')),
            'goods_quantity'   => trim((string) $this->request->getPost('goods_quantity')),
            'goods_unit'       => trim((string) $this->request->getPost('goods_unit')),
            'estimated_value'  => trim((string) $this->request->getPost('estimated_value')),
            'notes'            => trim((string) $this->request->getPost('notes')),
        ];
    }

    /**
     * Donatur aktif/nonaktif yang belum diarsipkan. Jika donasi yang
     * diedit merujuk donatur terarsip, donatur tersebut tetap ditampilkan
     * agar riwayat tidak kehilangan konteks.
     *
     * @return list<object>
     */
    private function donorOptions(?object $donation = null): array
    {
        $model = model(DonaturModel::class);
        $donors = $model->orderBy('name', 'ASC')->findAll();

        if ($donation === null || $donation->donor_id === null) {
            return $donors;
        }

        foreach ($donors as $donor) {
            if ((int) $donor->id === (int) $donation->donor_id) {
                return $donors;
            }
        }

        $archived = $model->withDeleted()->find((int) $donation->donor_id);

        if ($archived !== null) {
            $donors[] = $archived;

            usort(
                $donors,
                static fn (object $a, object $b): int => strcasecmp((string) $a->name, (string) $b->name),
            );
        }

        return $donors;
    }

    /**
     * Terapkan filter daftar yang sama pada query daftar dan ringkasan.
     *
     * @param array{search: string, type: string, status: string, dateFrom: string, dateTo: string} $filters
     */
    private function applyListFilters(DonasiModel $model, array $filters): void
    {
        if ($filters['search'] !== '') {
            $model->groupStart()
                ->like('donasi.transaction_number', $filters['search'])
                ->orLike('donatur.name', $filters['search'])
                ->orLike('donatur.code', $filters['search'])
                ->orLike('donasi.notes', $filters['search'])
                ->groupEnd();
        }

        if ($filters['type'] !== '') {
            $model->where('donasi.donation_type', $filters['type']);
        }

        if ($filters['status'] !== '') {
            $model->where('donasi.status', $filters['status']);
        }

        if ($filters['dateFrom'] !== '') {
            $model->where('donasi.donation_date >=', $filters['dateFrom']);
        }

        if ($filters['dateTo'] !== '') {
            $model->where('donasi.donation_date <=', $filters['dateTo']);
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
