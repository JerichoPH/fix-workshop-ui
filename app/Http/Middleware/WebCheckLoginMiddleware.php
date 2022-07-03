<?php

namespace App\Http\Middleware;

use App\Facades\JsonResponseFacade;
use App\Facades\Rbac;
use App\Model\Account;
use Closure;
use Illuminate\Http\Request;
use Jericho\TextHelper;

class WebCheckLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (request('account')) {
            # 验证密码
            $account = Account::with([])->where('account', request('account'))->firstOrFail()->toArray();

            # 获取用户权限相关信息
            $account['menus'] = Rbac::getMenus($account['id'])->toArray();  # 获取用户菜单
            $account['treeJson'] = TextHelper::toJson(Rbac::toTree($account['menus']));
            $account['permissionIds'] = Rbac::getPermissionIds($account['id'])->toArray();  # 获取权限编号

            # 记录用户数据
            session()->put('account', $account);
        }

        $loginUrl = 'login?' . http_build_query(['target' => $request->getRequestUri()]);

        if (!session()->has('account.id')) return $request->ajax()
            ? JsonResponseFacade::errorUnLogin()
            : redirect($loginUrl);
        return $next($request);
    }
}
