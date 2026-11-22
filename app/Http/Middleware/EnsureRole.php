<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    // Usage in routes: ->middleware('role:admin') or ->middleware('role:tenant')
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($request->user()?->role->value === $role, 403);

        return $next($request);
    }
}
