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

class RbacRoleController extends Controller
{
    /**
     * 角色列表
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function index()
    {
        return request()->ajax() ? $this->sendStandardRequest('rbacRole') : view('RbacRole.index');
    }

    /**
     * 新建角色页面
     * @return Factory|Application|View
     */
    public function create()
    {
        return view('RbacRole.create');
    }

    /**
     * 新建角色
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function store()
    {
        return $this->sendStandardRequest('rbacRole');
    }

    /**
     * 角色详情
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function show(string $uuid)
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest("rbacRole/$uuid");
        }
        return null;
    }

    /**
     * 编辑角色页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function edit(string $uuid)
    {
        return view('RbacRole.edit', ['uuid' => $uuid]);
    }

    /**
     * 编辑角色
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function update(string $uuid)
    {
        return $this->sendStandardRequest("rbacRole/$uuid");
    }

    /**
     * 删除角色
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function destroy(string $uuid)
    {
        return $this->sendStandardRequest("rbacRole/$uuid");
    }

    /**
     * 角色绑定管理页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function getBind(string $uuid)
    {
        return view('RbacRole.bind', ['uuid' => $uuid,]);
    }

    /**
     * 绑定用户
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function putBindAccounts(string $uuid)
    {
        return $this->sendStandardRequest("rbacRole/$uuid/bindAccounts");
    }

    /**
     * 绑定权限
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function putBindRbacPermissions(string $uuid)
    {
        return $this->sendStandardRequest("rbacRole/$uuid/bindRbacPermissions");
    }

    /**
     * 绑定权限（根据权限分组）
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function putBindRbacPermissionsByRbacPermissionGroup(string $uuid)
    {
        return $this->sendStandardRequest("rbacRole/$uuid/bindRbacPermissionsByRbacPermissionGroup");
    }
}
