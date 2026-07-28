<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\BookLog;
use App\Models\Program;
use App\Models\ReservationStudent;
use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAggregateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_student_can_fetch_mobile_home_aggregate(): void
    {
        $student = $this->student();
        $book = $this->book();

        BookLog::query()->create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'patron_name' => 'Test Student',
            'status' => 'Checked Out',
            'circulation_type' => BookLog::CIRCULATION_CHECKOUT,
            'renew_count' => 0,
            'timestamp' => Carbon::now('Asia/Manila'),
            'due_date' => Carbon::now('Asia/Manila')->addDays(2)->toDateString(),
            'fine_incurred' => 0,
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/home')
            ->assertOk()
            ->assertHeader('ETag')
            ->assertJsonPath('data.loan_stats.active_count', 1)
            ->assertJsonCount(1, 'data.new_arrivals')
            ->assertJsonCount(1, 'data.active_loans');
    }

    public function test_student_can_fetch_borrow_overview(): void
    {
        $student = $this->student();
        $book = $this->book();

        BookLog::query()->create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'patron_name' => 'Test Student',
            'status' => 'Checked Out',
            'circulation_type' => BookLog::CIRCULATION_CHECKOUT,
            'renew_count' => 0,
            'timestamp' => Carbon::now('Asia/Manila'),
            'due_date' => Carbon::now('Asia/Manila')->addDays(7)->toDateString(),
            'fine_incurred' => 0,
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/borrow-overview')
            ->assertOk()
            ->assertJsonPath('data.limits.current_active_loans', 1)
            ->assertJsonCount(1, 'data.active_loans')
            ->assertJsonCount(1, 'data.history');
    }

    public function test_room_dashboard_defaults_to_first_room(): void
    {
        $student = $this->student();
        $room = Room::query()->create([
            'name' => 'Discussion Room',
            'description' => 'Small group room',
            'capacity' => 6,
        ]);
        $reservation = RoomReservation::query()->create([
            'room_id' => $room->id,
            'student_id' => $student->id,
            'status' => 'pending',
            'date' => '2026-06-19',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'patron_email' => 'S-100',
            'number_of_students' => 1,
        ]);
        ReservationStudent::query()->create([
            'reservation_id' => $reservation->id,
            'name' => 'Test Student',
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/rooms/dashboard?date=2026-06-19')
            ->assertOk()
            ->assertJsonPath('data.availability.room_id', $room->id)
            ->assertJsonCount(1, 'data.rooms')
            ->assertJsonCount(1, 'data.reservations')
            ->assertJsonCount(1, 'data.availability.booked_slots');
    }

    public function test_mobile_checkout_writes_to_library_circulation_tables(): void
    {
        $student = $this->student();
        $book = $this->book();

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson('/api/mobile/borrow-cart/submit', [
            'book_ids' => [$book->id],
        ])
            ->assertCreated()
            ->assertJsonCount(1, 'data.processed');

        $this->assertDatabaseHas('library_book_logs', [
            'book_id' => $book->id,
            'student_id' => $student->id,
            'status' => 'Checked Out',
        ]);
        $this->assertDatabaseHas('library_books', [
            'id' => $book->id,
            'availability' => 'Borrowed',
        ]);
        $this->assertDatabaseMissing('book_logs', [
            'book_id' => $book->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_mobile_room_reservation_writes_to_library_room_tables(): void
    {
        $student = $this->student();
        $room = Room::query()->create([
            'name' => 'Mobile Room',
            'description' => 'Bookable from mobile',
            'capacity' => 4,
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson('/api/mobile/rooms/reservations', [
            'room_id' => $room->id,
            'date' => Carbon::now('Asia/Manila')->addDay()->toDateString(),
            'start_time' => '9:00',
            'start_ampm' => 'AM',
            'end_time' => '10:00',
            'end_ampm' => 'AM',
            'number_of_students' => 1,
            'student_names' => ['Test Student'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.room.id', $room->id);

        $this->assertDatabaseHas('library_room_reservations', [
            'room_id' => $room->id,
            'student_id' => $student->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('library_reservation_students', [
            'name' => 'Test Student',
        ]);
        $this->assertDatabaseMissing('room_reservations', [
            'room_id' => $room->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_mobile_feedback_writes_to_library_feedback_table(): void
    {
        $student = $this->student();

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson('/api/mobile/feedback', [
            'category' => 'App Issue',
            'comments' => 'The mobile library flow is working.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.category', 'App Issue')
            ->assertJsonPath('data.source', 'mobile')
            ->assertJsonPath('data.comments', 'The mobile library flow is working.');

        $this->assertDatabaseHas('library_feedback', [
            'name' => 'Test Student',
            'category' => 'App Issue',
            'source' => 'mobile',
            'student_id' => $student->id,
            'comments' => 'The mobile library flow is working.',
            'read_at' => null,
        ]);
        $this->assertDatabaseMissing('feedback', [
            'comments' => 'The mobile library flow is working.',
        ]);
    }

    public function test_mobile_feedback_parses_legacy_category_from_comments(): void
    {
        $student = $this->student();

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson('/api/mobile/feedback', [
            'comments' => '[Library Service] Legacy formatted feedback.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.category', 'Library Service');

        $this->assertDatabaseHas('library_feedback', [
            'student_id' => $student->id,
            'category' => 'Library Service',
            'comments' => '[Library Service] Legacy formatted feedback.',
        ]);
    }

    public function test_staff_user_is_rejected_from_student_aggregates(): void
    {
        $user = User::query()->create([
            'fname' => 'Staff',
            'lname' => 'User',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/home')->assertForbidden();
    }

    public function test_home_returns_recommended_books_for_student_program(): void
    {
        $program = Program::query()->create([
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science',
            'total_years' => 4,
        ]);

        $student = Student::query()->create([
            'id_number' => 'S-200',
            'lastname' => 'Student',
            'firstname' => 'Program',
            'qrcode' => 'S-200',
            'course' => 'BSCS',
        ]);

        $recommendedBook = Book::query()->create([
            'title_statement' => 'Algorithms for Program Students',
            'main_author' => 'Knuth',
            'pub_year' => '2024',
            'availability' => 'Available',
            'accession_no' => 'ACC-200',
            'call_number' => 'QA 200',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'Technology',
            'course' => 'Data Structures',
        ]);
        $recommendedBook->programs()->attach($program->id);

        Book::query()->create([
            'title_statement' => 'Unrelated Title',
            'main_author' => 'Other Author',
            'pub_year' => '2020',
            'availability' => 'Available',
            'accession_no' => 'ACC-201',
            'call_number' => 'QA 201',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/home')
            ->assertOk()
            ->assertJsonPath('data.recommendation_context.course', 'BSCS')
            ->assertJsonPath(
                'data.recommendation_context.program_name',
                'Bachelor of Science in Computer Science'
            )
            ->assertJsonCount(1, 'data.recommended_books')
            ->assertJsonPath('data.recommended_books.0.title', 'Algorithms for Program Students');
    }

    public function test_home_returns_empty_recommendations_when_student_has_no_course(): void
    {
        $student = $this->student();

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/home')
            ->assertOk()
            ->assertJsonPath('data.recommendation_context.course', null)
            ->assertJsonPath('data.recommendation_context.program_name', null)
            ->assertJsonCount(0, 'data.recommended_books');
    }

    public function test_home_returns_empty_recommendations_for_unknown_course(): void
    {
        $student = Student::query()->create([
            'id_number' => 'S-300',
            'lastname' => 'Student',
            'firstname' => 'Unknown',
            'qrcode' => 'S-300',
            'course' => 'UNKNOWN',
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/home')
            ->assertOk()
            ->assertJsonPath('data.recommendation_context.course', 'UNKNOWN')
            ->assertJsonPath('data.recommendation_context.program_name', null)
            ->assertJsonCount(0, 'data.recommended_books');
    }

    public function test_recommendations_endpoint_matches_home_recommendations(): void
    {
        $program = Program::query()->create([
            'program_code' => 'BSIT',
            'program_name' => 'Bachelor of Science in Information Technology',
            'total_years' => 4,
        ]);

        $student = Student::query()->create([
            'id_number' => 'S-400',
            'lastname' => 'Student',
            'firstname' => 'Dedicated',
            'qrcode' => 'S-400',
            'course' => 'BSIT',
        ]);

        $book = Book::query()->create([
            'title_statement' => 'Networking Essentials',
            'main_author' => 'Tanenbaum',
            'pub_year' => '2023',
            'availability' => 'Available',
            'accession_no' => 'ACC-400',
            'call_number' => 'TK 400',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'Technology',
        ]);
        $book->programs()->attach($program->id);

        Sanctum::actingAs($student, ['full-access']);

        $homeResponse = $this->getJson('/api/mobile/home')->assertOk();
        $recommendationsResponse = $this->getJson('/api/mobile/home/recommendations')->assertOk();

        $this->assertSame(
            $homeResponse->json('data.recommended_books'),
            $recommendationsResponse->json('data')
        );
    }

    public function test_recommendations_match_books_when_student_course_uses_program_name(): void
    {
        $program = Program::query()->create([
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science',
            'total_years' => 4,
        ]);

        $student = Student::query()->create([
            'id_number' => 'S-500',
            'lastname' => 'Student',
            'firstname' => 'Legacy',
            'qrcode' => 'S-500',
            'course' => 'Bachelor of Science in Computer Science',
        ]);

        $book = Book::query()->create([
            'title_statement' => 'Program Name Match',
            'main_author' => 'Author',
            'pub_year' => '2022',
            'availability' => 'Available',
            'accession_no' => 'ACC-500',
            'call_number' => 'QA 500',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'Technology',
        ]);
        $book->programs()->attach($program->id);

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/home/recommendations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Program Name Match');
    }

    private function student(): Student
    {
        return Student::query()->create([
            'id_number' => 'S-100',
            'lastname' => 'Student',
            'firstname' => 'Test',
            'qrcode' => 'S-100',
        ]);
    }

    private function book(): Book
    {
        return Book::query()->create([
            'title_statement' => 'Aggregate Testing',
            'main_author' => 'Area 51',
            'pub_year' => '2026',
            'availability' => 'Available',
            'accession_no' => 'ACC-100',
            'call_number' => 'QA 100',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);
    }
}
