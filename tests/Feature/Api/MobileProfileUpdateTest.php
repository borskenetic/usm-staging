<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Student;
use App\Models\StudentEditRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_profile_includes_extended_student_fields(): void
    {
        $student = $this->student();

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/profile')
            ->assertOk()
            ->assertJsonPath('data.student.birthday', '2002-11-08')
            ->assertJsonPath('data.student.emergency_person', 'Jane Reyes')
            ->assertJsonPath('data.student.emergency_number', '09171234567');
    }

    public function test_student_can_submit_profile_edit_request(): void
    {
        $student = $this->student();

        Sanctum::actingAs($student, ['full-access']);

        $response = $this->postJson('/api/mobile/profile/update', [
            'last_name' => 'Reyes',
            'first_name' => 'Marco',
            'middle_initial' => 'A',
            'birthday' => '2002-11-08',
            'course' => 'BSIT',
            'year' => '3',
            'mobile_number' => '09179990001',
            'address' => 'Updated address',
            'emergency_person' => 'Jane Reyes',
            'emergency_relationship' => 'Mother',
            'emergency_number' => '09171234567',
            'emergency_address' => 'Emergency address',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Edit request submitted for approval.')
            ->assertJsonPath('data.student.firstname', 'Mark');

        $this->assertDatabaseHas('library_student_edit_requests', [
            'student_id' => $student->id,
            'firstname' => 'Marco',
            'lastname' => 'Reyes',
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_pending_profile_edit_request_is_rejected(): void
    {
        $student = $this->student();

        StudentEditRequest::query()->create([
            'student_id' => $student->id,
            'lastname' => $student->lastname,
            'firstname' => $student->firstname,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson('/api/mobile/profile/update', [
            'last_name' => 'Reyes',
            'first_name' => 'Marco',
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'You already have a pending edit request.');
    }

    public function test_student_can_upload_profile_picture_edit_request(): void
    {
        $student = $this->student();

        Sanctum::actingAs($student, ['full-access']);

        $response = $this->post('/api/mobile/profile/update-picture', [
            'profile_picture' => UploadedFile::fake()->image('profile.jpg'),
            'change_reason' => 'New school ID photo',
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Edit request submitted for approval.');

        $this->assertDatabaseHas('library_student_edit_requests', [
            'student_id' => $student->id,
            'status' => 'pending',
            'admin_note' => 'New school ID photo',
        ]);
    }

    private function student(): Student
    {
        return Student::query()->create([
            'id_number' => '24-10099',
            'lastname' => 'Reyes',
            'firstname' => 'Mark',
            'qrcode' => 'S-00000099',
            'course' => 'BSIT',
            'birthday' => '2002-11-08',
            'mobile_number' => '09179990000',
            'address' => 'Campus address',
            'emergency_person' => 'Jane Reyes',
            'emergency_relationship' => 'Mother',
            'emergency_number' => '09171234567',
            'emergency_address' => 'Home address',
            'password_setup_completed' => true,
        ]);
    }
}
