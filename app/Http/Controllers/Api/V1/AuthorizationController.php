<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthorizationController extends Controller
{
    /**
     * @throws UnLoginException
     * @throws ForbiddenException
     * @throws EmptyException
     * @throws UnAuthorizationException
     */
    public function PostLogin(Request $request)
    {
        // 请求登陆
        $this->__curl->post("{$this->__ue_api_url}/authorization/login", $request->all());
         if(!$this->__curl->error){
             // session中保存jwt
             session("__jwt")->put($this->__curl->response->content->token);
         }
        $this->handleResponse();
    }
}
