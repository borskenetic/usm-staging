<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\Controller;
use App\Services\LibraryIdCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdCardController extends Controller
{
    use ResolvesMobileStudent;

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
}
