<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\CirculationTransactionMail;
use App\Models\Book;
use App\Models\BookLog;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CirculationTransactionMailTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_checkout_room_use_check_in_and_renewal_are_emailed_when_an_address_exists(): void
    {
        Mail::fake();
        $student = $this->student('patron@example.com');
        $book = $this->book('Kit\'s law');

        $checkout = $this->loan($book, $student, BookLog::CIRCULATION_CHECKOUT, 'Checked Out');
        $this->loan($book, $student, BookLog::CIRCULATION_ROOM_USE, 'Checked Out');
        $this->loan($book, $student, BookLog::CIRCULATION_CHECKOUT, 'Checked In', Carbon::now());

        $checkout->forceFill([
            'renew_count' => 1,
            'last_renewed_at' => Carbon::now(),
        ])->save();

        Mail::assertSent(CirculationTransactionMail::class, 4);
        Mail::assertSent(CirculationTransactionMail::class, fn (CirculationTransactionMail $mail) => $mail->hasTo('patron@example.com')
            && $mail->kind() === 'Check out'
            && $mail->log->is($checkout));
        Mail::assertSent(CirculationTransactionMail::class, fn (CirculationTransactionMail $mail) => $mail->kind() === 'Room use');
        Mail::assertSent(CirculationTransactionMail::class, fn (CirculationTransactionMail $mail) => $mail->kind() === 'Check in');
        Mail::assertSent(CirculationTransactionMail::class, fn (CirculationTransactionMail $mail) => $mail->renewal && $mail->kind() === 'Renewal');
    }

    public function test_check_in_emails_the_patron_and_says_so_on_the_desk(): void
    {
        Mail::fake();
        $staff = User::factory()->create(['role' => 'admin']);
        $student = $this->student('return@example.com');
        $book = $this->book('Returned title');
        $book->update(['accession_no' => 'ACC-RET-1', 'availability' => Book::AVAILABILITY_BORROWED]);

        BookLog::query()->create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'patron_name' => 'Jaafar, Kaironsalam',
            'status' => 'Checked Out',
            'circulation_type' => BookLog::CIRCULATION_CHECKOUT,
            'timestamp' => Carbon::now()->subDay(),
            'due_date' => Carbon::now()->addDay(),
            'renew_count' => 0,
            'fine_incurred' => 0,
        ]);

        $this->actingAs($staff)
            ->post(route('logs.store'), [
                'copy_identifier' => 'ACC-RET-1',
                'status' => 'checked_in',
                'student_id' => $student->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('circulation_mail', 'A copy was emailed to return@example.com.');

        Mail::assertSent(CirculationTransactionMail::class, fn (CirculationTransactionMail $mail) => $mail->hasTo('return@example.com')
            && $mail->kind() === 'Check in');
    }

    public function test_patrons_without_a_usable_email_are_skipped(): void
    {
        Mail::fake();
        $book = $this->book('American literature');

        foreach ([null, '', '   ', 'not-an-email'] as $email) {
            $this->loan($book, $this->student($email), BookLog::CIRCULATION_CHECKOUT, 'Checked Out');
        }

        Mail::assertNothingSent();
    }

    private function student(?string $email): Student
    {
        $student = Student::query()->create([
            'id_number' => '23-'.random_int(10000, 99999),
            'lastname' => 'Jaafar',
            'firstname' => 'Kaironsalam',
            'qrcode' => 'S-'.random_int(10000000, 99999999),
            'password_setup_completed' => true,
        ]);
        $student->email = $email;
        $student->save();

        return $student;
    }

    private function book(string $title): Book
    {
        return Book::query()->create([
            'title_statement' => $title,
            'main_author' => 'Author',
            'pub_year' => '2014',
            'availability' => Book::AVAILABILITY_BORROWED,
            'accession_no' => 'ACC-'.random_int(1000, 9999),
            'call_number' => 'S 494',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'Circulation',
        ]);
    }

    private function loan(Book $book, Student $student, string $type, string $status, ?Carbon $returned = null): BookLog
    {
        return BookLog::query()->create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'patron_name' => 'Jaafar, Kaironsalam',
            'status' => $status,
            'circulation_type' => $type,
            'timestamp' => Carbon::parse('2026-06-15 12:05:00'),
            'due_date' => $type === BookLog::CIRCULATION_ROOM_USE ? null : Carbon::parse('2026-06-18'),
            'returned_date' => $returned,
            'renew_count' => 0,
            'fine_incurred' => 0,
        ]);
    }
}
