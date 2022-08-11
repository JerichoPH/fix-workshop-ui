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
    public function Index()
    {
        if (request()->ajax()) {
            return $this->sendStandardRequest("organizationParagraph");
        } else {
            return view("OrganizationParagraph.index");
        }
    }

    /**
     * 新建站段页面
     * @return Factory|Application|View
     */
    public function Create()
    {
        return view("OrganizationParagraph.create");
    }

    /**
     * 新建站段
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    public function Store()
    {
        return $this->sendStandardRequest("organizationParagraph");
    }

    public function Show(string $uuid)
    {
        return $this->sendStandardRequest("organizationParagraph/{$uuid}");
    }

    public function Edit(string $uuid)
    {

    }

    public function Update(string $uuid)
    {

    }

    public function Destroy(string $uuid)
    {

    }
}
