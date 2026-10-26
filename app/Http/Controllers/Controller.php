<?php

namespace App\Http\Controllers;

use App\Models\ParentProfile;
use App\Models\TeacherProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * The signed-in parent profile (guaranteed non-null on parent routes).
     */
    protected function parentProfile(): ?ParentProfile
    {
        return request()->user()?->parentProfile;
    }

    /**
     * The signed-in teacher profile (guaranteed non-null on teacher routes).
     */
    protected function teacherProfile(): ?TeacherProfile
    {
        return request()->user()?->teacherProfile;
    }

    /**
     * Format a money amount for display.
     */
    protected function money(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $formatted = number_format((float) $amount, 2);

        return $withSymbol ? '₹'.$formatted : $formatted;
    }
}
