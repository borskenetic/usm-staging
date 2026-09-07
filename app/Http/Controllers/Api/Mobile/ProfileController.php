<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\Controller;
use App\Models\StudentEditRequest;
use App\Services\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ResolvesMobileStudent;

    /**
     * POST /mobile/profile/update
     */
    public function update(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        if ($student->editRequests()->where('status', 'pending')->exists()) {
            return response()->json([
                'message' => 'You already have a pending edit request.',
                'data' => null,
            ], 409);
        }

        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'course' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'string', 'max:10'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'emergency_person' => ['nullable', 'string', 'max:255'],
            'emergency_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_number' => ['nullable', 'string', 'max:20'],
            'emergency_address' => ['nullable', 'string'],
        ]);

        $course = $validated['course'] ?? $student->course;

        $editRequest = StudentEditRequest::query()->create([
            'student_id' => $student->id,
            'lastname' => $validated['last_name'],
            'firstname' => $validated['first_name'],
            'middle_initial' => $validated['middle_initial'] ?? $student->middle_initial,
            'birthday' => $validated['birthday'] ?? $student->birthday,
            'course' => $course,
            'year' => $validated['year'] ?? $student->year,
            'mobile_number' => $validated['mobile_number'] ?? $student->mobile_number,
            'address' => $validated['address'] ?? $student->address,
            'emergency_person' => $validated['emergency_person'] ?? $student->emergency_person,
            'emergency_relationship' => $validated['emergency_relationship'] ?? $student->emergency_relationship,
            'emergency_number' => $validated['emergency_number'] ?? $student->emergency_number,
            'emergency_address' => $validated['emergency_address'] ?? $student->emergency_address,
            'status' => 'pending',
        ]);

        app(AdminActivityLogger::class)->patronEditRequest(
            $editRequest,
            "{$student->lastname}, {$student->firstname}",
        );

        return $this->profileResponse($request, 'Edit request submitted for approval.');
    }

    /**
     * POST /mobile/profile/update-picture
     */
    public function updatePicture(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'profile_picture' => ['required', 'image', 'max:2048'],
            'change_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $photoPath = $this->storeProfilePicture($request->file('profile_picture'));
        $reason = $validated['change_reason'] ?? 'Profile picture update';

        $pending = $student->editRequests()->where('status', 'pending')->first();

        if ($pending) {
            $pending->update([
                'profile_picture' => $photoPath,
                'admin_note' => $reason,
            ]);
        } else {
            StudentEditRequest::query()->create([
                'student_id' => $student->id,
                'lastname' => $student->lastname,
                'firstname' => $student->firstname,
                'middle_initial' => $student->middle_initial,
                'birthday' => $student->birthday,
                'course' => $student->course,
                'year' => $student->year,
                'mobile_number' => $student->mobile_number,
                'address' => $student->address,
                'emergency_person' => $student->emergency_person,
                'emergency_relationship' => $student->emergency_relationship,
                'emergency_number' => $student->emergency_number,
                'emergency_address' => $student->emergency_address,
                'profile_picture' => $photoPath,
                'admin_note' => $reason,
                'status' => 'pending',
            ]);
        }

        return $this->profileResponse($request, 'Edit request submitted for approval.');
    }

    private function profileResponse(Request $request, string $message): JsonResponse
    {
        $meResponse = app(AuthController::class)->me($request);
        $payload = $meResponse->getData(true);
        $payload['message'] = $message;

        return response()->json($payload, $meResponse->getStatusCode());
    }

    private function storeProfilePicture($image): string
    {
        $filename = time().'_'.preg_replace('/\s+/', '_', $image->getClientOriginalName());
        $directory = base_path('images/edits');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $image->move($directory, $filename);

        return 'images/edits/'.$filename;
    }
}
