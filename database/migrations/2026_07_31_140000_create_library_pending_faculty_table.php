<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_pending_faculty', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('middle_initial')->nullable();
            $table->string('email')->nullable();
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->date('birthday')->nullable();
            $table->string('mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->text('emergency_address')->nullable();
            $table->string('profile_picture')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_pending_faculty');
    }
};
