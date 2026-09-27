<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    public function handle(Request $request, Closure $next, $module): Response
    {
        $user = auth()->user();

        if (!$user || !$user->role) {
            abort(403);
        }

        $hasAccess = $user->role
            ->modules()
            ->where('name', $module)
            ->exists();

        if (!$hasAccess) {
            abort(403);
        }

        return $next($request);
    }
}