<?php

namespace App\Policies;

use App\Models\TeacherProfile;
use App\Models\User;

class TeacherProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TeacherProfile $teacher): bool
    {
        return true;
    }

    public function update(User $user, TeacherProfile $teacher): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'teacher' && $user->teacherProfile?->id === $teacher->id;
    }

    public function verify(User $user): bool
    {
        return $user->role === 'admin';
    }
}
