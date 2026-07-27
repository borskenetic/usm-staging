<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedStudentPasswords extends Command
{
    protected $signature = 'pantas:seed-student-passwords';
    protected $description = 'Seed default hashed passwords for all existing students using their birthday (YYYYMMDD).';

    public function handle(): int
    {
        $students = Student::query()
            ->whereNotNull('birthday')
            ->whereNull('password')
            ->get();

        $count = 0;
        $skipped = 0;

        foreach ($students as $student) {
            $birthday = Carbon::parse($student->birthday);
            $defaultPassword = $birthday->format('Ymd');
            $student->forceFill([
                'password' => Hash::make($defaultPassword),
                'password_setup_completed' => false,
            ])->save();
            $count++;
        }

        $skipped = Student::query()->whereNull('birthday')->whereNull('password')->count();

        $this->info("Seeded {$count} student(s) with default passwords.");

        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} student(s) with no birthday set.");
        }

        return self::SUCCESS;
    }
}