<?php

namespace App\Services;

use App\Models\ParentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Registration helpers shared by the website and the Android app so both
 * produce exactly the same account structure.
 */
class RegistrationService
{
    /**
     * Register a parent/guardian account.
     */
    public function registerParent(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'mobile' => $data['mobile'],
                'password' => Hash::make($data['password']),
                'role' => 'parent',
                'status' => 'active',
            ]);

            ParentProfile::create([
                'user_id' => $user->id,
                'gender' => $data['gender'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'address_line1' => $data['address_line1'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            return $user->fresh();
        });
    }

    /**
     * Register a teacher account. The profile starts in `pending`
     * verification status and is only visible in public search once an
     * administrator approves it.
     */
    public function registerTeacher(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'mobile' => $data['mobile'],
                'password' => Hash::make($data['password']),
                'role' => 'teacher',
                'status' => 'active',
            ]);

            TeacherProfile::create([
                'user_id' => $user->id,
                'headline' => $data['headline'] ?? null,
                'bio' => $data['bio'] ?? null,
                'teaching_description' => $data['teaching_description'] ?? null,
                'methodology' => $data['methodology'] ?? null,
                'experience_years' => $data['experience_years'] ?? 0,
                'languages' => $data['languages'] ?? [],
                'qualification_summary' => $data['qualification_summary'] ?? null,
                'address_line1' => $data['address_line1'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'service_radius_km' => $data['service_radius_km'] ?? 10,
                'verification_status' => 'pending',
            ]);

            return $user->fresh();
        });
    }
}
