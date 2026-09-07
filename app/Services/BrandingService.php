<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BrandingSetting;
use App\Models\BrandingVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class BrandingService
{
    public const CACHE_KEY = 'branding.active';

    /** @var list<string> */
    public const ASSET_FIELDS = [
        'banner_path',
        'opac_banner_path',
        'opac_logo_path',
        'opac_default_book_cover_path',
        'sidebar_logo_path',
        'login_modal_logo_path',
        'register_modal_attendance_logo_path',
        'register_modal_library_logo_path',
    ];

    /** @var list<string> */
    public const COLOR_FIELDS = [
        'primary_color',
        'secondary_color',
        'accent_color',
        'sidebar_background_color',
        'sidebar_text_color',
        'sidebar_brand_text_color',
        'sidebar_active_color',
        'sidebar_hover_background_color',
        'sidebar_hover_text_color',
        'button_color',
        'sidebar_footer_background_color',
        'table_header_color',
        'table_header_text_color',
        'table_border_color',
        'table_hover_color',
        'login_modal_left_background_color',
        'login_modal_welcome_portal_color',
        'login_modal_description_color',
        'login_modal_background_color',
        'login_modal_form_background_color',
        'login_modal_form_border_color',
        'login_modal_text_color',
        'login_modal_button_color',
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
    ];

    /** @var list<string> */
    public const TEXT_FIELDS = [
        'sidebar_brand_name',
        'sidebar_brand_subtitle',
        'login_modal_welcome_label',
        'login_modal_portal_name',
        'login_modal_description',
        'login_modal_sign_in_heading',
        'login_modal_email_placeholder',
        'login_modal_password_placeholder',
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
    ];

    /** @var list<string> */
    public const LOGIN_MODAL_FIELDS = [
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
    ];

    /** @var list<string> */
    public const REGISTER_MODAL_FIELDS = [
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
    ];

    /** @var list<string> */
    public const SHARED_REGISTER_FIELDS = [
        'register_modal_heading',
        'register_modal_login_label',
        'register_modal_attendance_tab',
        'register_modal_library_tab',
    ];

    /** @var list<string> */
    public const ATTENDANCE_REGISTER_FIELDS = [
        'register_modal_attendance_logo_path',
        'register_modal_attendance_welcome_label',
        'register_modal_attendance_portal_name',
        'register_modal_attendance_description',
        'register_modal_attendance_heading',
        'register_modal_attendance_student_label',
        'register_modal_attendance_employee_label',
        'register_modal_attendance_student_submit',
        'register_modal_attendance_employee_submit',
        'register_modal_attendance_panel_color',
        'register_modal_attendance_welcome_portal_color',
        'register_modal_attendance_description_color',
        'register_modal_attendance_text_color',
        'register_modal_attendance_accent_color',
        'register_modal_attendance_active_role_color',
        'register_modal_attendance_submit_color',
    ];

    /** @var list<string> */
    public const LIBRARY_REGISTER_FIELDS = [
        'register_modal_library_logo_path',
        'register_modal_library_welcome_label',
        'register_modal_library_portal_name',
        'register_modal_library_description',
        'register_modal_library_heading',
        'register_modal_library_student_label',
        'register_modal_library_employee_label',
        'register_modal_library_student_submit',
        'register_modal_library_employee_submit',
        'register_modal_library_panel_color',
        'register_modal_library_welcome_portal_color',
        'register_modal_library_description_color',
        'register_modal_library_text_color',
        'register_modal_library_accent_color',
        'register_modal_library_active_role_color',
        'register_modal_library_submit_color',
    ];

    public function __construct(
        private readonly AdminActivityLogger $activityLogger,
        private readonly AssetOptimizer $assetOptimizer,
    ) {}

    /** @var list<string> */
    private const LEGACY_DEFAULT_COLORS = [
        '#1F4EA7',
        '#1f4ea7',
        '#1E3A8A',
        '#1e3a8a',
        '#2563EB',
        '#2563eb',
        '#EFF6FF',
        '#eff6ff',
        '#DBEAFE',
        '#dbeafe',
    ];

    /** @return array<string, mixed> */
    public function active(): array
    {
        $existing = Cache::get(self::CACHE_KEY);
        if (is_array($existing) && ! array_key_exists('overrides', $existing)) {
            Cache::forever(self::CACHE_KEY, $this->normalizeLegacyCachePayload($existing));
        }

        $cached = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $defaults = $this->defaults();
            $settings = $this->settings();

            if (! $settings) {
                return [
                    'overrides' => [],
                    'is_customized' => false,
                    'updated_at' => null,
                    'updated_by' => null,
                ];
            }

            $overrides = [];
            foreach (array_keys($defaults) as $field) {
                if (filled($settings->{$field})) {
                    $overrides[$field] = $settings->{$field};
                }
            }

            foreach (self::ASSET_FIELDS as $field) {
                if (
                    isset($overrides[$field])
                    && $overrides[$field] !== $defaults[$field]
                    && ! Storage::disk('public')->exists((string) $overrides[$field])
                ) {
                    unset($overrides[$field]);
                }
            }

            return [
                'overrides' => $overrides,
                'is_customized' => $overrides !== [],
                'updated_at' => $settings->updated_at,
                'updated_by' => $settings->updater?->name,
            ];
        });

        // Always start from current config defaults, then apply DB overrides only.
        $active = array_merge($this->defaults(), $cached['overrides'] ?? []);

        return $active + [
            'is_customized' => (bool) ($cached['is_customized'] ?? false),
            'updated_at' => $cached['updated_at'] ?? null,
            'updated_by' => $cached['updated_by'] ?? null,
        ];
    }

    /**
     * Convert pre-overrides cache payloads so missing keys pick up new defaults
     * without pinning retired blue Pantas defaults as permanent overrides.
     *
     * @param  array<string, mixed>  $legacy
     * @return array{overrides: array<string, mixed>, is_customized: bool, updated_at: mixed, updated_by: mixed}
     */
    private function normalizeLegacyCachePayload(array $legacy): array
    {
        $defaults = $this->defaults();
        $overrides = [];

        foreach (array_keys($defaults) as $field) {
            if (! array_key_exists($field, $legacy) || ! filled($legacy[$field])) {
                continue;
            }

            $value = $legacy[$field];
            if ($value === $defaults[$field]) {
                continue;
            }

            if (is_string($value) && in_array($value, self::LEGACY_DEFAULT_COLORS, true)) {
                continue;
            }

            $overrides[$field] = $value;
        }

        return [
            'overrides' => $overrides,
            'is_customized' => (bool) ($legacy['is_customized'] ?? $overrides !== []),
            'updated_at' => $legacy['updated_at'] ?? null,
            'updated_by' => $legacy['updated_by'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return config('branding.defaults', []);
    }

    public function assetUrl(string $field): string
    {
        $path = (string) ($this->active()[$field] ?? $this->defaults()[$field] ?? '');

        return str_starts_with($path, 'branding/')
            ? '/branding-assets/'.substr($path, strlen('branding/'))
            : '/'.ltrim($path, '/');
    }

    /** @param array<string, mixed> $values */
    public function update(
        array $values,
        User $user,
        ?UploadedFile $banner = null,
        ?UploadedFile $opacBanner = null,
        ?UploadedFile $opacLogo = null,
        ?UploadedFile $opacDefaultBookCover = null,
        ?UploadedFile $logo = null,
        ?UploadedFile $loginModalLogo = null,
        ?UploadedFile $attendanceRegisterLogo = null,
        ?UploadedFile $libraryRegisterLogo = null,
    ): BrandingSetting {
        $settings = $this->settings() ?? new BrandingSetting;
        $old = collect(array_keys($this->defaults()))
            ->mapWithKeys(fn (string $field): array => [$field => $settings->{$field}])
            ->all();
        $oldBanner = $settings->banner_path;
        $oldOpacBanner = $settings->opac_banner_path;
        $oldOpacLogo = $settings->opac_logo_path;
        $oldOpacDefaultBookCover = $settings->opac_default_book_cover_path;
        $oldLogo = $settings->sidebar_logo_path;
        $oldLoginModalLogo = $settings->login_modal_logo_path;
        $oldAttendanceRegisterLogo = $settings->register_modal_attendance_logo_path;
        $oldLibraryRegisterLogo = $settings->register_modal_library_logo_path;

        // Optimize before storing so the persisted file is compressed
        if ($banner) {
            $this->assetOptimizer->optimize($banner, 'banner');
        }
        if ($opacBanner) {
            $this->assetOptimizer->optimize($opacBanner, 'banner');
        }
        if ($opacLogo) {
            $this->assetOptimizer->optimize($opacLogo, 'logo');
        }
        if ($opacDefaultBookCover) {
            $this->assetOptimizer->optimize($opacDefaultBookCover, 'banner');
        }
        if ($logo) {
            $this->assetOptimizer->optimize($logo, 'logo');
        }
        if ($loginModalLogo) {
            $this->assetOptimizer->optimize($loginModalLogo, 'logo');
        }
        if ($attendanceRegisterLogo) {
            $this->assetOptimizer->optimize($attendanceRegisterLogo, 'logo');
        }
        if ($libraryRegisterLogo) {
            $this->assetOptimizer->optimize($libraryRegisterLogo, 'logo');
        }

        // Store optimized files
        $newBanner = $banner?->store('branding/banners', 'public');
        $newOpacBanner = $opacBanner?->store('branding/banners', 'public');
        $newOpacLogo = $opacLogo?->store('branding/opac', 'public');
        $newOpacDefaultBookCover = $opacDefaultBookCover?->store('branding/opac', 'public');
        $newLogo = $logo?->store('branding/logos', 'public');
        $newLoginModalLogo = $loginModalLogo?->store('branding/login-modal', 'public');
        $newAttendanceRegisterLogo = $attendanceRegisterLogo?->store('branding/register-modal', 'public');
        $newLibraryRegisterLogo = $libraryRegisterLogo?->store('branding/register-modal', 'public');

        try {
            DB::transaction(function () use ($settings, $values, $user, $newBanner, $newOpacBanner, $newOpacLogo, $newOpacDefaultBookCover, $newLogo, $newLoginModalLogo, $newAttendanceRegisterLogo, $newLibraryRegisterLogo): void {
                // Take a snapshot before applying changes
                $this->takeSnapshot($settings, $user);

                foreach (self::COLOR_FIELDS as $field) {
                    if (array_key_exists($field, $values)) {
                        $settings->{$field} = $values[$field] ? strtoupper((string) $values[$field]) : null;
                    }
                }

                foreach (self::TEXT_FIELDS as $field) {
                    if (array_key_exists($field, $values)) {
                        $settings->{$field} = filled($values[$field]) ? trim((string) $values[$field]) : null;
                    }
                }

                if ($newBanner) {
                    $settings->banner_path = $newBanner;
                }

                if ($newOpacBanner) {
                    $settings->opac_banner_path = $newOpacBanner;
                }

                if ($newOpacLogo) {
                    $settings->opac_logo_path = $newOpacLogo;
                }

                if ($newOpacDefaultBookCover) {
                    $settings->opac_default_book_cover_path = $newOpacDefaultBookCover;
                }

                if ($newLogo) {
                    $settings->sidebar_logo_path = $newLogo;
                }

                if ($newLoginModalLogo) {
                    $settings->login_modal_logo_path = $newLoginModalLogo;
                }

                if ($newAttendanceRegisterLogo) {
                    $settings->register_modal_attendance_logo_path = $newAttendanceRegisterLogo;
                }

                if ($newLibraryRegisterLogo) {
                    $settings->register_modal_library_logo_path = $newLibraryRegisterLogo;
                }

                $settings->updated_by = $user->getKey();
                $settings->save();
            });
        } catch (\Throwable $exception) {
            $this->deleteCustomFile($newBanner);
            $this->deleteCustomFile($newOpacBanner);
            $this->deleteCustomFile($newOpacLogo);
            $this->deleteCustomFile($newOpacDefaultBookCover);
            $this->deleteCustomFile($newLogo);
            $this->deleteCustomFile($newLoginModalLogo);
            $this->deleteCustomFile($newAttendanceRegisterLogo);
            $this->deleteCustomFile($newLibraryRegisterLogo);

            throw $exception;
        }

        $this->clearCache();
        $this->deleteReplacedFile($oldBanner, $settings->banner_path);
        $this->deleteReplacedFile($oldOpacBanner, $settings->opac_banner_path);
        $this->deleteReplacedFile($oldOpacLogo, $settings->opac_logo_path);
        $this->deleteReplacedFile($oldOpacDefaultBookCover, $settings->opac_default_book_cover_path);
        $this->deleteReplacedFile($oldLogo, $settings->sidebar_logo_path);
        $this->deleteReplacedFile($oldLoginModalLogo, $settings->login_modal_logo_path);
        $this->deleteReplacedFile($oldAttendanceRegisterLogo, $settings->register_modal_attendance_logo_path);
        $this->deleteReplacedFile($oldLibraryRegisterLogo, $settings->register_modal_library_logo_path);

        $this->logUpdateActivity($old, $settings, $user);

        return $settings->fresh('updater');
    }

    /**
     * Take a snapshot of the current branding settings for version history.
     */
    private function takeSnapshot(BrandingSetting $settings, User $user): void
    {
        if (! $settings->exists) {
            return; // Don't snapshot a new record before first save
        }

        $snapshot = [];
        foreach (array_keys($this->defaults()) as $field) {
            $snapshot[$field] = $settings->{$field};
        }

        BrandingVersion::query()->create([
            'branding_setting_id' => $settings->getKey(),
            'snapshot' => $snapshot,
            'changed_by' => $user->getKey(),
        ]);
    }

    /**
     * Restore branding settings from a specific version snapshot.
     */
    public function restoreFromVersion(int $versionId, User $user): BrandingSetting
    {
        $version = BrandingVersion::query()->findOrFail($versionId);
        $settings = $this->settings() ?? new BrandingSetting;

        DB::transaction(function () use ($version, $settings, $user): void {
            $this->takeSnapshot($settings, $user);

            foreach ($version->snapshot as $field => $value) {
                $settings->{$field} = $value;
            }

            $settings->updated_by = $user->getKey();
            $settings->save();
        });

        $this->clearCache();

        $this->activityLogger->log(
            module: 'branding',
            type: 'branding_restore',
            title: 'Branding restored from version #'.$versionId,
            body: 'Restored branding settings to the state saved on '.$version->created_at->format('M j, Y g:i A').'.',
            subject: $settings,
            actionUrl: route('developer.branding.versions', absolute: false),
            icon: 'restore',
        );

        return $settings->fresh('updater');
    }

    /**
     * Get paginated version history.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<BrandingVersion>
     */
    public function getVersions(int $perPage = 20)
    {
        return BrandingVersion::query()
            ->with('changer')
            ->latest('id')
            ->paginate($perPage);
    }

    /** @param array<string, mixed> $old */
    private function logUpdateActivity(array $old, BrandingSetting $settings, User $user): void
    {
        $changed = [];
        $fields = array_keys(config('branding.defaults', []));

        foreach ($fields as $field) {
            $newVal = $settings->{$field};
            if ((string) $old[$field] !== (string) $newVal) {
                $changed[] = $field;
            }
        }

        if ($changed === []) {
            return;
        }

        $title = 'Branding settings updated';
        $body = 'Changed: '.implode(', ', $changed).'.';
        $actionRoute = $this->determineActionRoute($changed);

        $this->activityLogger->log(
            module: 'branding',
            type: 'branding_update',
            title: $title,
            body: $body,
            subject: $settings,
            actionUrl: route($actionRoute, absolute: false),
            icon: 'palette',
        );
    }

    /** @param list<string> $changedFields */
    private function determineActionRoute(array $changedFields): string
    {
        $allLoginModal = collect($changedFields)->every(fn (string $field): bool => in_array($field, self::LOGIN_MODAL_FIELDS, true));
        $allRegisterModal = collect($changedFields)->every(fn (string $field): bool => in_array($field, self::REGISTER_MODAL_FIELDS, true));

        if ($allLoginModal && ! $allRegisterModal) {
            return 'developer.login-modal.edit';
        }

        if ($allRegisterModal && ! $allLoginModal) {
            return 'developer.register-modal.edit';
        }

        return 'developer.branding.edit';
    }

    private function deleteCustomFile(string|false|null $path): void
    {
        if (is_string($path) && str_starts_with($path, 'branding/')) {
            Storage::disk('public')->delete($path);
        }
    }

    public function restore(?string $field, User $user): BrandingSetting
    {
        $settings = $this->settings() ?? new BrandingSetting;
        $fields = array_keys($this->defaults());
        $oldPaths = [];

        if ($field !== null && in_array($field, $fields, true)) {
            $oldVal = $settings->{$field};
            $oldPath = $settings->{$field};
            $settings->{$field} = null;
            $settings->updated_by = $user->getKey();
            $settings->save();
            $this->clearCache();
            $this->deleteReplacedFile($oldPath, null);

            $this->activityLogger->log(
                module: 'branding',
                type: 'branding_restore',
                title: 'Branding value restored: '.$field,
                body: 'Restored '.$field.' to its original Pantas default.',
                subject: $settings,
                actionUrl: $this->restoreActionUrlForField($field),
                icon: 'restore',
            );

            return $settings->fresh('updater');
        }

        $oldPaths = collect(self::ASSET_FIELDS)
            ->map(fn (string $assetField): ?string => $settings->{$assetField})
            ->all();
        foreach ($fields as $defaultField) {
            $settings->{$defaultField} = null;
        }
        $settings->updated_by = $user->getKey();
        $settings->save();
        $this->clearCache();

        foreach ($oldPaths as $path) {
            $this->deleteReplacedFile($path, null);
        }

        $this->activityLogger->log(
            module: 'branding',
            type: 'branding_restore_all',
            title: 'All branding restored to Pantas defaults',
            body: 'Full restoration of banner, sidebar logo, and all colors.',
            subject: $settings,
            actionUrl: route('developer.branding.edit', absolute: false),
            icon: 'restore',
        );

        return $settings->fresh('updater');
    }

    private function restoreActionUrlForField(string $field): string
    {
        if (in_array($field, self::LOGIN_MODAL_FIELDS, true)) {
            return route('developer.login-modal.edit', absolute: false);
        }

        if (in_array($field, self::REGISTER_MODAL_FIELDS, true)) {
            return route('developer.register-modal.edit', absolute: false);
        }

        return route('developer.branding.edit', absolute: false);
    }

    public function restoreLoginModal(?string $field, User $user): BrandingSetting
    {
        if ($field !== null) {
            if (! in_array($field, self::LOGIN_MODAL_FIELDS, true)) {
                throw new \InvalidArgumentException('The requested field is not a login modal setting.');
            }

            return $this->restoreField(
                $field,
                $user,
                route('developer.login-modal.edit', absolute: false),
            );
        }

        $settings = $this->settings() ?? new BrandingSetting;
        $oldLogo = $settings->login_modal_logo_path;

        DB::transaction(function () use ($settings, $user): void {
            $this->takeSnapshot($settings, $user);
            foreach (self::LOGIN_MODAL_FIELDS as $loginModalField) {
                $settings->{$loginModalField} = null;
            }

            $settings->updated_by = $user->getKey();
            $settings->save();
        });

        $this->clearCache();
        $this->deleteReplacedFile($oldLogo, null);

        $this->activityLogger->log(
            module: 'branding',
            type: 'branding_restore_group',
            title: 'Login modal restored to Pantas defaults',
            body: 'Restored all login modal assets, text, and colors to their original Pantas defaults.',
            subject: $settings,
            actionUrl: route('developer.login-modal.edit', absolute: false),
            icon: 'restore',
        );

        return $settings->fresh('updater');
    }

    public function restoreRegisterModal(?string $field, User $user): BrandingSetting
    {
        if ($field !== null) {
            if (! in_array($field, self::REGISTER_MODAL_FIELDS, true)) {
                throw new \InvalidArgumentException('The requested field is not a register modal setting.');
            }

            return $this->restoreField(
                $field,
                $user,
                route('developer.register-modal.edit', absolute: false),
            );
        }

        $settings = $this->settings() ?? new BrandingSetting;
        $oldAttendanceLogo = $settings->register_modal_attendance_logo_path;
        $oldLibraryLogo = $settings->register_modal_library_logo_path;

        DB::transaction(function () use ($settings, $user): void {
            $this->takeSnapshot($settings, $user);
            foreach (self::REGISTER_MODAL_FIELDS as $registerModalField) {
                $settings->{$registerModalField} = null;
            }

            $settings->updated_by = $user->getKey();
            $settings->save();
        });

        $this->clearCache();
        $this->deleteReplacedFile($oldAttendanceLogo, null);
        $this->deleteReplacedFile($oldLibraryLogo, null);

        $this->activityLogger->log(
            module: 'branding',
            type: 'branding_restore_group',
            title: 'Register modal restored to Pantas defaults',
            body: 'Restored all register modal assets, text, and colors to their original Pantas defaults.',
            subject: $settings,
            actionUrl: route('developer.register-modal.edit', absolute: false),
            icon: 'restore',
        );

        return $settings->fresh('updater');
    }

    private function restoreField(string $field, User $user, string $actionUrl): BrandingSetting
    {
        $settings = $this->settings() ?? new BrandingSetting;
        $oldPath = $settings->{$field};
        $settings->{$field} = null;
        $settings->updated_by = $user->getKey();
        $settings->save();
        $this->clearCache();
        $this->deleteReplacedFile($oldPath, null);

        $this->activityLogger->log(
            module: 'branding',
            type: 'branding_restore',
            title: 'Branding value restored: '.$field,
            body: 'Restored '.$field.' to its original Pantas default.',
            subject: $settings,
            actionUrl: $actionUrl,
            icon: 'restore',
        );

        return $settings->fresh('updater');
    }

    public function settings(): ?BrandingSetting
    {
        if (! Schema::hasTable('branding_settings')) {
            return null;
        }

        return BrandingSetting::query()->with('updater')->first();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function deleteReplacedFile(?string $oldPath, ?string $newPath): void
    {
        if ($oldPath && $oldPath !== $newPath && str_starts_with($oldPath, 'branding/')) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
