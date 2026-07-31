<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Faculty;
use App\Models\PendingFaculty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileFacultyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_faculty_can_register_and_wait_for_approval(): void
    {
        $this->postJson('/api/mobile/register/faculty', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.employee_id', 'EMP-90001');

        $this->assertDatabaseHas('library_pending_faculty', [
            'employee_id' => 'EMP-90001',
            'firstname' => 'Juan',
            'department' => 'College of IT',
        ]);

        $this->postJson('/api/mobile/login', [
            'login_id' => 'EMP-90001',
            'password' => '19900115',
        ])->assertStatus(422);
    }

    public function test_duplicate_employee_id_is_rejected(): void
    {
        PendingFaculty::query()->create([
            'employee_id' => 'EMP-90001',
            'firstname' => 'Existing',
            'lastname' => 'Pending',
            'department' => 'College of IT',
            'designation' => 'Instructor',
            'birthday' => '1990-01-15',
            'mobile_number' => '09170000000',
        ]);

        $this->postJson('/api/mobile/register/faculty', $this->payload())
            ->assertStatus(422);

        Faculty::query()->create([
            'employee_id' => 'EMP-90002',
            'firstname' => 'Active',
            'lastname' => 'Faculty',
            'account_status' => 'Active',
        ]);

        $this->postJson('/api/mobile/register/faculty', $this->payload([
            'employee_id' => 'EMP-90002',
        ]))->assertStatus(422);
    }

    public function test_approved_faculty_can_login_with_birthday_password(): void
    {
        $this->postJson('/api/mobile/register/faculty', $this->payload())
            ->assertCreated();

        $pending = PendingFaculty::query()->where('employee_id', 'EMP-90001')->firstOrFail();

        $faculty = Faculty::query()->create([
            'employee_id' => $pending->employee_id,
            'lastname' => $pending->lastname,
            'firstname' => $pending->firstname,
            'middle_initial' => $pending->middle_initial,
            'department' => $pending->department,
            'designation' => $pending->designation,
            'birthday' => $pending->birthday,
            'mobile_number' => $pending->mobile_number,
            'account_status' => 'Active',
            'password' => null,
            'password_setup_completed' => false,
            'force_password_reset' => true,
        ]);
        $pending->delete();

        $this->postJson('/api/mobile/login', [
            'login_id' => 'EMP-90001',
            'password' => '19900115',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'faculty')
            ->assertJsonPath('data.faculty.employee_id', 'EMP-90001')
            ->assertJsonPath('must_change_password', true);

        $this->assertNotNull($faculty->fresh()->password);
    }

    public function test_rejected_faculty_cannot_login(): void
    {
        $this->postJson('/api/mobile/register/faculty', $this->payload())
            ->assertCreated();

        PendingFaculty::query()->where('employee_id', 'EMP-90001')->delete();

        $this->postJson('/api/mobile/login', [
            'login_id' => 'EMP-90001',
            'password' => '19900115',
        ])->assertStatus(422);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'firstname' => 'Juan',
            'lastname' => 'Dela Cruz',
            'middle_initial' => 'P',
            'employee_id' => 'EMP-90001',
            'designation' => 'Instructor I',
            'department' => 'College of IT',
            'birthday' => '1990-01-15',
            'mobile_number' => '09171234567',
            'address' => 'Kabacan, Cotabato',
        ], $overrides);
    }
}
