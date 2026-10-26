<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCommission extends Model
{
    protected $fillable = [
        'booking_id',
        'payment_id',
        'gross_amount',
        'commission_percent',
        'commission_amount',
        'fixed_fee',
        'teacher_amount',
        'platform_amount',
        'currency',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_percent' => 'decimal:3',
        'commission_amount' => 'decimal:2',
        'fixed_fee' => 'decimal:2',
        'teacher_amount' => 'decimal:2',
        'platform_amount' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
