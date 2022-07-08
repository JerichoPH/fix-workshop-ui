<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Facades\JsonResponseFacade;
use Curl\Curl;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $__curl = null;
    protected $__ue_url_root = "";
    protected $__ue_api_version = "";
    protected $__ue_api_url = "";

    public function __construct()
    {
        $this->__curl = new Curl();
        $this->__ue_url_root = env("UE_URL_ROOT");
        $this->__ue_api_version = env("UE_API_VERSION");
        $this->__ue_api_url = "{$this->__ue_url_root}/{$this->__ue_api_version}";
    }

    /**
     * @throws UnLoginException
     * @throws UnAuthorizationException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws Exception
     */
    protected function handleResponse()
    {
        if ($this->__curl->error) {
            $msg = $this->__curl->errorMessage;
            switch ($this->__curl->errorCode) {
                case 401:
                    throw new UnLoginException($msg);
                case 406:
                    throw new UnAuthorizationException($msg);
                case 403:
                    throw new EmptyException($msg);
                case 404:
                    throw new ForbiddenException($msg);
                case 500:
                default:
                    throw new Exception($msg);
            }
        } else {
            switch ($this->__curl->getHttpStatusCode()) {
                case 200:
                default:
                    return JsonResponseFacade::dict($this->__curl->response);
                case 201:
                    return JsonResponseFacade::created($this->__curl->response);
                case 202:
                    return JsonResponseFacade::updated($this->__curl->response);
                case 204:
                    return JsonResponseFacade::deleted($this->__curl->response);
            }
        }
    }
}
