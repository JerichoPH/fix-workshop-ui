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

class LocationRailroadGradeCrossController extends Controller
{
    /**
     * 道口列表
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Index()
    {
        return request()->ajax() ? $this->sendStandardRequest("locationRailroadGradeCross") : view("LocationRailroadGradeCross.index");
    }

    /**
     * 新建道口页面
     * @return Factory|Application|View
     */
    public function Create()
    {
        return view("LocationRailroadGradeCross.create");
    }

    /**
     * 新建道口
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Store()
    {
        return $this->sendStandardRequest("locationRailroadGradeCross");
    }

    /**
     * 道口详情
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Show(string $uuid)
    {
        return $this->sendStandardRequest("locationRailroadGradeCross/{$uuid}");
    }

    /**
     * 编辑道口页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function Edit(string $uuid)
    {
        return view("locationRailroadGradeCross/{$uuid}", ["uuid" => $uuid,]);
    }

    /**
     * 编辑道口
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Update(string $uuid)
    {
        return $this->sendStandardRequest("locationRailroadGradeCross/{$uuid}");
    }

    /**
     * 删除道口
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Destroy(string $uuid)
    {
        return $this->sendStandardRequest("locationRailroadGradeCross/{$uuid}");
    }

    /**
     * 道口绑定线别
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function PutBindLocationLines(string $uuid)
    {
        return $this->sendStandardRequest("locationRailroadGradeCross/{$uuid}/bindLocationLines");
    }
}
