<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PENDING_TEACHER = 'pending_teacher';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_DISPUTED = 'disputed';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';

    protected $fillable = [
        'reference_no',
        'student_profile_id',
        'parent_profile_id',
        'teacher_profile_id',
        'board_id',
        'class_standard_id',
        'subject_id',
        'teacher_location_id',
        'mode',
        'start_at',
        'end_at',
        'duration_minutes',
        'hourly_rate',
        'subtotal',
        'travel_charge',
        'discount_amount',
        'discount_code',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'pricing_snapshot',
        'notes',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
        'rescheduled_from_id',
        'confirmed_at',
        'completed_at',
        'attendance_status',
        'completion_confirmed_by',
        'reminder_sent_at',
        'is_recurring',
        'recurrence_count',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'duration_minutes' => 'integer',
        'hourly_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'travel_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentProfile::class, 'parent_profile_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function classStandard(): BelongsTo
    {
        return $this->belongsTo(ClassStandard::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(TeacherLocation::class, 'teacher_location_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(AttendanceRecord::class);
    }

    public function onlineSession(): HasOne
    {
        return $this->hasOne(OnlineClassSession::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(TeacherEarning::class);
    }

    public function commissions(): HasOne
    {
        return $this->hasOne(PlatformCommission::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(SlotReservation::class);
    }

    public function isUpcoming(): bool
    {
        return $this->start_at !== null && $this->start_at->isFuture();
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PENDING_TEACHER], true);
    }
}
