<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;

class VerifyApiToken
{
    /**
     * Handle an incoming API request from external systems like Workshop Management System.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-API-Key');
        if (!$token && $request->bearerToken()) {
            $token = $request->bearerToken();
        }

        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized: Missing API Key header (X-API-Key or Authorization Bearer token).'
            ], 401);
        }

        $apiToken = ApiToken::where('token', $token)->where('status', 'active')->first();

        if (!$apiToken) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized: Invalid or revoked API Token.'
            ], 401);
        }

        // Update token last used timestamp
        $apiToken->forceFill(['last_used_at' => now()])->save();

        // Attach organization context to request
        $request->merge([
            'api_organization_id' => $apiToken->organization_id,
            'api_token_id' => $apiToken->id,
        ]);

        session(['tenant_id' => $apiToken->organization_id]);

        return $next($request);
    }
}
