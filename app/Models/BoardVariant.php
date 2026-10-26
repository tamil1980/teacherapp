<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardVariant extends Model
{
    protected $fillable = ['board_id', 'name', 'code', 'state', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
