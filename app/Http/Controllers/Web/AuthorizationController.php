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
    final public function GetLogin()
    {
        return view("Authorization.login");
    }

    /**
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    final public function PostLogin(Request $request)
    {
        $this->sendStandardRequest(
            "authorization/login",
            $request->session()->get(__JWT__),
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
     * 退出
     * @return Application|RedirectResponse|Redirector
     */
    final public function GetLogout()
    {
        session()->forget(__ACCOUNT__);
        session()->forget(__JWT__);

        return redirect("authorization/login");
    }

    /**
     * 退出
     * @return mixed
     */
    final public function PostLogout()
    {
        session()->forget(__ACCOUNT__);
        session()->forget(__JWT__);

        return JsonResponseFacade::ok("退出成功");
    }
}
