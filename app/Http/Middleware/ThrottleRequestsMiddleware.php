<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThrottleRequestsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param Closure $next
     * @param int $maxAttempts
     * @param int $decayMinutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (env('IP_CONTROLLER') == true) {
            $whites = config('ip.white');
            $blacks = config('ip.black');
            $not_in_white = !in_array($request->getClientIp(), $whites);
            $in_black = in_array($request->getClientIp(), $blacks);
            if ($not_in_white || $in_black) return response()->make('拒绝访问', 421);
        }
        return $next($request);
    }
}
