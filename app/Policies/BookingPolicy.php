<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher') {
            return $user->teacherProfile?->id === $booking->teacher_profile_id;
        }

        if ($user->role === 'parent') {
            return $user->parentProfile?->id === $booking->parent_profile_id;
        }

        return false;
    }

    public function update(User $user, Booking $booking): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'teacher'
            && $user->teacherProfile?->id === $booking->teacher_profile_id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher') {
            return $user->teacherProfile?->id === $booking->teacher_profile_id;
        }

        if ($user->role === 'parent') {
            return $user->parentProfile?->id === $booking->parent_profile_id;
        }

        return false;
    }

    public function complete(User $user, Booking $booking): bool
    {
        return $this->update($user, $booking) || $this->cancel($user, $booking);
    }

    public function viewMeetingLink(User $user, Booking $booking): bool
    {
        if ($booking->payment_status !== \App\Models\Booking::PAYMENT_PAID) {
            return false;
        }

        return $this->view($user, $booking);
    }
}
