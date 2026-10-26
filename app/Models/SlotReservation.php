<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlotReservation extends Model
{
    protected $fillable = [
        'teacher_profile_id',
        'slot_start_at',
        'slot_end_at',
        'reservation_type',
        'slot_hold_id',
        'booking_id',
        'expires_at',
    ];

    protected $casts = [
        'slot_start_at' => 'datetime',
        'slot_end_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const TYPE_HOLD = 'hold';

    public const TYPE_BOOKING = 'booking';

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function hold(): BelongsTo
    {
        return $this->belongsTo(SlotHold::class, 'slot_hold_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
