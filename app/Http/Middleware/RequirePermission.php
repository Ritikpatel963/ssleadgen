<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    /**
     * Expects route middleware like: permission:access,write
     */
    public function handle(Request $request, Closure $next, string $module, string $level = 'read'): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($module, $level)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            abort(403, 'You do not have permission to access this section.');
        }

        return $next($request);
    }
}
