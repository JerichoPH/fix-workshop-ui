<?php

namespace App\Http\Middleware;

use App\Facades\JsonResponseFacade;
use Closure;
use Illuminate\Http\Request;

class CheckParagraphSyncBasicDataMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!env('PARAGRAPH_SYNC_BASIC_DATA')) {
            return JsonResponseFacade::errorForbidden('未开启段中心维护权限');
        }
        return $next($request);
    }
}
