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

class RbacPermissionGroupController extends Controller
{
    /**
     * 权限列表
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function index()
    {
        return request()->ajax() ? $this->sendStandardRequest('rbacPermissionGroup') : view('RbacPermissionGroup.index');
    }

    /**
     * 创建权限分组页面
     * @return Factory|Application|View
     */
    public function create()
    {
        return view('RbacPermissionGroup.create');
    }

    /**
     * 创建权限分组
     * @param Request $request
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function store(Request $request)
    {
        return $this->sendStandardRequest('rbacPermissionGroup');
    }

    /**
     * 权限分组详情
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function show(string $uuid)
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest('rbacPermissionGroup/$uuid');
        }
        return null;
    }

    /**
     * 编辑权限分组页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function edit(string $uuid)
    {
        return view('RbacPermissionGroup.edit', ['uuid' => $uuid]);
    }

    /**
     * 编辑权限分组
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function update(string $uuid)
    {
        return $this->sendStandardRequest('rbacPermissionGroup/$uuid');
    }

    /**
     * 删除权限分组
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function destroy(string $uuid)
    {
        return $this->sendStandardRequest('rbacPermissionGroup/$uuid');
    }
}
