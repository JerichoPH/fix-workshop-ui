<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthorizationController extends Controller
{
    final public function GetLogin()
    {
        return view("Authorization.login");
    }

    /**
     * @throws UnLoginException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     */
    final public function PostLogin(Request $request)
    {
        $this->sendStandardRequest("authorization/login", $request->session()->get("__jwt__"), function () {
            if (!$this->curl->error) session()->put("__jwt__", $this->curl->response->content->token);
            return $this->handleResponse();
        });
    }
}
