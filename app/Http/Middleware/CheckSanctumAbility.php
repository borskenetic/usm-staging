<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSanctumAbility
{
    /**
     * Verify that the authenticated token has a specific Sanctum ability.
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            return response()->json([
                'message' => 'No valid token provided.',
                'data' => null,
            ], 401);
        }

        foreach ($abilities as $ability) {
            if (! $token->can($ability)) {
                return response()->json([
                    'message' => 'Your current token does not have permission for this action.',
                    'data' => null,
                ], 403);
            }
        }

        return $next($request);
    }
}