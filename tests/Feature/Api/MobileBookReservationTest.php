<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileBookReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_student_can_reserve_unavailable_book(): void
    {
        $student = $this->student();
        $book = $this->book(['availability' => 'Borrowed']);

        Sanctum::actingAs($student, ['full-access']);

        $response = $this->postJson('/api/mobile/books/reservations', [
            'book_id' => $book->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.queue_position', 1)
            ->assertJsonPath('data.book.id', $book->id);

        $this->assertDatabaseHas('library_book_reservations', [
            'student_id' => $student->id,
            'book_id' => $book->id,
            'status' => 'pending',
            'queue_position' => 1,
        ]);
    }

    public function test_student_cannot_reserve_available_book(): void
    {
        $student = $this->student();
        $book = $this->book(['availability' => 'Available']);

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson('/api/mobile/books/reservations', [
            'book_id' => $book->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'This book has available copies — no reservation needed.');
    }

    public function test_student_can_list_and_cancel_reservation(): void
    {
        $student = $this->student();
        $book = $this->book(['availability' => 'Borrowed']);

        $reservation = BookReservation::query()->create([
            'student_id' => $student->id,
            'book_id' => $book->id,
            'status' => 'pending',
            'queue_position' => 1,
            'reserved_at' => now(),
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/books/reservations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reservation->id);

        $this->deleteJson("/api/mobile/books/reservations/{$reservation->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('library_book_reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_student_cannot_view_another_students_reservation(): void
    {
        $owner = $this->student('24-10001');
        $other = $this->student('24-10002');
        $book = $this->book(['availability' => 'Borrowed']);

        $reservation = BookReservation::query()->create([
            'student_id' => $owner->id,
            'book_id' => $book->id,
            'status' => 'pending',
            'queue_position' => 1,
            'reserved_at' => now(),
        ]);

        Sanctum::actingAs($other, ['full-access']);

        $this->getJson("/api/mobile/books/reservations/{$reservation->id}")
            ->assertNotFound();
    }

    private function student(string $idNumber = '24-10099'): Student
    {
        return Student::query()->create([
            'id_number' => $idNumber,
            'lastname' => 'Reyes',
            'firstname' => 'Mark',
            'qrcode' => 'S-'.$idNumber,
            'course' => 'BSIT',
            'password_setup_completed' => true,
        ]);
    }

    private function book(array $overrides = []): Book
    {
        return Book::query()->create(array_merge([
            'title_statement' => 'Reservation Testing',
            'main_author' => 'Area 51',
            'pub_year' => '2026',
            'availability' => 'Available',
            'accession_no' => 'ACC-'.uniqid(),
            'call_number' => 'QA 100',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ], $overrides));
    }
}
