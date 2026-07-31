<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('library_faculty')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('subject')->nullable();
            $table->string('join_code', 16)->unique();
            $table->boolean('is_private')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->timestamps();
        });

        Schema::create('library_classroom_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('library_classrooms')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('library_students')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending|approved|rejected|left
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['classroom_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('library_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('library_faculty')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('library_folder_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('library_folders')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['folder_id', 'book_id']);
        });

        Schema::create('library_classroom_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('library_classrooms')->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained('library_folders')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['classroom_id', 'folder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_classroom_folders');
        Schema::dropIfExists('library_folder_books');
        Schema::dropIfExists('library_folders');
        Schema::dropIfExists('library_classroom_members');
        Schema::dropIfExists('library_classrooms');
    }
};
