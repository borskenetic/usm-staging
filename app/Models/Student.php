<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'library_students';

    protected $fillable = [
        'id_number',
        'lastname',
        'firstname',
        'middle_initial',
        'birthday',
        'qrcode',
        'course',
        'year',
        'profile_picture',
        'student_signature',
        'password',
        'password_setup_completed',
        'force_password_reset',
        'failed_login_attempts',
        'locked_until',
        'mobile_number',
        'address',
        'emergency_person',
        'emergency_relationship',
        'emergency_number',
        'emergency_address',
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
        return Carbon::parse($this->birthday)->format('Ymd');
    }

    public function isUsingDerivedDefault(): bool
    {
        if ($this->password === null) {
            return true;
        }

        return Hash::check($this->deriveDefaultPassword(), $this->password);
    }

    public function editRequests()
    {
        return $this->hasMany(StudentEditRequest::class);
    }

    public function bookLogs()
    {
        return $this->hasMany(BookLog::class, 'student_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(LibraryAttendanceLog::class, 'student_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function passwordResetLogs(): HasMany
    {
        return $this->hasMany(StudentPasswordResetLog::class, 'student_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(StudentNotification::class, 'student_id');
    }
}
