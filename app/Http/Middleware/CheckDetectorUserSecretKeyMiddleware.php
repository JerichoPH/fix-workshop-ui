<?php

namespace App\Http\Middleware;

use App\Facades\JsonResponseFacade;
use App\Model\DetectorUser;
use Closure;
use Illuminate\Http\Request;

class CheckDetectorUserSecretKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    final public function handle(Request $request, Closure $next)
    {
        if (!env("ALLOW_ALL_DETECTOR_USER_PASS")) {
            $request_data = $request->all();
            @$request_header = $request_data["header"];
            if (empty($request_header)) return JsonResponseFacade::errorValidate('没有收到检测数据(数据头)');
            if (!@$request_header["secret_key"]) return JsonResponseFacade::errorEmpty("secretKey不能为空");

            if (!DetectorUser::with([])->where("secret_key", @$request_header["secret_key"])->exists()) {
                return JsonResponseFacade::errorForbidden("密码错误");
            }
        }
        return $next($request);
    }
}
