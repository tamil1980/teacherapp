<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentProfile;
use App\Models\TeacherProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Shared helpers for the mobile JSON API.
 */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function error(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message] + $extra, $status);
    }

    /**
     * @param  callable|null  $transform
     */
    protected function paginated(LengthAwarePaginator $paginator, ?callable $transform = null): JsonResponse
    {
        $items = collect($paginator->items());

        return $this->ok([
            'items' => $transform ? $items->map($transform)->values()->all() : $items->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    protected function teacherOrFail(): TeacherProfile
    {
        $teacher = request()->user()?->teacherProfile;

        abort_unless($teacher !== null, 403, 'Your teacher profile could not be found.');

        return $teacher;
    }

    protected function parentOrFail(): ParentProfile
    {
        $parent = request()->user()?->parentProfile;

        abort_unless($parent !== null, 403, 'Your parent profile could not be found.');

        return $parent;
    }

    protected function perPage(int $default = 20, int $max = 100): int
    {
        $perPage = (int) request()->query('per_page', $default);

        return max(1, min($perPage, $max));
    }

    protected function audit(string $action, string $subjectType, ?int $subjectId, array $meta = []): void
    {
        \App\Models\AdminAuditLog::create([
            'admin_id' => request()->user()?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
