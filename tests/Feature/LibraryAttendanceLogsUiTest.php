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

class LibraryAttendanceLogsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_library_admin_can_view_filtered_logs_without_section_column(): void
    {
        $student = Student::query()->create([
            'id_number' => '24-51851',
            'qrcode' => 'S-LOG-001',
            'firstname' => 'Fatma',
            'lastname' => 'Aba',
            'course' => 'BSE',
            'year' => '2nd Year',
        ]);

        LibraryAttendanceLog::query()->create([
            'student_id' => $student->id,
            'status' => 'IN',
            'section' => 'General',
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        $admin = $this->staffUser('library_admin');

        $this->actingAs($admin)
            ->withSession(['active_module' => 'library'])
            ->get('/library/attendance/logs?tab=students&search='.urlencode('Aba, Fatma'))
            ->assertOk()
            ->assertSee('Student visit logs')
            ->assertSee('Aba, Fatma')
            ->assertSee('ID 24-51851')
            ->assertSee('BSE')
            ->assertSee('2nd Year')
            ->assertSee('Export PDF')
            ->assertSee('Export Excel')
            ->assertSee('Apply filters')
            ->assertSee('Faculty &amp; Staff', false)
            ->assertDontSee('>Section</th>', false);
    }

    public function test_employee_logs_tab_excludes_student_scans(): void
    {
        $student = Student::query()->create([
            'id_number' => '24-10001',
            'qrcode' => 'S-LOG-002',
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
            'qrcode' => 'E-LOG-002',
        ]);

        LibraryAttendanceLog::query()->create([
            'student_id' => $student->id,
            'status' => 'IN',
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        LibraryAttendanceLog::query()->create([
            'employee_id' => $employee->id,
            'status' => 'OUT',
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        $admin = $this->staffUser('library_admin');

        $this->actingAs($admin)
            ->withSession(['active_module' => 'library'])
            ->get('/library/attendance/logs?tab=employees')
            ->assertOk()
            ->assertSee('Faculty & Staff visit logs')
            ->assertSee('Paleta, Leonard')
            ->assertDontSee('Aba, Fatma');
    }

    public function test_library_admin_can_export_excel_for_filtered_logs(): void
    {
        $employee = LibraryEmployee::query()->create([
            'employee_id' => '08-00688',
            'firstname' => 'Leonard',
            'lastname' => 'Paleta',
            'program' => 'BSAM',
            'year_start_work' => '2008',
            'designation' => 'Dean',
            'qrcode' => 'E-LOG-001',
        ]);

        LibraryAttendanceLog::query()->create([
            'employee_id' => $employee->id,
            'status' => 'OUT',
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        $admin = $this->staffUser('library_admin');

        $this->actingAs($admin)
            ->withSession(['active_module' => 'library'])
            ->get('/library/attendance/logs/export/excel?tab=employees&search='.urlencode('Paleta, Leonard'))
            ->assertOk();
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
