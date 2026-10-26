<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('support.index', compact('tickets'));
    }

    public function create(Request $request): View
    {
        $booking = null;

        if ($request->integer('booking_id')) {
            $booking = $this->visibleBookingsQuery($request)
                ->where('bookings.id', $request->integer('booking_id'))
                ->first();
        }

        return view('support.create', compact('booking'));
    }

    /**
     * Restrict bookings to the ones the signed-in user is allowed to see.
     */
    private function visibleBookingsQuery(Request $request)
    {
        $user = $request->user();

        return \App\Models\Booking::query()->where(function ($query) use ($user) {
            if ($user->role === 'admin') {
                $query->whereRaw('1 = 1');
            } elseif ($user->role === 'teacher') {
                $query->where('teacher_profile_id', $user->teacherProfile?->id);
            } else {
                $query->where('parent_profile_id', $user->parentProfile?->id);
            }
        });
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'in:general,booking,payment,teacher,technical,complaint,other'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
            'priority' => ['nullable', 'in:low,normal,high'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'booking_id' => $validated['booking_id'] ?? null,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'priority' => $validated['priority'] ?? 'normal',
            'status' => 'open',
        ]);

        // Notify every administrator that a ticket is waiting.
        $admins = \App\Models\User::where('role', 'admin')->where('status', 'active')->get();
        $this->notifications->notifyMany(
            $admins,
            'support.ticket',
            'New support ticket: '.$ticket->subject,
            'A new '.str_replace('_', ' ', $ticket->category).' ticket has been raised.',
            ['ticket_id' => $ticket->id]
        );

        return redirect()->route('support.show', $ticket)
            ->with('success', 'Your support ticket has been raised. Our team will get back to you shortly.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id || $request->user()->role === 'admin', 403);

        return view('support.show', compact('ticket'));
    }
}
