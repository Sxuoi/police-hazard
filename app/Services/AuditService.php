<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Ramsey\Uuid\Uuid;

/**
 * AuditService — PRD §14.1, §14.2.
 * Called from Action classes (never Controllers).
 * Every audit entry includes request context for distributed tracing.
 */
class AuditService
{
    /**
     * Keys that must be stripped from audit metadata (case-insensitive match).
     */
    private const REDACTED_PATTERNS = [
        'password',
        'authorization',
        'bearer',
        'token',
        'secret',
    ];

    /**
     * Log an audit event.
     *
     * @param  string  $eventType  Event from the catalogue (PRD §14.1)
     * @param  Model|null  $entity  The entity being acted upon
     * @param  array  $metadata  Additional context data
     * @param  array|null  $before  State before change (for updates)
     * @param  array|null  $after  State after change (for updates)
     */
    public function log(
        string $eventType,
        ?Model $entity = null,
        array $metadata = [],
        ?array $before = null,
        ?array $after = null,
    ): AuditLog {
        return AuditLog::create([
            'id' => Uuid::uuid7()->toString(),
            'actor_id' => Auth::id(),
            'actor_ip' => Request::ip(),
            'actor_user_agent' => Request::userAgent(),
            'saker_id' => Auth::user()?->saker_id,
            'event_type' => $eventType,
            'entity_type' => $entity ? class_basename($entity) : 'System',
            'entity_id' => $entity?->getKey(),
            'payload_before' => $before ? $this->redact($before) : null,
            'payload_after' => $after ? $this->redact($after) : null,
            'metadata' => ! empty($metadata) ? $this->redact($metadata) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Redact sensitive keys from metadata arrays.
     *
     * Strips any key whose name contains 'password', 'authorization', 'bearer',
     * 'token', or 'secret' (case-insensitive). Nested arrays are redacted recursively.
     *
     * (R8.3, R11.5)
     *
     * @param  array  $metadata  The metadata array to redact
     * @return array The redacted metadata array
     */
    public function redact(array $metadata): array
    {
        $result = [];

        foreach ($metadata as $key => $value) {
            $keyLower = strtolower((string) $key);

            $isSensitive = false;
            foreach (self::REDACTED_PATTERNS as $pattern) {
                if (str_contains($keyLower, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $result[$key] = $this->redact($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
