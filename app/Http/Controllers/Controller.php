<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Facades\JsonResponseFacade;
use Closure;
use Curl\Curl;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $curl = null;
    protected $ue_url_root = "";
    protected $ue_api_version = "";
    protected $ue_api_url = "";

    public function __construct()
    {
        $this->curl = new Curl();
        $this->ue_url_root = env("UE_URL_ROOT");
        $this->ue_api_version = env("UE_API_VERSION");
        $this->ue_api_url = "{$this->ue_url_root}/{$this->ue_api_version}";
    }

    /**
     * 发送标准请求
     * @param string $url
     * @param string|null $token
     * @param Closure|null $before
     * @param Closure|null $after
     * @return mixed
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws UnAuthorizationException
     * @throws UnLoginException
     */
    protected function sendStandardRequest(string $url, ?string $token = "", Closure $before = null, Closure $after = null)
    {
        $method = strtolower(request()->method());
        if ($token) $this->curl->setHeader("Authorization", "JWT $token");
        $this->curl->setHeader("Content-Type", "application/json");
        $request = null;
        if ($before) $request = $before(request());
        $this->curl->{$method}("$this->ue_api_url/$url", $request ?: request()->all());
        if ($after) return $after($this->curl);
        return $this->handleResponse();
    }

    /**
     * 处理请求结果
     * @throws UnLoginException
     * @throws UnAuthorizationException
     * @throws EmptyException
     * @throws ForbiddenException
     * @throws Exception
     */
    protected function handleResponse()
    {
        if ($this->curl->error) {
            $msg = @$this->curl->response->msg ?: "";
            switch ($this->curl->errorCode) {
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
            switch ($this->curl->getHttpStatusCode()) {
                case 200:
                default:
                    return JsonResponseFacade::dict((array)$this->curl->response->content, $this->curl->response->msg);
                case 201:
                    return JsonResponseFacade::created((array)$this->curl->response->content, $this->curl->response->msg);
                case 202:
                    return JsonResponseFacade::updated((array)$this->curl->response->content, $this->curl->response->msg);
                case 204:
                    return JsonResponseFacade::deleted([], $this->curl->response->msg);
            }
        }
    }
}
