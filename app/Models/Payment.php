<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    public const STATUS_CREATED = 'created';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    protected $fillable = [
        'reference_no',
        'booking_id',
        'parent_profile_id',
        'user_id',
        'gateway',
        'order_id',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'method',
        'amount',
        'gateway_fee',
        'tax_amount',
        'refunded_amount',
        'currency',
        'status',
        'failure_reason',
        'paid_at',
        'invoice_number',
        'ip_address',
        'user_agent',
        'gateway_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentProfile::class, 'parent_profile_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(PlatformCommission::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_PARTIALLY_REFUNDED, self::STATUS_REFUNDED], true);
    }

    public function refundableAmount(): float
    {
        return (float) $this->amount - (float) $this->refunded_amount;
    }
}
