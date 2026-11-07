<?php

namespace App\Libraries;

use App\Models\DetailDistribusiModel;
use App\Models\DistribusiModel;
use App\Models\DonasiModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use DomainException;
use RuntimeException;

/**
 * Logika bisnis penyaluran donasi.
 *
 * Aturan saldo yang diterapkan:
 * - Saldo TIDAK disimpan sebagai kolom/tabel saldo. Saldo selalu dihitung
 *   dari data sumber agar tidak ada angka yang bisa menyimpang.
 * - Penerimaan uang yang dihitung: donasi uang berstatus `verified`.
 *   Donasi `pending` belum diverifikasi sehingga belum boleh disalurkan.
 * - Penyaluran uang yang dihitung: distribusi `approved` dan `distributed`.
 *   Draf belum mengurangi saldo; `cancelled` tidak pernah mengurangi saldo.
 * - Saldo barang dihitung per pasangan nama barang + satuan yang
 *   dinormalisasi (huruf kecil, spasi berlebih dirapikan). Tidak ada
 *   konversi satuan: `kg` dan `liter` adalah stok yang berbeda.
 * - Seluruh donasi dan distribusi saat ini berada pada kolam global
 *   (`program_id` NULL). Alokasi per program belum diaktifkan agar saldo
 *   program yang berbeda tidak tercampur tanpa aturan.
 * - Sebelum saldo dihitung pada proses persetujuan, service mengunci baris
 *   `distribution_balance_locks` dengan SELECT ... FOR UPDATE di dalam
 *   transaksi. Dua permintaan bersamaan untuk kunci yang sama akan
 *   dijalankan berurutan sehingga saldo tidak dapat dipakai dua kali.
 */
class DistribusiService
{
    public const MAX_AMOUNT    = '9999999999999.99';
    public const MAX_QUANTITY  = '9999999999.99';
    public const MAX_ITEMS     = 10;
    public const MONEY_KEY     = 'money';
    public const LOCK_TABLE    = 'distribution_balance_locks';
    public const MAX_LOCK_TRIES = 3;

    private DistribusiModel $distributions;
    private DetailDistribusiModel $details;
    private DonasiModel $donations;
    private AuditLogger $audit;

    public function __construct(
        ?DistribusiModel $distributions = null,
        ?DetailDistribusiModel $details = null,
        ?DonasiModel $donations = null,
        ?AuditLogger $audit = null,
    ) {
        $this->distributions = $distributions ?? model(DistribusiModel::class);
        $this->details       = $details ?? model(DetailDistribusiModel::class);
        $this->donations     = $donations ?? model(DonasiModel::class);
        $this->audit         = $audit ?? service('auditLogger');
    }

    /**
     * @param array<string, mixed>  $data
     * @param array<int, mixed>     $items
     */
    public function create(array $data, array $items = []): int
    {
        $errors = $this->validationErrors($data, $items);

        if ($errors !== []) {
            throw new DomainException(implode(' ', $errors));
        }

        $payload = $this->payload($data);
        $rows    = $payload['distribution_type'] === DistribusiModel::TYPE_GOODS
            ? $this->payloadItems($items)
            : [];

        $payload['status']     = DistribusiModel::STATUS_DRAFT;
        $payload['created_by'] = service('auth')->id();

        $db = $this->distributions->db;
        $db->transBegin();

        try {
            $id = $this->insertWithGeneratedNumber($payload);
            $this->insertDetails($id, $rows);

            $this->audit->log(
                'distribution.created',
                'Mencatat distribusi baru: ' . $payload['distribution_number'],
                'distribusi',
                $id,
                null,
                $this->safeValues($payload) + ['items' => $rows],
            );

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, mixed>    $items
     */
    public function update(int $id, array $data, array $items = []): void
    {
        $errors = $this->validationErrors($data, $items);

        if ($errors !== []) {
            throw new DomainException(implode(' ', $errors));
        }

        $db = $this->distributions->db;
        $db->transBegin();

        try {
            $target = $this->distributions->find($id);

            if ($target === null) {
                throw new DomainException('Distribusi tidak ditemukan.');
            }

            if ($target->status !== DistribusiModel::STATUS_DRAFT) {
                throw new DomainException('Hanya distribusi berstatus draf yang dapat diubah. Transaksi final harus dibatalkan lalu dicatat ulang.');
            }

            $payload = $this->payload($data);
            $rows    = $payload['distribution_type'] === DistribusiModel::TYPE_GOODS
                ? $this->payloadItems($items)
                : [];
            $editable = array_intersect_key($payload, array_flip([
                'program_id',
                'donation_id',
                'distribution_type',
                'distribution_date',
                'recipient_name',
                'recipient_type',
                'recipient_address',
                'amount',
                'description',
                'notes',
            ]));

            if (! $this->distributions->validateForSave($editable + [
                'distribution_number' => $target->distribution_number,
                'status'              => $target->status,
            ], $id)) {
                throw new DomainException($this->modelValidationMessage());
            }

            $oldItems = $this->detailsFor($id);

            $this->details->where('distribution_id', $id)->delete();
            $this->insertDetails($id, $rows);

            $this->distributions
                ->where('status', DistribusiModel::STATUS_DRAFT)
                ->update($id, $editable);

            // affectedRows() bernilai 0 bila nilai tidak berubah, sehingga
            // status dibaca ulang untuk membuktikan baris masih draf.
            $current = $this->distributions->find($id);

            if ($current === null || $current->status !== DistribusiModel::STATUS_DRAFT) {
                throw new DomainException('Distribusi sudah berubah status dan tidak dapat diubah.');
            }

            $this->audit->log(
                'distribution.updated',
                'Mengubah distribusi: ' . $target->distribution_number,
                'distribusi',
                $id,
                $this->safeValues((array) $target) + ['items' => $oldItems],
                $this->safeValues($editable) + ['items' => $rows],
            );

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    /**
     * Menyetujui distribusi draf. Titik inilah saldo diperiksa dan dikunci,
     * karena status approved mulai mengurangi saldo aktif.
     */
    public function approve(int $id): void
    {
        $db = $this->distributions->db;
        $db->transBegin();

        try {
            $target = $this->distributions->find($id);

            if ($target === null) {
                throw new DomainException('Distribusi tidak ditemukan.');
            }

            if ($target->status !== DistribusiModel::STATUS_DRAFT) {
                throw new DomainException('Hanya distribusi berstatus draf yang dapat disetujui.');
            }

            $items = $this->detailsFor($id);

            if ($target->distribution_type === DistribusiModel::TYPE_GOODS && $items === []) {
                throw new DomainException('Distribusi barang wajib memiliki minimal satu rincian barang.');
            }

            $this->lockBalanceKeys($this->balanceKeysFor($target->distribution_type, $items));
            $this->assertBalanceSufficient($target, $items);

            $actorId = service('auth')->id();
            $now     = date('Y-m-d H:i:s');

            $this->distributions
                ->where('status', DistribusiModel::STATUS_DRAFT)
                ->update($id, [
                    'status'      => DistribusiModel::STATUS_APPROVED,
                    'approved_by' => $actorId,
                    'approved_at' => $now,
                ]);

            if ($db->affectedRows() < 1) {
                throw new DomainException('Distribusi sudah berubah status dan tidak dapat disetujui.');
            }

            $this->audit->log(
                'distribution.approved',
                'Menyetujui distribusi: ' . $target->distribution_number,
                'distribusi',
                $id,
                ['status' => $target->status],
                [
                    'status'      => DistribusiModel::STATUS_APPROVED,
                    'approved_by' => $actorId,
                    'approved_at' => $now,
                ],
            );

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    /**
     * Menandai distribusi yang sudah disetujui sebagai tersalurkan.
     * Saldo tidak berubah karena status approved sudah mengurangi saldo.
     */
    public function markDistributed(int $id): void
    {
        $db = $this->distributions->db;
        $db->transBegin();

        try {
            $target = $this->distributions->find($id);

            if ($target === null) {
                throw new DomainException('Distribusi tidak ditemukan.');
            }

            if ($target->status !== DistribusiModel::STATUS_APPROVED) {
                throw new DomainException('Hanya distribusi yang sudah disetujui yang dapat ditandai tersalurkan.');
            }

            $now = date('Y-m-d H:i:s');

            $this->distributions
                ->where('status', DistribusiModel::STATUS_APPROVED)
                ->update($id, [
                    'status'         => DistribusiModel::STATUS_DISTRIBUTED,
                    'distributed_at' => $now,
                ]);

            if ($db->affectedRows() < 1) {
                throw new DomainException('Distribusi sudah berubah status dan tidak dapat ditandai tersalurkan.');
            }

            $this->audit->log(
                'distribution.distributed',
                'Menandai distribusi tersalurkan: ' . $target->distribution_number,
                'distribusi',
                $id,
                ['status' => $target->status],
                [
                    'status'         => DistribusiModel::STATUS_DISTRIBUTED,
                    'distributed_at' => $now,
                ],
            );

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    /**
     * Membatalkan distribusi tanpa menghapus data. Saldo aktif otomatis
     * kembali karena status cancelled tidak dihitung pada perhitungan saldo.
     */
    public function cancel(int $id, string $reason): void
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new DomainException('Alasan pembatalan minimal 5 karakter.');
        }

        if (mb_strlen($reason) > 255) {
            throw new DomainException('Alasan pembatalan maksimal 255 karakter.');
        }

        $db = $this->distributions->db;
        $db->transBegin();

        try {
            $target = $this->distributions->find($id);

            if ($target === null) {
                throw new DomainException('Distribusi tidak ditemukan.');
            }

            if ($target->status === DistribusiModel::STATUS_CANCELLED) {
                throw new DomainException('Distribusi yang sudah dibatalkan tidak dapat dibatalkan lagi.');
            }

            $wasActive = in_array($target->status, DistribusiModel::ACTIVE_STATUSES, true);

            $this->distributions
                ->whereIn('status', [
                    DistribusiModel::STATUS_DRAFT,
                    DistribusiModel::STATUS_APPROVED,
                    DistribusiModel::STATUS_DISTRIBUTED,
                ])
                ->update($id, [
                    'status'           => DistribusiModel::STATUS_CANCELLED,
                    'cancelled_reason' => $reason,
                ]);

            if ($db->affectedRows() < 1) {
                throw new DomainException('Distribusi sudah berubah status dan tidak dapat dibatalkan.');
            }

            $this->audit->log(
                'distribution.cancelled',
                'Membatalkan distribusi: ' . $target->distribution_number,
                'distribusi',
                $id,
                [
                    'status'         => $target->status,
                    'approved_by'    => $target->approved_by,
                    'approved_at'    => $target->approved_at,
                    'distributed_at' => $target->distributed_at,
                ],
                [
                    'status'           => DistribusiModel::STATUS_CANCELLED,
                    'cancelled_reason' => $reason,
                    'released_balance' => $wasActive,
                ],
            );

            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    /**
     * Aturan bisnis untuk ditampilkan per field pada formulir.
     * Service memeriksa ulang aturan yang sama sebelum menulis data.
     *
     * @param array<string, mixed> $data
     * @param array<int, mixed>    $items
     *
     * @return array<string, string>
     */
    public function validationErrors(array $data, array $items = []): array
    {
        $errors = [];

        $type = (string) ($data['distribution_type'] ?? '');

        if (! in_array($type, DistribusiModel::TYPES, true)) {
            $errors['distribution_type'] = 'Jenis bantuan wajib dipilih.';
        }

        $date = trim((string) ($data['distribution_date'] ?? ''));

        if (! $this->isValidDate($date)) {
            $errors['distribution_date'] = 'Tanggal penyaluran wajib diisi dengan format YYYY-MM-DD yang benar.';
        } elseif ($date > date('Y-m-d')) {
            $errors['distribution_date'] = 'Tanggal penyaluran tidak boleh melebihi hari ini.';
        }

        $recipientName = trim((string) ($data['recipient_name'] ?? ''));

        if ($recipientName === '') {
            $errors['recipient_name'] = 'Nama penerima manfaat wajib diisi.';
        } elseif (mb_strlen($recipientName) > 150) {
            $errors['recipient_name'] = 'Nama penerima manfaat maksimal 150 karakter.';
        }

        $recipientType = (string) ($data['recipient_type'] ?? '');

        if (! in_array($recipientType, DistribusiModel::RECIPIENT_TYPES, true)) {
            $errors['recipient_type'] = 'Jenis penerima wajib dipilih.';
        }

        if (mb_strlen(trim((string) ($data['recipient_address'] ?? ''))) > 1000) {
            $errors['recipient_address'] = 'Alamat penerima maksimal 1000 karakter.';
        }

        $description = trim((string) ($data['description'] ?? ''));

        if ($description === '') {
            $errors['description'] = 'Tujuan penyaluran wajib diisi.';
        } elseif (mb_strlen($description) > 2000) {
            $errors['description'] = 'Tujuan penyaluran maksimal 2000 karakter.';
        }

        if (mb_strlen(trim((string) ($data['notes'] ?? ''))) > 2000) {
            $errors['notes'] = 'Keterangan maksimal 2000 karakter.';
        }

        if ($type === DistribusiModel::TYPE_MONEY) {
            $amount = $this->decimalOrNull($data['amount'] ?? null);
            $cents  = $amount === null ? 0 : $this->toScaled($amount);

            if ($amount === null || $cents <= 0) {
                $errors['amount'] = 'Nominal penyaluran wajib lebih dari 0.';
            } elseif ($this->toScaled($amount) > $this->toScaled(self::MAX_AMOUNT)) {
                $errors['amount'] = 'Nominal penyaluran melebihi batas yang didukung.';
            }
        }

        if ($type === DistribusiModel::TYPE_GOODS) {
            $count = 0;

            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $name    = trim((string) ($item['item_name'] ?? ''));
                $unit    = trim((string) ($item['unit'] ?? ''));
                $rawQty  = trim((string) ($item['quantity'] ?? ''));

                if ($name === '' && $unit === '' && $rawQty === '') {
                    continue;
                }

                $count++;

                if ($name === '') {
                    $errors['items.' . $index . '.item_name'] = 'Nama barang wajib diisi.';
                } elseif (mb_strlen($name) > 150) {
                    $errors['items.' . $index . '.item_name'] = 'Nama barang maksimal 150 karakter.';
                }

                if ($unit === '') {
                    $errors['items.' . $index . '.unit'] = 'Satuan wajib diisi.';
                } elseif (mb_strlen($unit) > 30) {
                    $errors['items.' . $index . '.unit'] = 'Satuan maksimal 30 karakter.';
                }

                $quantity = $this->decimalOrNull($rawQty);
                $cents    = $quantity === null ? 0 : $this->toScaled($quantity);

                if ($quantity === null || $cents <= 0) {
                    $errors['items.' . $index . '.quantity'] = 'Kuantitas wajib lebih dari 0.';
                } elseif ($cents > $this->toScaled(self::MAX_QUANTITY)) {
                    $errors['items.' . $index . '.quantity'] = 'Kuantitas melebihi batas yang didukung.';
                }
            }

            if ($count === 0) {
                $errors['items'] = 'Minimal satu barang wajib diisi untuk distribusi barang.';
            } elseif ($count > self::MAX_ITEMS) {
                $errors['items'] = 'Maksimal ' . self::MAX_ITEMS . ' jenis barang per distribusi.';
            }
        }

        return $errors;
    }

    /**
     * Saldo dana global (program_id NULL).
     *
     * @return array{received_cents: int, distributed_cents: int, available_cents: int, received: string, distributed: string, available: string}
     */
    public function moneyBalance(): array
    {
        $received = (string) $this->scalar(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM donasi "
            . "WHERE donation_type = 'money' AND status = 'verified' "
            . 'AND deleted_at IS NULL',
        );

        $distributed = (string) $this->scalar(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM distribusi '
            . "WHERE distribution_type = 'money' AND status IN ('approved', 'distributed') "
            . 'AND deleted_at IS NULL',
        );

        $receivedCents    = $this->toScaled($received);
        $distributedCents = $this->toScaled($distributed);
        $availableCents   = $receivedCents - $distributedCents;

        return [
            'received_cents'    => $receivedCents,
            'distributed_cents' => $distributedCents,
            'available_cents'   => $availableCents,
            'received'          => $this->formatMoney($receivedCents),
            'distributed'       => $this->formatMoney($distributedCents),
            'available'         => $this->formatMoney($availableCents),
        ];
    }

    /**
     * Saldo seluruh barang, dikelompokkan per nama + satuan ternormalisasi.
     *
     * @return array<string, array{key: string, item_name: string, unit: string, received_cents: int, distributed_cents: int, available_cents: int, received: string, distributed: string, available: string}>
     */
    public function goodsBalances(): array
    {
        $receivedRows = $this->distributions->db->query(
            "SELECT LOWER(TRIM(REGEXP_REPLACE(goods_name, '[[:space:]]+', ' '))) AS item_key,"
            . " LOWER(TRIM(REGEXP_REPLACE(goods_unit, '[[:space:]]+', ' '))) AS unit_key,"
            . ' MIN(goods_name) AS sample_name, MIN(goods_unit) AS sample_unit,'
            . ' SUM(goods_quantity) AS total'
            . " FROM donasi WHERE donation_type = 'goods' AND status = 'verified'"
            . ' AND deleted_at IS NULL'
            . ' AND goods_name IS NOT NULL AND goods_unit IS NOT NULL'
            . ' GROUP BY item_key, unit_key',
        )->getResultArray();

        $distributedRows = $this->distributions->db->query(
            "SELECT LOWER(TRIM(REGEXP_REPLACE(d.item_name, '[[:space:]]+', ' '))) AS item_key,"
            . " LOWER(TRIM(REGEXP_REPLACE(d.unit, '[[:space:]]+', ' '))) AS unit_key,"
            . ' MIN(d.item_name) AS sample_name, MIN(d.unit) AS sample_unit,'
            . ' SUM(d.quantity) AS total'
            . ' FROM detail_distribusi d'
            . ' INNER JOIN distribusi s ON s.id = d.distribution_id'
            . " WHERE s.distribution_type = 'goods' AND s.status IN ('approved', 'distributed')"
            . ' AND s.deleted_at IS NULL'
            . ' GROUP BY item_key, unit_key',
        )->getResultArray();

        $balances = [];

        foreach ($receivedRows as $row) {
            $key      = (string) $row['item_key'] . '|' . (string) $row['unit_key'];
            $balances[$key] = [
                'key'               => $key,
                'item_name'         => (string) $row['sample_name'],
                'unit'              => (string) $row['sample_unit'],
                'received_cents'    => $this->toScaled((string) $row['total']),
                'distributed_cents' => 0,
            ];
        }

        foreach ($distributedRows as $row) {
            $key = (string) $row['item_key'] . '|' . (string) $row['unit_key'];

            if (! isset($balances[$key])) {
                $balances[$key] = [
                    'key'               => $key,
                    'item_name'         => (string) $row['sample_name'],
                    'unit'              => (string) $row['sample_unit'],
                    'received_cents'    => 0,
                    'distributed_cents' => 0,
                ];
            }

            $balances[$key]['distributed_cents'] += $this->toScaled((string) $row['total']);
        }

        foreach ($balances as $key => $balance) {
            $available = $balance['received_cents'] - $balance['distributed_cents'];

            $balances[$key]['available_cents'] = $available;
            $balances[$key]['received']        = $this->formatQuantity($balance['received_cents']);
            $balances[$key]['distributed']     = $this->formatQuantity($balance['distributed_cents']);
            $balances[$key]['available']       = $this->formatQuantity($available);
        }

        ksort($balances);

        return $balances;
    }

    /**
     * @return array{key: string, item_name: string, unit: string, received_cents: int, distributed_cents: int, available_cents: int, received: string, distributed: string, available: string}
     */
    public function goodsBalance(string $itemName, string $unit): array
    {
        $key      = $this->goodsKey($itemName, $unit);
        $balances = $this->goodsBalances();

        if (isset($balances[$key])) {
            return $balances[$key];
        }

        return [
            'key'               => $key,
            'item_name'         => $itemName,
            'unit'              => $unit,
            'received_cents'    => 0,
            'distributed_cents' => 0,
            'available_cents'   => 0,
            'received'          => '0,00',
            'distributed'       => '0,00',
            'available'         => '0,00',
        ];
    }

    /**
     * Saldo barang yang masih tersedia (untuk ditampilkan di formulir).
     *
     * @return list<array<string, mixed>>
     */
    public function availableGoods(): array
    {
        return array_values(array_filter(
            $this->goodsBalances(),
            static fn (array $balance): bool => $balance['available_cents'] > 0,
        ));
    }

    /**
     * @return list<object>
     */
    public function detailsFor(int $distributionId): array
    {
        return $this->details
            ->where('distribution_id', $distributionId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Normalisasi nama barang/satuan. Harus konsisten dengan ekspresi SQL
     * REGEXP_REPLACE + LOWER + TRIM yang dipakai goodsBalances().
     */
    public function normalizeText(string $value): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower(trim($collapsed), 'UTF-8');
    }

    /**
     * Kunci saldo barang yang dipakai pada tabel kunci (berprefiks `goods:`).
     */
    public function balanceKey(string $itemName, string $unit): string
    {
        return 'goods:' . $this->goodsKey($itemName, $unit);
    }

    /**
     * Kunci saldo barang tanpa prefiks, konsisten dengan kunci array dari
     * goodsBalances().
     */
    public function goodsKey(string $itemName, string $unit): string
    {
        return $this->normalizeText($itemName) . '|' . $this->normalizeText($unit);
    }

    /**
     * @param list<object> $items
     *
     * @return list<string>
     */
    private function balanceKeysFor(string $type, array $items): array
    {
        if ($type === DistribusiModel::TYPE_MONEY) {
            return [self::MONEY_KEY];
        }

        $keys = [];

        foreach ($items as $item) {
            $keys[] = $this->balanceKey((string) $item->item_name, (string) $item->unit);
        }

        return $keys === [] ? [self::MONEY_KEY] : $keys;
    }

    /**
     * @param list<string> $keys
     */
    private function lockBalanceKeys(array $keys): void
    {
        $keys = array_values(array_unique($keys));
        sort($keys);

        $db = $this->distributions->db;

        foreach ($keys as $key) {
            // INSERT ... ON DUPLICATE KEY mengunci baris kunci (atau membuatnya
            // bila belum ada) sehingga penulis lain harus menunggu sampai
            // transaksi ini selesai.
            $db->query(
                'INSERT INTO ' . self::LOCK_TABLE . ' (balance_key, created_at, updated_at) '
                . 'VALUES (?, NOW(), NOW()) '
                . 'ON DUPLICATE KEY UPDATE updated_at = NOW()',
                [$key],
            );

            $db->query(
                'SELECT balance_key FROM ' . self::LOCK_TABLE . ' WHERE balance_key = ? FOR UPDATE',
                [$key],
            );
        }
    }

    /**
     * Saldo diperiksa SETELAH kunci diperoleh agar nilai yang dibaca adalah
     * nilai terbaru dari transaksi lain yang mungkin baru commit.
     *
     * @param list<object> $items
     */
    private function assertBalanceSufficient(object $target, array $items): void
    {
        if ($target->distribution_type === DistribusiModel::TYPE_MONEY) {
            $balance = $this->moneyBalance();
            $needed  = $this->toScaled((string) $target->amount);

            if ($needed > $balance['available_cents']) {
                throw new DomainException(
                    'Saldo dana tidak mencukupi. Tersedia Rp ' . $balance['available']
                    . ', dibutuhkan Rp ' . $this->formatMoney($needed) . '.',
                );
            }

            return;
        }

        $neededByKey = [];

        foreach ($items as $item) {
            $key = $this->goodsKey((string) $item->item_name, (string) $item->unit);
            $neededByKey[$key] = ($neededByKey[$key] ?? 0) + $this->toScaled((string) $item->quantity);
        }

        $balances = $this->goodsBalances();

        foreach ($neededByKey as $key => $needed) {
            $available = isset($balances[$key]) ? $balances[$key]['available_cents'] : 0;

            if ($needed > $available) {
                [$itemName, $unit] = $this->describeBalanceKey($key, $items, $balances);

                throw new DomainException(
                    'Stok barang "' . $itemName . '" (' . $unit . ') tidak mencukupi. Tersedia '
                    . $this->formatQuantity($available) . ' ' . $unit
                    . ', dibutuhkan ' . $this->formatQuantity($needed) . ' ' . $unit . '.',
                );
            }
        }
    }

    /**
     * @param list<object> $items
     * @param array<string, array<string, mixed>> $balances
     *
     * @return array{0: string, 1: string}
     */
    private function describeBalanceKey(string $key, array $items, array $balances): array
    {
        foreach ($items as $item) {
            if ($this->goodsKey((string) $item->item_name, (string) $item->unit) === $key) {
                return [(string) $item->item_name, (string) $item->unit];
            }
        }

        if (isset($balances[$key])) {
            return [(string) $balances[$key]['item_name'], (string) $balances[$key]['unit']];
        }

        return [$key, ''];
    }

    /**
     * @param list<array<string, string>> $rows
     */
    private function insertDetails(int $distributionId, array $rows): void
    {
        foreach ($rows as $row) {
            $inserted = $this->details->insert([
                'distribution_id' => $distributionId,
                'item_name'       => $row['item_name'],
                'quantity'        => $row['quantity'],
                'unit'            => $row['unit'],
                'unit_value'      => null,
                'notes'           => null,
            ]);

            if ($inserted === false) {
                throw new RuntimeException('Rincian barang gagal disimpan: ' . $this->details->errors());
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function insertWithGeneratedNumber(array &$payload): int
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_LOCK_TRIES; $attempt++) {
            $payload['distribution_number'] = $this->generateNumber();

            if (! $this->distributions->validateForSave($payload)) {
                throw new DomainException($this->modelValidationMessage());
            }

            try {
                $id = $this->distributions->insert($payload, true);
            } catch (DatabaseException $exception) {
                $lastException = $exception;

                if (stripos($exception->getMessage(), 'duplicate') === false) {
                    throw new RuntimeException('Distribusi gagal disimpan.', 0, $exception);
                }

                continue;
            }

            if ($id === false) {
                throw new RuntimeException('Distribusi gagal disimpan: ' . $this->modelValidationMessage());
            }

            return (int) $id;
        }

        throw new RuntimeException('Distribusi gagal disimpan karena nomor distribusi tidak unik.', 0, $lastException);
    }

    private function generateNumber(): string
    {
        $prefix = 'DSR-' . date('Ymd') . '-';

        $last = $this->distributions->builder()
            ->select('distribution_number')
            ->like('distribution_number', $prefix, 'after')
            ->orderBy('distribution_number', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        $sequence = 1;

        if ($last !== null) {
            $suffix = substr((string) $last->distribution_number, strlen($prefix));

            if ($suffix !== '' && ctype_digit($suffix)) {
                $sequence = (int) $suffix + 1;
            }
        }

        do {
            $number = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;

            $exists = $this->distributions->builder()
                ->where('distribution_number', $number)
                ->countAllResults() > 0;
        } while ($exists);

        return $number;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $type = (string) ($data['distribution_type'] ?? '');

        return [
            'program_id'        => null,
            'donation_id'       => null,
            'distribution_type' => $type,
            'distribution_date' => trim((string) ($data['distribution_date'] ?? '')),
            'recipient_name'    => trim((string) ($data['recipient_name'] ?? '')),
            'recipient_type'    => (string) ($data['recipient_type'] ?? ''),
            'recipient_address' => $this->nullableTrim($data['recipient_address'] ?? null),
            'amount'            => $type === DistribusiModel::TYPE_MONEY
                ? $this->decimalOrNull($data['amount'] ?? null)
                : null,
            'description'       => $this->nullableTrim($data['description'] ?? null),
            'notes'             => $this->nullableTrim($data['notes'] ?? null),
        ];
    }

    /**
     * @param array<int, mixed> $items
     *
     * @return list<array<string, string>>
     */
    private function payloadItems(array $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['item_name'] ?? ''));
            $unit = trim((string) ($item['unit'] ?? ''));
            $qty  = $this->decimalOrNull($item['quantity'] ?? null);

            if ($name === '' || $unit === '' || $qty === null) {
                continue;
            }

            $rows[] = [
                'item_name' => $name,
                'quantity'  => $qty,
                'unit'      => $unit,
            ];
        }

        return $rows;
    }

    private function scalar(string $sql): mixed
    {
        $row = $this->distributions->db->query($sql)->getRow();

        return $row === null ? 0 : ($row->total ?? 0);
    }

    /**
     * Nilai desimal (maksimal 2 angka di belakang koma) menjadi bilangan
     * bulat berskala 100 agar perbandingan saldo tidak memakai float.
     */
    private function toScaled(string $value): int
    {
        $value = trim(str_replace(',', '.', $value));

        if ($value === '' || preg_match('/^-?\d+(?:\.\d+)?$/', $value) !== 1) {
            return 0;
        }

        $negative = str_starts_with($value, '-');
        $value    = ltrim($value, '-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        $scaled = ((int) $whole) * 100 + (int) $fraction;

        return $negative ? -$scaled : $scaled;
    }

    private function formatMoney(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $abs  = abs($cents);

        return $sign . number_format(intdiv($abs, 100), 0, ',', '.')
            . ',' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    private function formatQuantity(int $hundredths): string
    {
        return $this->formatMoney($hundredths);
    }

    private function decimalOrNull(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        return str_replace(',', '.', $trimmed);
    }

    private function isValidDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map(
            static fn (string $part): int => (int) $part,
            explode('-', $value),
        );

        return checkdate($month, $day, $year);
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function modelValidationMessage(): string
    {
        $errors = $this->distributions->errors();

        return $errors === [] ? 'Data distribusi tidak valid.' : implode(' ', $errors);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function safeValues(array $values): array
    {
        return array_intersect_key($values, array_flip([
            'distribution_number',
            'program_id',
            'donation_id',
            'distribution_type',
            'status',
            'distribution_date',
            'recipient_name',
            'recipient_type',
            'recipient_address',
            'amount',
            'description',
            'notes',
            'created_by',
            'approved_by',
            'approved_at',
            'distributed_at',
            'cancelled_reason',
        ]));
    }
}
