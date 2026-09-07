<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const LEGACY_BLUES = [
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

    /** @var list<string> */
    private const COLOR_COLUMNS = [
        'primary_color',
        'button_color',
        'table_hover_color',
        'login_modal_left_background_color',
        'login_modal_description_color',
        'login_modal_button_color',
        'register_modal_library_panel_color',
        'register_modal_library_accent_color',
        'register_modal_library_submit_color',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('branding_settings')) {
            return;
        }

        foreach (self::COLOR_COLUMNS as $column) {
            if (! Schema::hasColumn('branding_settings', $column)) {
                continue;
            }

            DB::table('branding_settings')
                ->whereIn($column, self::LEGACY_BLUES)
                ->update([$column => null]);
        }

        Cache::forget('branding.active');
    }

    public function down(): void
    {
        // Non-destructive: legacy blues were cleared so current green defaults apply.
    }
};
