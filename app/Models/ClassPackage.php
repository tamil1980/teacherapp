<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassPackage extends Model
{
    protected $fillable = [
        'teacher_profile_id',
        'name',
        'subject_id',
        'class_standard_id',
        'board_id',
        'mode',
        'total_sessions',
        'session_minutes',
        'price_per_session',
        'total_price',
        'description',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'total_sessions' => 'integer',
        'session_minutes' => 'integer',
        'price_per_session' => 'decimal:2',
        'total_price' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }
}
