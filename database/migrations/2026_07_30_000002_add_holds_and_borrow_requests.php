<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_book_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('library_book_reservations', 'held_book_id')) {
                $table->foreignId('held_book_id')
                    ->nullable()
                    ->after('book_id')
                    ->constrained('library_books')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('library_book_reservations', 'fulfilled_at')) {
                $table->timestamp('fulfilled_at')->nullable()->after('hold_expires_at');
            }

            if (! Schema::hasColumn('library_book_reservations', 'fulfilled_by')) {
                $table->foreignId('fulfilled_by')
                    ->nullable()
                    ->after('fulfilled_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::create('library_borrow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('library_students')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('requested_at')->useCurrent();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('staff_note')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index('status');
        });

        Schema::create('library_borrow_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrow_request_id')
                ->constrained('library_borrow_requests')
                ->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['borrow_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_borrow_request_items');
        Schema::dropIfExists('library_borrow_requests');

        Schema::table('library_book_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('library_book_reservations', 'fulfilled_by')) {
                $table->dropConstrainedForeignId('fulfilled_by');
            }

            if (Schema::hasColumn('library_book_reservations', 'fulfilled_at')) {
                $table->dropColumn('fulfilled_at');
            }

            if (Schema::hasColumn('library_book_reservations', 'held_book_id')) {
                $table->dropConstrainedForeignId('held_book_id');
            }
        });
    }
};
