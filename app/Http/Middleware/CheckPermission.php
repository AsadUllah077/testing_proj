<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission): Response
    {
        $isApiRequest = $request->expectsJson() || $request->is('api/*');

        // Check if authenticated
        if (! $request->user()) {
            if ($isApiRequest) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('user.login.view');
        }

        // Check if soft deleted (account deactivated)
        if ($request->user()->trashed()) {
            if ($isApiRequest) {
                return response()->json(['success' => false, 'message' => 'Your account has been deactivated.'], 403);
            }

            Auth::logout();

            return redirect()->route('user.login.view')->with('error', 'Your account has been deactivated.');
        }

        // Super Admin bypass (only User model has isSuperAdmin)
        if (method_exists($request->user(), 'isSuperAdmin') && $request->user()->isSuperAdmin()) {
            return $next($request);
        }

        // Parse pipe-separated permissions (e.g. "api.report.individual|api.report.all" means any of them)
        $permissions = str_contains($permission, '|') ? explode('|', $permission) : [$permission];

        $hasPermission = count($permissions) === 1
            ? $request->user()->hasPermission($permissions[0])
            : $request->user()->hasAnyPermission($permissions);

        if (! $hasPermission) {
            if ($isApiRequest) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action.'], 403);
            }

            return redirect()->back()->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
