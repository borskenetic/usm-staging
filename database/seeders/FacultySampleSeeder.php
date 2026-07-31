<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FacultySampleSeeder extends Seeder
{
    public function run(): void
    {
        Faculty::query()->updateOrCreate(
            ['employee_id' => 'EMP-10001'],
            [
                'lastname' => 'Santos',
                'firstname' => 'Maria',
                'middle_initial' => 'A',
                'email' => 'maria.santos@usm.edu.ph',
                'department' => 'College of Computing',
                'designation' => 'Assistant Professor',
                'birthday' => '1985-03-15',
                'mobile_number' => '09171234567',
                'account_status' => 'Active',
                'password' => Hash::make('password'),
                'password_setup_completed' => true,
                'force_password_reset' => false,
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]
        );

        $this->command?->info('Sample faculty seeded: EMP-10001 / password');
    }
}
