<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\BookReservationController;
use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Student;
use Carbon\Carbon;
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
        $book = $this->book(['availability' => Book::AVAILABILITY_BORROWED]);

        Sanctum::actingAs($student, ['full-access']);

        $response = $this->postJson('/api/mobile/books/reservations', [
            'book_id' => $book->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.queue_position', 1);

        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $student->id,
            'type' => 'book_reservation_pending',
        ]);
    }

    public function test_fulfil_next_in_queue_places_hard_hold_on_returned_copy(): void
    {
        $student = $this->student();
        $book = $this->book(['availability' => Book::AVAILABILITY_BORROWED]);

        $reservation = BookReservation::query()->create([
            'student_id' => $student->id,
            'book_id' => $book->id,
            'status' => BookReservation::STATUS_PENDING,
            'queue_position' => 1,
            'reserved_at' => now(),
        ]);

        $returnedCopy = $this->book([
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-HOLD-1',
        ]);

        BookReservationController::fulfilNextInQueue($returnedCopy);

        $reservation->refresh();
        $returnedCopy->refresh();

        $this->assertSame(BookReservation::STATUS_READY, $reservation->status);
        $this->assertSame($returnedCopy->id, $reservation->held_book_id);
        $this->assertSame(Book::AVAILABILITY_ON_HOLD, $returnedCopy->availability);
    }

    public function test_mobile_cart_rejects_on_hold_copy(): void
    {
        $student = $this->student();
        $book = $this->book(['availability' => Book::AVAILABILITY_ON_HOLD]);

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson('/api/mobile/borrow-cart/submit', [
            'book_ids' => [$book->id],
        ])
            ->assertStatus(409);

        $this->assertDatabaseCount('library_borrow_requests', 0);
    }

    public function test_student_cannot_reserve_available_book(): void
    {
        $student = $this->student();
        $book = $this->book(['availability' => Book::AVAILABILITY_AVAILABLE]);

        Sanctum::actingAs($student, ['full-access']);

        $this->postJson('/api/mobile/books/reservations', [
            'book_id' => $book->id,
        ])->assertStatus(409);
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
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-'.uniqid(),
            'call_number' => 'QA 100',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ], $overrides));
    }
}
