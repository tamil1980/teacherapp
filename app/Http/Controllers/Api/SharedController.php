<?php

namespace App\Http\Controllers\Api;

use App\Models\AppNotification;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SharedController extends ApiController
{
    public function notifications(Request $request): JsonResponse
    {
        $query = AppNotification::where('user_id', $request->user()->id);

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        $paginator = $query->orderByDesc('created_at')->paginate($this->perPage());

        return $this->ok([
            'items' => collect($paginator->items())->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'data' => $n->data,
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => optional($n->created_at)->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'unread_count' => AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count(),
        ]);
    }

    public function markNotificationRead(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403, 'This notification does not belong to you.');

        $notification->update(['read_at' => now()]);

        return $this->ok(null, 'Notification marked as read.');
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->ok(null, 'All notifications marked as read.');
    }

    public function tickets(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->with('booking:id,reference_no')
            ->latest()
            ->paginate($this->perPage());

        return $this->paginated($tickets);
    }

    public function storeTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:60'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'booking_id' => $validated['booking_id'] ?? null,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
            'priority' => $validated['priority'] ?? 'normal',
        ]);

        return $this->ok(['ticket' => $ticket], 'Support request submitted.', 201);
    }

    public function ticket(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403, 'This ticket does not belong to you.');

        $ticket->load('booking:id,reference_no');

        return $this->ok(['ticket' => $ticket]);
    }
}
