<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Book;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Faculty;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_faculty_creates_assignment_student_submits_and_faculty_completes(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $book = $this->book();

        $classroom = Classroom::query()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Hybrid Class',
            'join_code' => 'HYBRID01',
            'requires_approval' => false,
            'is_private' => true,
        ]);

        ClassroomMember::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'status' => ClassroomMember::STATUS_APPROVED,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($faculty, ['full-access']);

        $create = $this->postJson("/api/mobile/faculty/classrooms/{$classroom->id}/assignments", [
            'title' => 'Read chapter 1',
            'instructions' => 'Summarize key points',
            'due_at' => now()->addDays(7)->toIso8601String(),
            'book_ids' => [$book->id],
        ])->assertCreated();

        $assignmentId = $create->json('data.id');
        $this->assertSame(1, $create->json('data.book_count'));
        $this->assertSame(1, $create->json('data.submission_counts.assigned'));

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/assignments')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Read chapter 1');

        $this->postJson("/api/mobile/assignments/{$assignmentId}/submit", [
            'response_text' => 'Main idea is X.',
        ])
            ->assertOk()
            ->assertJsonPath('data.my_submission.status', 'submitted');

        Sanctum::actingAs($faculty, ['full-access']);

        $this->getJson("/api/mobile/faculty/assignments/{$assignmentId}/submissions")
            ->assertOk()
            ->assertJsonPath('data.0.status', 'submitted');

        $this->postJson("/api/mobile/faculty/assignments/{$assignmentId}/submissions/{$student->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('library_assignment_submissions', [
            'assignment_id' => $assignmentId,
            'student_id' => $student->id,
            'status' => AssignmentSubmission::STATUS_COMPLETED,
        ]);
    }

    public function test_new_assignment_is_not_completed_on_any_endpoint(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();

        $classroom = Classroom::query()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Fresh Class',
            'join_code' => 'FRESH001',
            'requires_approval' => false,
        ]);

        ClassroomMember::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'status' => ClassroomMember::STATUS_APPROVED,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($faculty, ['full-access']);

        $create = $this->postJson("/api/mobile/faculty/classrooms/{$classroom->id}/assignments", [
            'title' => 'New homework',
        ])->assertCreated();

        $assignmentId = $create->json('data.id');

        $create->assertJsonPath('data.submission_counts.assigned', 1)
            ->assertJsonPath('data.submission_counts.completed', 0)
            ->assertJsonPath('data.submission_counts.total', 1);

        $this->getJson("/api/mobile/faculty/classrooms/{$classroom->id}/assignments")
            ->assertOk()
            ->assertJsonPath('data.0.submission_counts.completed', 0)
            ->assertJsonPath('data.0.submission_counts.total', 1);

        $this->getJson("/api/mobile/faculty/assignments/{$assignmentId}/submissions")
            ->assertOk()
            ->assertJsonPath('data.0.status', 'assigned');

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/assignments')
            ->assertOk()
            ->assertJsonPath('data.0.my_submission.status', 'assigned');

        $this->getJson("/api/mobile/classrooms/{$classroom->id}/assignments")
            ->assertOk()
            ->assertJsonPath('data.0.my_submission.status', 'assigned');

        $this->getJson("/api/mobile/assignments/{$assignmentId}")
            ->assertOk()
            ->assertJsonPath('data.my_submission.status', 'assigned')
            ->assertJsonPath('data.status', 'published');
    }

    public function test_student_can_mark_assignment_done_without_text(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();

        $classroom = Classroom::query()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Done Class',
            'join_code' => 'DONE0001',
            'requires_approval' => false,
        ]);

        ClassroomMember::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'status' => ClassroomMember::STATUS_APPROVED,
            'joined_at' => now(),
        ]);

        $assignment = Assignment::query()->create([
            'classroom_id' => $classroom->id,
            'faculty_id' => $faculty->id,
            'title' => 'Quick task',
            'status' => Assignment::STATUS_PUBLISHED,
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson("/api/mobile/assignments/{$assignment->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.my_submission.status', 'completed');
    }

    private function faculty(): Faculty
    {
        return Faculty::query()->create([
            'employee_id' => 'EMP-ASSIGN-1',
            'lastname' => 'Teacher',
            'firstname' => 'Ann',
            'password' => Hash::make('password'),
            'password_setup_completed' => true,
            'account_status' => 'Active',
        ]);
    }

    private function student(): Student
    {
        return Student::query()->create([
            'id_number' => '24-ASSIGN1',
            'lastname' => 'Learner',
            'firstname' => 'Bob',
            'qrcode' => 'S-ASSIGN1',
            'password' => Hash::make('password'),
            'password_setup_completed' => true,
        ]);
    }

    private function book(): Book
    {
        return Book::query()->create([
            'title_statement' => 'Assigned Book',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-ASSIGN-'.uniqid(),
            'call_number' => 'QA 222',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);
    }
}
