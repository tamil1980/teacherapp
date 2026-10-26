<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherBoard extends Model
{
    protected $fillable = ['teacher_profile_id', 'board_id'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
