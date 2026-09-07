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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DeveloperLoginModalFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(BrandingService::CACHE_KEY);
        $this->seed(RoleSeeder::class);
    }

    public function test_login_modal_defaults_and_columns_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('branding_settings', [
            'login_modal_logo_path',
            'login_modal_welcome_label',
            'login_modal_portal_name',
            'login_modal_description',
            'login_modal_sign_in_heading',
            'login_modal_email_placeholder',
            'login_modal_password_placeholder',
            'login_modal_left_background_color',
            'login_modal_welcome_portal_color',
            'login_modal_description_color',
            'login_modal_background_color',
            'login_modal_form_background_color',
            'login_modal_form_border_color',
            'login_modal_text_color',
            'login_modal_button_color',
        ]));

        $active = app(BrandingService::class)->active();
        $defaults = config('branding.defaults');

        $this->assertSame($defaults['login_modal_logo_path'], $active['login_modal_logo_path']);
        $this->assertSame($defaults['login_modal_portal_name'], $active['login_modal_portal_name']);
        $this->assertSame($defaults['login_modal_sign_in_heading'], $active['login_modal_sign_in_heading']);
        $this->assertSame($defaults['login_modal_button_color'], $active['login_modal_button_color']);
        $this->assertSame($defaults['login_modal_welcome_portal_color'], $active['login_modal_welcome_portal_color']);
        $this->assertSame($defaults['login_modal_description_color'], $active['login_modal_description_color']);
        $this->assertSame($defaults['login_modal_form_background_color'], $active['login_modal_form_background_color']);
        $this->assertSame($defaults['login_modal_form_border_color'], $active['login_modal_form_border_color']);
        $this->assertFalse($active['is_customized']);
    }

    public function test_stale_cached_branding_payload_is_upgraded_with_current_defaults(): void
    {
        $stalePayload = collect(config('branding.defaults'))
            ->except(BrandingService::LOGIN_MODAL_FIELDS)
            ->all() + [
                'is_customized' => true,
                'updated_at' => null,
                'updated_by' => 'Legacy Developer',
            ];
        Cache::forever(BrandingService::CACHE_KEY, $stalePayload);

        $active = app(BrandingService::class)->active();

        $defaults = config('branding.defaults');
        $this->assertSame($defaults['login_modal_portal_name'], $active['login_modal_portal_name']);
        $this->assertSame($defaults['login_modal_sign_in_heading'], $active['login_modal_sign_in_heading']);
        $this->assertSame($defaults['login_modal_button_color'], $active['login_modal_button_color']);
        $this->assertTrue($active['is_customized']);
        $this->assertArrayHasKey('overrides', Cache::get(BrandingService::CACHE_KEY));
    }

    public function test_service_persists_login_modal_overrides_upload_and_activity(): void
    {
        Storage::fake('public');
        $developer = $this->developer();
        $this->actingAs($developer);

        $settings = app(BrandingService::class)->update(
            [
                'login_modal_welcome_label' => 'Welcome back',
                'login_modal_portal_name' => 'Campus Portal',
                'login_modal_description' => 'Use your staff account to continue.',
                'login_modal_sign_in_heading' => 'Staff sign in',
                'login_modal_email_placeholder' => 'name@example.edu',
                'login_modal_password_placeholder' => 'Your password',
                'login_modal_left_background_color' => '#112233',
                'login_modal_welcome_portal_color' => '#F8FAFC',
                'login_modal_description_color' => '#E2E8F0',
                'login_modal_background_color' => '#F8FAFC',
                'login_modal_form_background_color' => '#F1F5F9',
                'login_modal_form_border_color' => '#64748B',
                'login_modal_text_color' => '#223344',
                'login_modal_button_color' => '#AABBCC',
            ],
            $developer,
            loginModalLogo: UploadedFile::fake()->image('login-logo.png', 300, 300),
        );

        Storage::disk('public')->assertExists($settings->login_modal_logo_path);
        $this->assertStringStartsWith('branding/login-modal/', (string) $settings->login_modal_logo_path);

        $active = app(BrandingService::class)->active();
        $this->assertSame('Welcome back', $active['login_modal_welcome_label']);
        $this->assertSame('Campus Portal', $active['login_modal_portal_name']);
        $this->assertSame('#AABBCC', $active['login_modal_button_color']);
        $this->assertSame('#F8FAFC', $active['login_modal_welcome_portal_color']);
        $this->assertSame('#E2E8F0', $active['login_modal_description_color']);
        $this->assertSame('#F1F5F9', $active['login_modal_form_background_color']);
        $this->assertSame('#64748B', $active['login_modal_form_border_color']);
        $this->assertTrue($active['is_customized']);

        $activity = AdminActivity::query()->where('type', 'branding_update')->firstOrFail();
        $this->assertStringContainsString('login_modal_logo_path', (string) $activity->body);
        $this->assertStringContainsString('login_modal_portal_name', (string) $activity->body);
    }

    public function test_missing_logo_falls_back_and_restore_clears_overrides(): void
    {
        $developer = $this->developer();
        $this->actingAs($developer);
        BrandingSetting::query()->create([
            'login_modal_logo_path' => 'branding/login-modal/missing.png',
            'login_modal_portal_name' => 'Temporary Portal',
            'login_modal_button_color' => '#010203',
            'updated_by' => $developer->id,
        ]);
        Cache::forget(BrandingService::CACHE_KEY);

        $this->assertSame(
            config('branding.defaults.login_modal_logo_path'),
            app(BrandingService::class)->active()['login_modal_logo_path']
        );

        app(BrandingService::class)->restore('login_modal_portal_name', $developer);
        $this->assertNull(BrandingSetting::query()->value('login_modal_portal_name'));

        app(BrandingService::class)->restore(null, $developer);
        $settings = BrandingSetting::query()->firstOrFail();

        foreach (array_keys(config('branding.defaults')) as $field) {
            $this->assertNull($settings->{$field});
        }

        $this->assertFalse(app(BrandingService::class)->active()['is_customized']);
        $this->assertDatabaseHas('admin_activities', ['type' => 'branding_restore']);
        $this->assertDatabaseHas('admin_activities', ['type' => 'branding_restore_all']);
    }

    private function developer(): User
    {
        $user = User::factory()->create(['role' => 'developer', 'is_active' => true]);
        $user->assignRole('developer');

        return $user;
    }
}
