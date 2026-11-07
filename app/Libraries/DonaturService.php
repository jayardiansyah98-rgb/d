<?php

namespace App\Libraries;

use App\Models\DonaturModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use DomainException;
use RuntimeException;

/**
 * Logika bisnis data donatur.
 *
 * Kode donatur dibuat otomatis dengan format DNR-YYYYMMDD-NNNN dan tidak
 * diubah saat edit agar tetap menjadi identitas yang stabil. Penghapusan
 * memakai soft delete sehingga riwayat donasi tetap utuh.
 */
class DonaturService
{
    private DonaturModel $donors;
    private AuditLogger $audit;

    public function __construct(?DonaturModel $donors = null, ?AuditLogger $audit = null)
    {
        $this->donors = $donors ?? model(DonaturModel::class);
        $this->audit  = $audit ?? service('auditLogger');
    }

    /**
     * @param array<string, mixed> $data Data yang sudah lolos validasi.
     */
    public function create(array $data): int
    {
        $payload              = $this->payload($data);
        $payload['created_by'] = service('auth')->id();

        if (! $this->donors->validateForSave($payload)) {
            throw new DomainException($this->validationMessage());
        }

        $this->assertNoDuplicate($payload);

        $id = $this->insertWithGeneratedCode($payload);

        $this->audit->log(
            'donor.created',
            'Menambah donatur: ' . $payload['name'],
            'donatur',
            $id,
            null,
            $this->safeValues($payload),
        );

        return $id;
    }

    /**
     * @param array<string, mixed> $data Data yang sudah lolos validasi.
     */
    public function update(int $id, array $data): void
    {
        $target = $this->donors->find($id);

        if ($target === null) {
            throw new DomainException('Donatur tidak ditemukan.');
        }

        $payload = $this->payload($data);

        if (! $this->donors->validateForSave($payload + ['code' => $target->code], $id)) {
            throw new DomainException($this->validationMessage());
        }

        $this->assertNoDuplicate($payload, $id);

        if ($this->donors->update($id, $payload) === false) {
            throw new RuntimeException('Perubahan data donatur gagal disimpan.');
        }

        $this->audit->log(
            'donor.updated',
            'Mengubah data donatur: ' . $payload['name'],
            'donatur',
            $id,
            $this->safeValues((array) $target),
            $payload,
        );
    }

    public function toggleStatus(int $id): string
    {
        $target = $this->donors->find($id);

        if ($target === null) {
            throw new DomainException('Donatur tidak ditemukan.');
        }

        $newStatus = $target->status === DonaturModel::STATUS_ACTIVE
            ? DonaturModel::STATUS_INACTIVE
            : DonaturModel::STATUS_ACTIVE;

        if ($this->donors->update($id, ['status' => $newStatus]) === false) {
            throw new RuntimeException('Status donatur gagal diubah.');
        }

        $this->audit->log(
            $newStatus === DonaturModel::STATUS_ACTIVE ? 'donor.activated' : 'donor.deactivated',
            ($newStatus === DonaturModel::STATUS_ACTIVE ? 'Mengaktifkan' : 'Menonaktifkan') . ' donatur: ' . $target->name,
            'donatur',
            $id,
            ['status' => $target->status],
            ['status' => $newStatus],
        );

        return $newStatus;
    }

    public function archive(int $id): void
    {
        $target = $this->donors->find($id);

        if ($target === null) {
            throw new DomainException('Donatur tidak ditemukan.');
        }

        if ($this->donors->delete($id) === false) {
            throw new RuntimeException('Donatur gagal diarsipkan.');
        }

        $this->audit->log(
            'donor.archived',
            'Mengarsipkan donatur: ' . $target->name,
            'donatur',
            $id,
            $this->safeValues((array) $target),
            null,
        );
    }

    /**
     * Simpan donatur dengan kode otomatis. Bila terjadi tabrakan kode
     * (mis. dua permintaan bersamaan), kode dibuat ulang dan dicoba lagi.
     *
     * @param array<string, mixed> $payload
     */
    private function insertWithGeneratedCode(array $payload): int
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $payload['code'] = $this->generateCode();

            try {
                $id = $this->donors->insert($payload, true);
            } catch (DatabaseException $exception) {
                $lastException = $exception;

                if (stripos($exception->getMessage(), 'duplicate') === false) {
                    throw new RuntimeException('Donatur gagal disimpan.', 0, $exception);
                }

                continue;
            }

            if ($id === false) {
                throw new RuntimeException('Donatur gagal disimpan: ' . $this->validationMessage());
            }

            return (int) $id;
        }

        throw new RuntimeException('Donatur gagal disimpan karena kode donatur tidak unik.', 0, $lastException);
    }

    /**
     * Buat kode unik DNR-YYYYMMDD-NNNN, termasuk pengecekan terhadap
     * donatur yang sudah diarsipkan.
     */
    private function generateCode(): string
    {
        $prefix = 'DNR-' . date('Ymd') . '-';

        $last = $this->donors->builder()
            ->select('code')
            ->like('code', $prefix, 'after')
            ->orderBy('code', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        $sequence = 1;

        if ($last !== null) {
            $suffix = substr((string) $last->code, strlen($prefix));

            if ($suffix !== '' && ctype_digit($suffix)) {
                $sequence = (int) $suffix + 1;
            }
        }

        do {
            $code = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;

            $exists = $this->donors->builder()->where('code', $code)->countAllResults() > 0;
        } while ($exists);

        return $code;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $email = $this->nullableTrim($data['email'] ?? null);

        return [
            'name'         => trim((string) ($data['name'] ?? '')),
            'type'         => (string) ($data['type'] ?? ''),
            'email'        => $email === null ? null : strtolower($email),
            'phone'        => $this->nullableTrim($data['phone'] ?? null),
            'address'      => $this->nullableTrim($data['address'] ?? null),
            'notes'        => $this->nullableTrim($data['notes'] ?? null),
            'is_anonymous' => filter_var($data['is_anonymous'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'status'       => (string) ($data['status'] ?? ''),
        ];
    }

    /**
     * Tolak duplikasi yang tidak disengaja: email yang sama (tanpa
     * membedakan huruf besar/kecil) atau kombinasi nama + nomor telepon
     * yang sama. Donatur yang sudah diarsipkan tidak dihitung agar data
     * lama tidak menghalangi pendaftaran ulang.
     *
     * @param array<string, mixed> $payload
     */
    private function assertNoDuplicate(array $payload, ?int $ignoreId = null): void
    {
        $candidates = $this->donors->builder()
            ->select('id, code, name, email, phone')
            ->where('deleted_at', null)
            ->get()
            ->getResult();

        $email = $payload['email'] !== null ? mb_strtolower((string) $payload['email']) : null;
        $name  = $this->normalizeName((string) $payload['name']);
        $phone = $payload['phone'] !== null ? $this->normalizePhone((string) $payload['phone']) : '';

        foreach ($candidates as $candidate) {
            if ($ignoreId !== null && (int) $candidate->id === $ignoreId) {
                continue;
            }

            if ($email !== null && $candidate->email !== null
                && mb_strtolower((string) $candidate->email) === $email) {
                throw new DomainException('Email sudah digunakan oleh donatur lain (' . $candidate->code . ').');
            }

            if ($phone !== ''
                && $this->normalizePhone((string) $candidate->phone) === $phone
                && $this->normalizeName((string) $candidate->name) === $name) {
                throw new DomainException('Donatur dengan nama dan nomor telepon yang sama sudah terdaftar (' . $candidate->code . ').');
            }
        }
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function validationMessage(): string
    {
        $errors = $this->donors->errors();

        return $errors === [] ? 'Data donatur tidak valid.' : implode(' ', $errors);
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
        return array_intersect_key($values, array_flip([
            'code',
            'name',
            'type',
            'email',
            'phone',
            'address',
            'notes',
            'is_anonymous',
            'status',
            'created_by',
        ]));
    }
}
