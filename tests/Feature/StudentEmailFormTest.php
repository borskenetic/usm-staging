<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentEmailFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_save_and_clear_a_student_email(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $student = Student::query()->create([
            'id_number' => '111',
            'lastname' => 'Phoenix',
            'firstname' => 'Marco',
            'qrcode' => 'S-00000010',
            'password_setup_completed' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('students.edit', $student))
            ->assertOk()
            ->assertSee('Email address')
            ->assertSee('patron-form-page', false);

        $this->actingAs($staff)
            ->get(route('students.create'))
            ->assertOk()
            ->assertSee('Email address')
            ->assertSee('Register a student');

        $this->actingAs($staff)
            ->put(route('students.update', $student), [
                'firstname' => 'Marco',
                'lastname' => 'Phoenix',
                'email' => 'Marco@Example.com',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertSame('marco@example.com', $student->fresh()->email);

        $this->actingAs($staff)
            ->put(route('students.update', $student), [
                'firstname' => 'Marco',
                'lastname' => 'Phoenix',
                'email' => '   ',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertNull($student->fresh()->email);
    }
}
