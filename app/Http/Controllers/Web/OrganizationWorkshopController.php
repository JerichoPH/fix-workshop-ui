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

class OrganizationWorkshopController extends Controller
{
    /**
     * 车间列表
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function index()
    {
        return request()->ajax() ? $this->sendStandardRequest('organizationWorkshop') : view('OrganizationWorkshop.index');
    }

    /**
     * 新建车间页面
     * @return Factory|Application|View
     */
    public function create()
    {
        return view('OrganizationWorkshop.create');
    }

    /**
     * 新建车间
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function store()
    {
        return $this->sendStandardRequest(
            'organizationWorkshop',
            function (Request $request) {
                $request = $request->all();
                $request['be_enable'] = boolval(@$request['be_enable']);
                return $request;
            }
        );
    }

    /**
     * 车间详情
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function show(string $uuid)
    {
        return $this->sendStandardRequest("organizationWorkshop/$uuid");
    }

    /**
     * 编辑车间页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function edit(string $uuid)
    {
        return view('OrganizationWorkshop.edit', ['uuid' => $uuid,]);
    }

    /**
     * 编辑车间
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
            "organizationWorkshop/$uuid",
            function (Request $request) {
                $request = $request->all();
                $request['be_enable'] = boolval($request['be_enable']);
                return $request;
            }
        );
    }

    /**
     * 删除车间
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function destroy(string $uuid)
    {
        return $this->sendStandardRequest("organizationWorkshop/$uuid");
    }
}
