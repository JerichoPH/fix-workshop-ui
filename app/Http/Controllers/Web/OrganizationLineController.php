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

class OrganizationLineController extends Controller
{
    /**
     * 列表
     * @throws UnLoginException
     * @throws ForbiddenException
     * @throws EmptyException
     * @throws UnAuthorizationException
     */
    public function Index()
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest("organization/line", session(__JWT__));
        } else {
            return view("OrganizationLine.index");
        }
    }

    /**
     * 新建页面
     * @return Factory|Application|View
     */
    public function Create()
    {
        return view("OrganizationLine.create");
    }

    /**
     * 新建
     * @throws UnLoginException
     * @throws ForbiddenException
     * @throws EmptyException
     * @throws UnAuthorizationException
     */
    public function Store()
    {
        return $this->sendStandardRequest(
            "organization/line",
            session(__JWT__),
            function (Request $request) {
                $request = $request->all();
                $request["be_enable"] = boolval($request["be_enable"]);
                return $request;
            }
        );
    }

    /**
     * 详情页面
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function Show(string $uuid)
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest("organization/line/{$uuid}", session(__JWT__));
        }
    }

    /**
     * 编辑页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function Edit(string $uuid)
    {
        return view("OrganizationLine.edit", ["uuid" => $uuid,]);
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
    public function Update(string $uuid)
    {
        return $this->sendStandardRequest(
            "organization/line/{$uuid}",
            session(__JWT__),
            function (Request $request) {
                $request = $request->all();
                $request["be_enable"] = boolval($request["be_enable"]);
                return $request;
            }
        );
    }

    public function Destroy(string $uuid)
    {

    }
}
