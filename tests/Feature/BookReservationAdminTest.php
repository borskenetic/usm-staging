<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\BookReservationController;
use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookReservationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_staff_can_fulfill_ready_reservation_at_desk(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $student = Student::query()->create([
            'id_number' => '24-10102',
            'lastname' => 'Cruz',
            'firstname' => 'Ana',
            'qrcode' => 'S-10102',
            'password_setup_completed' => true,
        ]);

        $heldCopy = Book::query()->create([
            'title_statement' => 'Desk Claim Book',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_ON_HOLD,
            'accession_no' => 'ACC-DC-1',
            'call_number' => 'QA 300',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);

        $reservation = BookReservation::query()->create([
            'student_id' => $student->id,
            'book_id' => $heldCopy->id,
            'held_book_id' => $heldCopy->id,
            'status' => BookReservation::STATUS_READY,
            'queue_position' => 1,
            'reserved_at' => now(),
            'available_at' => now(),
            'hold_expires_at' => Carbon::now()->addDay(),
        ]);

        $this->actingAs($staff)
            ->post(route('books.reservations.fulfill', $reservation->id))
            ->assertRedirect();

        $reservation->refresh();
        $heldCopy->refresh();

        $this->assertSame(BookReservation::STATUS_FULFILLED, $reservation->status);
        $this->assertSame(Book::AVAILABILITY_BORROWED, $heldCopy->availability);
        $this->assertDatabaseHas('library_book_logs', [
            'student_id' => $student->id,
            'book_id' => $heldCopy->id,
            'status' => 'Checked Out',
        ]);
    }

    public function test_expire_stale_holds_promotes_next_patron(): void
    {
        $student = Student::query()->create([
            'id_number' => '24-10103',
            'lastname' => 'Lim',
            'firstname' => 'Jay',
            'qrcode' => 'S-10103',
            'password_setup_completed' => true,
        ]);

        $heldCopy = Book::query()->create([
            'title_statement' => 'Expire Hold Book',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_ON_HOLD,
            'accession_no' => 'ACC-EX-1',
            'call_number' => 'QA 400',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);

        BookReservation::query()->create([
            'student_id' => $student->id,
            'book_id' => $heldCopy->id,
            'held_book_id' => $heldCopy->id,
            'status' => BookReservation::STATUS_READY,
            'queue_position' => 1,
            'reserved_at' => now()->subDays(3),
            'available_at' => now()->subDays(3),
            'hold_expires_at' => now()->subHour(),
        ]);

        BookReservationController::expireStaleHolds();

        $this->assertDatabaseHas('library_book_reservations', [
            'student_id' => $student->id,
            'status' => BookReservation::STATUS_EXPIRED,
        ]);
        $this->assertSame(
            Book::AVAILABILITY_AVAILABLE,
            $heldCopy->fresh()->availability
        );
    }
}
