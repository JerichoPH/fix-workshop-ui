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

class LocationCenterController extends Controller
{
    /**
     * 中心列表
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Index()
    {
        return request()->ajax() ? $this->sendStandardRequest("locationCenter") : view("locationCenter.index");
    }

    /**
     * 新建中心页面
     * @return Factory|Application|View
     */
    public function Create()
    {
        return view("locationCenter.create");
    }

    /**
     * 新建中心
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Store()
    {
        return $this->sendStandardRequest("locationCenter");
    }

    /**
     * 中心详情
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Show(string $uuid)
    {
        return $this->sendStandardRequest("locationCenter/{$uuid}");
    }

    /**
     * 编辑中心页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function Edit(string $uuid)
    {
        return view("LocationCenter.edit", ["uuid" => $uuid,]);
    }

    /**
     * 编辑中心
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Update(string $uuid)
    {
        return $this->sendStandardRequest(
            "locationCenter/{$uuid}",
            function (Request $request) {
                $request = $request->all();
                $request["be_enable"] = boolval($request["be_enable"]);
                return $request;
            }
        );
    }

    /**
     * 删除中心
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function Destroy(string $uuid)
    {
        return $this->sendStandardRequest("locationCenter/{$uuid}");
    }

    /**
     * 中心绑定线别
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function PutBindLocationLines(string $uuid)
    {
        return $this->sendStandardRequest("locationCenter/{$uuid}/bindLocationLines");
    }
}
