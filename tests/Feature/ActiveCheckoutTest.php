<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookLog;
use App\Models\BookReservation;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_staff_can_confirm_return_from_active_checkouts_page(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $student = Student::query()->create([
            'id_number' => '24-10106',
            'lastname' => 'Gomez',
            'firstname' => 'Ella',
            'qrcode' => 'S-10106',
            'password_setup_completed' => true,
        ]);

        $book = Book::query()->create([
            'title_statement' => 'Return Test Book',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_BORROWED,
            'accession_no' => 'ACC-RT-1',
            'call_number' => 'QA 700',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);

        $loan = BookLog::query()->create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'patron_name' => 'Gomez, Ella',
            'status' => 'Checked Out',
            'circulation_type' => BookLog::CIRCULATION_CHECKOUT,
            'timestamp' => now(),
            'due_date' => Carbon::now()->addDays(7),
        ]);

        $this->actingAs($staff)
            ->get(route('checkouts.active'))
            ->assertOk()
            ->assertSee('Return Test Book');

        $this->actingAs($staff)
            ->post(route('checkouts.confirm_return', $loan->id))
            ->assertRedirect();

        $book->refresh();
        $this->assertSame(Book::AVAILABILITY_AVAILABLE, $book->availability);
        $this->assertDatabaseHas('library_book_logs', [
            'book_id' => $book->id,
            'student_id' => $student->id,
            'status' => 'Checked In',
        ]);
    }

    public function test_confirm_return_notifies_next_reserver(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $borrower = Student::query()->create([
            'id_number' => '24-10107',
            'lastname' => 'Park',
            'firstname' => 'Sam',
            'qrcode' => 'S-10107',
            'password_setup_completed' => true,
        ]);
        $reserver = Student::query()->create([
            'id_number' => '24-10108',
            'lastname' => 'Yu',
            'firstname' => 'Kim',
            'qrcode' => 'S-10108',
            'password_setup_completed' => true,
        ]);

        $book = Book::query()->create([
            'title_statement' => 'Queued Return Book',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_BORROWED,
            'accession_no' => 'ACC-QR-1',
            'call_number' => 'QA 800',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);

        $loan = BookLog::query()->create([
            'book_id' => $book->id,
            'student_id' => $borrower->id,
            'patron_name' => 'Park, Sam',
            'status' => 'Checked Out',
            'circulation_type' => BookLog::CIRCULATION_CHECKOUT,
            'timestamp' => now()->subDays(3),
            'due_date' => Carbon::now()->addDays(4),
        ]);

        BookReservation::query()->create([
            'student_id' => $reserver->id,
            'book_id' => $book->id,
            'status' => BookReservation::STATUS_PENDING,
            'queue_position' => 1,
            'reserved_at' => now()->subDay(),
        ]);

        $this->actingAs($staff)
            ->post(route('checkouts.confirm_return', $loan->id))
            ->assertRedirect();

        $book->refresh();
        $this->assertSame(Book::AVAILABILITY_ON_HOLD, $book->availability);
        $this->assertDatabaseHas('library_book_reservations', [
            'student_id' => $reserver->id,
            'status' => BookReservation::STATUS_READY,
            'held_book_id' => $book->id,
        ]);
        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $reserver->id,
            'type' => 'book_reservation_ready',
        ]);
    }
}
