<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowRequestAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_staff_can_approve_pending_borrow_request(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $student = Student::query()->create([
            'id_number' => '24-10104',
            'lastname' => 'Reyes',
            'firstname' => 'Mia',
            'qrcode' => 'S-10104',
            'password_setup_completed' => true,
        ]);

        $book = Book::query()->create([
            'title_statement' => 'Approve Me',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-AP-1',
            'call_number' => 'QA 500',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);

        $borrowRequest = BorrowRequest::query()->create([
            'student_id' => $student->id,
            'status' => BorrowRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $item = BorrowRequestItem::query()->create([
            'borrow_request_id' => $borrowRequest->id,
            'book_id' => $book->id,
            'status' => BorrowRequestItem::STATUS_PENDING,
        ]);

        $this->actingAs($staff)
            ->post(route('checkout.requests.approve', $borrowRequest->id), [
                'item_ids' => [$item->id],
            ])
            ->assertRedirect();

        $borrowRequest->refresh();
        $book->refresh();

        $this->assertSame(BorrowRequest::STATUS_APPROVED, $borrowRequest->status);
        $this->assertSame(Book::AVAILABILITY_BORROWED, $book->availability);
        $this->assertDatabaseHas('library_book_logs', [
            'student_id' => $student->id,
            'book_id' => $book->id,
            'status' => 'Checked Out',
        ]);
        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $student->id,
            'type' => 'borrow_request_approved',
        ]);
    }

    public function test_staff_can_reject_pending_borrow_request(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $student = Student::query()->create([
            'id_number' => '24-10105',
            'lastname' => 'Tan',
            'firstname' => 'Leo',
            'qrcode' => 'S-10105',
            'password_setup_completed' => true,
        ]);

        $book = Book::query()->create([
            'title_statement' => 'Reject Me',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-RJ-1',
            'call_number' => 'QA 600',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);

        $borrowRequest = BorrowRequest::query()->create([
            'student_id' => $student->id,
            'status' => BorrowRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        BorrowRequestItem::query()->create([
            'borrow_request_id' => $borrowRequest->id,
            'book_id' => $book->id,
            'status' => BorrowRequestItem::STATUS_PENDING,
        ]);

        $this->actingAs($staff)
            ->post(route('checkout.requests.reject', $borrowRequest->id), [
                'staff_note' => 'Copy damaged.',
            ])
            ->assertRedirect();

        $borrowRequest->refresh();
        $book->refresh();

        $this->assertSame(BorrowRequest::STATUS_REJECTED, $borrowRequest->status);
        $this->assertSame(Book::AVAILABILITY_AVAILABLE, $book->availability);
        $this->assertDatabaseCount('library_book_logs', 0);
        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $student->id,
            'type' => 'borrow_request_rejected',
        ]);
    }

    public function test_staff_can_approve_single_borrow_request_item(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $student = Student::query()->create([
            'id_number' => '24-10109',
            'lastname' => 'Wong',
            'firstname' => 'Ivy',
            'qrcode' => 'S-10109',
            'password_setup_completed' => true,
        ]);

        $bookA = Book::query()->create([
            'title_statement' => 'Item A',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-IA-1',
            'call_number' => 'QA 900',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);
        $bookB = Book::query()->create([
            'title_statement' => 'Item B',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-IB-1',
            'call_number' => 'QA 901',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);

        $borrowRequest = BorrowRequest::query()->create([
            'student_id' => $student->id,
            'status' => BorrowRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $itemA = BorrowRequestItem::query()->create([
            'borrow_request_id' => $borrowRequest->id,
            'book_id' => $bookA->id,
            'status' => BorrowRequestItem::STATUS_PENDING,
        ]);
        BorrowRequestItem::query()->create([
            'borrow_request_id' => $borrowRequest->id,
            'book_id' => $bookB->id,
            'status' => BorrowRequestItem::STATUS_PENDING,
        ]);

        $this->actingAs($staff)
            ->post(route('checkout.requests.items.approve', $itemA->id))
            ->assertRedirect();

        $borrowRequest->refresh();
        $this->assertSame(BorrowRequest::STATUS_PENDING, $borrowRequest->status);
        $this->assertSame(BorrowRequestItem::STATUS_APPROVED, $itemA->fresh()->status);
        $this->assertDatabaseHas('library_book_logs', [
            'student_id' => $student->id,
            'book_id' => $bookA->id,
            'status' => 'Checked Out',
        ]);
        $this->assertDatabaseCount('student_notifications', 0);
    }
}
