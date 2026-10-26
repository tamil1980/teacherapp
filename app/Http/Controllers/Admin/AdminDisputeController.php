<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Dispute;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Disputes raised against a booking, plus the complaint queue.
 */
class AdminDisputeController extends Controller
{
    use AuditsActions;

    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => (string) $request->query('status', ''),
            'type' => (string) $request->query('type', 'disputes'),
        ];

        if (! in_array($filters['type'], ['disputes', 'complaints'], true)) {
            $filters['type'] = 'disputes';
        }

        $disputes = Dispute::query()
            ->with(['booking.teacher.user', 'booking.parent.user'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $complaints = Complaint::query()
            ->with(['booking.teacher.user'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.disputes.index', [
            'disputes' => $disputes,
            'complaints' => $complaints,
            'filters' => $filters,
            'disputeStatusCounts' => Dispute::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
            'complaintStatusCounts' => Complaint::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
        ]);
    }

    /**
     * Close a dispute with a resolution note.
     */
    public function resolve(Request $request, Dispute $dispute): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['resolved', 'rejected', 'open', 'in_progress'])],
            'resolution' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $previous = $dispute->status;

        $dispute->update([
            'status' => $validated['status'],
            'resolution' => $validated['resolution'],
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        $this->notifyParty($dispute, 'dispute.resolved', 'Your dispute has been resolved', $validated['resolution']);

        $this->audit('dispute.resolve', Dispute::class, $dispute->id, [
            'booking_id' => $dispute->booking_id,
            'from' => $previous,
            'to' => $validated['status'],
            'resolution' => $validated['resolution'],
        ]);

        return back()->with('success', 'The dispute has been marked as '.$validated['status'].'.');
    }

    /**
     * Let the party that raised the dispute know about the outcome.
     */
    private function notifyParty(Dispute $dispute, string $type, string $title, string $message): void
    {
        $user = $dispute->raised_by ? User::find($dispute->raised_by) : null;

        if (! $user) {
            return;
        }

        $this->notifications->notify(
            $user,
            $type,
            $title,
            $message,
            ['dispute_id' => $dispute->id, 'booking_id' => $dispute->booking_id]
        );
    }
}
