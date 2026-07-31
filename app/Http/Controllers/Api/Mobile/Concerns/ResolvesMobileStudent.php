<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\Concerns;

use App\Models\Faculty;
use App\Models\Student;
use App\Models\User;
use App\Services\Auth\ModuleAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ResolvesMobileStudent
{
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
}
