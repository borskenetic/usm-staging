<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('library_book_reservations')) {
            Schema::create('library_book_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('library_students')->cascadeOnDelete();
                $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
                $table->string('status')->default('pending');
                $table->unsignedInteger('queue_position')->default(0);
                $table->timestamp('reserved_at')->useCurrent();
                $table->timestamp('available_at')->nullable();
                $table->timestamp('hold_expires_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['book_id', 'status']);
                $table->index(['student_id', 'status']);
            });

            return;
        }

        Schema::table('library_book_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('library_book_reservations', 'queue_position')) {
                $table->unsignedInteger('queue_position')->default(0)->after('status');
            }

            if (! Schema::hasColumn('library_book_reservations', 'reserved_at')) {
                $table->timestamp('reserved_at')->nullable()->after('queue_position');
            }

            if (! Schema::hasColumn('library_book_reservations', 'available_at')) {
                $table->timestamp('available_at')->nullable()->after('reserved_at');
            }

            if (! Schema::hasColumn('library_book_reservations', 'hold_expires_at')) {
                $table->timestamp('hold_expires_at')->nullable()->after('available_at');
            }
        });

        // Backfill mobile columns from legacy data.
        if (Schema::hasColumn('library_book_reservations', 'ready_at')) {
            DB::table('library_book_reservations')
                ->whereNull('available_at')
                ->whereNotNull('ready_at')
                ->update(['available_at' => DB::raw('ready_at')]);
        }

        DB::table('library_book_reservations')
            ->whereNull('reserved_at')
            ->update(['reserved_at' => DB::raw('created_at')]);

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            $indexes = collect(DB::select('SHOW INDEX FROM library_book_reservations'))
                ->pluck('Key_name')
                ->unique();

            Schema::table('library_book_reservations', function (Blueprint $table) use ($indexes) {
                if (! $indexes->contains('library_book_reservations_book_id_status_index')) {
                    $table->index(['book_id', 'status']);
                }

                if (! $indexes->contains('library_book_reservations_student_id_status_index')) {
                    $table->index(['student_id', 'status']);
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('library_book_reservations')) {
            return;
        }

        Schema::table('library_book_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('library_book_reservations', 'hold_expires_at')) {
                $table->dropColumn('hold_expires_at');
            }

            if (Schema::hasColumn('library_book_reservations', 'available_at')) {
                $table->dropColumn('available_at');
            }

            if (Schema::hasColumn('library_book_reservations', 'reserved_at')) {
                $table->dropColumn('reserved_at');
            }

            if (Schema::hasColumn('library_book_reservations', 'queue_position')) {
                $table->dropColumn('queue_position');
            }
        });
    }
};
