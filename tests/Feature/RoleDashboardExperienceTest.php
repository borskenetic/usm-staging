<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_super_admin_system_sidebar_and_dashboard_are_scoped_to_system_module(): void
    {
        $user = $this->staffUser('super_admin');

        $this->actingAs($user)
            ->withSession(['active_module' => 'super-admin'])
            ->get('/dashboard/super-admin')
            ->assertOk()
            ->assertSee('Total Staff')
            ->assertSee('Staff Distribution')
            ->assertSee('Create Staff')
            ->assertSee('Admin Activity')
            ->assertDontSee('Library Dashboard')
            ->assertDontSee('Attendance Dashboard');
    }

    public function test_library_admin_sees_admin_library_groups_and_dashboard_metrics(): void
    {
        $user = $this->staffUser('library_admin');

        $this->actingAs($user)
            ->withSession(['active_module' => 'library'])
            ->get('/dashboard/library-admin')
            ->assertOk()
            ->assertSee('Total Books')
            ->assertSee('Borrowing Trend')
            ->assertSee('Pending Patrons')
            ->assertSee('MARC Frameworks')
            ->assertSee('Visit Reports')
            ->assertDontSee('Attendance Dashboard')
            ->assertDontSee('Staff Accounts');
    }

    public function test_library_staff_sees_operational_library_sidebar_only(): void
    {
        $user = $this->staffUser('library_staff');

        $this->actingAs($user)
            ->withSession(['active_module' => 'library'])
            ->get('/dashboard/library-staff')
            ->assertOk()
            ->assertSee('Borrowed Today')
            ->assertSee('Library Scanner')
            ->assertSee('Room Schedule')
            ->assertSee('OPAC')
            ->assertDontSee('MARC Frameworks')
            ->assertDontSee('Fines')
            ->assertDontSee('Attendance Dashboard');
    }

    public function test_attendance_admin_sees_attendance_groups_and_dashboard_metrics(): void
    {
        $user = $this->staffUser('attendance_admin');

        $this->actingAs($user)
            ->withSession(['active_module' => 'attendance'])
            ->get('/dashboard/attendance-admin')
            ->assertOk()
            ->assertSee('Scans Today')
            ->assertSee('Attendance Trend')
            ->assertSee('Pending Attendance Registrations')
            ->assertSee('Absences')
            ->assertSee('Feedback Responses')
            ->assertDontSee('Library Dashboard')
            ->assertDontSee('Staff Accounts');
    }

    public function test_attendance_staff_sees_operational_attendance_sidebar_only(): void
    {
        $user = $this->staffUser('attendance_staff');

        $this->actingAs($user)
            ->withSession(['active_module' => 'attendance'])
            ->get('/dashboard/attendance-staff')
            ->assertOk()
            ->assertSee('Last Scan Time')
            ->assertSee('Hourly Scans Today')
            ->assertSee('Attendance Scanner')
            ->assertDontSee('Reports Hub')
            ->assertDontSee('Library Dashboard')
            ->assertDontSee('Staff Accounts');
    }

    public function test_admin_activity_and_attendance_pending_pages_keep_existing_permissions(): void
    {
        $this->withoutVite();

        $superAdmin = $this->staffUser('super_admin');
        $libraryAdmin = $this->staffUser('library_admin');
        $libraryStaff = $this->staffUser('library_staff');
        $attendanceAdmin = $this->staffUser('attendance_admin');
        $attendanceStaff = $this->staffUser('attendance_staff');

        $this->actingAs($superAdmin)->get('/library/attendance/activities')->assertOk();
        $this->actingAs($libraryAdmin)->get('/library/attendance/activities')->assertOk();
        $this->actingAs($libraryStaff)->get('/library/attendance/activities')->assertForbidden();
        $this->actingAs($attendanceAdmin)->get('/attendance/pending')->assertOk();
        $this->actingAs($attendanceStaff)->get('/attendance/pending')->assertForbidden();
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
