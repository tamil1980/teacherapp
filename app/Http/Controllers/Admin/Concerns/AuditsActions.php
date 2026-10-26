<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\AdminAuditLog;

/**
 * Every mutating administrator action is written to the audit trail.
 *
 * The trail records *who* acted, *what* they acted on and a JSON snapshot of
 * the relevant details so that the platform can be reviewed later.
 */
trait AuditsActions
{
    /**
     * @param  array<string, mixed>  $meta
     */
    protected function audit(string $action, ?string $subjectType = null, ?int $subjectId = null, array $meta = []): AdminAuditLog
    {
        return AdminAuditLog::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta === [] ? null : $meta,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
