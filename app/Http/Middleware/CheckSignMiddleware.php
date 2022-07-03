<?php

namespace App\Http\Middleware;

use App\Facades\JsonResponseFacade;
use App\Facades\TextFacade;
use App\Model\Account;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CheckSignMiddleware
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
        try {
            if (!boolval($request->header('dev'))) {
                $account = Account::with([])->where('access_key', $request->header('access_key'))->firstOrFail();
                $checkSign = TextFacade::checkSign2($request->all(), $account->secret_key, $request->header('sign'));
                if (!$checkSign['result'])
                    return JsonResponseFacade::errorUnauthorized('验签失败', $checkSign['details']);
            }

            return $next($request);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '用户不存在'], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }
}
