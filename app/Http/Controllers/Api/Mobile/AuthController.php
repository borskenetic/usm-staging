<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\StudentChangePasswordRequest;
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
     * Authenticate a student using student_id + password.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $student = Student::query()
            ->where('id_number', trim($validated['student_id']))
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'student_id' => ['The provided Student ID was not found.'],
            ]);
        }

        // Check account lock state
        if ($student->isLocked()) {
            $minutesRemaining = Carbon::now()->diffInMinutes($student->locked_until, true);
            $minutesRemaining = max(1, (int) ceil($minutesRemaining));

            throw ValidationException::withMessages([
                'student_id' => [
                    "Account is temporarily locked due to too many failed login attempts. Please try again in {$minutesRemaining} minute(s).",
                ],
            ]);
        }

        // Determine the effective password to verify against
        $storedHash = $student->password;
        $effectivePassword = $validated['password'];

        // If no password set yet, derive from birthday
        if ($storedHash === null) {
            if (! $student->birthday) {
                throw ValidationException::withMessages([
                    'student_id' => ['This account has no password configured. Please contact the library staff.'],
                ]);
            }
            $storedHash = Hash::make($student->deriveDefaultPassword());
            $student->forceFill(['password' => $storedHash])->save();
        }

        // Verify password
        if (! Hash::check($effectivePassword, $storedHash)) {
            $student->recordFailedAttempt();

            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        // Password correct — reset failed attempts
        $student->resetFailedAttempts();

        // Check if password change is required
        $needsChange = $student->needsPasswordChange();

        if ($needsChange) {
            // Issue limited-scope token (only valid for change-password)
            $token = $student->createToken('pantas-mobile-pending', ['password-change'])->plainTextToken;

            return response()->json([
                'message' => 'Login successful. Password change required.',
                'must_change_password' => true,
                'data' => [
                    'token' => $token,
                    'user' => $this->formatUser($student),
                    'student' => $this->formatStudent($student),
                ],
            ]);
        }

        // Issue full-access token
        $token = $student->createToken('pantas-mobile', ['full-access'])->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'must_change_password' => false,
            'data' => [
                'token' => $token,
                'user' => $this->formatUser($student),
                'student' => $this->formatStudent($student),
            ],
        ]);
    }

    /**
     * POST /mobile/student/change-password
     *
     * Student-facing password change (requires password-change scoped token).
     */
    public function studentChangePassword(StudentChangePasswordRequest $request): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof Student) {
            return response()->json([
                'message' => 'Only students can change their password through this endpoint.',
                'data' => null,
            ], 403);
        }

        $validated = $request->validated();

        // Verify current password against stored hash
        $storedHash = $student->password;
        if ($storedHash === null && $student->birthday) {
            $storedHash = Hash::make($student->deriveDefaultPassword());
            $student->forceFill(['password' => $storedHash])->save();
        }

        if (! Hash::check($validated['current_password'], $storedHash)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Update password
        $student->forceFill([
            'password' => Hash::make($validated['password']),
            'password_setup_completed' => true,
            'force_password_reset' => false,
        ])->save();

        // Invalidate all existing tokens for this student
        $student->tokens()->delete();

        // Issue a fresh full-access token
        $newToken = $student->createToken('pantas-mobile', ['full-access'])->plainTextToken;

        // Create in-app notification
        StudentNotification::create([
            'student_id' => $student->id,
            'type' => 'password_changed',
            'title' => 'Password changed',
            'message' => 'Your password was changed successfully.',
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
            'must_change_password' => false,
            'data' => [
                'token' => $newToken,
                'user' => $this->formatUser($student),
                'student' => $this->formatStudent($student),
            ],
        ]);
    }

    /**
     * POST /mobile/change-password
     *
     * Staff-only password change (kept for backward compatibility with User model).
     */
    public function changePassword(Request $request): JsonResponse
    {
        if ($request->user() instanceof Student) {
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

        // Clear self-set password — reverts to derived default
        $student->forceFill([
            'password' => null,
            'password_setup_completed' => false,
            'force_password_reset' => true,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        // Invalidate all existing tokens
        $student->tokens()->delete();

        // Log the reset
        StudentPasswordResetLog::create([
            'student_id' => $student->id,
            'staff_id' => $staff->id,
            'reason' => $validated['reason'] ?? null,
        ]);

        // Notify the student
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
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        return response()->json([
            'message' => 'Authenticated user retrieved.',
            'data' => [
                'user' => $this->formatUser($student),
                'student' => $this->formatStudent($student),
            ],
        ]);
    }

    private function resolveStudent(Request $request): Student|JsonResponse
    {
        $tokenable = $request->user();

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

    private function formatUser(User|Student $user): array
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
}