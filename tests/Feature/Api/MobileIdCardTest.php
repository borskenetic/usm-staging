<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Student;
use App\Models\User;
use App\Services\LibraryIdCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileIdCardTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_student_can_fetch_digital_id_as_base64_pngs(): void
    {
        $student = Student::query()->create([
            'id_number' => '24-10099',
            'lastname' => 'Reyes',
            'firstname' => 'Mark',
            'qrcode' => 'S-00000099',
            'course' => 'BSIT',
            'birthday' => '2002-11-08',
            'password_setup_completed' => true,
        ]);

        Sanctum::actingAs($student, ['full-access']);

        $response = $this->getJson('/api/mobile/id-card');

        $response
            ->assertOk()
            ->assertJsonPath('data.mime', 'image/png')
            ->assertJsonPath('data.student_number', '24-10099')
            ->assertJsonPath('data.full_name', 'Mark Reyes');

        $front = (string) $response->json('data.front_png_base64');
        $back = (string) $response->json('data.back_png_base64');

        $this->assertNotSame('', $front);
        $this->assertNotSame('', $back);

        $frontBinary = base64_decode($front, true);
        $backBinary = base64_decode($back, true);

        $this->assertNotFalse($frontBinary);
        $this->assertNotFalse($backBinary);
        $this->assertStringStartsWith("\x89PNG", $frontBinary);
        $this->assertStringStartsWith("\x89PNG", $backBinary);
    }

    public function test_unlinked_user_cannot_fetch_digital_id(): void
    {
        $user = User::factory()->create([
            'student_id' => null,
            'role' => 'student',
        ]);

        Sanctum::actingAs($user, ['full-access']);

        $this->getJson('/api/mobile/id-card')
            ->assertStatus(409)
            ->assertJsonPath('message', 'No student profile is linked to this account.');
    }

    public function test_library_id_card_service_generates_png_for_web_controllers(): void
    {
        $student = Student::query()->create([
            'id_number' => '24-10100',
            'lastname' => 'Garcia',
            'firstname' => 'Sophia',
            'qrcode' => 'S-00000100',
            'course' => 'BSED',
            'password_setup_completed' => true,
        ]);

        $service = app(LibraryIdCardService::class);
        $front = $service->frontPngForStudent($student);
        $back = $service->backPngForStudent($student);

        $this->assertStringStartsWith("\x89PNG", $front);
        $this->assertStringStartsWith("\x89PNG", $back);
        $this->assertGreaterThan(1000, strlen($front));
    }
}
