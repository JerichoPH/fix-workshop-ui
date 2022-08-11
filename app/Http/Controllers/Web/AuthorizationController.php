<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

class AuthorizationController extends Controller
{
    /**
     * 登录页面
     * @return Factory|Application|View
     */
    public function GetLogin()
    {
        return view("Authorization.login");
    }

    /**
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function PostLogin(Request $request)
    {
        $this->sendStandardRequest(
            "authorization/login",
            null,
            function () {
                if (!$this->curl->error) {
                    session()->put(__JWT__, $this->curl->response->content->token);
                    session()->put(__ACCOUNT__, [
                        "username" => $this->curl->response->content->username,
                        "nickname" => $this->curl->response->content->nickname,
                        "uuid" => $this->curl->response->content->uuid,
                    ]);
                }
                return $this->handleResponse();
            }
        );
    }

    /**
     * 获取当前用户菜单
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function GetMenus()
    {
        return $this->sendStandardRequest("authorization/menus");
    }

    /**
     * 退出
     * @return Application|RedirectResponse|Redirector
     */
    public function GetLogout()
    {
        session()->forget(__ACCOUNT__);
        session()->forget(__JWT__);

        return redirect("authorization/login");
    }

    /**
     * 退出
     * @return mixed
     */
    public function PostLogout()
    {
        session()->forget(__ACCOUNT__);
        session()->forget(__JWT__);

        return JsonResponseFacade::OK("退出成功");
    }
}
