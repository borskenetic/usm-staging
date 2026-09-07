<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_fine_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('library_fine_settings', 'student_fine_per_day')) {
                $table->decimal('student_fine_per_day', 8, 2)->nullable()->after('fine_per_day');
            }
            if (! Schema::hasColumn('library_fine_settings', 'student_max_fine')) {
                $table->decimal('student_max_fine', 8, 2)->nullable()->after('student_fine_per_day');
            }
            if (! Schema::hasColumn('library_fine_settings', 'student_grace_period_days')) {
                $table->unsignedInteger('student_grace_period_days')->nullable()->after('student_max_fine');
            }
            if (! Schema::hasColumn('library_fine_settings', 'student_loan_duration_days')) {
                $table->unsignedInteger('student_loan_duration_days')->nullable()->after('student_grace_period_days');
            }
            if (! Schema::hasColumn('library_fine_settings', 'employee_fine_per_day')) {
                $table->decimal('employee_fine_per_day', 8, 2)->nullable()->after('student_loan_duration_days');
            }
            if (! Schema::hasColumn('library_fine_settings', 'employee_max_fine')) {
                $table->decimal('employee_max_fine', 8, 2)->nullable()->after('employee_fine_per_day');
            }
            if (! Schema::hasColumn('library_fine_settings', 'employee_grace_period_days')) {
                $table->unsignedInteger('employee_grace_period_days')->nullable()->after('employee_max_fine');
            }
            if (! Schema::hasColumn('library_fine_settings', 'employee_loan_duration_days')) {
                $table->unsignedInteger('employee_loan_duration_days')->nullable()->after('employee_grace_period_days');
            }
        });

        $rows = DB::table('library_fine_settings')->get();

        foreach ($rows as $row) {
            DB::table('library_fine_settings')->where('id', $row->id)->update([
                'student_fine_per_day' => $row->student_fine_per_day ?? $row->fine_per_day,
                'student_max_fine' => $row->student_max_fine ?? $row->max_fine,
                'student_grace_period_days' => $row->student_grace_period_days ?? $row->grace_period_days,
                'student_loan_duration_days' => $row->student_loan_duration_days ?? $row->loan_duration_days,
                'employee_fine_per_day' => $row->employee_fine_per_day ?? $row->fine_per_day,
                'employee_max_fine' => $row->employee_max_fine ?? $row->max_fine,
                'employee_grace_period_days' => $row->employee_grace_period_days ?? $row->grace_period_days,
                'employee_loan_duration_days' => $row->employee_loan_duration_days ?? $row->loan_duration_days,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('library_fine_settings', function (Blueprint $table) {
            $table->dropColumn([
                'student_fine_per_day',
                'student_max_fine',
                'student_grace_period_days',
                'student_loan_duration_days',
                'employee_fine_per_day',
                'employee_max_fine',
                'employee_grace_period_days',
                'employee_loan_duration_days',
            ]);
        });
    }
};
