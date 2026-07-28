<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use App\Services\TopbarNotificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_unread_feedback_appears_in_topbar_notifications_for_library_admin(): void
    {
        Feedback::query()->create([
            'name' => 'Test Student',
            'comments' => 'Need more study rooms.',
            'source' => 'mobile',
            'category' => 'Room Reservation Concern',
        ]);

        $admin = $this->staffUser('library_admin');
        session(['active_module' => 'library']);

        $notifications = app(TopbarNotificationService::class)->forUser($admin);

        $this->assertTrue(
            $notifications->contains(
                fn (array $notification) => $notification['title'] === 'New library feedback'
                    && str_contains($notification['message'], '1 feedback submission')
                    && $notification['url'] === route('feedback.index')
            )
        );
    }

    public function test_library_admin_can_mark_feedback_as_read(): void
    {
        $feedback = Feedback::query()->create([
            'name' => 'Anonymous',
            'comments' => 'Great service.',
            'source' => 'web',
        ]);

        $admin = $this->staffUser('library_admin');
        session(['active_module' => 'library']);

        $this->actingAs($admin)
            ->post(route('feedback.read', $feedback))
            ->assertRedirect();

        $this->assertNotNull($feedback->fresh()->read_at);
    }

    public function test_library_admin_can_mark_all_feedback_as_read(): void
    {
        Feedback::query()->create([
            'name' => 'Student A',
            'comments' => 'First message',
            'source' => 'mobile',
        ]);
        Feedback::query()->create([
            'name' => 'Student B',
            'comments' => 'Second message',
            'source' => 'web',
        ]);

        $admin = $this->staffUser('library_admin');
        session(['active_module' => 'library']);

        $this->actingAs($admin)
            ->post(route('feedback.read-all'))
            ->assertRedirect();

        $this->assertSame(0, Feedback::query()->unread()->count());
    }

    public function test_feedback_index_shows_structured_columns(): void
    {
        Feedback::query()->create([
            'name' => 'Jane Student',
            'comments' => 'App keeps logging me out.',
            'source' => 'mobile',
            'category' => 'App Issue',
        ]);

        $admin = $this->staffUser('library_admin');
        session(['active_module' => 'library']);

        $this->actingAs($admin)
            ->get(route('feedback.index'))
            ->assertOk()
            ->assertSee('App Issue')
            ->assertSee('Mobile')
            ->assertSee('Jane Student')
            ->assertSee('App keeps logging me out.');
    }

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
