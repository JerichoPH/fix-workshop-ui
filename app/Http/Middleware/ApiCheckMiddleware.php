<?php

namespace App\Http\Middleware;

use App\Model\Account;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Jericho\HttpResponseHelper;

class ApiCheckMiddleware
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
            if (!array_key_exists('HTTP_HEADERS_AS_BASE64', $_SERVER)) return response()->json(['message' => '权限认证错误','status'=>401], 401);
            $headers = json_decode(base64_decode($_SERVER['HTTP_HEADERS_AS_BASE64']), true);
            $account = Account::with([])->where('account', array_key_exists('Account', $headers) ? $headers['Account'] : '')->firstOrFail();
            if (!Hash::check(array_key_exists('Password', $headers) ? $headers['Password'] : '', $account->password)) return HttpResponseHelper::errorUnauthorized('账号或密码不匹配');
            session()->put('account', $account->toArray());
        } catch (ModelNotFoundException $e) {
            return HttpResponseHelper::errorUnauthorized('用户不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::errorUnauthorized('意外错误：' . $exception->getMessage() . '  ' . $exception->getFile() . '  ' . $exception->getLine());
        }
        return $next($request);
    }
}
