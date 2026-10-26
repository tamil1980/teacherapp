<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherPayoutItem extends Model
{
    protected $fillable = ['teacher_payout_id', 'teacher_earning_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function payout(): BelongsTo
    {
        return $this->belongsTo(TeacherPayout::class);
    }

    public function earning(): BelongsTo
    {
        return $this->belongsTo(TeacherEarning::class, 'teacher_earning_id');
    }
}
