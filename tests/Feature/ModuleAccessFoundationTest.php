<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAccessFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_super_admin_defaults_to_super_admin_dashboard(): void
    {
        $user = $this->staffUser('super_admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('dashboard.super-admin', absolute: false));
    }

    public function test_library_staff_defaults_to_library_staff_dashboard(): void
    {
        $user = $this->staffUser('library_staff');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('dashboard.library-staff', absolute: false));
    }

    public function test_attendance_admin_defaults_to_attendance_admin_dashboard(): void
    {
        $user = $this->staffUser('attendance_admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('dashboard.attendance-admin', absolute: false));
    }

    public function test_library_staff_cannot_access_attendance_dashboard(): void
    {
        $user = $this->staffUser('library_staff');

        $response = $this->actingAs($user)->get('/dashboard/attendance-staff');

        $response->assertForbidden();
        $response->assertSee('403', false);
        $response->assertSee('FORBIDDEN', false);
        $response->assertSee('Back to dashboard', false);
        $response->assertSee(route('dashboard', absolute: false), false);
    }

    public function test_user_can_switch_to_authorized_module(): void
    {
        $user = $this->staffUser('library_admin');
        $user->assignRole('attendance_staff');

        $response = $this->actingAs($user)->post('/switch-module', [
            'module' => 'attendance',
        ]);

        $response->assertRedirect(route('dashboard.attendance-staff', absolute: false));
        $this->assertSame('attendance', session('active_module'));
    }

    public function test_user_cannot_switch_to_unauthorized_module(): void
    {
        $user = $this->staffUser('library_staff');

        $response = $this->actingAs($user)->post('/switch-module', [
            'module' => 'attendance',
        ]);

        $response->assertForbidden();
    }

    public function test_inactive_staff_is_logged_out(): void
    {
        $user = $this->staffUser('library_staff', ['is_active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login', absolute: false));
        $this->assertGuest();
    }

    /** @param array<string, mixed> $attributes */
    private function staffUser(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => $role,
            'is_active' => true,
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }
}
