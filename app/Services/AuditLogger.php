<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Record a system-level audit event.
     *
     * @param string     $action      Dot-notation action: 'user.deactivated'
     * @param Model|null $subject     The entity the action was performed on
     * @param string     $description Human-readable description
     * @param array|null $metadata    Structured additional data
     */
    public static function log(
        string $action,
        ?Model $subject,
        string $description,
        ?array $metadata = null
    ): void {
        AuditLog::create([
            'actor_id'     => auth()->id(),
            'action'       => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'description'  => $description,
            'metadata'     => $metadata,
            'ip_address'   => request()->ip(),
        ]);
    }
}
