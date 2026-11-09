<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminActivity;
use App\Models\Book;
use App\Models\BookLog;
use App\Models\Student;
use App\Models\User;
use App\Services\TopbarNotificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfCheckoutRoomUseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_kiosk_checkout_is_room_use_and_notifies_library_admin(): void
    {
        $student = Student::query()->create([
            'id_number' => '24-50001',
            'lastname' => 'Cruz',
            'firstname' => 'Ana',
        ]);
        $book = Book::query()->create([
            'title_statement' => 'Reserve Readings in Biology',
            'main_author' => 'USM Faculty',
            'accession_no' => 'GG-TEST-0001',
            'barcode' => 'BC-TEST-0001',
            'section' => 'Reserve',
            'availability' => Book::AVAILABILITY_AVAILABLE,
        ]);

        $response = $this->postJson(route('checkout.process'), [
            'student_id' => $student->id_number,
            'book_id' => $book->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('books.0.due_date', null);

        $log = BookLog::query()->where('book_id', $book->id)->firstOrFail();
        $this->assertSame(BookLog::CIRCULATION_ROOM_USE, $log->circulation_type);
        $this->assertNull($log->due_date);
        $this->assertSame('Borrowed', $book->fresh()->availability);

        $activity = AdminActivity::query()->where('type', AdminActivity::TYPE_SELF_CHECKOUT)->firstOrFail();
        $this->assertSame('Kiosk room use', $activity->title);
        $this->assertStringContainsString('Cruz, Ana', (string) $activity->body);
        $this->assertStringContainsString('Reserve Readings in Biology', (string) $activity->body);

        $admin = User::factory()->create([
            'role' => 'library_admin',
            'is_active' => true,
        ]);
        $admin->assignRole('library_admin');

        $notice = app(TopbarNotificationService::class)
            ->forUser($admin)
            ->firstWhere('title', 'Kiosk room use');

        $this->assertNotNull($notice);
        $this->assertStringContainsString('1 kiosk room use', $notice['message']);
    }
}
