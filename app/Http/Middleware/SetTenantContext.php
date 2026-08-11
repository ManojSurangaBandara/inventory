<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->is_super_admin) {
                // Check if superadmin switched tenant context via query parameter or session
                if ($request->has('switch_tenant')) {
                    session(['active_tenant_id' => $request->get('switch_tenant')]);
                }
            } else {
                session(['active_tenant_id' => $user->organization_id]);
            }

            View::share('currentUser', $user);
            View::share('currentOrganization', $user->organization);
        }

        return $next($request);
    }
}
