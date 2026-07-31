<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\Faculty;
use App\Models\FacultyFolder;
use Illuminate\Database\Seeder;

class ClassroomSampleSeeder extends Seeder
{
    public function run(): void
    {
        $faculty = Faculty::query()->where('employee_id', 'EMP-10001')->first();
        if (! $faculty) {
            $this->command?->warn('Faculty EMP-10001 not found; skip classroom sample.');

            return;
        }

        $classroom = Classroom::query()->updateOrCreate(
            [
                'faculty_id' => $faculty->id,
                'name' => 'BSIT 3A - Web Development',
            ],
            [
                'description' => 'Sample classroom for mobile QA.',
                'subject' => 'Web Development',
                'join_code' => 'JOINDEMO',
                'is_private' => true,
                'requires_approval' => false,
            ]
        );

        $folder = FacultyFolder::query()->updateOrCreate(
            [
                'faculty_id' => $faculty->id,
                'name' => 'Week 1 Readings',
            ],
            [
                'description' => 'Starter recommended books.',
            ]
        );

        $bookIds = Book::query()
            ->whereNull('archived_at')
            ->orderBy('id')
            ->limit(3)
            ->pluck('id')
            ->all();

        if ($bookIds !== []) {
            $folder->books()->syncWithoutDetaching($bookIds);
        }

        $classroom->folders()->syncWithoutDetaching([$folder->id]);

        $this->command?->info("Sample classroom seeded: join code {$classroom->join_code}");
    }
}
