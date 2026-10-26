<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherFee extends Model
{
    protected $fillable = [
        'teacher_profile_id',
        'subject_id',
        'class_standard_id',
        'board_id',
        'mode',
        'hourly_rate',
        'session_minutes',
        'travel_charge',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'travel_charge' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classStandard(): BelongsTo
    {
        return $this->belongsTo(ClassStandard::class);
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * How specific this fee row is. Higher = more specific = wins when
     * resolving the price for a booking.
     */
    public function specificity(): int
    {
        $score = 1;

        if ($this->subject_id) {
            $score += 4;
        }
        if ($this->class_standard_id) {
            $score += 2;
        }
        if ($this->board_id) {
            $score += 1;
        }

        return $score;
    }
}
