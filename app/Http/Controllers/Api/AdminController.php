<?php

namespace App\Http\Controllers\Api;

use App\Models\AdminAuditLog;
use App\Models\Board;
use App\Models\Booking;
use App\Models\ClassStandard;
use App\Models\Dispute;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\PromotionalOffer;
use App\Models\Refund;
use App\Models\Review;
use App\Models\Setting;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\BookingService;
use App\Services\EarningService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends ApiController
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly PaymentService $payments,
        private readonly EarningService $earnings,
    ) {}

    public function dashboard(): JsonResponse
    {
        $now = now();

        return $this->ok([
            'stats' => [
                'teachers' => TeacherProfile::count(),
                'pending_teacher_verifications' => TeacherProfile::where('verification_status', 'pending')->count(),
                'parents' => ParentProfile::count(),
                'students' => StudentProfile::count(),
                'bookings_total' => Booking::count(),
                'bookings_today' => Booking::whereBetween('start_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])->count(),
                'pending_requests' => Booking::where('status', Booking::STATUS_PENDING_TEACHER)->count(),
                'revenue_total' => (float) Payment::where('status', Payment::STATUS_PAID)->sum('amount'),
                'revenue_month' => (float) Payment::where('status', Payment::STATUS_PAID)
                    ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->sum('amount'),
                'refunds_total' => (float) Refund::sum('amount'),
                'payouts_pending' => TeacherPayout::whereIn('status', [TeacherPayout::STATUS_REQUESTED, TeacherPayout::STATUS_PROCESSING])->count(),
                'available_earnings' => (float) TeacherEarning::where('status', TeacherEarning::STATUS_AVAILABLE)->sum('net_amount'),
                'open_disputes' => Dispute::whereIn('status', ['open', 'under_review'])->count(),
                'pending_reviews' => Review::where('status', 'pending')->count(),
                'users' => User::count(),
            ],
            'recent_bookings' => Booking::with(['teacher.user', 'parent.user', 'subject'])
                ->orderByDesc('created_at')->limit(10)->get(),
            'pending_teachers' => TeacherProfile::with('user:id,name,email,mobile')
                ->where('verification_status', 'pending')->latest()->limit(10)->get(),
        ]);
    }

    // --------------------------------------------------------------- teachers

    public function teachers(Request $request): JsonResponse
    {
        $query = TeacherProfile::with('user:id,name,email,mobile,status,avatar_path');

        if ($status = $request->query('verification_status')) {
            $query->where('verification_status', $status);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('headline', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
            });
        }

        $paginator = $query->latest()->paginate($this->perPage());

        return $this->paginated($paginator);
    }

    public function teacher(TeacherProfile $teacher): JsonResponse
    {
        $teacher->load([
            'user:id,name,email,mobile,status,avatar_path,created_at',
            'teacherSubjects.subject', 'teacherBoards.board', 'teacherClassStandards.classStandard',
            'teachingModes', 'locations', 'fees', 'qualifications', 'documents',
            'bookings' => fn ($q) => $q->latest()->limit(20),
            'reviews' => fn ($q) => $q->latest()->limit(20),
        ]);

        return $this->ok(['teacher' => $teacher]);
    }

    public function verifyTeacher(Request $request, TeacherProfile $teacher): JsonResponse
    {
        $teacher->update([
            'verification_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        $this->audit('teacher.verify', TeacherProfile::class, $teacher->id);

        return $this->ok(['teacher' => $teacher->fresh()], 'Teacher approved.');
    }

    public function rejectTeacher(Request $request, TeacherProfile $teacher): JsonResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $teacher->update([
            'verification_status' => 'rejected',
            'rejection_reason' => $validated['reason'],
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        $this->audit('teacher.reject', TeacherProfile::class, $teacher->id, ['reason' => $validated['reason']]);

        return $this->ok(['teacher' => $teacher->fresh()], 'Teacher application rejected.');
    }

    public function suspendTeacher(Request $request, TeacherProfile $teacher): JsonResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $teacher->user?->update(['status' => 'suspended']);
        $this->audit('teacher.suspend', TeacherProfile::class, $teacher->id, ['reason' => $validated['reason'] ?? null]);

        return $this->ok(null, 'Teacher account suspended.');
    }

    public function reactivateTeacher(TeacherProfile $teacher): JsonResponse
    {
        $teacher->user?->update(['status' => 'active']);
        $this->audit('teacher.reactivate', TeacherProfile::class, $teacher->id);

        return $this->ok(null, 'Teacher account reactivated.');
    }

    // ---------------------------------------------------------------- parents

    public function parents(Request $request): JsonResponse
    {
        $query = ParentProfile::with(['user:id,name,email,mobile,status,avatar_path'])->withCount('students');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('mobile', 'like', '%'.$search.'%'));
        }

        return $this->paginated($query->latest()->paginate($this->perPage()));
    }

    public function parent(ParentProfile $parent): JsonResponse
    {
        $parent->load([
            'user:id,name,email,mobile,status,avatar_path,created_at',
            'students.classStandard', 'students.board',
            'bookings' => fn ($q) => $q->latest()->limit(20),
        ]);

        return $this->ok(['parent' => $parent]);
    }

    public function suspendParent(Request $request, ParentProfile $parent): JsonResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $parent->user?->update(['status' => 'suspended']);
        $this->audit('parent.suspend', ParentProfile::class, $parent->id, ['reason' => $validated['reason'] ?? null]);

        return $this->ok(null, 'Parent account suspended.');
    }

    public function students(Request $request): JsonResponse
    {
        $query = StudentProfile::with(['parent.user:id,name,email,mobile', 'classStandard', 'board']);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('display_name', 'like', '%'.$search.'%');
        }

        return $this->paginated($query->latest()->paginate($this->perPage()));
    }

    // ---------------------------------------------------------------- catalog

    public function catalog(): JsonResponse
    {
        return $this->ok([
            'boards' => Board::orderBy('sort_order')->get(),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'classes' => ClassStandard::orderBy('sort_order')->get(),
        ]);
    }

    public function storeBoard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:boards,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        $board = Board::create($validated);
        $this->audit('catalog.board.create', Board::class, $board->id);

        return $this->ok(['board' => $board], 'Board created.', 201);
    }

    public function updateBoard(Request $request, Board $board): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'code' => ['sometimes', 'string', 'max:40', Rule::unique('boards', 'code')->ignore($board->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }

        $board->update($validated);
        $this->audit('catalog.board.update', Board::class, $board->id);

        return $this->ok(['board' => $board->fresh()], 'Board updated.');
    }

    public function destroyBoard(Board $board): JsonResponse
    {
        $board->delete();
        $this->audit('catalog.board.delete', Board::class, $board->id);

        return $this->ok(null, 'Board deleted.');
    }

    public function storeSubject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:subjects,code'],
            'icon' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        $subject = Subject::create($validated);
        $this->audit('catalog.subject.create', Subject::class, $subject->id);

        return $this->ok(['subject' => $subject], 'Subject created.', 201);
    }

    public function updateSubject(Request $request, Subject $subject): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'code' => ['sometimes', 'string', 'max:40', Rule::unique('subjects', 'code')->ignore($subject->id)],
            'icon' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }

        $subject->update($validated);
        $this->audit('catalog.subject.update', Subject::class, $subject->id);

        return $this->ok(['subject' => $subject->fresh()], 'Subject updated.');
    }

    public function destroySubject(Subject $subject): JsonResponse
    {
        $subject->delete();
        $this->audit('catalog.subject.delete', Subject::class, $subject->id);

        return $this->ok(null, 'Subject deleted.');
    }

    public function storeClass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:class_standards,code'],
            'level_order' => ['nullable', 'integer', 'min:0'],
            'stage' => ['nullable', 'string', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $class = ClassStandard::create($validated);
        $this->audit('catalog.class.create', ClassStandard::class, $class->id);

        return $this->ok(['class' => $class], 'Class created.', 201);
    }

    public function updateClass(Request $request, ClassStandard $classStandard): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'code' => ['sometimes', 'string', 'max:40', Rule::unique('class_standards', 'code')->ignore($classStandard->id)],
            'level_order' => ['nullable', 'integer', 'min:0'],
            'stage' => ['nullable', 'string', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $classStandard->update($validated);
        $this->audit('catalog.class.update', ClassStandard::class, $classStandard->id);

        return $this->ok(['class' => $classStandard->fresh()], 'Class updated.');
    }

    public function destroyClass(ClassStandard $classStandard): JsonResponse
    {
        $classStandard->delete();
        $this->audit('catalog.class.delete', ClassStandard::class, $classStandard->id);

        return $this->ok(null, 'Class deleted.');
    }

    // --------------------------------------------------------------- bookings

    public function bookings(Request $request): JsonResponse
    {
        $query = Booking::with(['teacher.user', 'parent.user', 'student', 'subject']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('reference_no', 'like', '%'.$search.'%');
        }

        return $this->paginated($query->latest()->paginate($this->perPage()));
    }

    public function booking(Booking $booking): JsonResponse
    {
        $booking->load([
            'teacher.user', 'parent.user', 'student', 'subject', 'classStandard', 'board',
            'location', 'payments', 'attendance', 'statusHistories', 'review', 'earnings', 'onlineSession',
        ]);

        return $this->ok(['booking' => $booking]);
    }

    public function updateBookingStatus(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING,
                Booking::STATUS_COMPLETED, Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED, Booking::STATUS_DISPUTED,
            ])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['status'] === Booking::STATUS_CANCELLED) {
            $this->bookings->cancel($booking, $validated['reason'] ?? 'Cancelled by admin', 'admin');
        } elseif ($validated['status'] === Booking::STATUS_COMPLETED) {
            $this->bookings->complete($booking, 'admin');
        } else {
            $previous = $booking->status;
            $booking->update(['status' => $validated['status']]);
            $this->bookings->history($booking, $previous, $validated['status'], $validated['reason'] ?? 'Status changed by admin');
        }

        $this->audit('booking.status', Booking::class, $booking->id, $validated);

        return $this->ok(['booking' => $booking->fresh()], 'Booking status updated.');
    }

    // --------------------------------------------------------------- payments

    public function payments(Request $request): JsonResponse
    {
        $query = Payment::with(['booking.teacher.user', 'booking.parent.user', 'booking.subject']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->paginated($query->latest()->paginate($this->perPage()));
    }

    public function payment(Payment $payment): JsonResponse
    {
        $payment->load(['booking.teacher.user', 'booking.parent.user', 'booking.student', 'refunds', 'commission']);

        return $this->ok(['payment' => $payment]);
    }

    public function refundPayment(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$payment->refundableAmount()],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $refund = $this->payments->refund(
            $payment,
            (float) $validated['amount'],
            $validated['reason'] ?? 'Refund approved by admin',
            $request->user()->id,
            $request->user()->id,
            $validated['notes'] ?? null
        );

        $this->audit('payment.refund', Payment::class, $payment->id, $validated);

        return $this->ok(['refund' => $refund, 'payment' => $payment->fresh()], 'Refund processed.');
    }

    public function refunds(): JsonResponse
    {
        return $this->paginated(Refund::with(['payment', 'booking'])->latest()->paginate($this->perPage()));
    }

    // ---------------------------------------------------------------- payouts

    public function payouts(Request $request): JsonResponse
    {
        $query = TeacherPayout::with('teacher.user:id,name,email');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->paginated($query->latest()->paginate($this->perPage()));
    }

    public function payout(TeacherPayout $payout): JsonResponse
    {
        $payout->load(['teacher.user', 'items']);

        return $this->ok(['payout' => $payout]);
    }

    public function storePayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teacher_profiles,id'],
            'amount' => ['nullable', 'numeric', 'min:1'],
            'method' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacher = TeacherProfile::findOrFail($validated['teacher_id']);

        try {
            $payout = $this->earnings->createPayout(
                $teacher,
                isset($validated['amount']) ? (float) $validated['amount'] : null,
                $validated['method'] ?? 'bank_transfer',
                $validated['notes'] ?? null,
                $request->user()->id
            );
        } catch (\RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $this->audit('payout.create', TeacherPayout::class, $payout->id, ['amount' => (float) $payout->amount]);

        return $this->ok(['payout' => $payout], 'Payout recorded.', 201);
    }

    public function completePayout(Request $request, TeacherPayout $payout): JsonResponse
    {
        $validated = $request->validate([
            'gateway_payout_id' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($payout->status === TeacherPayout::STATUS_COMPLETED) {
            return $this->error('This payout has already been marked as completed.', 422);
        }

        $payout->update([
            'status' => TeacherPayout::STATUS_COMPLETED,
            'gateway_payout_id' => $validated['gateway_payout_id'],
            'notes' => $validated['notes'] ?? $payout->notes,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        $this->audit('payout.complete', TeacherPayout::class, $payout->id, $validated);

        return $this->ok(['payout' => $payout->fresh()], 'Payout marked as completed.');
    }

    // ---------------------------------------------------------------- reviews

    public function reviews(Request $request): JsonResponse
    {
        $query = Review::with(['teacher.user:id,name', 'parent.user:id,name', 'student', 'booking.subject']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->paginated($query->latest()->paginate($this->perPage()));
    }

    public function moderateReview(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['published', 'hidden', 'rejected'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $review->update(['status' => $validated['status']]);
        $this->recalculateRating($review->teacher_profile_id);
        $this->audit('review.moderate', Review::class, $review->id, $validated);

        return $this->ok(['review' => $review->fresh()], 'Review moderated.');
    }

    // --------------------------------------------------------------- disputes

    public function disputes(): JsonResponse
    {
        return $this->paginated(Dispute::with(['booking.teacher.user:id,name', 'booking.parent.user:id,name'])->latest()->paginate($this->perPage()));
    }

    public function resolveDispute(Request $request, Dispute $dispute): JsonResponse
    {
        $validated = $request->validate([
            'resolution' => ['required', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['resolved', 'rejected', 'under_review'])],
        ]);

        $dispute->update([
            'resolution' => $validated['resolution'],
            'status' => $validated['status'] ?? 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $this->audit('dispute.resolve', Dispute::class, $dispute->id, $validated);

        return $this->ok(['dispute' => $dispute->fresh()], 'Dispute updated.');
    }

    // ----------------------------------------------------------------- offers

    public function offers(): JsonResponse
    {
        return $this->ok(['offers' => PromotionalOffer::latest()->get()]);
    }

    public function storeOffer(Request $request): JsonResponse
    {
        $offer = PromotionalOffer::create($this->offerAttributes($request, null));

        return $this->ok(['offer' => $offer], 'Offer created.', 201);
    }

    public function updateOffer(Request $request, PromotionalOffer $offer): JsonResponse
    {
        $offer->update($this->offerAttributes($request, $offer));

        return $this->ok(['offer' => $offer->fresh()], 'Offer updated.');
    }

    public function destroyOffer(PromotionalOffer $offer): JsonResponse
    {
        $offer->delete();

        return $this->ok(null, 'Offer removed.');
    }

    // --------------------------------------------------------------- settings

    public function settings(): JsonResponse
    {
        return $this->ok([
            'settings' => Setting::orderBy('group')->orderBy('key')->get(),
            'public' => Setting::where('is_public', true)->get(),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::put((string) $key, $value, 'general', false);
        }

        $this->audit('settings.update', Setting::class, null, ['keys' => array_keys($validated['settings'])]);

        return $this->ok(['settings' => Setting::orderBy('group')->get()], 'Settings updated.');
    }

    public function auditLogs(): JsonResponse
    {
        return $this->paginated(AdminAuditLog::with('admin:id,name')->latest()->paginate($this->perPage(50)));
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @return array<string, mixed>
     */
    protected function offerAttributes(Request $request, ?PromotionalOffer $offer): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('promotional_offers', 'code')->ignore($offer?->id)],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'applies_to_first_booking' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);

        return $validated;
    }

    protected function recalculateRating(int $teacherProfileId): void
    {
        $stats = Review::where('teacher_profile_id', $teacherProfileId)
            ->where('status', 'published')
            ->selectRaw('AVG(rating) as average, COUNT(*) as total')
            ->first();

        TeacherProfile::where('id', $teacherProfileId)->update([
            'rating_average' => round((float) ($stats->average ?? 0), 2),
            'rating_count' => (int) ($stats->total ?? 0),
        ]);
    }
}
