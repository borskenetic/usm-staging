<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\PendingFaculty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FacultyRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:16'],
            'employee_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('library_pending_faculty', 'employee_id'),
                Rule::unique('library_faculty', 'employee_id'),
            ],
            'designation' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'program' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'mobile_number' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:255'],
            'emergency_address' => ['nullable', 'string'],
        ]);

        $department = trim((string) ($validated['department'] ?? $validated['program'] ?? ''));
        if ($department === '') {
            throw ValidationException::withMessages([
                'department' => ['Department or program is required.'],
            ]);
        }

        $birthday = $validated['birthday'] ?? $validated['birth_date'] ?? null;
        if ($birthday === null) {
            throw ValidationException::withMessages([
                'birthday' => ['Birthday is required.'],
            ]);
        }

        $employeeId = trim($validated['employee_id']);

        if (Faculty::query()->where('employee_id', $employeeId)->exists()
            || PendingFaculty::query()->where('employee_id', $employeeId)->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => ['This employee ID is already registered or pending approval.'],
            ]);
        }

        $pending = PendingFaculty::query()->create([
            'employee_id' => $employeeId,
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'middle_initial' => $validated['middle_initial'] ?? null,
            'email' => $validated['email'] ?? null,
            'department' => $department,
            'designation' => $validated['designation'],
            'birthday' => $birthday,
            'mobile_number' => $validated['mobile_number'],
            'address' => $validated['address'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
            'emergency_contact_number' => $validated['emergency_contact_number'] ?? null,
            'emergency_address' => $validated['emergency_address'] ?? null,
        ]);

        return response()->json([
            'message' => 'Faculty registration submitted. Please wait for library approval before signing in.',
            'data' => [
                'id' => $pending->id,
                'employee_id' => $pending->employee_id,
                'status' => 'pending',
            ],
        ], 201);
    }
}
