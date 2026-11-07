<?php

namespace App\Libraries;

use App\Models\AuditLogModel;
use Config\Auth as AuthConfig;

/**
 * Menulis jejak audit aktivitas penting.
 *
 * Audit bersifat append-only: tidak ada endpoint aplikasi yang mengubah
 * atau menghapus baris audit_logs.
 */
class AuditLogger
{
    private AuditLogModel $logs;

    public function __construct(?AuditLogModel $logs = null)
    {
        $this->logs = $logs ?? model(AuditLogModel::class);
    }

    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public function log(
        string $action,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        $request = service('request');

        $userAgent = '';
        if (method_exists($request, 'getUserAgent')) {
            $userAgent = $request->getUserAgent()->getAgentString();
        }

        $ipAddress = method_exists($request, 'getIPAddress')
            ? $request->getIPAddress()
            : null;

        $payload = [
            'user_id'     => $this->currentUserId(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'description' => $description,
            'old_values'  => $oldValues !== null ? $this->encode($oldValues) : null,
            'new_values'  => $newValues !== null ? $this->encode($newValues) : null,
            'ip_address'  => $ipAddress,
            'user_agent'  => $userAgent !== '' ? mb_substr($userAgent, 0, 255) : null,
        ];

        try {
            if ($this->logs->insert($payload) === false) {
                log_message('error', 'Gagal menulis audit log: {action}', ['action' => $action]);
            }
        } catch (\Throwable $exception) {
            // Audit tidak boleh membuat aplikasi berhenti, tetapi kegagalan
            // harus tercatat di log aplikasi untuk ditindaklanjuti.
            log_message('error', 'Gagal menulis audit log: {message}', ['message' => $exception->getMessage()]);
        }
    }

    private function currentUserId(): ?int
    {
        $userId = session(config(AuthConfig::class)->sessionKey);

        return is_numeric($userId) ? (int) $userId : null;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function encode(array $values): ?string
    {
        $encoded = json_encode(
            $this->sanitize($values),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return $encoded === false ? null : $encoded;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && preg_match('/password|token|secret|hash/i', $key) === 1) {
                unset($values[$key]);

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }
}
