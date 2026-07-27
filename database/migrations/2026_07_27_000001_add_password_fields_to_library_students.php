<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_students', function (Blueprint $table) {
            $table->string('password')->nullable()->after('student_signature');
            $table->boolean('password_setup_completed')->default(false)->after('password');
            $table->boolean('force_password_reset')->default(false)->after('password_setup_completed');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('force_password_reset');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('library_students', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'password_setup_completed',
                'force_password_reset',
                'failed_login_attempts',
                'locked_until',
            ]);
        });
    }
};