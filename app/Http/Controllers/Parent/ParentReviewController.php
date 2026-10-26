<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentReviewController extends Controller
{
    public function index(): View
    {
        $reviews = Review::where('parent_profile_id', $this->parentProfile()->id)
            ->with(['teacher.user', 'booking'])
            ->latest()
            ->paginate(15);

        return view('parent.reviews.index', compact('reviews'));
    }

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($booking);

        if ($booking->status !== Booking::STATUS_COMPLETED) {
            return back()->with('error', 'You can only review a teacher after the session is completed.');
        }

        if ($booking->review()->exists()) {
            return back()->with('error', 'You have already reviewed this session.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $review = Review::create([
            'booking_id' => $booking->id,
            'parent_profile_id' => $this->parentProfile()->id,
            'teacher_profile_id' => $booking->teacher_profile_id,
            'student_profile_id' => $booking->student_profile_id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'is_anonymous' => (bool) ($validated['is_anonymous'] ?? false),
            'status' => 'published',
        ]);

        $this->recalculateTeacherRating($booking->teacher_profile_id);

        return redirect()->route('parent.bookings.show', $booking)->with('success', 'Thank you for your review.');
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $this->authorizeReview($review);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review->update($validated);
        $this->recalculateTeacherRating($review->teacher_profile_id);

        return back()->with('success', 'Your review has been updated.');
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $this->authorizeReview($review);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ]);

        $review->update([
            'teacher_reply' => $validated['reply'],
            'replied_at' => now(),
        ]);

        return back()->with('success', 'Your reply has been published.');
    }

    private function authorizeBooking(Booking $booking): void
    {
        abort_unless(
            $booking->parent_profile_id === $this->parentProfile()?->id,
            403,
            'You are not allowed to review this booking.'
        );
    }

    private function authorizeReview(Review $review): void
    {
        abort_unless(
            $review->parent_profile_id === $this->parentProfile()?->id,
            403,
            'You are not allowed to modify this review.'
        );
    }

    private function recalculateTeacherRating(int $teacherProfileId): void
    {
        $teacher = \App\Models\TeacherProfile::find($teacherProfileId);

        if (! $teacher) {
            return;
        }

        $stats = Review::where('teacher_profile_id', $teacherProfileId)
            ->where('status', 'published')
            ->selectRaw('AVG(rating) as average, COUNT(*) as total')
            ->first();

        $teacher->update([
            'rating_average' => round((float) ($stats->average ?? 0), 2),
            'rating_count' => (int) ($stats->total ?? 0),
        ]);
    }
}
