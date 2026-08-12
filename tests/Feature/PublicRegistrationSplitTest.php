<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendancePendingStudent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRegistrationSplitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_public_registration_page_opens_modal_with_both_services(): void
    {
        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('data-initial-view="register"', false)
            ->assertSee('Attendance Registration')
            ->assertSee('Library Registration')
            ->assertSee(route('attendance.pending.store', absolute: false))
            ->assertSee(route('attendance.pendingEmployee.store', absolute: false))
            ->assertSee(route('library.pending.store', absolute: false))
            ->assertSee(route('library.pendingEmployee.store', absolute: false));
    }

    public function test_module_register_urls_redirect_to_shared_register_page(): void
    {
        $this->get('/register/attendance')
            ->assertRedirect(route('patron.register', ['service' => 'attendance']));

        $this->get('/register/library')
            ->assertRedirect(route('patron.register', ['service' => 'library']));

        $this->get('/register?service=library')
            ->assertOk()
            ->assertSee('data-initial-service="library"', false)
            ->assertSee('data-initial-view="register"', false);
    }

    public function test_landing_page_contains_login_register_modal_for_both_services(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('data-auth-open="login"', false)
            ->assertSee('Attendance Registration', false)
            ->assertSee('Library Registration', false)
            ->assertSee(route('attendance.pending.store', absolute: false))
            ->assertSee(route('attendance.pendingEmployee.store', absolute: false))
            ->assertSee(route('library.pending.store', absolute: false))
            ->assertSee(route('library.pendingEmployee.store', absolute: false));
    }

    public function test_attendance_registration_creates_attendance_pending_student_only(): void
    {
        $response = $this->post('/register/attendance', [
            'student_id' => 'ATT-PENDING-001',
            'firstname' => 'Alan',
            'lastname' => 'Turing',
            'course' => 'BSCS',
            'year' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_pending_students', [
            'student_id' => 'ATT-PENDING-001',
            'firstname' => 'Alan',
        ]);
        $this->assertDatabaseMissing('library_pending_students', [
            'student_id' => 'ATT-PENDING-001',
        ]);
    }

    public function test_attendance_employee_registration_creates_pending_employee_with_qr_style_code(): void
    {
        $response = $this->post('/register/attendance/employee', [
            'employee_id' => 'ATT-EMP-001',
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'department' => 'Computer Science',
            'position' => 'Faculty',
            'mobile_number' => '09171234567',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_pending_employees', [
            'employee_id' => 'ATT-EMP-001',
            'firstname' => 'Grace',
            'mobile_number' => '09171234567',
            'qrcode' => 'AE-00000001',
        ]);
        $this->assertDatabaseMissing('library_pending_employees', [
            'employee_id' => 'ATT-EMP-001',
        ]);
    }

    public function test_library_registration_creates_library_pending_student_only(): void
    {
        $response = $this->post('/register/library', [
            'id_number' => 'LIB-PENDING-001',
            'firstname' => 'Mary',
            'lastname' => 'Jackson',
            'course' => 'BSIT',
            'year' => '2',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('library_pending_students', [
            'id_number' => 'LIB-PENDING-001',
            'firstname' => 'Mary',
        ]);
        $this->assertDatabaseMissing('attendance_pending_students', [
            'student_id' => 'LIB-PENDING-001',
        ]);
    }

    public function test_attendance_admin_approval_moves_pending_student_and_logs_activity(): void
    {
        $admin = User::factory()->create([
            'role' => 'attendance_admin',
            'is_active' => true,
        ]);
        $admin->assignRole('attendance_admin');

        $pending = AttendancePendingStudent::query()->create([
            'student_id' => 'ATT-APPROVE-001',
            'firstname' => 'Dorothy',
            'lastname' => 'Vaughan',
            'course' => 'BSCS',
        ]);

        $response = $this->actingAs($admin)->post("/attendance/pending/students/{$pending->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_students', [
            'student_id' => 'ATT-APPROVE-001',
            'qrcode' => 'S-00000001',
        ]);
        $this->assertDatabaseMissing('attendance_pending_students', [
            'id' => $pending->id,
        ]);
        $this->assertDatabaseHas('admin_activities', [
            'user_id' => $admin->id,
            'module' => 'attendance',
            'type' => 'patron.approved',
            'title' => 'Attendance student approved',
            'body' => 'ATT-APPROVE-001',
        ]);
    }
}
