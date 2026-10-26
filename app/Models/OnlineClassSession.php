<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineClassSession extends Model
{
    protected $fillable = [
        'booking_id',
        'provider',
        'meeting_id',
        'join_url',
        'host_url',
        'passcode',
        'scheduled_start',
        'scheduled_end',
        'started_at',
        'ended_at',
        'status',
        'recording_url',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
