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

class OrganizationParagraphController extends Controller
{
    /**
     * 站段列表
     * @return Factory|Application|View|mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function index()
    {
        return request()->ajax() ? $this->sendStandardRequest('organizationParagraph') : view('OrganizationParagraph.index');
    }

    /**
     * 新建站段页面
     * @return Factory|Application|View
     */
    public function create()
    {
        return view('OrganizationParagraph.create');
    }

    /**
     * 新建站段
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function store()
    {
        return $this->sendStandardRequest(
            'organizationParagraph',
            function (Request $request) {
                $request = $request->all();
                $request['be_enable'] = boolval($request['be_enable']);
                return $request;
            }
        );
    }

    /**
     * 站段详情
     * @param string $uuid
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    public function show(string $uuid)
    {
        return $this->sendStandardRequest("organizationParagraph/$uuid");
    }

    /**
     * 编辑站段页面
     * @param string $uuid
     * @return Factory|Application|View
     */
    public function edit(string $uuid)
    {
        return view('OrganizationParagraph.edit', ['uuid' => $uuid,]);
    }

    /**
     * 编辑站段
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
            "organizationParagraph/$uuid",
            function (Request $request) {
                $request = $request->all();
                $request['be_enable'] = boolval($request['be_enable']);
                return $request;
            }
        );
    }

    /**
     * 删除站段
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function destroy(string $uuid)
    {
        return $this->sendStandardRequest("organizationParagraph/$uuid");
    }
}
