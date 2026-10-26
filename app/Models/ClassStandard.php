<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassStandard extends Model
{
    protected $fillable = ['name', 'code', 'level_order', 'stage', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function teacherClassStandards(): HasMany
    {
        return $this->hasMany(TeacherClassStandard::class);
    }
}
