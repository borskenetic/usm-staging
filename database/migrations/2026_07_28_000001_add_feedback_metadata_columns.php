<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_feedback', function (Blueprint $table) {
            $table->string('category')->nullable()->after('email');
            $table->string('source')->default('web')->after('category');
            $table->foreignId('student_id')
                ->nullable()
                ->after('source')
                ->constrained('library_students')
                ->nullOnDelete();
            $table->timestamp('read_at')->nullable()->after('comments');
        });
    }

    public function down(): void
    {
        Schema::table('library_feedback', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn(['category', 'source', 'read_at']);
        });
    }
};
