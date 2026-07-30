<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_book_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('library_students')->cascadeOnDelete();
            // The sample book id passed from the mobile app (one copy in the group).
            // Reservations are grouped by title + author + pub_year, so any copy
            // in the group becoming available can fulfil the reservation.
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, ready, fulfilled, expired, cancelled
            $table->unsignedInteger('queue_position')->default(0);
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['book_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_book_reservations');
    }
};