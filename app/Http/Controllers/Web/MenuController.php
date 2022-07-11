<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * 列表
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    final public function Index()
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest(
                "menu",
                session(__JWT__),
                function (Request $request) {
                    $request = $request->all();
                    $request["parent_uuid"] = @$request["parent_uuid"] ?: "";
                    return $request;
                });
        } else {
            return view("Menu.index");
        }
    }

    /**
     * 新建菜单页面
     * @return Factory|Application|View
     */
    final public function Create()
    {
        return view("Menu.create");
    }

    /**
     * 新建菜单
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    final public function Store()
    {
        return $this->sendStandardRequest("menu", session(__JWT__));
    }

    /**
     * 菜单详情
     * @throws UnLoginException
     * @throws ForbiddenException
     * @throws EmptyException
     * @throws UnAuthorizationException
     */
    final public function Show(string $uuid)
    {
        return $this->sendStandardRequest("menu/$uuid", session(__JWT__));
    }

    /**
     * 菜单编辑页面
     * @return Factory|Application|View
     */
    final public function Edit()
    {
        return view("Menu.show");
    }

    final public function Update()
    {

    }

    final public function Destroy()
    {

    }
}
