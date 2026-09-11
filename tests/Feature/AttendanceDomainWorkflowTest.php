<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceEmployee;
use App\Models\AttendanceLog;
use App\Models\AttendanceStudent;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDomainWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_school_attendance_scan_uses_attendance_student_records(): void
    {
        $student = AttendanceStudent::query()->create([
            'student_id' => '25-00003',
            'firstname' => 'Dorothy',
            'lastname' => 'Vaughan',
            'qrcode' => 'ATT-QR-0003',
        ]);

        $response = $this->postJson('/attendance', [
            'qrcode' => 'ATT-QR-0003',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('type', 'student')
            ->assertJsonPath('student_id', $student->id)
            ->assertJsonPath('status', 'IN');

        $this->assertDatabaseHas('attendance_logs', [
            'student_id' => $student->id,
            'status' => 'IN',
        ]);

        $this->assertTrue(AttendanceLog::query()->firstOrFail()->student->is($student));
    }

    public function test_school_attendance_scan_accepts_student_id_number(): void
    {
        $student = AttendanceStudent::query()->create([
            'student_id' => '25-00013',
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'qrcode' => 'ATT-QR-0013',
        ]);

        $this->postJson('/attendance', [
            'qrcode' => '25-00013',
        ])
            ->assertOk()
            ->assertJsonPath('type', 'student')
            ->assertJsonPath('student_id', $student->id)
            ->assertJsonPath('status', 'IN');
    }

    public function test_school_attendance_scan_accepts_employee_qr_or_id(): void
    {
        $employee = AttendanceEmployee::query()->create([
            'employee_id' => 'EMP-0007',
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'qrcode' => 'ATT-E-0007',
        ]);

        $this->postJson('/attendance', [
            'qrcode' => 'EMP-0007',
        ])
            ->assertOk()
            ->assertJsonPath('type', 'employee')
            ->assertJsonPath('employee_id', $employee->id)
            ->assertJsonPath('status', 'IN');

        $this->assertDatabaseHas('attendance_logs', [
            'employee_id' => $employee->id,
            'status' => 'IN',
        ]);
    }

    public function test_school_attendance_scan_rejects_library_only_patron(): void
    {
        Student::query()->create([
            'id_number' => '24-19462',
            'firstname' => 'Fatma',
            'lastname' => 'Aba',
            'qrcode' => 'S-LIB-19462',
        ]);

        $this->postJson('/attendance', [
            'qrcode' => '24-19462',
        ])
            ->assertOk()
            ->assertJsonPath('type', 'error')
            ->assertJsonPath('message', 'RFID not recognized.');
    }

    public function test_attendance_admin_can_view_daily_student_absences(): void
    {
        $admin = $this->attendanceAdmin();
        $present = AttendanceStudent::query()->create([
            'student_id' => '25-00004',
            'firstname' => 'Mary',
            'lastname' => 'Jackson',
            'course' => 'BSIT',
            'year' => 'First Year',
        ]);
        $absent = AttendanceStudent::query()->create([
            'student_id' => '25-00005',
            'firstname' => 'Katherine',
            'lastname' => 'Johnson',
            'course' => 'BSIT',
            'year' => 'First Year',
        ]);

        AttendanceLog::query()->create([
            'student_id' => $present->id,
            'status' => 'IN',
            'scanned_at' => '2026-07-09 07:30:00',
        ]);

        $this->actingAs($admin)
            ->get('/attendance-logs/absences?date=2026-07-09')
            ->assertOk()
            ->assertSee('Attendance Absences')
            ->assertSee($absent->lastname)
            ->assertDontSee($present->lastname);
    }

    public function test_attendance_absences_are_admin_only(): void
    {
        $staff = User::factory()->create([
            'role' => 'attendance_staff',
            'is_active' => true,
        ]);
        $staff->assignRole('attendance_staff');

        $this->actingAs($staff)
            ->get('/attendance-logs/absences')
            ->assertForbidden();
    }

    private function attendanceAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'attendance_admin',
            'is_active' => true,
        ]);
        $admin->assignRole('attendance_admin');

        return $admin;
    }
}
