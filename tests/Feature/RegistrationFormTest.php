<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegistrationFormTest extends TestCase
{
    public function test_parent_registration_requires_a_matching_password_confirmation(): void
    {
        $response = $this->get('/register/parent');

        $response->assertOk()
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Confirm password')
            ->assertSee('minlength="8"', false)
            ->assertSee('Passwords do not match.');
    }

    public function test_teacher_registration_requires_a_matching_password_confirmation(): void
    {
        $response = $this->get('/register/teacher');

        $response->assertOk()
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Confirm password')
            ->assertSee('minlength="8"', false);
    }
}
