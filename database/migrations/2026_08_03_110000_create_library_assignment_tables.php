<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('library_classrooms')->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('library_faculty')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status')->default('published'); // published|archived
            $table->timestamps();

            $table->index(['classroom_id', 'status']);
        });

        Schema::create('library_assignment_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('library_assignments')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['assignment_id', 'book_id']);
        });

        Schema::create('library_assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('library_assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('library_students')->cascadeOnDelete();
            $table->string('status')->default('assigned'); // assigned|submitted|completed
            $table->text('response_text')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_assignment_submissions');
        Schema::dropIfExists('library_assignment_books');
        Schema::dropIfExists('library_assignments');
    }
};
