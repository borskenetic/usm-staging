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

final class DeveloperRegisterModalFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(BrandingService::CACHE_KEY);
        $this->seed(RoleSeeder::class);
    }

    public function test_register_modal_defaults_and_columns_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('branding_settings', [
            'register_modal_attendance_logo_path',
            'register_modal_library_logo_path',
            'register_modal_heading',
            'register_modal_login_label',
            'register_modal_attendance_tab',
            'register_modal_library_tab',
            'register_modal_attendance_welcome_label',
            'register_modal_attendance_portal_name',
            'register_modal_attendance_description',
            'register_modal_attendance_heading',
            'register_modal_attendance_student_label',
            'register_modal_attendance_employee_label',
            'register_modal_attendance_student_submit',
            'register_modal_attendance_employee_submit',
            'register_modal_library_welcome_label',
            'register_modal_library_portal_name',
            'register_modal_library_description',
            'register_modal_library_heading',
            'register_modal_library_student_label',
            'register_modal_library_employee_label',
            'register_modal_library_student_submit',
            'register_modal_library_employee_submit',
            'register_modal_attendance_panel_color',
            'register_modal_attendance_welcome_portal_color',
            'register_modal_attendance_description_color',
            'register_modal_attendance_text_color',
            'register_modal_attendance_accent_color',
            'register_modal_attendance_active_role_color',
            'register_modal_attendance_submit_color',
            'register_modal_library_panel_color',
            'register_modal_library_welcome_portal_color',
            'register_modal_library_description_color',
            'register_modal_library_text_color',
            'register_modal_library_accent_color',
            'register_modal_library_active_role_color',
            'register_modal_library_submit_color',
        ]));

        $active = app(BrandingService::class)->active();
        $defaults = config('branding.defaults');

        $this->assertSame($defaults['register_modal_attendance_logo_path'], $active['register_modal_attendance_logo_path']);
        $this->assertSame($defaults['register_modal_library_logo_path'], $active['register_modal_library_logo_path']);
        $this->assertSame($defaults['register_modal_heading'], $active['register_modal_heading']);
        $this->assertSame($defaults['register_modal_login_label'], $active['register_modal_login_label']);
        $this->assertSame($defaults['register_modal_attendance_tab'], $active['register_modal_attendance_tab']);
        $this->assertSame($defaults['register_modal_library_tab'], $active['register_modal_library_tab']);
        $this->assertSame($defaults['register_modal_attendance_welcome_label'], $active['register_modal_attendance_welcome_label']);
        $this->assertSame($defaults['register_modal_attendance_portal_name'], $active['register_modal_attendance_portal_name']);
        $this->assertSame($defaults['register_modal_library_welcome_label'], $active['register_modal_library_welcome_label']);
        $this->assertSame($defaults['register_modal_library_portal_name'], $active['register_modal_library_portal_name']);
        $this->assertSame($defaults['register_modal_attendance_panel_color'], $active['register_modal_attendance_panel_color']);
        $this->assertSame($defaults['register_modal_attendance_welcome_portal_color'], $active['register_modal_attendance_welcome_portal_color']);
        $this->assertSame($defaults['register_modal_attendance_description_color'], $active['register_modal_attendance_description_color']);
        $this->assertSame($defaults['register_modal_library_panel_color'], $active['register_modal_library_panel_color']);
        $this->assertSame($defaults['register_modal_library_welcome_portal_color'], $active['register_modal_library_welcome_portal_color']);
        $this->assertSame($defaults['register_modal_library_description_color'], $active['register_modal_library_description_color']);
        $this->assertFalse($active['is_customized']);
    }

    public function test_stale_cached_branding_payload_is_upgraded_with_register_defaults(): void
    {
        $stalePayload = collect(config('branding.defaults'))
            ->except(BrandingService::REGISTER_MODAL_FIELDS)
            ->all() + [
                'is_customized' => true,
                'updated_at' => null,
                'updated_by' => 'Legacy Developer',
            ];
        Cache::forever(BrandingService::CACHE_KEY, $stalePayload);

        $active = app(BrandingService::class)->active();

        $defaults = config('branding.defaults');
        $this->assertSame($defaults['register_modal_heading'], $active['register_modal_heading']);
        $this->assertSame($defaults['register_modal_login_label'], $active['register_modal_login_label']);
        $this->assertSame($defaults['register_modal_attendance_panel_color'], $active['register_modal_attendance_panel_color']);
        $this->assertSame($defaults['register_modal_library_panel_color'], $active['register_modal_library_panel_color']);
        $this->assertTrue($active['is_customized']);
        $this->assertArrayHasKey('overrides', Cache::get(BrandingService::CACHE_KEY));
    }

    public function test_service_persists_register_modal_overrides_upload_and_activity(): void
    {
        Storage::fake('public');
        $developer = $this->developer();
        $this->actingAs($developer);

        $settings = app(BrandingService::class)->update(
            [
                'register_modal_heading' => 'Create Account',
                'register_modal_login_label' => 'Back to Login',
                'register_modal_attendance_tab' => 'School Attendance',
                'register_modal_library_tab' => 'Library Access',
                'register_modal_attendance_welcome_label' => 'Join Attendance',
                'register_modal_attendance_portal_name' => 'Campus Attendance',
                'register_modal_attendance_description' => 'Register for school attendance.',
                'register_modal_attendance_heading' => 'Attendance Signup',
                'register_modal_attendance_student_label' => 'Pupil',
                'register_modal_attendance_employee_label' => 'Staff',
                'register_modal_attendance_student_submit' => 'Submit Pupil Registration',
                'register_modal_attendance_employee_submit' => 'Submit Staff Registration',
                'register_modal_library_welcome_label' => 'Join Library',
                'register_modal_library_portal_name' => 'Campus Library',
                'register_modal_library_description' => 'Register for library access.',
                'register_modal_library_heading' => 'Library Signup',
                'register_modal_library_student_label' => 'Scholar',
                'register_modal_library_employee_label' => 'Faculty',
                'register_modal_library_student_submit' => 'Submit Scholar Registration',
                'register_modal_library_employee_submit' => 'Submit Faculty Registration',
                'register_modal_attendance_panel_color' => '#112233',
                'register_modal_attendance_welcome_portal_color' => '#F8FAFC',
                'register_modal_attendance_description_color' => '#FEF3C7',
                'register_modal_attendance_text_color' => '#223344',
                'register_modal_attendance_accent_color' => '#334455',
                'register_modal_attendance_active_role_color' => '#445566',
                'register_modal_attendance_submit_color' => '#556677',
                'register_modal_library_panel_color' => '#667788',
                'register_modal_library_welcome_portal_color' => '#E0F2FE',
                'register_modal_library_description_color' => '#DBEAFE',
                'register_modal_library_text_color' => '#778899',
                'register_modal_library_accent_color' => '#8899AA',
                'register_modal_library_active_role_color' => '#99AABB',
                'register_modal_library_submit_color' => '#AABBCC',
            ],
            $developer,
            attendanceRegisterLogo: UploadedFile::fake()->image('att-reg.png', 300, 300),
            libraryRegisterLogo: UploadedFile::fake()->image('lib-reg.png', 300, 300),
        );

        Storage::disk('public')->assertExists($settings->register_modal_attendance_logo_path);
        Storage::disk('public')->assertExists($settings->register_modal_library_logo_path);
        $this->assertStringStartsWith('branding/register-modal/', (string) $settings->register_modal_attendance_logo_path);
        $this->assertStringStartsWith('branding/register-modal/', (string) $settings->register_modal_library_logo_path);

        $active = app(BrandingService::class)->active();
        $this->assertSame('Create Account', $active['register_modal_heading']);
        $this->assertSame('Campus Attendance', $active['register_modal_attendance_portal_name']);
        $this->assertSame('Campus Library', $active['register_modal_library_portal_name']);
        $this->assertSame('#556677', $active['register_modal_attendance_submit_color']);
        $this->assertSame('#F8FAFC', $active['register_modal_attendance_welcome_portal_color']);
        $this->assertSame('#FEF3C7', $active['register_modal_attendance_description_color']);
        $this->assertSame('#AABBCC', $active['register_modal_library_submit_color']);
        $this->assertSame('#E0F2FE', $active['register_modal_library_welcome_portal_color']);
        $this->assertSame('#DBEAFE', $active['register_modal_library_description_color']);
        $this->assertTrue($active['is_customized']);

        $activity = AdminActivity::query()->where('type', 'branding_update')->firstOrFail();
        $this->assertStringContainsString('register_modal_heading', (string) $activity->body);
        $this->assertStringContainsString('register_modal_attendance_portal_name', (string) $activity->body);
        $this->assertStringContainsString('register_modal_library_portal_name', (string) $activity->body);
    }

    public function test_missing_logo_falls_back_and_restore_clears_overrides(): void
    {
        $developer = $this->developer();
        $this->actingAs($developer);
        BrandingSetting::query()->create([
            'register_modal_attendance_logo_path' => 'branding/register-modal/missing-att.png',
            'register_modal_library_logo_path' => 'branding/register-modal/missing-lib.png',
            'register_modal_heading' => 'Custom Register',
            'register_modal_attendance_portal_name' => 'Custom Attendance',
            'register_modal_attendance_panel_color' => '#010203',
            'updated_by' => $developer->id,
        ]);
        Cache::forget(BrandingService::CACHE_KEY);

        $this->assertSame(
            config('branding.defaults.register_modal_attendance_logo_path'),
            app(BrandingService::class)->active()['register_modal_attendance_logo_path']
        );
        $this->assertSame(
            config('branding.defaults.register_modal_library_logo_path'),
            app(BrandingService::class)->active()['register_modal_library_logo_path']
        );

        app(BrandingService::class)->restore('register_modal_heading', $developer);
        $this->assertNull(BrandingSetting::query()->value('register_modal_heading'));

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
