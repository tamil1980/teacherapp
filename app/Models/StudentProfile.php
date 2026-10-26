<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_profile_id',
        'display_name',
        'class_standard_id',
        'board_id',
        'gender',
        'date_of_birth',
        'school_name',
        'learning_requirements',
        'medical_notes',
        'subject_interests',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'subject_interests' => 'array',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentProfile::class, 'parent_profile_id');
    }

    public function classStandard(): BelongsTo
    {
        return $this->belongsTo(ClassStandard::class);
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
