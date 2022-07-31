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
    public function Index()
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
    public function Create()
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
    public function Store()
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
    public function Show(string $uuid)
    {
        return $this->sendStandardRequest("menu/$uuid", session(__JWT__));
    }

    /**
     * 菜单编辑页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function Edit(string $uuid)
    {
        return view("Menu.edit", ["uuid" => $uuid]);
    }

    /**
     * 更新菜单
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Update(string $uuid)
    {
        return $this->sendStandardRequest("menu/$uuid", session(__JWT__));
    }

    /**
     * 删除菜单
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function Destroy(string $uuid)
    {
        return $this->sendStandardRequest("menu/$uuid", session(__JWT__));
    }
}