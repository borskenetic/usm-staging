<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminActivity;
use App\Models\BrandingSetting;
use App\Models\User;
use App\Services\BrandingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DeveloperBrandingActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(BrandingService::CACHE_KEY);
        $this->seed(RoleSeeder::class);
    }

    public function test_branding_update_creates_activity_record(): void
    {
        $developer = $this->staffUser('developer');

        $this->actingAs($developer)->put('/developer/branding', $this->colors([
            'primary_color' => '#112233',
        ]))->assertRedirect(route('developer.branding.edit', absolute: false));

        $this->assertDatabaseHas('admin_activities', [
            'user_id' => $developer->id,
            'module' => 'branding',
            'type' => 'branding_update',
        ]);

        $activity = AdminActivity::query()->where('module', 'branding')->where('type', 'branding_update')->firstOrFail();
        $this->assertStringContainsString('primary_color', $activity->body ?? '');
        $this->assertSame('palette', $activity->icon);
    }

    public function test_branding_upload_creates_activity_record(): void
    {
        Storage::fake('public');
        $developer = $this->staffUser('developer');

        $this->actingAs($developer)->put('/developer/branding', $this->colors([
            'banner' => UploadedFile::fake()->image('new-banner.jpg', 1200, 400),
        ]))->assertRedirect(route('developer.branding.edit', absolute: false));

        $this->assertDatabaseHas('admin_activities', [
            'user_id' => $developer->id,
            'module' => 'branding',
            'type' => 'branding_update',
        ]);

        $activity = AdminActivity::query()->where('module', 'branding')->where('type', 'branding_update')->firstOrFail();
        $this->assertStringContainsString('banner_path', $activity->body ?? '');
    }

    public function test_partial_restore_creates_activity_record(): void
    {
        $developer = $this->staffUser('developer');
        BrandingSetting::query()->create($this->colors([
            'updated_by' => $developer->id,
        ]));

        $this->actingAs($developer)->post('/developer/branding/restore', [
            'field' => 'accent_color',
        ])->assertRedirect(route('developer.branding.edit', absolute: false));

        $this->assertDatabaseHas('admin_activities', [
            'user_id' => $developer->id,
            'module' => 'branding',
            'type' => 'branding_restore',
        ]);

        $activity = AdminActivity::query()->where('module', 'branding')->where('type', 'branding_restore')->firstOrFail();
        $this->assertStringContainsString('accent_color', $activity->title);
        $this->assertSame('restore', $activity->icon);
    }

    public function test_full_restore_creates_activity_record(): void
    {
        $developer = $this->staffUser('developer');
        BrandingSetting::query()->create($this->colors([
            'updated_by' => $developer->id,
        ]));

        $this->actingAs($developer)->post('/developer/branding/restore')
            ->assertRedirect(route('developer.branding.edit', absolute: false));

        $this->assertDatabaseHas('admin_activities', [
            'user_id' => $developer->id,
            'module' => 'branding',
            'type' => 'branding_restore_all',
        ]);

        $activity = AdminActivity::query()->where('module', 'branding')->where('type', 'branding_restore_all')->firstOrFail();
        $this->assertStringContainsString('Full restoration', $activity->body ?? '');
        $this->assertSame('restore', $activity->icon);
    }

    public function test_developer_can_view_branding_activity_page(): void
    {
        $developer = $this->staffUser('developer');
        BrandingSetting::query()->create($this->colors(['updated_by' => $developer->id]));

        // Perform some actions to create activity records
        $this->actingAs($developer)->put('/developer/branding', $this->colors([
            'primary_color' => '#AABBCC',
        ]))->assertRedirect();
        $this->actingAs($developer)->post('/developer/branding/restore', [
            'field' => 'primary_color',
        ])->assertRedirect();
        $this->actingAs($developer)->post('/developer/branding/restore')->assertRedirect();

        $response = $this->actingAs($developer)->get('/developer/branding/activity');
        $response->assertOk()
            ->assertSee('Branding Activity')
            ->assertSee('Update')
            ->assertSee('Partial Restore')
            ->assertSee('Full Restore');
    }

    public function test_non_developer_cannot_view_branding_activity_page(): void
    {
        foreach (['super_admin', 'library_admin', 'attendance_admin'] as $role) {
            $this->actingAs($this->staffUser($role))
                ->get('/developer/branding/activity')
                ->assertForbidden();
        }
    }

    public function test_activity_page_shows_only_branding_records(): void
    {
        $developer = $this->staffUser('developer');

        // Create a non-branding activity
        AdminActivity::query()->create([
            'user_id' => $developer->id,
            'module' => 'system',
            'type' => 'staff_created',
            'title' => 'Non-branding activity',
        ]);

        // Create a branding activity
        BrandingSetting::query()->create($this->colors(['updated_by' => $developer->id]));
        $this->actingAs($developer)->put('/developer/branding', $this->colors([
            'button_color' => '#FF0000',
        ]))->assertRedirect();

        $response = $this->actingAs($developer)->get('/developer/branding/activity');
        $response->assertOk();

        // Should see branding activity body text (the view renders $activity->body ?? $activity->title)
        $response->assertSee('Changed: button_color');
        // Should NOT see non-branding activity
        $response->assertDontSee('Non-branding activity');
    }

    public function test_original_assets_never_deleted_after_upload(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/Bannernew.jpg', 'original-banner-content');
        Storage::disk('public')->put('images/pantasLogo-box.png', 'original-logo-content');
        $developer = $this->staffUser('developer');

        $this->actingAs($developer)->put('/developer/branding', $this->colors([
            'banner' => UploadedFile::fake()->image('uploaded.jpg', 1200, 400),
            'sidebar_logo' => UploadedFile::fake()->image('uploaded.png', 300, 300),
        ]))->assertRedirect();

        // Original assets must still exist
        Storage::disk('public')->assertExists('images/Bannernew.jpg');
        Storage::disk('public')->assertExists('images/pantasLogo-box.png');
    }

    public function test_missing_custom_file_falls_back_to_original(): void
    {
        $developer = $this->staffUser('developer');
        BrandingSetting::query()->create([
            'banner_path' => 'branding/banners/missing.jpg',
            'sidebar_logo_path' => 'branding/logos/missing.png',
            'updated_by' => $developer->id,
        ]);
        Cache::forget(BrandingService::CACHE_KEY);

        $active = app(BrandingService::class)->active();
        $this->assertSame('images/Bannernew.jpg', $active['banner_path']);
        $this->assertSame(config('branding.defaults.sidebar_logo_path'), $active['sidebar_logo_path']);
    }

    public function test_cache_invalidation_on_update_and_restore(): void
    {
        $developer = $this->staffUser('developer');
        $service = app(BrandingService::class);

        // Warm cache
        $service->active();
        $this->assertTrue(Cache::has(BrandingService::CACHE_KEY));

        // Update
        $this->actingAs($developer)->put('/developer/branding', $this->colors([
            'primary_color' => '#FF0000',
        ]))->assertRedirect();
        $this->assertFalse(Cache::has(BrandingService::CACHE_KEY));

        // Warm again
        $service->active();
        $this->assertTrue(Cache::has(BrandingService::CACHE_KEY));

        // Restore (partial)
        $this->actingAs($developer)->post('/developer/branding/restore', [
            'field' => 'primary_color',
        ])->assertRedirect();
        $this->assertFalse(Cache::has(BrandingService::CACHE_KEY));

        // Warm again
        $service->active();
        $this->assertTrue(Cache::has(BrandingService::CACHE_KEY));

        // Full restore
        $this->actingAs($developer)->post('/developer/branding/restore')->assertRedirect();
        $this->assertFalse(Cache::has(BrandingService::CACHE_KEY));
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function colors(array $overrides = []): array
    {
        return array_merge([
            'primary_color' => '#1E3A8A',
            'secondary_color' => '#0F766E',
            'accent_color' => '#B45309',
            'sidebar_background_color' => '#FFFFFF',
            'sidebar_text_color' => '#0F172A',
            'sidebar_active_color' => '#2563EB',
            'button_color' => '#1E3A8A',
        ], $overrides);
    }

    private function staffUser(string $role): User
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
