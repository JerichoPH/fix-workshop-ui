<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Exceptions\UnLoginException;
use App\Exceptions\ValidateException;
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
    protected $ueUrlRoot = "";
    protected $ueApiVersion = "";
    protected $ueApiUrl = "";

    public function __construct()
    {
        $this->curl = new Curl();
        $this->ueUrlRoot = env("UE_URL_ROOT");
        $this->ueApiVersion = env("UE_API_VERSION");
        $this->ueApiUrl = "{$this->ueUrlRoot}/{$this->ueApiVersion}";
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
    protected function sendStandardRequest(string $url, Closure $before = null, Closure $after = null)
    {
        $method = strtolower(request()->method());
        if (GetJWTFromSession()) $this->curl->setHeader("Authorization", "JWT " . GetJWTFromSession());
        switch ($method) {
            case "GET":
                $this->curl->setHeader("Accept", "application/json");
                break;
            default:
                $this->curl->setHeader("Content-Type", "application/json");
                break;
        }
        $request = null;
        if ($before) $request = $before(request());
        $this->curl->{$method}("$this->ueApiUrl/$url", $request ?? request()->all());
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
                    throw new ForbiddenException($msg);
                case 404:
                    throw new EmptyException($msg);
                case 421:
                    throw new ValidateException($msg);
                case 500:
                default:
                    throw new Exception($this->curl->errorMessage);
            }
        } else {
            switch ($this->curl->getHttpStatusCode()) {
                case 200:
                default:
                    return JsonResponseFacade::Dict((array)$this->curl->response->content, $this->curl->response->msg);
                case 201:
                    return JsonResponseFacade::Created((array)$this->curl->response->content, $this->curl->response->msg);
                case 202:
                    return JsonResponseFacade::Updated((array)$this->curl->response->content, $this->curl->response->msg);
                case 204:
                    return JsonResponseFacade::Deleted([], @$this->curl->response->msg ?: "删除成功");
            }
        }
    }
}
