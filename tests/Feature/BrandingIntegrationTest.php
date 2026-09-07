<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BrandingSetting;
use App\Models\User;
use App\Services\BrandingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class BrandingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::forget(BrandingService::CACHE_KEY);
        $this->seed(RoleSeeder::class);
    }

    public function test_custom_assets_and_colors_are_shared_with_authenticated_layout(): void
    {
        Storage::disk('public')->put('branding/banners/custom.jpg', 'banner');
        Storage::disk('public')->put('branding/logos/custom.png', 'logo');
        $developer = $this->staffUser('developer');
        BrandingSetting::query()->create([
            'banner_path' => 'branding/banners/custom.jpg',
            'sidebar_logo_path' => 'branding/logos/custom.png',
            'sidebar_brand_name' => 'Custom Pantas',
            'sidebar_brand_subtitle' => 'Custom Portal',
            'sidebar_brand_text_color' => '#ABCDEF',
            'primary_color' => '#112233',
            'secondary_color' => '#223344',
            'accent_color' => '#334455',
            'sidebar_background_color' => '#445566',
            'sidebar_text_color' => '#F1F2F3',
            'sidebar_active_color' => '#556677',
            'sidebar_hover_background_color' => '#102030',
            'sidebar_hover_text_color' => '#F0E0D0',
            'button_color' => '#667788',
            'sidebar_footer_background_color' => '#AA1122',
            'table_header_color' => '#778899',
            'table_header_text_color' => '#F4F5F6',
            'table_border_color' => '#8899AA',
            'table_hover_color' => '#99AABB',
            'updated_by' => $developer->id,
        ]);
        Cache::forget(BrandingService::CACHE_KEY);

        $this->actingAs($developer)
            ->get('/developer/dashboard')
            ->assertOk()
            ->assertSee('/branding-assets/logos/custom.png', false)
            ->assertSee('/branding-assets/banners/custom.jpg', false)
            ->assertSee('Custom Pantas')
            ->assertSee('Custom Portal')
            ->assertSee('--branding-sidebar-brand-text: #ABCDEF', false)
            ->assertSee('--shell-primary: #112233', false)
            ->assertSee('--branding-sidebar-background: #445566', false)
            ->assertSee('--branding-sidebar-hover-background: #102030', false)
            ->assertSee('--branding-sidebar-hover-text: #F0E0D0', false)
            ->assertSee('--branding-table-header: #778899', false)
            ->assertSee('--branding-table-hover: #99AABB', false)
            ->assertSee('--branding-sidebar-footer-background: #AA1122', false)
            ->assertSee('background: var(--branding-sidebar-footer-background)', false)
            ->assertSee('[data-theme="pantas-default"]', false)
            ->assertDontSee('[data-theme="nord-dark"] {', false);
    }

    public function test_missing_custom_assets_fall_back_to_original_paths(): void
    {
        $developer = $this->staffUser('developer');
        BrandingSetting::query()->create([
            'banner_path' => 'branding/banners/missing.jpg',
            'sidebar_logo_path' => 'branding/logos/missing.png',
            'updated_by' => $developer->id,
        ]);
        Cache::forget(BrandingService::CACHE_KEY);

        $response = $this->actingAs($developer)->get('/developer/dashboard');

        $response->assertOk()
            ->assertSee('/images/Bannernew.jpg', false)
            ->assertSee('/images/pantasLogo-box.png', false)
            ->assertDontSee('/branding-assets/banners/missing.jpg', false)
            ->assertDontSee('/branding-assets/logos/missing.png', false);
    }

    public function test_custom_assets_are_exposed_on_public_opac_layout(): void
    {
        Storage::disk('public')->put('branding/banners/public.jpg', 'banner');
        Storage::disk('public')->put('branding/logos/public.png', 'logo');
        BrandingSetting::query()->create([
            'banner_path' => 'branding/banners/public.jpg',
            'sidebar_logo_path' => 'branding/logos/public.png',
        ]);
        Cache::forget(BrandingService::CACHE_KEY);

        $this->get('/opac')
            ->assertOk()
            ->assertSee('/branding-assets/banners/public.jpg', false)
            ->assertSee('/branding-assets/logos/public.png', false);

        $this->get('/branding-assets/banners/public.jpg')
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->get('/branding-assets/banners/not-active.jpg')->assertNotFound();
    }

    public function test_legacy_cached_blue_defaults_do_not_override_green_shell_primary(): void
    {
        Cache::forever(BrandingService::CACHE_KEY, config('branding.defaults') + [
            'primary_color' => '#1F4EA7',
            'button_color' => '#1F4EA7',
            'is_customized' => false,
            'updated_at' => null,
            'updated_by' => null,
        ]);

        $active = app(BrandingService::class)->active();

        $this->assertSame('#1B5E20', $active['primary_color']);
        $this->assertSame('#1B5E20', $active['button_color']);
        $this->assertArrayHasKey('overrides', Cache::get(BrandingService::CACHE_KEY));
    }

    private function staffUser(string $role): User
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
