<?php

namespace Tests\Feature;

use App\Models\ParentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingWithoutStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_without_a_child_is_redirected_to_add_one_before_booking(): void
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'status' => 'active',
            'mobile' => '9876543210',
        ]);
        ParentProfile::create(['user_id' => $parent->id]);

        $teacherUser = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
            'mobile' => '9876543211',
        ]);
        $teacher = TeacherProfile::create([
            'user_id' => $teacherUser->id,
            'slug' => 'approved-teacher',
            'verification_status' => 'approved',
        ]);

        $response = $this->actingAs($parent)
            ->get(route('bookings.create', ['teacher' => $teacher, 'mode' => 'online']));

        $response->assertRedirect(route('parent.students.create'))
            ->assertSessionHas('warning', 'Add a child profile before booking your first class.');
    }
}
