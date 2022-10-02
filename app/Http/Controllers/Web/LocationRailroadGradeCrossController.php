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
    public function index()
    {
        return request()->ajax() ? $this->sendStandardRequest('locationRailroadGradeCross') : view('LocationRailroadGradeCross.index');
    }

    /**
     * 新建道口页面
     * @return Factory|Application|View
     */
    public function create()
    {
        return view('LocationRailroadGradeCross.create');
    }

    /**
     * 新建道口
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function store()
    {
        return $this->sendStandardRequest(
            'locationRailroadGradeCross',
            function (Request $request) {
                $request = $request->all();
                $request['be_enable'] = boolval($request['be_enable']);
                return $request;
            }
        );
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
    public function show(string $uuid)
    {
        return $this->sendStandardRequest("locationRailroadGradeCross/$uuid");
    }

    /**
     * 编辑道口页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function edit(string $uuid)
    {
        return view('LocationRailroadGradeCross.edit', ['uuid' => $uuid,]);
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
    public function update(string $uuid)
    {
        return $this->sendStandardRequest(
            "locationRailroadGradeCross/$uuid",
            function(Request $request){
                $request = $request->all();
                $request['be_enable'] = boolval($request['be_enable']);
                return $request;
            }
        );
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
    public function destroy(string $uuid)
    {
        return $this->sendStandardRequest("locationRailroadGradeCross/$uuid");
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
    public function putBindLocationLines(string $uuid)
    {
        return $this->sendStandardRequest("locationRailroadGradeCross/$uuid/bindLocationLines");
    }
}
