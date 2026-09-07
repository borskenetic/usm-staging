<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminActivity;
use App\Models\User;
use App\Services\AdminActivityLogger;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_library_admin_can_view_activity_log_with_tabs_and_filters(): void
    {
        $admin = $this->staffUser('library_admin');

        AdminActivity::query()->create([
            'user_id' => null,
            'module' => 'library',
            'type' => AdminActivity::TYPE_PATRON_REGISTRATION,
            'title' => 'New student registration',
            'body' => 'DOE, JANE (2026-0001) awaiting approval',
            'action_url' => '/pending',
            'icon' => 'bi-person-plus',
        ]);

        AdminActivity::query()->create([
            'user_id' => $admin->id,
            'module' => 'library',
            'type' => 'catalog.updated',
            'title' => 'Catalog record updated',
            'body' => 'Sample Book',
            'action_url' => null,
            'icon' => 'bi-book',
        ]);

        $this->actingAs($admin)
            ->get(route('library.attendance.activities', ['category' => 'patron']))
            ->assertOk()
            ->assertSee('Activity log')
            ->assertSee('Patron notifications')
            ->assertSee('New student registration')
            ->assertDontSee('Catalog record updated');

        $this->actingAs($admin)
            ->get(route('library.attendance.activities', ['category' => 'staff']))
            ->assertOk()
            ->assertSee('Catalog record updated')
            ->assertDontSee('New student registration');
    }

    public function test_library_staff_cannot_access_activity_log(): void
    {
        $staff = $this->staffUser('library_staff');

        $this->actingAs($staff)
            ->get(route('library.attendance.activities'))
            ->assertForbidden();
    }

    public function test_patron_registration_writes_patron_notification_activity(): void
    {
        $this->post('/register', [
            'id_number' => '2026-9999',
            'firstname' => 'Lenyrose',
            'lastname' => 'Onotan',
            'middle_initial' => 'A',
            'course' => 'BSIT',
            'year' => '3',
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_activities', [
            'module' => 'library',
            'type' => AdminActivity::TYPE_PATRON_REGISTRATION,
            'title' => 'New student registration',
        ]);

        $activity = AdminActivity::query()->where('type', AdminActivity::TYPE_PATRON_REGISTRATION)->firstOrFail();
        $this->assertTrue($activity->isPatronNotification());
    }

    public function test_feedback_submission_writes_patron_notification_activity(): void
    {
        $this->post('/feedback', [
            'name' => 'Visitor',
            'email' => 'visitor@example.test',
            'comments' => 'Great quiet study spaces.',
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_activities', [
            'module' => 'library',
            'type' => AdminActivity::TYPE_FEEDBACK_SUBMITTED,
            'title' => 'New OPAC feedback',
            'body' => 'Great quiet study spaces.',
        ]);
    }

    public function test_activity_logger_helpers_set_action_urls(): void
    {
        app(AdminActivityLogger::class)->selfCheckout('DOE, JOHN', 2);

        $activity = AdminActivity::query()->where('type', AdminActivity::TYPE_SELF_CHECKOUT)->firstOrFail();
        $this->assertSame('Self check-out', $activity->title);
        $this->assertStringContainsString('checked out 2 books', (string) $activity->body);
        $this->assertNotEmpty($activity->action_url);
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
