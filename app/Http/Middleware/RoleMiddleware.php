<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Check if the authenticated user's role matches the required role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role  The required role name
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();

        if (! $user || $user->role->name !== $role) {
            return response()->json(['message' => 'Forbidden - You do not have access to this resource'], 403);
        }

        return $next($request);
    }
}
