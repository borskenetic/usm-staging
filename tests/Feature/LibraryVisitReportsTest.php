<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LibraryAttendanceLog;
use App\Models\LibraryEmployee;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryVisitReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_library_admin_can_open_student_reports_hub(): void
    {
        $admin = $this->staffUser('library_admin');

        $this->actingAs($admin)
            ->withSession(['active_module' => 'library'])
            ->get('/library/attendance/logs/reports?tab=students')
            ->assertOk()
            ->assertSee('Student visit reports')
            ->assertSee('Open Full Dashboard')
            ->assertSee('Download Combined CSV')
            ->assertSee('Faculty &amp; Staff', false);
    }

    public function test_library_admin_can_open_faculty_dashboard_scoped_to_employees(): void
    {
        $student = Student::query()->create([
            'id_number' => '24-90001',
            'qrcode' => 'S-RPT-001',
            'firstname' => 'Fatma',
            'lastname' => 'Aba',
            'course' => 'BSE',
            'year' => '2nd Year',
        ]);

        $employee = LibraryEmployee::query()->create([
            'employee_id' => '08-00688',
            'firstname' => 'Leonard',
            'lastname' => 'Paleta',
            'program' => 'BSAM',
            'year_start_work' => '2008',
            'designation' => 'Dean',
            'qrcode' => 'E-RPT-001',
        ]);

        LibraryAttendanceLog::query()->create([
            'student_id' => $student->id,
            'status' => 'IN',
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        LibraryAttendanceLog::query()->create([
            'employee_id' => $employee->id,
            'status' => 'IN',
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        $admin = $this->staffUser('library_admin');

        $this->actingAs($admin)
            ->withSession(['active_module' => 'library'])
            ->get('/library/attendance/logs/reports/dashboard?tab=employees')
            ->assertOk()
            ->assertSee('Faculty & Staff visit dashboard')
            ->assertSee('Paleta, Leonard')
            ->assertDontSee('Aba, Fatma');
    }

    public function test_library_admin_can_export_student_reports_csv(): void
    {
        $student = Student::query()->create([
            'id_number' => '24-90002',
            'qrcode' => 'S-RPT-002',
            'firstname' => 'Wareyn',
            'lastname' => 'Villacrusis',
            'course' => 'BSCE',
            'year' => '1st Year',
        ]);

        LibraryAttendanceLog::query()->create([
            'student_id' => $student->id,
            'status' => 'IN',
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        $admin = $this->staffUser('library_admin');

        $response = $this->actingAs($admin)
            ->withSession(['active_module' => 'library'])
            ->get('/library/attendance/logs/reports/export?tab=students');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }

    public function test_library_staff_cannot_access_visit_reports(): void
    {
        $staff = $this->staffUser('library_staff');

        $this->actingAs($staff)
            ->withSession(['active_module' => 'library'])
            ->get('/library/attendance/logs/reports')
            ->assertForbidden();
    }

    private function staffUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
