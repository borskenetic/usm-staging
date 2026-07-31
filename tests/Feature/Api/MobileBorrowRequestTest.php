<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileBorrowRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_submit_cart_creates_pending_request_without_checkout(): void
    {
        $student = $this->student();
        $book = $this->book();

        Sanctum::actingAs($student, ['full-access']);

        $response = $this->postJson('/api/mobile/borrow-cart/submit', [
            'book_ids' => [$book->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Borrow request submitted for staff approval.')
            ->assertJsonPath('data.request.status', 'pending');

        $this->assertDatabaseHas('library_borrow_requests', [
            'student_id' => $student->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('library_book_logs', 0);
        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $student->id,
            'type' => 'borrow_request_submitted',
        ]);
    }

    public function test_student_can_list_and_cancel_pending_request(): void
    {
        $student = $this->student();
        $book = $this->book();

        $request = BorrowRequest::query()->create([
            'student_id' => $student->id,
            'status' => BorrowRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);
        $request->items()->create([
            'book_id' => $book->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $this->getJson('/api/mobile/borrow-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->deleteJson("/api/mobile/borrow-requests/{$request->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    private function student(): Student
    {
        return Student::query()->create([
            'id_number' => '24-10101',
            'lastname' => 'Diaz',
            'firstname' => 'Lia',
            'qrcode' => 'S-10101',
            'course' => 'BSIT',
            'password_setup_completed' => true,
        ]);
    }

    private function book(): Book
    {
        return Book::query()->create([
            'title_statement' => 'Borrow Request Testing',
            'main_author' => 'Area 51',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-BR-'.uniqid(),
            'call_number' => 'QA 200',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);
    }
}
