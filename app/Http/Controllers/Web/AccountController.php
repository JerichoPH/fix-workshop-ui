<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * 用户列表
     * @throws UnLoginException
     * @throws ForbiddenException
     * @throws EmptyException
     * @throws UnAuthorizationException
     */
    final public function Index()
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest("account", session(__JWT__));
        } else {
            return view("Account.index");
        }
    }

    /**
     * 新建用户页面
     * @return Factory|Application|View
     */
    final public function Create()
    {
        return view("Account.create");
    }

    /**
     * 新建用户
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    final public function Store()
    {
        return $this->sendStandardRequest("account", session(__JWT__));
    }

    /**
     * 角色详情
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    final public function Show(string $uuid)
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest("account/$uuid", session(__JWT__));
        }
        return null;
    }

    /**
     * 编辑用户页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    final public function Edit(string $uuid)
    {
        return view("Account.edit", ["uuid" => $uuid,]);
    }

    /**
     * 编辑用户
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    final public function Update(string $uuid)
    {
        return $this->sendStandardRequest("account/$uuid", session(__JWT__));
    }

    /**
     * 编辑用户密码
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    final public function UpdatePassword(string $uuid)
    {
        return $this->sendStandardRequest("account/$uuid/updatePassword", session(__JWT__));
    }

    /**
     * 删除用户
     * @param string $uuid
     */
    final public function Destroy(string $uuid)
    {

    }
}
