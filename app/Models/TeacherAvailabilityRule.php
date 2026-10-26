<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAvailabilityRule extends Model
{
    protected $fillable = [
        'teacher_profile_id',
        'weekday',
        'start_time',
        'end_time',
        'mode',
        'session_minutes',
        'buffer_minutes',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'session_minutes' => 'integer',
        'buffer_minutes' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public const WEEKDAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function weekdayName(): string
    {
        return self::WEEKDAYS[$this->weekday] ?? '—';
    }
}
