<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherTimeSlot extends Model
{
    protected $fillable = [
        'teacher_profile_id',
        'slot_date',
        'start_time',
        'end_time',
        'status',
        'mode',
        'buffer_minutes',
        'note',
        'is_active',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'buffer_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_HOLIDAY = 'holiday';

    public const STATUS_CUSTOM = 'custom';

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }
}
