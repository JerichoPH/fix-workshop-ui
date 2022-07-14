<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Http\Controllers\Controller;

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
            return view("Organization.Line.index");
        }
    }

    public function Create()
    {

    }

    public function Store()
    {

    }

    public function Show(string $uuid)
    {

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
