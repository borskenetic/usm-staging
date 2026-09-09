<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteSplitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_public_routes_remain_registered_after_module_split(): void
    {
        foreach ([
            'home',
            'login',
            'dashboard',
            'patron.register',
            'library.register',
            'attendance.register',
            'attendance.scan',
            'landing',
            'kiosk.scan',
            'rooms.book',
            'rooms.schedule',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Expected [{$routeName}] route to be registered.");
        }
    }

    public function test_library_staff_can_reach_library_routes_but_not_attendance_admin_routes(): void
    {
        $user = $this->staffUser('library_staff');

        $this->actingAs($user)->get('/books')->assertOk();
        $this->actingAs($user)->get('/attendance-logs')->assertForbidden();
    }

    public function test_attendance_staff_can_reach_attendance_routes_but_not_library_staff_routes(): void
    {
        $user = $this->staffUser('attendance_staff');

        $this->actingAs($user)->get('/attendance/change-video')->assertOk();
        $this->actingAs($user)->get('/books')->assertForbidden();
    }

    public function test_user_account_routes_are_available_to_library_admins(): void
    {
        $superAdmin = $this->staffUser('super_admin');
        $libraryAdmin = $this->staffUser('library_admin');
        $libraryStaff = $this->staffUser('library_staff');

        $this->actingAs($superAdmin)->get('/view-users')->assertOk();
        $this->actingAs($libraryAdmin)->get('/view-users')->assertOk();
        $this->actingAs($libraryStaff)->get('/view-users')->assertForbidden();
    }

    public function test_super_admin_sidebar_shows_only_active_module_navigation(): void
    {
        $superAdmin = $this->staffUser('super_admin');

        $this->actingAs($superAdmin)
            ->withSession(['active_module' => 'super-admin'])
            ->get('/dashboard/super-admin')
            ->assertOk()
            ->assertSee('Staff Accounts')
            ->assertDontSee('Books')
            ->assertDontSee('Scanner')
            ->assertDontSee('OPAC');

        $this->actingAs($superAdmin)
            ->withSession(['active_module' => 'library'])
            ->get('/dashboard/library-admin')
            ->assertOk()
            ->assertSee('Books')
            ->assertSee('OPAC')
            ->assertSee('User accounts')
            ->assertDontSee('Attendance Scanner');

        $this->actingAs($superAdmin)
            ->withSession(['active_module' => 'attendance'])
            ->get('/dashboard/attendance-admin')
            ->assertOk()
            ->assertSee('Scanner')
            ->assertDontSee('Staff Accounts')
            ->assertDontSee('Books')
            ->assertDontSee('OPAC');
    }

    public function test_attendance_dashboard_navigation_is_attendance_only_for_attendance_staff(): void
    {
        $user = $this->staffUser('attendance_staff');

        $this->actingAs($user)
            ->get('/dashboard/attendance-staff')
            ->assertOk()
            ->assertSee('Scanner')
            ->assertDontSee('Books')
            ->assertDontSee('OPAC');
    }

    public function test_library_dashboard_navigation_is_library_only_for_library_staff(): void
    {
        $user = $this->staffUser('library_staff');

        $this->actingAs($user)
            ->get('/dashboard/library-staff')
            ->assertOk()
            ->assertSee('Books')
            ->assertSee('OPAC')
            ->assertDontSee('Attendance Scanner')
            ->assertDontSee('Attendance Dashboard');
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
