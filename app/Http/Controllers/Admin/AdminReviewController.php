<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Review moderation.
 */
class AdminReviewController extends Controller
{
    use AuditsActions;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_FLAGGED = 'flagged';

    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => (string) $request->query('status', ''),
            'rating' => (string) $request->query('rating', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $reviews = Review::query()
            ->with(['teacher.user', 'parent.user', 'student', 'booking'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['rating'] !== '', fn ($query) => $query->where('rating', (int) $filters['rating']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('title', 'like', '%'.$filters['q'].'%')
                        ->orWhere('comment', 'like', '%'.$filters['q'].'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'filters' => $filters,
            'statusCounts' => Review::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
        ]);
    }

    /**
     * Publish, hide or flag a review.
     */
    public function moderate(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['publish', 'hide', 'flag'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $status = match ($validated['action']) {
            'publish' => self::STATUS_PUBLISHED,
            'hide' => self::STATUS_HIDDEN,
            'flag' => self::STATUS_FLAGGED,
        };

        $previous = $review->status;

        $review->update(['status' => $status]);

        if ($review->teacher?->user) {
            $this->notifications->notify(
                $review->teacher->user,
                'review.moderated',
                'A review about you was '.$status,
                $validated['note'] ?? 'The moderation state of a review about your classes has changed.',
                ['review_id' => $review->id]
            );
        }

        $this->audit('review.moderate', Review::class, $review->id, [
            'teacher_profile_id' => $review->teacher_profile_id,
            'booking_id' => $review->booking_id,
            'rating' => (int) $review->rating,
            'from' => $previous,
            'to' => $status,
            'note' => $validated['note'] ?? null,
        ]);

        return back()->with('success', 'The review is now '.$status.'.');
    }
}
