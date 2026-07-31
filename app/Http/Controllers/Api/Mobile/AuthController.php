<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\StudentChangePasswordRequest;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\StudentNotification;
use App\Models\StudentPasswordResetLog;
use App\Models\User;
use App\Services\Auth\ModuleAccessService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /mobile/login
     *
     * Authenticate a student or faculty using login_id (or legacy student_id) + password.
     * Lookup order: Student by id_number, then Faculty by employee_id.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login_id' => ['nullable', 'string', 'max:255'],
            'student_id' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $loginId = trim((string) ($validated['login_id'] ?? $validated['student_id'] ?? ''));

        if ($loginId === '') {
            throw ValidationException::withMessages([
                'login_id' => ['Please enter your ID Number.'],
            ]);
        }

        $student = Student::query()->where('id_number', $loginId)->first();
        if ($student) {
            return $this->completeStudentLogin($student, $validated['password'], $loginId);
        }

        $faculty = Faculty::query()->where('employee_id', $loginId)->first();
        if ($faculty) {
            return $this->completeFacultyLogin($faculty, $validated['password'], $loginId);
        }

        throw ValidationException::withMessages([
            'login_id' => ['The provided ID Number was not found.'],
        ]);
    }

    /**
     * POST /mobile/student/change-password
     *
     * Password change for Student or Faculty (requires password-change scoped token).
     */
    public function studentChangePassword(StudentChangePasswordRequest $request): JsonResponse
    {
        $tokenable = $request->user();

        if ($tokenable instanceof Student) {
            return $this->completePasswordChange($tokenable, $request->validated(), isFaculty: false);
        }

        if ($tokenable instanceof Faculty) {
            return $this->completePasswordChange($tokenable, $request->validated(), isFaculty: true);
        }

        return response()->json([
            'message' => 'Only students or faculty can change their password through this endpoint.',
            'data' => null,
        ], 403);
    }

    /**
     * POST /mobile/change-password
     *
     * Staff-only password change (kept for backward compatibility with User model).
     */
    public function changePassword(Request $request): JsonResponse
    {
        if ($request->user() instanceof Student || $request->user() instanceof Faculty) {
            return response()->json([
                'message' => 'Please use the student change-password endpoint.',
                'data' => null,
            ], 409);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Password changed successfully. Please log in again.',
            'data' => null,
        ]);
    }

    /**
     * POST /mobile/students/{student}/reset-password
     *
     * Staff-initiated password reset for a student.
     */
    public function staffResetPassword(Request $request, Student $student): JsonResponse
    {
        $staff = $request->user();

        if (! $staff instanceof User) {
            return response()->json([
                'message' => 'Only staff users can reset student passwords.',
                'data' => null,
            ], 403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $student->forceFill([
            'password' => null,
            'password_setup_completed' => false,
            'force_password_reset' => true,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $student->tokens()->delete();

        StudentPasswordResetLog::create([
            'student_id' => $student->id,
            'staff_id' => $staff->id,
            'reason' => $validated['reason'] ?? null,
        ]);

        StudentNotification::create([
            'student_id' => $student->id,
            'type' => 'password_reset',
            'title' => 'Password reset by staff',
            'message' => 'Your password was reset by library staff. Please use your birthdate as the default password and change it on your next login.',
        ]);

        return response()->json([
            'message' => "Password for student {$student->id_number} has been reset.",
            'data' => null,
        ]);
    }

    /**
     * POST /mobile/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout successful.',
            'data' => null,
        ]);
    }

    /**
     * GET /mobile/me
     * GET /mobile/profile
     */
    public function me(Request $request): JsonResponse
    {
        $tokenable = $request->user();

        if ($tokenable instanceof Faculty) {
            return response()->json([
                'message' => 'Authenticated user retrieved.',
                'data' => [
                    'user' => $this->formatUser($tokenable),
                    'faculty' => $this->formatFaculty($tokenable),
                    'student' => null,
                ],
            ]);
        }

        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        return response()->json([
            'message' => 'Authenticated user retrieved.',
            'data' => [
                'user' => $this->formatUser($student),
                'student' => $this->formatStudent($student),
                'faculty' => null,
            ],
        ]);
    }

    private function completeStudentLogin(Student $student, string $password, string $loginId): JsonResponse
    {
        $this->assertNotLocked($student, $loginId);

        $storedHash = $student->password;

        if ($storedHash === null) {
            if (! $student->birthday) {
                throw ValidationException::withMessages([
                    'login_id' => ['This account has no password configured. Please contact the library staff.'],
                ]);
            }
            $storedHash = Hash::make($student->deriveDefaultPassword());
            $student->forceFill(['password' => $storedHash])->save();
        }

        if (! Hash::check($password, $storedHash)) {
            $student->recordFailedAttempt();

            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        $student->resetFailedAttempts();

        $needsChange = $student->needsPasswordChange();
        $abilities = $needsChange ? ['password-change'] : ['full-access'];
        $tokenName = $needsChange ? 'pantas-mobile-pending' : 'pantas-mobile';
        $token = $student->createToken($tokenName, $abilities)->plainTextToken;

        return response()->json([
            'message' => $needsChange
                ? 'Login successful. Password change required.'
                : 'Login successful.',
            'must_change_password' => $needsChange,
            'data' => [
                'token' => $token,
                'user' => $this->formatUser($student),
                'student' => $this->formatStudent($student),
                'faculty' => null,
            ],
        ]);
    }

    private function completeFacultyLogin(Faculty $faculty, string $password, string $loginId): JsonResponse
    {
        $this->assertNotLocked($faculty, $loginId);

        if (($faculty->account_status ?? 'Active') !== 'Active') {
            throw ValidationException::withMessages([
                'login_id' => ['This faculty account is not active. Please contact the library staff.'],
            ]);
        }

        $storedHash = $faculty->password;

        if ($storedHash === null) {
            $storedHash = Hash::make($faculty->deriveDefaultPassword());
            $faculty->forceFill(['password' => $storedHash])->save();
        }

        if (! Hash::check($password, $storedHash)) {
            $faculty->recordFailedAttempt();

            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        $faculty->resetFailedAttempts();

        $needsChange = $faculty->needsPasswordChange();
        $abilities = $needsChange ? ['password-change'] : ['full-access'];
        $tokenName = $needsChange ? 'pantas-mobile-pending' : 'pantas-mobile';
        $token = $faculty->createToken($tokenName, $abilities)->plainTextToken;

        return response()->json([
            'message' => $needsChange
                ? 'Login successful. Password change required.'
                : 'Login successful.',
            'must_change_password' => $needsChange,
            'data' => [
                'token' => $token,
                'user' => $this->formatUser($faculty),
                'student' => null,
                'faculty' => $this->formatFaculty($faculty),
            ],
        ]);
    }

    /**
     * @param  array{current_password: string, password: string}  $validated
     */
    private function completePasswordChange(Student|Faculty $patron, array $validated, bool $isFaculty): JsonResponse
    {
        $storedHash = $patron->password;
        if ($storedHash === null) {
            $storedHash = Hash::make($patron->deriveDefaultPassword());
            $patron->forceFill(['password' => $storedHash])->save();
        }

        if (! Hash::check($validated['current_password'], $storedHash)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $patron->forceFill([
            'password' => Hash::make($validated['password']),
            'password_setup_completed' => true,
            'force_password_reset' => false,
        ])->save();

        $patron->tokens()->delete();

        $newToken = $patron->createToken('pantas-mobile', ['full-access'])->plainTextToken;

        if (! $isFaculty && $patron instanceof Student) {
            StudentNotification::create([
                'student_id' => $patron->id,
                'type' => 'password_changed',
                'title' => 'Password changed',
                'message' => 'Your password was changed successfully.',
            ]);
        }

        $payload = [
            'token' => $newToken,
            'user' => $this->formatUser($patron),
            'student' => $isFaculty ? null : $this->formatStudent($patron instanceof Student ? $patron : null),
            'faculty' => $isFaculty && $patron instanceof Faculty ? $this->formatFaculty($patron) : null,
        ];

        return response()->json([
            'message' => 'Password changed successfully.',
            'must_change_password' => false,
            'data' => $payload,
        ]);
    }

    private function assertNotLocked(Student|Faculty $patron, string $loginId): void
    {
        if (! $patron->isLocked()) {
            return;
        }

        $minutesRemaining = Carbon::now()->diffInMinutes($patron->locked_until, true);
        $minutesRemaining = max(1, (int) ceil($minutesRemaining));

        throw ValidationException::withMessages([
            'login_id' => [
                "Account is temporarily locked due to too many failed login attempts. Please try again in {$minutesRemaining} minute(s).",
            ],
        ]);
    }

    private function resolveStudent(Request $request): Student|JsonResponse
    {
        $tokenable = $request->user();

        if ($tokenable instanceof Faculty) {
            return response()->json([
                'message' => 'This endpoint is only available to students.',
                'data' => null,
            ], 403);
        }

        if ($tokenable instanceof Student) {
            return $tokenable;
        }

        if ($tokenable instanceof User) {
            if (app(ModuleAccessService::class)->availableModules($tokenable) !== []) {
                return response()->json([
                    'message' => 'This account is not allowed to use the mobile app.',
                    'data' => null,
                ], 403);
            }

            $tokenable->loadMissing('student');

            if ($tokenable->student) {
                return $tokenable->student;
            }
        }

        return response()->json([
            'message' => 'No student profile is linked to this account.',
            'data' => null,
        ], 409);
    }

    private function formatUser(User|Student|Faculty $user): array
    {
        if ($user instanceof Student) {
            return [
                'id' => $user->id,
                'name' => trim((string) $user->firstname.' '.(string) $user->lastname),
                'fname' => $user->firstname,
                'lname' => $user->lastname,
                'email' => null,
                'role' => 'student',
            ];
        }

        if ($user instanceof Faculty) {
            return [
                'id' => $user->id,
                'name' => trim((string) $user->firstname.' '.(string) $user->lastname),
                'fname' => $user->firstname,
                'lname' => $user->lastname,
                'email' => $user->email,
                'role' => 'faculty',
            ];
        }

        return [
            'id' => $user->id,
            'name' => trim((string) $user->fname.' '.(string) $user->lname),
            'fname' => $user->fname,
            'lname' => $user->lname,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }

    private function formatStudent(?Student $student): ?array
    {
        if (! $student) {
            return null;
        }

        return [
            'id' => $student->id,
            'id_number' => $student->id_number,
            'lastname' => $student->lastname,
            'firstname' => $student->firstname,
            'middle_initial' => $student->middle_initial,
            'course' => $student->course,
            'year' => $student->year,
            'birthday' => $student->birthday?->toDateString(),
            'mobile_number' => $student->mobile_number,
            'address' => $student->address,
            'emergency_person' => $student->emergency_person,
            'emergency_relationship' => $student->emergency_relationship,
            'emergency_number' => $student->emergency_number,
            'emergency_address' => $student->emergency_address,
            'profile_picture' => filled($student->profile_picture)
                ? asset($student->profile_picture)
                : null,
        ];
    }

    private function formatFaculty(?Faculty $faculty): ?array
    {
        if (! $faculty) {
            return null;
        }

        return [
            'id' => $faculty->id,
            'employee_id' => $faculty->employee_id,
            'lastname' => $faculty->lastname,
            'firstname' => $faculty->firstname,
            'middle_initial' => $faculty->middle_initial,
            'email' => $faculty->email,
            'department' => $faculty->department,
            'designation' => $faculty->designation,
            'birthday' => $faculty->birthday?->toDateString(),
            'mobile_number' => $faculty->mobile_number,
            'account_status' => $faculty->account_status,
            'profile_picture' => filled($faculty->profile_picture)
                ? asset($faculty->profile_picture)
                : null,
        ];
    }
}
