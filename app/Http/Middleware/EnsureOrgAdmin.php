<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrgAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || (!Auth::user()->isSuperAdmin() && !Auth::user()->isOrgAdmin())) {
            abort(403, 'Unauthorized access: Organization Administrator privileges required.');
        }

        return $next($request);
    }
}
