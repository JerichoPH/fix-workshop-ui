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

class RbacPermissionController extends Controller
{
    /**
     * 权限列表
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    final public function Index()
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest("rbacPermission", session(__JWT__));
        } else {
            return view("RbacPermission.index");
        }
    }

    /**
     * 新建页面
     * @return Factory|Application|View
     */
    final public function Create()
    {
        return view("RbacPermission.create");
    }

    /**
     * 新建
     * @throws UnLoginException
     * @throws ForbiddenException
     * @throws EmptyException
     * @throws UnAuthorizationException
     */
    final public function Store()
    {
        return $this->sendStandardRequest("rbacPermission", session(__JWT__));
    }

    /**
     * 详情
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    final public function Show(string $uuid)
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest("rbacPermission/{$uuid}", session(__JWT__));
        }
        return null;
    }

    /**
     * 编辑页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    final public function Edit(string $uuid)
    {
        return view("RbacPermission.edit", ["uuid" => $uuid,]);
    }

    /**
     * 编辑
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    final public function Update(string $uuid)
    {
        return $this->sendStandardRequest("rbacPermission/{$uuid}", session(__JWT__));
    }

    /**
     * 删除
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    final public function Destroy(string $uuid)
    {
        return $this->sendStandardRequest("rbacPermission/{$uuid}", session(__JWT__));
    }
}
