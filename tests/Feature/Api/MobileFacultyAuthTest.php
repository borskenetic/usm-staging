<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Faculty;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileFacultyAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_student_login_still_works_with_login_id(): void
    {
        $student = Student::query()->create([
            'id_number' => '24-20001',
            'lastname' => 'Cruz',
            'firstname' => 'Ana',
            'qrcode' => 'S-20001',
            'birthday' => '2000-01-15',
            'password' => Hash::make('password'),
            'password_setup_completed' => true,
        ]);

        $this->postJson('/api/mobile/login', [
            'login_id' => '24-20001',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('must_change_password', false)
            ->assertJsonPath('data.user.role', 'student')
            ->assertJsonPath('data.student.id_number', '24-20001')
            ->assertJsonPath('data.faculty', null);

        $this->assertNotNull($student->fresh());
    }

    public function test_faculty_can_login_and_fetch_profile(): void
    {
        Faculty::query()->create([
            'employee_id' => 'EMP-10001',
            'lastname' => 'Santos',
            'firstname' => 'Maria',
            'email' => 'maria.santos@usm.edu.ph',
            'department' => 'College of Computing',
            'designation' => 'Assistant Professor',
            'account_status' => 'Active',
            'password' => Hash::make('password'),
            'password_setup_completed' => true,
        ]);

        $login = $this->postJson('/api/mobile/login', [
            'login_id' => 'EMP-10001',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'faculty')
            ->assertJsonPath('data.faculty.employee_id', 'EMP-10001')
            ->assertJsonPath('data.student', null);

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/mobile/profile')
            ->assertOk()
            ->assertJsonPath('data.user.role', 'faculty')
            ->assertJsonPath('data.faculty.department', 'College of Computing');
    }

    public function test_unknown_id_fails_login(): void
    {
        $this->postJson('/api/mobile/login', [
            'login_id' => 'UNKNOWN-999',
            'password' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login_id']);
    }

    public function test_faculty_cannot_access_student_borrow_endpoints(): void
    {
        $faculty = Faculty::query()->create([
            'employee_id' => 'EMP-10002',
            'lastname' => 'Reyes',
            'firstname' => 'Juan',
            'account_status' => 'Active',
            'password' => Hash::make('password'),
            'password_setup_completed' => true,
        ]);

        Book::query()->create([
            'title_statement' => 'Isolation Book',
            'main_author' => 'Author',
            'pub_year' => '2026',
            'availability' => Book::AVAILABILITY_AVAILABLE,
            'accession_no' => 'ACC-FAC-1',
            'call_number' => 'QA 100',
            'content_type' => 'Book',
            'library_name' => 'Main',
            'section' => 'General',
        ]);

        Sanctum::actingAs($faculty, ['full-access']);

        $this->getJson('/api/mobile/borrowed-books')
            ->assertForbidden();

        $this->postJson('/api/mobile/borrow-cart/submit', [
            'book_ids' => [1],
        ])->assertForbidden();
    }

    public function test_legacy_student_id_field_still_accepted(): void
    {
        Student::query()->create([
            'id_number' => '24-20002',
            'lastname' => 'Lim',
            'firstname' => 'Ben',
            'qrcode' => 'S-20002',
            'password' => Hash::make('password'),
            'password_setup_completed' => true,
        ]);

        $this->postJson('/api/mobile/login', [
            'student_id' => '24-20002',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'student');
    }
}
