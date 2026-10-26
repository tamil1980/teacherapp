<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function view(User $user, Review $review): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher') {
            return $user->teacherProfile?->id === $review->teacher_profile_id;
        }

        return $user->parentProfile?->id === $review->parent_profile_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'parent' && $user->parentProfile !== null;
    }

    public function update(User $user, Review $review): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher') {
            return $user->teacherProfile?->id === $review->teacher_profile_id;
        }

        return $user->parentProfile?->id === $review->parent_profile_id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->role === 'admin';
    }
}
