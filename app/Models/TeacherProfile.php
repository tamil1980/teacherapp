<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TeacherProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'slug',
        'headline',
        'bio',
        'teaching_description',
        'methodology',
        'experience_years',
        'photo_path',
        'verification_status',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'languages',
        'qualification_summary',
        'address_line1',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'service_radius_km',
        'default_session_minutes',
        'buffer_minutes',
        'completed_sessions_count',
        'rating_average',
        'rating_count',
        'accepts_online',
        'accepts_offline',
        'cancellation_policy',
        'meeting_provider',
    ];

    protected $casts = [
        'languages' => 'array',
        'verified_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'service_radius_km' => 'decimal:2',
        'rating_average' => 'decimal:2',
        'accepts_online' => 'boolean',
        'accepts_offline' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (is_null($model->slug)) {
                $model->slug = $model->generateSlug();
            }
        });

        static::updating(function ($model) {
            if (is_null($model->slug)) {
                $model->slug = $model->generateSlug();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(TeacherQualification::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TeacherDocument::class);
    }

    public function teacherSubjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function teacherBoards(): HasMany
    {
        return $this->hasMany(TeacherBoard::class);
    }

    public function teacherClassStandards(): HasMany
    {
        return $this->hasMany(TeacherClassStandard::class);
    }

    public function teachingModes(): HasMany
    {
        return $this->hasMany(TeacherTeachingMode::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(TeacherLocation::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(TeacherFee::class);
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(TeacherAvailabilityRule::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TeacherTimeSlot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(TeacherEarning::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(TeacherPayout::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isActiveAndApproved(): bool
    {
        return $this->isApproved() && $this->user && $this->user->status === 'active';
    }

    /**
     * Route model binding resolves teachers by their primary key.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Generate a unique slug for this teacher.
     */
    public function generateSlug(): string
    {
        $base = Str::slug($this->user?->name ?? 'teacher');
        $slug = $base;
        $suffix = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
