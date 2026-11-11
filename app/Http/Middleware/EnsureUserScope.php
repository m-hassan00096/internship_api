<?php

namespace App\Http\Middleware;

use App\Enums\HttpStatusCode;
use App\Enums\UserScope;
use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $user = $request->user();

        $expectedScope = match (strtolower($scope)) {
            'client' => UserScope::CLIENT,
            'vendor' => UserScope::VENDOR,
            'admin' => UserScope::ADMIN,
            default => null,
        };

        if (! $user || ! $expectedScope || $user->role !== $expectedScope) {
            return ApiResponse::error(
                __('auth.forbidden'),
                HttpStatusCode::FORBIDDEN
            );
        }

        return $next($request);
    }
}
