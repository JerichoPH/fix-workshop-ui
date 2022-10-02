<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\View\Factory;
use Illuminate\View\View;

class LocationStationController extends Controller
{
    /**
     * 工区类型列表
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function index()
    {
        return request()->ajax() ? $this->sendStandardRequest('locationStation') : view('LocationStation.index');
    }

    /**
     * 新建工区类型页面
     * @return Factory|Application|View
     */
    public function create()
    {
        return view('LocationStation.create');
    }

    /**
     * 新建工区类型
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function store()
    {
        return $this->sendStandardRequest(
            'locationStation',
            function (Request $request) {
                $request = $request->all();
                $request['be_enable'] = boolval($request['be_enable']);
                return $request;
            }
        );
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
    public function show(string $uuid)
    {
        return $this->sendStandardRequest("locationStation/$uuid");
    }

    /**
     * 编辑工区类型页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function edit(string $uuid)
    {
        return view('LocationStation.edit', ['uuid' => $uuid,]);
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
    public function update(string $uuid)
    {
        return $this->sendStandardRequest(
            "locationStation/$uuid",
            function (Request $request) {
                $request = $request->all();
                $request['be_enable'] = boolval($request['be_enable']);
                return $request;
            }
        );
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
    public function destroy(string $uuid)
    {
        return $this->sendStandardRequest("locationStation/$uuid");
    }

    /**
     * 站场绑定线别
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function putBindLocationLines(string $uuid)
    {
        return $this->sendStandardRequest("locationStation/$uuid/bindLocationLines");
    }
}
