<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherPayout extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'teacher_profile_id',
        'reference_no',
        'amount',
        'currency',
        'method',
        'status',
        'gateway_payout_id',
        'bank_account_masked',
        'period_start',
        'period_end',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'processed_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TeacherPayoutItem::class);
    }
}
