<?php

namespace App\Http\Middleware;

use App\Facades\JsonResponseFacade;
use App\Model\Account;
use Closure;
use Dingo\Api\Routing\Helpers;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Jericho\JwtHelper;

class ApiCheckJwtMiddleware
{
    use Helpers;

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
            $jwt = JWT::decode($request->header('Token'), env('JWT_KEY'), ['HS256']);
            // if ($jwt->exp < time()) return JsonResponseFacade::errorUnauthorized('令牌过期', $jwt);

            $account = Account::with(['WorkAreaByUniqueCode',])->where('id', $jwt->payload->id)->firstOrFail();

            session()->put('account', $account->toArray());
            return $next($request);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorUnauthorized('没有找到用户');
        } catch (\Exception $e) {
            return JsonResponseFacade::errorUnauthorized('令牌解析失败');
        }
    }
}
