<?php

namespace App\Http\Middleware;

use App\Enums\UserScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTeamId
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            if ($user->role === UserScope::VENDOR) {
                setPermissionsTeamId($user->manager_id ?? $user->id);
            } else {
                setPermissionsTeamId(1);
            }
        }

        return $next($request);
    }
}
