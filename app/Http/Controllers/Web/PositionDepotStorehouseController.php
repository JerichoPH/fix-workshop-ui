<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\View\Factory;
use Illuminate\View\View;

class PositionDepotStorehouseController extends Controller
{
    /**
     * 工区类型列表
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Index()
    {
        return request()->ajax() ? $this->sendStandardRequest("positionDepotStorehouse") : view("PositionDepotStorehouse.index");
    }

    /**
     * 新建工区类型页面
     * @return Factory|Application|View
     */
    public function Create()
    {
        return view("PositionDepotStorehouse.create");
    }

    /**
     * 新建工区类型
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Store()
    {
        return $this->sendStandardRequest("positionDepotStorehouse");
    }

    /**
     * 工区类型详情
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Show(string $uuid)
    {
        return $this->sendStandardRequest("positionDepotStorehouse/{$uuid}");
    }

    /**
     * 编辑工区类型页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function Edit(string $uuid)
    {
        return view("PositionDepotStorehouse.edit", ["uuid" => $uuid,]);
    }

    /**
     * 编辑工区类型
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Update(string $uuid)
    {
        return $this->sendStandardRequest("positionDepotStorehouse/{$uuid}");
    }

    /**
     * 删除工区类型
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Destroy(string $uuid)
    {
        return $this->sendStandardRequest("positionDepotStorehouse/{$uuid}");
    }
}