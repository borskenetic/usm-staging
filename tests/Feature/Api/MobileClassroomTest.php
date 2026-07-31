<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Faculty;
use App\Models\FacultyFolder;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileClassroomTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_faculty_can_create_classroom_and_share_folder(): void
    {
        $faculty = $this->faculty();
        $book = $this->book();

        Sanctum::actingAs($faculty, ['full-access']);

        $create = $this->postJson('/api/mobile/faculty/classrooms', [
            'name' => 'Web Dev Class',
            'requires_approval' => false,
        ])->assertCreated();

        $classroomId = $create->json('data.id');
        $this->assertNotEmpty($create->json('data.join_code'));

        $folder = $this->postJson('/api/mobile/faculty/folders', [
            'name' => 'Week 1',
        ])->assertCreated()->json('data');

        $this->postJson("/api/mobile/faculty/folders/{$folder['id']}/books", [
            'book_ids' => [$book->id],
        ])->assertOk();

        $this->postJson("/api/mobile/faculty/classrooms/{$classroomId}/folders", [
            'folder_id' => $folder['id'],
        ])->assertOk();

        $this->getJson("/api/mobile/faculty/classrooms/{$classroomId}")
            ->assertOk()
            ->assertJsonPath('data.folder_count', 1);
    }

    public function test_student_auto_joins_and_sees_faculty_recommendations(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $book = $this->book();

        $classroom = Classroom::query()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Auto Join Room',
            'join_code' => 'AUTOJOIN1',
            'requires_approval' => false,
            'is_private' => true,
        ]);

        $folder = FacultyFolder::query()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Reads',
        ]);
        $folder->books()->attach($book->id);
        $classroom->folders()->attach($folder->id);

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson('/api/mobile/classrooms/join', [
            'join_code' => 'AUTOJOIN1',
        ])
            ->assertCreated()
            ->assertJsonPath('data.membership.status', 'approved');

        $this->getJson('/api/mobile/home/faculty-recommendations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.classroom.name', 'Auto Join Room');

        $this->getJson('/api/mobile/home')
            ->assertOk()
            ->assertJsonStructure(['data' => ['faculty_recommendations', 'recommended_books']]);
    }

    public function test_approval_required_join_stays_pending_until_faculty_approves(): void
    {
        $faculty = $this->faculty('EMP-20002');
        $student = $this->student('24-30002');

        $classroom = Classroom::query()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Approval Room',
            'join_code' => 'NEEDAPPR',
            'requires_approval' => true,
            'is_private' => true,
        ]);

        Sanctum::actingAs($student, ['full-access']);
        $join = $this->postJson('/api/mobile/classrooms/join', [
            'join_code' => 'NEEDAPPR',
        ])->assertCreated();

        $memberId = $join->json('data.membership.id');
        $this->assertSame('pending', $join->json('data.membership.status'));

        Sanctum::actingAs($faculty, ['full-access']);
        $this->postJson("/api/mobile/faculty/classrooms/{$classroom->id}/members/{$memberId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('library_classroom_members', [
            'id' => $memberId,
            'status' => ClassroomMember::STATUS_APPROVED,
        ]);
    }

    private function faculty(string $employeeId = 'EMP-20001'): Faculty
    {
        return Faculty::query()->create([
            'employee_id' => $employeeId,
            'lastname' => 'Santos',
            'firstname' => 'Maria',
            'account_status' => 'Active',
            'password' => Hash::make('password'),
            'password_setup_completed' => true,
        ]);
    }

    private function student(string $idNumber = '24-30001'): Student
    {
        return Student::query()->create([
            'id_number' => $idNumber,
            'lastname' => 'Cruz',
            'firstname' => 'Ana',
            'qrcode' => 'S-'.$idNumber,
            'password' => Hash::make('password'),
            'password_setup_completed' => true,
        ]);
    }

    private function book(): Book
    {
        return Book::query()->create([
            'title_statement' => 'Classroom Rec Book',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-CR-'.uniqid(),
            'call_number' => 'QA 111',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);
    }
}
