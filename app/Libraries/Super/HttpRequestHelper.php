<?php

namespace App\Libraries\Super;

class HttpRequestHelper
{

    private static $_INS = null;
    private $_url = null;
    private $_params = null;
    private $_curl = null;
    private $_response = null;
    private $_error = null;
    private $_isFormData = true;
    private $_isUrlEncode = false;
    private $_isReturnMap = false;

    private final function __construct(string $url, bool $isSsl = true)
    {
        # 使用curl发送协议
        $this->_curl = curl_init();
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        }
        $this->_url = $url;

        curl_setopt($this->_curl, CURLOPT_USERAGENT, $userAgent);  # 设置请求代理信息
        curl_setopt($this->_curl, CURLOPT_AUTOREFERER, true);  # 开启自动请求头

        # SSL相关设置
        if ($isSsl) {
            curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false);  # 终止服务器端验证SSL（建议在对方是明确安全的服务器时使用）
            curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, 2);  # 检查服务器SSL证书中是否存在一个公用名（common name）
        }
    }

    /**
     * 初始化
     * @param string $url
     * @param bool $isSsl
     * @return HttpRequestHelper
     */
    public final static function INS(string $url, bool $isSsl = true): self
    {
        if (!self::$_INS) self::$_INS = new self($url, $isSsl);
        return self::$_INS;
    }

    /**
     * 设置参数使用urlencode
     * @param bool $isUrlEncode
     * @return HttpRequestHelper
     */
    public final function isUrlEncode(bool $isUrlEncode = true): self
    {
        $this->_isUrlEncode = $isUrlEncode;
        return $this;
    }

    /**
     * 使用form表单
     * @param bool $isFormData
     * @return $this
     */
    public final function isFormData(bool $isFormData = true): self
    {
        $this->_isFormData = $isFormData;
        return $this;
    }

    /**
     * 使用x-www-urlencode方式
     * @param bool $isXWwwUrlEncode
     * @return HttpRequestHelper
     */
    public final function isXWwwUrlEncode(bool $isXWwwUrlEncode = true): self
    {
        $this->_isFormData = !$isXWwwUrlEncode;
        return $this;
    }

    /**
     * 设置参数
     * @param array $params
     * @return HttpRequestHelper
     */
    public final function params(array $params): self
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * 设置url地址
     * @param string $url
     * @return HttpRequestHelper
     */
    public final function url(string $url): self
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * 发送请求
     * @return $this
     */
    public final function get()
    {
        curl_setopt($this->_curl, CURLOPT_URL, $this->_makeParams('get', true));  # 发送请求目标地址
        # 发送get请求
        curl_setopt($this->_curl, CURLOPT_HEADER, false);
        # 不处理响应头
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);

        $this->_send();

        return $this;
    }

    /**
     * 生成params
     * @param string $method
     * @param bool $returnUrl
     * @return string|null
     */
    private final function _makeParams(string $method = 'get', bool $returnUrl = false)
    {
        if ($this->_params) {
            /**
             * 生成urlencode参数
             */
            $urlEncodeParams = function () {
                if ($this->_isUrlEncode) {
                    $this->_params = http_build_query($this->_params);
                } else {
                    $tmp = [];
                    foreach ($this->_params as $key => $value) {
                        $tmp [] = "{$key}={$value}";
                    }
                    $this->_params = implode('&', $tmp);
                }
            };

            switch ($method) {
                case 'get':
                default:
                    # 格式化参数
                    $urlEncodeParams = $urlEncodeParams();
                    return $returnUrl ? "{$this->_url}?{$this->_params}" : $this->_params;
                    break;
                case 'post':
                    if (!$this->_isFormData) $urlEncodeParams = $urlEncodeParams();
                    return $this->_params;
                    break;
            }
        }

        return null;
    }

    /**
     * 执行发送请求
     * @return HttpRequestHelper
     */
    private final function _send(): self
    {
        # 发送请求
        $this->_response = curl_exec($this->_curl);
        if ($this->_response == false) {
            $this->_error = curl_error($this->_curl);
        }

        return $this;
    }

    /**
     * 发送post请求
     * @return HttpRequestHelper
     */
    public final function post(): self
    {
        $this->_makeParams();
        curl_setopt($this->_curl, CURLOPT_URL, $this->_url);  # 发送请求目标地址
        curl_setopt($this->_curl, CURLOPT_POST, true);  # 处理post响应信息
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $this->_params);  # 处理请求数据
        curl_setopt($this->_curl, CURLOPT_HEADER, false);  # 禁止处理响应头信息
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);  # 开启返回相应结果

        $this->_send();

        return $this;
    }

    /**
     * 获取原始返回值
     * @return array
     */
    public final function prototype(): array
    {
        return $this->_makeReturn($this->_response);
    }

    /**
     * 生成返回值
     * @param $data
     * @return array
     */
    final private function _makeReturn($data): array
    {
        if ($this->_isReturnMap) {
            return $this->_error ?
                [
                    '请求结果' => $this->_error,
                    '发送结果' => $this->_response,
                    '发送参数' => $this->_params,
                    '发送地址' => $this->_url
                ] :
                [
                    '请求结果' => $data,
                    '发送结果' => true,
                    '发送参数' => $this->_params,
                    '发送地址' => $this->_url
                ];
        } else {
            return $this->_error ?
                [
                    $this->_error,
                    $this->_response,
                    $this->_params,
                    $this->_url
                ] :
                [
                    $data,
                    true,
                    $this->_params,
                    $this->_url
                ];
        }

    }

    /**
     * 设置返回值格式是否是Map格式
     * @param bool $isReturnMap
     * @return HttpRequestHelper
     */
    final public function isReturnMap(bool $isReturnMap = true): self
    {
        $this->_isReturnMap = $isReturnMap;
        return $this;
    }

    /**
     * 返回xml格式
     * @return array
     */
    public final function xml(): array
    {
        return $this->_makeReturn(simplexml_load_string($this->_response));
    }

    /**
     * 返回xml数组格式
     * @return array
     */
    public final function xmlToArray(): array
    {
        return $this->_makeReturn((array)simplexml_load_string($this->_response));
    }

    /**
     * 返回xml数组格式
     * @return array
     */
    final public function x2a()
    {
        return $this->xmlToArray();
    }

    /**
     * 返回json格式
     * @param int $jsonParams
     * @return array
     */
    public final function json($jsonParams = 256): array
    {
        return $this->_makeReturn(json_decode($this->_response, $jsonParams));
    }
}
