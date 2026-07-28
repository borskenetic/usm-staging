<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\LibraryAttendanceLog;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAttendancePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_student_can_fetch_attendance_preview_with_paired_visits(): void
    {
        $student = Student::query()->create([
            'id_number' => 'S-100',
            'lastname' => 'Student',
            'firstname' => 'Test',
            'qrcode' => 'S-100',
        ]);

        $scanIn = Carbon::parse('2026-07-28 08:00:00', 'Asia/Manila');
        LibraryAttendanceLog::query()->create([
            'student_id' => $student->id,
            'status' => 'IN',
            'section' => 'Reading Area',
            'scanned_at' => $scanIn,
        ]);
        LibraryAttendanceLog::query()->create([
            'student_id' => $student->id,
            'status' => 'OUT',
            'section' => 'Reading Area',
            'scanned_at' => $scanIn->copy()->addHours(2),
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/attendance/preview')
            ->assertOk()
            ->assertJsonCount(1, 'data.recent_visits')
            ->assertJsonPath('data.monthly_visit_count', 1)
            ->assertJsonPath('data.recent_visits.0.duration_minutes', 120);
    }
}
