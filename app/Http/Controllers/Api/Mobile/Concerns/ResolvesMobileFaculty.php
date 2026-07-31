<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\Concerns;

use App\Models\Faculty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ResolvesMobileFaculty
{
    private function resolveFaculty(Request $request): Faculty|JsonResponse
    {
        $tokenable = $request->user();

        if ($tokenable instanceof Faculty) {
            return $tokenable;
        }

        return response()->json([
            'message' => 'This endpoint is only available to faculty.',
            'data' => null,
        ], 403);
    }
}
