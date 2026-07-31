<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Faculty extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'library_faculty';

    protected $fillable = [
        'employee_id',
        'lastname',
        'firstname',
        'middle_initial',
        'email',
        'department',
        'designation',
        'birthday',
        'mobile_number',
        'profile_picture',
        'account_status',
        'password',
        'password_setup_completed',
        'force_password_reset',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'password_setup_completed' => 'boolean',
            'force_password_reset' => 'boolean',
            'locked_until' => 'datetime',
        ];
    }

    public function needsPasswordChange(): bool
    {
        return ! $this->password_setup_completed || $this->force_password_reset;
    }

    public function isLocked(): bool
    {
        if ($this->locked_until === null) {
            return false;
        }

        return Carbon::now()->lessThan($this->locked_until);
    }

    public function recordFailedAttempt(): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = Carbon::now()->addMinutes(15);
        }

        $this->save();
    }

    public function resetFailedAttempts(): void
    {
        $this->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    public function deriveDefaultPassword(): string
    {
        if ($this->birthday) {
            return Carbon::parse($this->birthday)->format('Ymd');
        }

        return 'ChangeMe123';
    }

    public function isUsingDerivedDefault(): bool
    {
        if ($this->password === null) {
            return true;
        }

        return Hash::check($this->deriveDefaultPassword(), $this->password);
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'faculty_id');
    }

    public function folders()
    {
        return $this->hasMany(FacultyFolder::class, 'faculty_id');
    }
}
