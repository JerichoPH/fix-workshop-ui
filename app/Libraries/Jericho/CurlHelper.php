<?php

namespace Jericho;

class CurlHelper
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const TEXT = 'TEXT';
    const JSON = 'JSON';
    const XML = 'XML';

    private $_request = [];
    private $_url = null;
    private $_user_agent = null;
    private $_curl = null;

    /**
     * @param array $request
     * @return array|\Illuminate\Http\JsonResponse|mixed
     * @throws \Exception
     */
    public static function init(array $request)
    {
        $instance = new self($request);
        return $instance->get();
    }

    /**
     * CurlHelper constructor.
     * @param array $request
     * @throws BadRequestException
     */
    private function __construct(array $request)
    {
        if (!env('SPAS_PROTOCOL')) throw new BadRequestException('数据中台协议头不能为空', 403);
        if (!env('SPAS_URL_ROOT')) throw new BadRequestException('数据中台根地址不能为空', 403);
        if (!env('SPAS_PORT')) throw new BadRequestException('数据中台端口不能为空', 403);
        if (!env('SPAS_API_ROOT')) throw new BadRequestException('数据中台接口根路径不能为空', 403);
        if (!env('SPAS_USERNAME')) throw new BadRequestException('数据中台用户名不能为空', 403);
        if (!env('SPAS_PASSWORD')) throw new BadRequestException('数据中台密码不能为空', 403);

        $this->_request = array_merge([
            'url' => '',  # 请求地址
            'headers' => [],  # 请求头
            'queries' => [],  # GET参数
            'contents' => [],  # 请求体
            'method' => null,  # 请求方法
            'ssl' => false,  # SSL认证
        ], $request);
        if (!$this->_request['url']) throw new BadRequestException('URL不能为空', 403);
        $this->_url = !empty($this->_request['queries']) ? $this->_request['url'] . '?' . http_build_query($this->_request['queries']) : $this->_request['url'];
        $this->_request['url'] = $this->_url;

        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->_user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        } else {
            $this->_user_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        # curl
        $this->_curl = curl_init();  # 创建curl句柄
        curl_setopt($this->_curl, CURLOPT_URL, $this->_url);  # 设置请求连接

        # 代理
        curl_setopt($this->_curl, CURLOPT_USERAGENT, $this->_user_agent);
        curl_setopt($this->_curl, CURLOPT_AUTOREFERER, true);

        # ssl认证
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, $this->_request['ssl']);  # 终止服务器端验证SSL（建议在对方是明确安全的服务器时使用）
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, 2);  # 检查服务器SSL证书中是否存在一个公用名（common name）

        # 请求头
        if (!empty($this->_request['headers'])) {
            if (in_array($this->_request['method'], [self::PUT, self::DELETE])) $this->_request['headers'] = array_merge($this->_request['headers'], ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $this->_request['headers']);
            curl_setopt($this->_curl, CURLOPT_HEADER, 0);  # 返回response头部信息
        }

        curl_setopt($this->_curl, CURLOPT_HEADER, true);  # 处理响应头
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);  # 是否返回响应结果
    }

    /**
     * @param bool $show_request
     * @return array|\Illuminate\Http\JsonResponse|mixed
     * @throws \Exception
     */
    private function get(bool $show_request = true)
    {
        switch (strtoupper($this->_request['method'])) {
            default:
            case self::GET:
                break;
            case self::POST:
                curl_setopt($this->_curl, CURLOPT_POST, true);
                if ($this->_request['contents']) curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $this->_request['contents']);  # 处理请求数据
                break;
            case self::PUT:
                curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, strtoupper(self::PUT));  # 发送PUT、DELETE请求
                if ($this->_request['contents']) curl_setopt($this->_curl, CURLOPT_POSTFIELDS, http_build_query($this->_request['contents']));  # 处理请求数据
                break;
            case self::DELETE:
                curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, strtoupper(self::DELETE));  # 发送PUT、DELETE请求
                if ($this->_request['contents']) curl_setopt($this->_curl, CURLOPT_POSTFIELDS, http_build_query($this->_request['contents']));  # 处理请求数据
                break;
        }

        # 发送请求
        $response = curl_exec($this->_curl);
        if ($response == false) throw new BadRequestException(curl_error($this->_curl) . ":" . json_encode($this->_request), curl_errno($this->_curl));

        return $this->_parse_response($response, $show_request);
    }

    /**
     * 解析响应
     * @param string $response
     * @param bool $show_request
     * @return array|\Illuminate\Http\JsonResponse
     */
    private function _parse_response(string $response, bool $show_request = true)
    {
        # 根据响应头自行处理
        $header_size = curl_getinfo($this->_curl, CURLINFO_HEADER_SIZE);  # 获取响应头
        $header = substr($response, 0, $header_size);  # 分离响应头
        $body = substr($response, $header_size);  # 分离响应体

        # 格式化响应头
        $headers = explode("\r\n", $header);
        foreach ($headers as $index => $item) $headers[$index] = $item;

        list($version, $status_code, $status) = explode(' ', $headers[0]);  # 分离响应状态

        # 解析响应体
        $body_type = self::TEXT;
        if (in_array('Content-Type: application/json', $headers)) $body_type = self::JSON;  # JSON格式响应体
        if (in_array('Content-Type: application/text', $headers)) $body_type = self::TEXT;  # TEXT格式响应体

        # 组合返回格式
        $ret = [
            'header' => $headers,
            'version' => $version,
            'code' => $status_code,
            'status' => $status,
        ];

        switch (strtoupper($body_type)) {
            case self::JSON:
                $ret['body'] = json_decode($body, true);
                break;
            default:
            case self::TEXT:
                $ret['body'] = $body;
                break;
        }

        if ($show_request) $ret['request'] = $this->_request;  # 是否显示请求信息

        return $ret;
    }


    /**
     * 发送请求
     * @param string $url target url
     * @param string $method request type. optional: get or post.
     * @param null $content request post use this data.
     * @param array $headers
     * @param bool|true $ssl this request is use ssl verify.
     * @return string response
     * @author JerichoPH
     */
    public static function send($url, $method, $content = null, $headers = [], $ssl = false)
    {
        # 使用curl发送协议
        $curl = curl_init();
        # curl请求相关设置
        curl_setopt($curl, CURLOPT_URL, $url);
        # 发送请求目标地址
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        }
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        # 设置请求代理信息
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        # 开启自动请求头
        # SSL相关设置
        if ($ssl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $ssl);
            # 终止服务器端验证SSL（建议在对方是明确安全的服务器时使用）
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            # 检查服务器SSL证书中是否存在一个公用名（common name）
        }
        # 设置头部
        if ($headers) {
            if (in_array($method, ['PUT', 'X', 'DELETE'])) $headers = array_merge($headers, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_HEADER, 0);  # 返回response头部信息
        }
        # curl响应相关设置
        switch (strtoupper($method)) {
            case 'GET' :
                # 发送get请求
                curl_setopt($curl, CURLOPT_HEADER, false);  # 不处理响应头
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  # 是否返回响应结果
                break;
            case 'X':
                # 发送post请求
                curl_setopt($curl, CURLOPT_POST, true);  # 设置请求头
                if ($content) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($content));  # 处理请求数据
                curl_setopt($curl, CURLOPT_HEADER, false);  # 禁止处理响应头信息
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  # 开启返回相应结果
                break;
            case 'POST' :
                # 发送post请求
                curl_setopt($curl, CURLOPT_POST, true);  # 处理post响应信息
                if ($content) curl_setopt($curl, CURLOPT_POSTFIELDS, $content);  # 处理请求数据
                curl_setopt($curl, CURLOPT_HEADER, false);  # 禁止处理响应头信息
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  # 开启返回相应结果
                break;
            case 'PUT':
            case 'DELETE':
                # 发送PUT、DELETE请求
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));  # 发送PUT、DELETE请求
                if ($content) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($content));  # 处理请求数据
                curl_setopt($curl, CURLOPT_HEADER, false);  # 禁止处理响应头信息
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  # 开启返回相应结果
                break;
        }
        # 发送请求
        $response = curl_exec($curl);
        if ($response == false) {
            return curl_error($curl);
        }

        return $response;
    }

    /**
     * 远程获取图片
     * @param string $url 获取地址
     * @param string $saveDir 保存地址
     * @param string $filename 保存文件名
     * @param bool $isNetworkImage 是否是网络图片
     * @return array
     */
    public static function getImage($url, $saveDir = '', $filename = '', $isNetworkImage = true)
    {

        if (trim($url) == '') {
            return array('file_name' => '', 'save_path' => '', 'error' => 1);
        }
        if (trim($saveDir) == '') {
            $saveDir = './';
        }
        if (trim($filename) == '') {//保存文件名
            $ext = strrchr($url, '.');
            if ($ext != '.gif' && $ext != '.jpg') {
                return array('file_name' => '', 'save_path' => '', 'error' => 3, 'details' => $ext);
            }
            $filename = time() . $ext;
        }
        if (0 !== strrpos($saveDir, '/')) {
            $saveDir .= '/';
        }
        //创建保存目录
        if (!file_exists($saveDir) && !mkdir($saveDir, 0777, true)) {
            return array('file_name' => '', 'save_path' => '', 'error' => 5);
        }
        //获取远程文件所采用的方法
        if ($isNetworkImage) {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $img = curl_exec($ch);
            curl_close($ch);
        } else {
            ob_start();
            readfile($url);
            $img = ob_get_contents();
            ob_end_clean();
        }
        //$size=strlen($img);
        //文件大小
        $fp2 = @fopen($saveDir . $filename, 'a');
        fwrite($fp2, $img);
        fclose($fp2);
        unset($img, $url);
        return array('file_name' => $filename, 'save_path' => $saveDir . $filename, 'error' => 0);
    }
}

class BadRequestException extends \Exception
{
}
