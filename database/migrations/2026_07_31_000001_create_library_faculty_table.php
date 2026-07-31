<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_faculty', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('lastname');
            $table->string('firstname');
            $table->string('middle_initial')->nullable();
            $table->string('email')->nullable();
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->date('birthday')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('account_status')->default('Active');
            $table->string('password')->nullable();
            $table->boolean('password_setup_completed')->default(false);
            $table->boolean('force_password_reset')->default(false);
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_faculty');
    }
};
