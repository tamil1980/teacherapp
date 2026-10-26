<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    protected $fillable = [
        'raised_by',
        'against_user_id',
        'booking_id',
        'reason',
        'description',
        'status',
        'action_taken',
        'handled_by',
        'handled_at',
    ];

    protected $casts = ['handled_at' => 'datetime'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
