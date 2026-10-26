<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'reference_no',
        'payment_id',
        'booking_id',
        'amount',
        'platform_fee_retained',
        'teacher_share_refunded',
        'reason',
        'notes',
        'status',
        'gateway_refund_id',
        'requested_by',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee_retained' => 'decimal:2',
        'teacher_share_refunded' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
