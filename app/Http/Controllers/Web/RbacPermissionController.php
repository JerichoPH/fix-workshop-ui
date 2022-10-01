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
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function index()
    {
        return request()->ajax() ? $this->sendStandardRequest('rbacPermission') : view('RbacPermission.index');
    }

    /**
     * 新建页面
     * @return Factory|Application|View
     */
    public function create()
    {
        return view('RbacPermission.create');
    }

    /**
     * 新建
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function store()
    {
        return $this->sendStandardRequest('rbacPermission');
    }

    /**
     * 详情
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function show(string $uuid)
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest('rbacPermission/{$uuid}');
        }
        return null;
    }

    /**
     * 编辑页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function edit(string $uuid)
    {
        return view('RbacPermission.edit', ['uuid' => $uuid,]);
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
    public function update(string $uuid)
    {
        return $this->sendStandardRequest('rbacPermission/{$uuid}');
    }

    /**
     * 删除
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function destroy(string $uuid)
    {
        return $this->sendStandardRequest('rbacPermission/{$uuid}');
    }

    /**
     * 批量添加资源路由模态框
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function PostResource()
    {
        return $this->sendStandardRequest('rbacPermission/resource');
    }
}
