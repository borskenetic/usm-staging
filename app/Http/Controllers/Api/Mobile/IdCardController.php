<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\Auth\ModuleAccessService;
use App\Services\LibraryIdCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdCardController extends Controller
{
    public function __construct(
        private readonly LibraryIdCardService $idCardService,
    ) {}

    /**
     * GET /mobile/id-card
     *
     * Returns freshly generated front/back library ID PNGs as base64.
     */
    public function show(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $frontPng = $this->idCardService->frontPngForStudent($student);
        $backPng = $this->idCardService->backPngForStudent($student);

        return response()->json([
            'message' => 'Digital ID retrieved.',
            'data' => [
                'front_png_base64' => base64_encode($frontPng),
                'back_png_base64' => base64_encode($backPng),
                'mime' => 'image/png',
                'student_number' => $student->id_number,
                'full_name' => trim("{$student->firstname} {$student->lastname}"),
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
}
