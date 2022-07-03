<?php

namespace App\Http\Middleware;

use Closure;

class GetIpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        session()->put('currentClientIp', $request->getClientIp());
        return $next($request);
    }
}
