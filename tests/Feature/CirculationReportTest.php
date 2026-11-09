<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookLog;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class CirculationReportTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_transaction_report_uses_the_current_filters(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $student = $this->student('Jaafar', 'Kaironsalam', '23-62500');
        $kept = $this->book("Kit's law", 'ACC-KIT');
        $other = $this->book('American literature', 'ACC-AMER');

        $this->loan($kept, $student, Carbon::parse('2026-06-15 12:05:00'), Carbon::parse('2026-06-18'));
        $this->loan($other, $student, Carbon::parse('2026-06-15 12:04:00'), Carbon::parse('2026-06-18'));

        $this->actingAs($staff)
            ->get(route('logs.index', ['book_title' => "Kit's law"]))
            ->assertOk()
            ->assertSee('Download Excel report');

        $response = $this->actingAs($staff)->get(route('transactions.export', [
            'book_title' => "Kit's law",
        ]));

        $response->assertOk();
        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();

        $this->assertSame('Circulation transaction report', $sheet->getCell('A1')->getValue());
        $this->assertStringContainsString("Book: Kit's law", (string) $sheet->getCell('B3')->getValue());

        $titles = $sheet->rangeToArray('A7:A20');
        $flat = implode(' ', array_map(fn ($row) => (string) ($row[0] ?? ''), $titles));
        $this->assertStringContainsString("Kit's law", $flat);
        $this->assertStringNotContainsString('American literature', $flat);
    }

    public function test_books_in_circulation_report_lists_open_loans(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $student = $this->student('Losala', 'Jan Mark', '24-71028');
        $book = $this->book('2-in-1 coconut dehusking machine', 'ACC-COCO');

        $this->loan($book, $student, Carbon::parse('2026-03-19 16:10:00'), Carbon::parse('2026-03-24'));

        $this->actingAs($staff)
            ->get(route('checkouts.active'))
            ->assertOk()
            ->assertSee('Download Excel report');

        $response = $this->actingAs($staff)->get(route('checkouts.export'));
        $response->assertOk();

        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();
        $this->assertSame('Books in circulation', $sheet->getCell('A1')->getValue());

        $values = $sheet->toArray();
        $flat = implode("\n", array_map(fn ($row) => implode(' ', $row), $values));
        $this->assertStringContainsString('2-in-1 coconut dehusking machine', $flat);
        $this->assertStringContainsString('Losala, Jan Mark', $flat);
        $this->assertStringContainsString('Overdue', $flat);
    }

    private function student(string $lastname, string $firstname, string $idNumber): Student
    {
        return Student::query()->create([
            'id_number' => $idNumber,
            'lastname' => $lastname,
            'firstname' => $firstname,
            'qrcode' => 'S-'.$idNumber,
            'password_setup_completed' => true,
        ]);
    }

    private function book(string $title, string $accession): Book
    {
        return Book::query()->create([
            'title_statement' => $title,
            'main_author' => 'Author',
            'pub_year' => '2014',
            'availability' => Book::AVAILABILITY_BORROWED,
            'accession_no' => $accession,
            'call_number' => 'S 494',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'Circulation',
        ]);
    }

    private function loan(Book $book, Student $student, Carbon $out, Carbon $due): BookLog
    {
        return BookLog::query()->create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'patron_name' => "{$student->lastname}, {$student->firstname}",
            'status' => 'Checked Out',
            'circulation_type' => BookLog::CIRCULATION_CHECKOUT,
            'timestamp' => $out,
            'due_date' => $due,
            'renew_count' => 0,
        ]);
    }
}
