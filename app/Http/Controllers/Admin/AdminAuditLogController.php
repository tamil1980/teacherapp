<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The audit trail: every mutating administrator action.
 */
class AdminAuditLogController extends Controller
{
    use AuditsActions;

    public function index(Request $request): View
    {
        $filters = [
            'action' => (string) $request->query('action', ''),
            'admin_id' => (string) $request->query('admin_id', ''),
            'subject_type' => (string) $request->query('subject_type', ''),
            'q' => trim((string) $request->query('q', '')),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        $logs = AdminAuditLog::query()
            ->with('admin')
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['admin_id'] !== '', fn ($query) => $query->where('admin_id', (int) $filters['admin_id']))
            ->when($filters['subject_type'] !== '', fn ($query) => $query->where('subject_type', $filters['subject_type']))
            ->when($filters['from'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->when($filters['q'] !== '', fn ($query) => $query->where('action', 'like', '%'.$filters['q'].'%'))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => AdminAuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'subjectTypes' => AdminAuditLog::query()
                ->select('subject_type')
                ->distinct()
                ->orderBy('subject_type')
                ->pluck('subject_type')
                ->filter(),
            'admins' => User::query()->where('role', 'admin')->orderBy('name')->get(),
        ]);
    }
}
