<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_fine_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('library_fine_settings', 'sublocation')) {
                $table->string('sublocation', 100)->nullable()->after('id');
                $table->index('sublocation');
            }
            if (! Schema::hasColumn('library_fine_settings', 'student_max_loans')) {
                $table->unsignedInteger('student_max_loans')->nullable();
            }
            if (! Schema::hasColumn('library_fine_settings', 'employee_max_loans')) {
                $table->unsignedInteger('employee_max_loans')->nullable();
            }
            if (! Schema::hasColumn('library_fine_settings', 'employee_loans_unlimited')) {
                $table->boolean('employee_loans_unlimited')->nullable();
            }
            if (! Schema::hasColumn('library_fine_settings', 'policy_max_renewals')) {
                $table->unsignedInteger('policy_max_renewals')->nullable();
            }
            if (! Schema::hasColumn('library_fine_settings', 'policy_reborrow_cooldown_days')) {
                $table->unsignedInteger('policy_reborrow_cooldown_days')->nullable();
            }
            if (! Schema::hasColumn('library_fine_settings', 'policy_reservation_hold_days')) {
                $table->unsignedInteger('policy_reservation_hold_days')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('library_fine_settings', function (Blueprint $table) {
            if (Schema::hasColumn('library_fine_settings', 'sublocation')) {
                $table->dropIndex(['sublocation']);
                $table->dropColumn('sublocation');
            }

            $columns = [
                'student_max_loans',
                'employee_max_loans',
                'employee_loans_unlimited',
                'policy_max_renewals',
                'policy_reborrow_cooldown_days',
                'policy_reservation_hold_days',
            ];

            $present = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn('library_fine_settings', $column)
            ));

            if ($present !== []) {
                $table->dropColumn($present);
            }
        });
    }
};
