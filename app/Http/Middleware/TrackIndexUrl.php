<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackIndexUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        // Only track GET requests that have a named route ending in '.index'
        if ($route && $request->isMethod('GET') && str_ends_with($route->getName() ?? '', '.index')) {
            session(['index_url_' . $route->getName() => $request->fullUrl()]);
        }

        return $next($request);
    }
}
