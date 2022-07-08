<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Throwable;

class JsonResponseService
{
    /**
     * 响应简单类型数字
     * @param array $data
     * @return JsonResponse
     */
    final public static function dump(...$data): JsonResponse
    {
        return response()->json([
            'msg' => 'dump response',
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
        ],
            200);
    }

    /**
     * data 别名方法
     * @param array $data
     * @param string $msg
     * @param ...$details
     * @return JsonResponse
     */
    final public static function dict($data = [], string $msg = 'OK', ...$details): JsonResponse
    {
        return self::data($data, $msg, $details);
    }

    /**
     * 响应数据
     * @param mixed $data
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function data($data = [], string $msg = 'OK', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
            'details' => $details,
        ],
            200);
    }

    /**
     * 操作成功
     * @param string $msg
     * @return JsonResponse
     */
    final public function ok(string $msg = 'OK'): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => [],
            'details' => [],
        ], 200);
    }

    /**
     * 新建成功
     * @param array $data
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function created(array $data = [], string $msg = '新建成功', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
            'details' => $details,
        ],
            200);
    }

    /**
     * 更新成功
     * @param array $data
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function updated(array $data = [], string $msg = '编辑成功', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
            'details' => $details
        ],
            200);
    }

    /**
     * 删除成功
     * @param string $msg
     * @param array $data
     * @param array $details
     * @return JsonResponse
     */
    final public static function deleted(array $data = [], string $msg = '删除成功', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
            'details' => $details,
        ],
            200);
    }

    /**
     * 空资源
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function errorEmpty(string $msg = '数据不存在', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 404,
            'errorCode' => 1,
            'details' => $details,
        ],
            404);
    }

    /**
     * 禁止操作
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function errorForbidden(string $msg, ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 403,
            'errorCode' => 2,
            'details' => $details,
        ],
            403);
    }

    /**
     * 未登录
     * @param string $msg
     * @param ...$details
     * @return JsonResponse
     */
    final public static function errorUnLogin(string $msg = '未登录', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 401,
            'errorCode' => 3,
            'details' => $details,
        ],
            401);
    }

    /**
     * 未授权
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function errorUnauthorized(string $msg = '未授权', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 406,
            'errorCode' => 3,
            'details' => $details
        ],
            406);
    }

    /**
     * 表单验证失败
     * @param string $msg
     * @return JsonResponse
     */
    final public static function errorValidate(string $msg): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 422,
            'errorCode' => 4,
        ],
            422);
    }

    /**
     * 自定义错误
     * @param string $msg
     * @param int $errorCode
     * @param Throwable|null $e
     * @return JsonResponse
     */
    final public static function errorCustom(string $msg = '意外错误', int $errorCode = 5, Throwable $e = null): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 500,
            'errorCode' => $errorCode,
            'details' => [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
            'trace' => $e->getTrace(),
        ]);
    }

    /**
     * 异常错误
     * @param \Throwable $e
     * @param string $msg
     * @param int $errorCode
     * @return JsonResponse
     */
    final public static function errorException(Throwable $e, string $msg = '意外错误', int $errorCode = 6): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 500,
            'errorCode' => $errorCode,
            'details' => [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
            'trace' => $e->getTrace(),
        ],
            500);
    }
}
