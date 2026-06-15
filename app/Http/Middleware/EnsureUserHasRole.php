<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole($roles)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $activeRole = $request->session()->get('active_role');
        if ($activeRole && $user->hasRole($activeRole) && ! in_array($activeRole, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
