<?php

namespace App\Libraries;

use App\Models\DonasiModel;
use App\Models\DonaturModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use DomainException;
use RuntimeException;

/**
 * Logika bisnis pencatatan donasi.
 *
 * - Nomor transaksi dibuat otomatis: DON-YYYYMMDD-NNNN.
 * - Donasi baru selalu berstatus pending.
 * - Hanya donasi pending yang boleh diubah.
 * - Verifikasi mengisi verified_by/verified_at.
 * - Pembatalan wajib beralasan dan tidak menghapus data.
 */
class DonasiService
{
    private const MAX_AMOUNT = 9999999999999.99;
    private const MAX_QUANTITY = 9999999999.99;

    private DonasiModel $donations;
    private DonaturModel $donors;
    private AuditLogger $audit;

    public function __construct(
        ?DonasiModel $donations = null,
        ?DonaturModel $donors = null,
        ?AuditLogger $audit = null,
    ) {
        $this->donations = $donations ?? model(DonasiModel::class);
        $this->donors    = $donors ?? model(DonaturModel::class);
        $this->audit     = $audit ?? service('auditLogger');
    }

    /**
     * @param array<string, mixed> $data Data form yang sudah lolos validasi dasar.
     */
    public function create(array $data): int
    {
        $payload = $this->payload($data);

        $errors = $this->validationErrorsFromPayload($payload);

        if ($errors !== []) {
            throw new DomainException(implode(' ', $errors));
        }

        $payload['status']     = DonasiModel::STATUS_PENDING;
        $payload['created_by'] = service('auth')->id();

        $db = $this->donations->db;
        $db->transBegin();

        try {
            $id = $this->insertWithGeneratedNumber($payload);

            $this->audit->log(
                'donation.created',
                'Mencatat donasi baru: ' . $payload['transaction_number'],
                'donasi',
                $id,
                null,
                $this->safeValues($payload),
            );

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $data Data form yang sudah lolos validasi dasar.
     */
    public function update(int $id, array $data): void
    {
        $payload = $this->payload($data);

        $db = $this->donations->db;
        $db->transBegin();

        try {
            $target = $this->donations->find($id);

            if ($target === null) {
                throw new DomainException('Donasi tidak ditemukan.');
            }

            if ($target->status !== DonasiModel::STATUS_PENDING) {
                throw new DomainException('Hanya donasi berstatus menunggu yang dapat diubah.');
            }

            $errors = $this->validationErrorsFromPayload(
                $payload,
                $target->donor_id !== null ? (int) $target->donor_id : null,
            );

            if ($errors !== []) {
                throw new DomainException(implode(' ', $errors));
            }

            if (! $this->donations->validateForSave(
                $payload + ['transaction_number' => $target->transaction_number],
                $id,
            )) {
                throw new DomainException($this->modelValidationMessage());
            }

            $this->donations
                ->where('status', DonasiModel::STATUS_PENDING)
                ->update($id, $payload);

            $current = $this->donations->find($id);

            if ($current === null || $current->status !== DonasiModel::STATUS_PENDING) {
                throw new DomainException('Donasi sudah berubah status dan tidak dapat diubah.');
            }

            $this->audit->log(
                'donation.updated',
                'Mengubah donasi: ' . $target->transaction_number,
                'donasi',
                $id,
                $this->safeValues((array) $target),
                $payload,
            );

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    public function verify(int $id): void
    {
        $db = $this->donations->db;
        $db->transBegin();

        try {
            $target = $this->donations->find($id);

            if ($target === null) {
                throw new DomainException('Donasi tidak ditemukan.');
            }

            if ($target->status !== DonasiModel::STATUS_PENDING) {
                throw new DomainException('Hanya donasi berstatus menunggu yang dapat diverifikasi.');
            }

            $actorId = service('auth')->id();
            $now     = date('Y-m-d H:i:s');

            $this->donations
                ->where('status', DonasiModel::STATUS_PENDING)
                ->update($id, [
                    'status'      => DonasiModel::STATUS_VERIFIED,
                    'verified_by' => $actorId,
                    'verified_at' => $now,
                ]);

            if ($db->affectedRows() < 1) {
                throw new DomainException('Donasi sudah berubah status dan tidak dapat diverifikasi.');
            }

            $this->audit->log(
                'donation.verified',
                'Memverifikasi donasi: ' . $target->transaction_number,
                'donasi',
                $id,
                ['status' => $target->status],
                [
                    'status'      => DonasiModel::STATUS_VERIFIED,
                    'verified_by' => $actorId,
                    'verified_at' => $now,
                ],
            );

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    public function cancel(int $id, string $reason): void
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new DomainException('Alasan pembatalan minimal 5 karakter.');
        }

        if (mb_strlen($reason) > 255) {
            throw new DomainException('Alasan pembatalan maksimal 255 karakter.');
        }

        $db = $this->donations->db;
        $db->transBegin();

        try {
            $target = $this->donations->find($id);

            if ($target === null) {
                throw new DomainException('Donasi tidak ditemukan.');
            }

            if (! in_array($target->status, [DonasiModel::STATUS_PENDING, DonasiModel::STATUS_VERIFIED], true)) {
                throw new DomainException('Donasi yang sudah dibatalkan tidak dapat dibatalkan lagi.');
            }

            $this->donations
                ->whereIn('status', [DonasiModel::STATUS_PENDING, DonasiModel::STATUS_VERIFIED])
                ->update($id, [
                    'status'           => DonasiModel::STATUS_CANCELLED,
                    'cancelled_reason' => $reason,
                ]);

            if ($db->affectedRows() < 1) {
                throw new DomainException('Donasi sudah berubah status dan tidak dapat dibatalkan.');
            }

            $this->audit->log(
                'donation.cancelled',
                'Membatalkan donasi: ' . $target->transaction_number,
                'donasi',
                $id,
                [
                    'status'      => $target->status,
                    'verified_by' => $target->verified_by,
                    'verified_at' => $target->verified_at,
                ],
                [
                    'status'           => DonasiModel::STATUS_CANCELLED,
                    'cancelled_reason' => $reason,
                ],
            );

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    /**
     * Pemeriksaan aturan bisnis untuk ditampilkan per field di form.
     * Service tetap memeriksa ulang sebelum menulis data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validationErrors(array $data, ?int $existingDonorId = null): array
    {
        return $this->validationErrorsFromPayload($this->payload($data), $existingDonorId);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, string>
     */
    private function validationErrorsFromPayload(array $payload, ?int $existingDonorId = null): array
    {
        $errors = $this->donations->businessRuleErrors($payload);

        $date = $payload['donation_date'];

        if ($date === '' || strtotime($date) === false) {
            $errors['donation_date'] = 'Tanggal donasi wajib diisi dengan format yang benar.';
        } elseif (strtotime($date) > strtotime(date('Y-m-d'))) {
            $errors['donation_date'] = 'Tanggal donasi tidak boleh melebihi hari ini.';
        }

        if ($payload['donor_id'] !== null) {
            $donorId = (int) $payload['donor_id'];
            $donor   = $this->donors->find($donorId);

            if ($donor === null) {
                $archived = $this->donors->withDeleted()->find($donorId);

                // Donatur terarsip tetap boleh dipertahankan pada donasi
                // yang sama agar riwayat tidak terputus saat koreksi.
                if ($archived === null || $existingDonorId !== $donorId) {
                    $errors['donor_id'] = 'Donatur tidak ditemukan atau sudah diarsipkan.';
                }
            }
        }

        if ($payload['donation_type'] === DonasiModel::TYPE_MONEY) {
            if (! in_array($payload['payment_method'], DonasiModel::PAYMENT_METHODS, true)) {
                $errors['payment_method'] = 'Metode pembayaran wajib dipilih untuk donasi uang.';
            } elseif ($payload['payment_method'] === DonasiModel::PAYMENT_BANK_TRANSFER && $payload['reference_number'] === null) {
                $errors['reference_number'] = 'Nomor referensi wajib diisi untuk transfer bank.';
            }

            if ($payload['amount'] !== null && (float) $payload['amount'] > self::MAX_AMOUNT) {
                $errors['amount'] = 'Nominal donasi melebihi batas yang didukung.';
            }
        }

        if ($payload['donation_type'] === DonasiModel::TYPE_GOODS) {
            if ($payload['goods_quantity'] !== null && (float) $payload['goods_quantity'] > self::MAX_QUANTITY) {
                $errors['goods_quantity'] = 'Kuantitas barang melebihi batas yang didukung.';
            }

            if ($payload['estimated_value'] !== null && (float) $payload['estimated_value'] > self::MAX_AMOUNT) {
                $errors['estimated_value'] = 'Nilai estimasi melebihi batas yang didukung.';
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function insertWithGeneratedNumber(array &$payload): int
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $payload['transaction_number'] = $this->generateNumber();

            if (! $this->donations->validateForSave($payload)) {
                throw new DomainException($this->modelValidationMessage());
            }

            try {
                $id = $this->donations->insert($payload, true);
            } catch (DatabaseException $exception) {
                $lastException = $exception;

                if (stripos($exception->getMessage(), 'duplicate') === false) {
                    throw new RuntimeException('Donasi gagal disimpan.', 0, $exception);
                }

                continue;
            }

            if ($id === false) {
                throw new RuntimeException('Donasi gagal disimpan: ' . $this->modelValidationMessage());
            }

            return (int) $id;
        }

        throw new RuntimeException('Donasi gagal disimpan karena nomor transaksi tidak unik.', 0, $lastException);
    }

    private function generateNumber(): string
    {
        $prefix = 'DON-' . date('Ymd') . '-';

        $last = $this->donations->builder()
            ->select('transaction_number')
            ->like('transaction_number', $prefix, 'after')
            ->orderBy('transaction_number', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        $sequence = 1;

        if ($last !== null) {
            $suffix = substr((string) $last->transaction_number, strlen($prefix));

            if ($suffix !== '' && ctype_digit($suffix)) {
                $sequence = (int) $suffix + 1;
            }
        }

        do {
            $number = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;

            $exists = $this->donations->builder()
                ->where('transaction_number', $number)
                ->countAllResults() > 0;
        } while ($exists);

        return $number;
    }

    /**
     * Bentuk payload yang konsisten dengan check constraint database:
     * kolom uang dan barang tidak boleh terisi bersamaan.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $type        = (string) ($data['donation_type'] ?? '');
        $isAnonymous = filter_var($data['is_anonymous'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $donorId     = (int) ($data['donor_id'] ?? 0);

        $payload = [
            'donor_id'         => $donorId > 0 ? $donorId : null,
            'is_anonymous'     => $isAnonymous,
            'program_id'       => null,
            'donation_type'    => $type,
            'status'           => DonasiModel::STATUS_PENDING,
            'donation_date'    => trim((string) ($data['donation_date'] ?? '')),
            'amount'           => null,
            'payment_method'   => null,
            'reference_number' => null,
            'goods_name'       => null,
            'goods_quantity'   => null,
            'goods_unit'       => null,
            'estimated_value'  => null,
            'notes'            => $this->nullableTrim($data['notes'] ?? null),
        ];

        if ($type === DonasiModel::TYPE_MONEY) {
            $payload['amount']           = $this->decimalOrNull($data['amount'] ?? null);
            $payload['payment_method']   = $this->nullableTrim($data['payment_method'] ?? null);
            $payload['reference_number'] = $this->nullableTrim($data['reference_number'] ?? null);
        } elseif ($type === DonasiModel::TYPE_GOODS) {
            $payload['goods_name']      = $this->nullableTrim($data['goods_name'] ?? null);
            $payload['goods_quantity']  = $this->decimalOrNull($data['goods_quantity'] ?? null);
            $payload['goods_unit']      = $this->nullableTrim($data['goods_unit'] ?? null);
            $payload['estimated_value'] = $this->decimalOrNull($data['estimated_value'] ?? null);
        }

        return $payload;
    }

    private function decimalOrNull(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        return str_replace(',', '.', $trimmed);
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function modelValidationMessage(): string
    {
        $errors = $this->donations->errors();

        return $errors === [] ? 'Data donasi tidak valid.' : implode(' ', $errors);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function safeValues(array $values): array
    {
        return array_intersect_key($values, array_flip([
            'transaction_number',
            'donor_id',
            'is_anonymous',
            'program_id',
            'donation_type',
            'status',
            'donation_date',
            'amount',
            'payment_method',
            'reference_number',
            'goods_name',
            'goods_quantity',
            'goods_unit',
            'estimated_value',
            'notes',
            'created_by',
            'verified_by',
            'verified_at',
            'cancelled_reason',
        ]));
    }
}
