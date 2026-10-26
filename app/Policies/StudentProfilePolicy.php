<?php

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\User;

class StudentProfilePolicy
{
    public function view(User $user, StudentProfile $student): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'parent' && $user->parentProfile?->id === $student->parent_profile_id;
    }

    public function update(User $user, StudentProfile $student): bool
    {
        return $this->view($user, $student);
    }

    public function delete(User $user, StudentProfile $student): bool
    {
        return $this->view($user, $student);
    }
}
