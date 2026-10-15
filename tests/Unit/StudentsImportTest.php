<?php

namespace Tests\Unit;

use App\Imports\StudentsImport;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentsImportTest extends TestCase
{
    use RefreshDatabase;
    #[DataProvider('fullNameProvider')]
    public function test_parse_full_name(string $input, string $lastname, string $firstname, ?string $middleInitial): void
    {
        $parsed = StudentsImport::parseFullName($input);

        $this->assertSame($lastname, $parsed['lastname']);
        $this->assertSame($firstname, $parsed['firstname']);
        $this->assertSame($middleInitial, $parsed['middle_initial']);
    }

    public static function fullNameProvider(): array
    {
        return [
            'with middle initial' => ['Ali, Arsad A.', 'Ali', 'Arsad', 'A'],
            'multi-word first name' => ['Alilaya, Bai Rahima A.', 'Alilaya', 'Bai Rahima', 'A'],
            'no middle initial' => ['Belen, Lovely Joy', 'Belen', 'Lovely Joy', null],
            'trailing space' => ['Belen, Lovely Joy ', 'Belen', 'Lovely Joy', null],
            'no comma' => ['OnlyOneName', 'OnlyOneName', '', null],
            'empty' => ['', '', '', null],
        ];
    }

    public function test_parse_birthday_from_string(): void
    {
        $this->assertSame('2005-06-29', StudentsImport::parseBirthday('2005-06-29'));
        $this->assertNull(StudentsImport::parseBirthday(null));
        $this->assertNull(StudentsImport::parseBirthday(''));
    }

    public function test_new_student_qr_continues_after_highest_s_code_not_newest_row(): void
    {
        Student::query()->create([
            'id_number' => '00-00001',
            'lastname' => 'Old',
            'firstname' => 'Student',
            'qrcode' => 'S-00000001',
        ]);
        Student::query()->create([
            'id_number' => '00-00002',
            'lastname' => 'Newer',
            'firstname' => 'Rfid',
            'qrcode' => 'RFID-999',
        ]);

        $import = new StudentsImport;
        $student = $import->model([
            'id_number' => '26-90752',
            'full_name' => 'Ali, Arsad A.',
            'birth_date' => '2005-06-29',
            'program' => 'ABEnglish',
            'year_level' => '1st Year',
            'mobile_no' => '09604586750',
            'home_address' => 'Abellera St, Poblacion, Kabacan, North Cotabato',
        ]);

        $this->assertNotNull($student);
        $this->assertSame('S-00000002', $student->qrcode);
        $this->assertSame('Ali', $student->lastname);
        $this->assertSame('Arsad', $student->firstname);
        $this->assertSame('A', $student->middle_initial);
    }

    public function test_profile_picture_path_uses_filename_only(): void
    {
        $this->assertSame('images/profile_pictures/juan.jpg', StudentsImport::profilePicturePath('juan.jpg'));
        $this->assertSame('images/profile_pictures/juan.jpg', StudentsImport::profilePicturePath('C:\\photos\\juan.jpg'));
        $this->assertSame('images/profile_pictures/juan.jpg', StudentsImport::profilePicturePath('folder/juan.jpg'));
        $this->assertNull(StudentsImport::profilePicturePath(null));
        $this->assertNull(StudentsImport::profilePicturePath(''));
    }
}
