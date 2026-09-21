<?php

namespace Tests\Unit;

use App\Imports\StudentsImport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentsImportTest extends TestCase
{
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

    public function test_profile_picture_path_uses_filename_only(): void
    {
        $this->assertSame('images/profile_pictures/juan.jpg', StudentsImport::profilePicturePath('juan.jpg'));
        $this->assertSame('images/profile_pictures/juan.jpg', StudentsImport::profilePicturePath('C:\\photos\\juan.jpg'));
        $this->assertSame('images/profile_pictures/juan.jpg', StudentsImport::profilePicturePath('folder/juan.jpg'));
        $this->assertNull(StudentsImport::profilePicturePath(null));
        $this->assertNull(StudentsImport::profilePicturePath(''));
    }
}
