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

class OrganizationWorkshopTypeController extends Controller
{
    /**
     * 车间类型列表
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Index()
    {
        return request()->ajax() ? $this->sendStandardRequest("organizationWorkshopType") : view("OrganizationWorkshopType.index");
    }

    /**
     * 新建车间类型页面
     * @return Factory|Application|View
     */
    public function Create()
    {
        return view("OrganizationWorkshopType.create");
    }

    /**
     * 新建车间类型
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Store()
    {
        return $this->sendStandardRequest("organizationWorkshopType");
    }

    /**
     * 车间类型详情
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Show(string $uuid)
    {
        return $this->sendStandardRequest("organizationWorkshopType/{$uuid}");
    }

    /**
     * 编辑车间类型列表
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function Edit(string $uuid)
    {
        return view("OrganizationWorkshopType.edit", ["uuid" => $uuid,]);
    }

    /**
     * 编辑车间类型
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Update(string $uuid)
    {
        return $this->sendStandardRequest("organizationWorkshopType/{$uuid}");
    }

    /**
     * 删除车间类型
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Destroy(string $uuid)
    {
        return $this->sendStandardRequest("organizationWorkshopType/{$uuid}");
    }
}
