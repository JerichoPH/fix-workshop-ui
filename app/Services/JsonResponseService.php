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
            "msg" => "dump response",
            "status" => 200,
            "errorCode" => 0,
            "data" => $data,
        ]);
    }

    /**
     * 操作成功
     * @param null $content
     * @param null $pagination
     * @param string $msg
     * @param ...$details
     * @return JsonResponse
     */
    final public function ok($content = null, $pagination = null, string $msg = "OK", ...$details): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 200,
            "errorCode" => 0,
            "content" => $content,
            "pagination" => $pagination,
            "details" => $details,
        ]);
    }

    /**
     * 新建成功
     * @param null $content
     * @param string $msg
     * @param ...$details
     * @return JsonResponse
     */
    final public static function created($content = null, string $msg = "新建成功", ...$details): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 200,
            "errorCode" => 0,
            "data" => $content,
            "details" => $details,
        ]);
    }

    /**
     * 更新成功
     * @param null $data
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function updated($data = null, string $msg = "编辑成功", ...$details): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 200,
            "errorCode" => 0,
            "data" => $data,
            "details" => $details
        ]);
    }

    /**
     * 删除成功
     * @param null $data
     * @param string $msg
     * @param ...$details
     * @return JsonResponse
     */
    final public static function deleted($data = null, string $msg = "删除成功", ...$details): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 200,
            "errorCode" => 0,
            "data" => $data,
            "details" => $details,
        ]);
    }

    /**
     * 空资源
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function wrongEmpty(string $msg = "数据不存在", ...$details): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 404,
            "errorCode" => 1,
            "data" => [],
            "details" => $details,
        ],
            404);
    }

    /**
     * 禁止操作
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function wrongForbidden(string $msg, ...$details): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 403,
            "errorCode" => 2,
            "data" => [],
            "details" => $details,
        ],
            403);
    }

    /**
     * 未登录
     * @param string $msg
     * @param ...$details
     * @return JsonResponse
     */
    final public static function wrongUnLogin(string $msg = "未登录", ...$details): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 401,
            "errorCode" => 3,
            "data" => [],
            "details" => $details,
        ],
            401);
    }

    /**
     * 未授权
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function wrongUnauthorized(string $msg = "未授权", ...$details): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 406,
            "errorCode" => 3,
            "data" => [],
            "details" => $details
        ],
            406);
    }

    /**
     * 表单验证失败
     * @param string $msg
     * @return JsonResponse
     */
    final public static function wrongValidate(string $msg): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 422,
            "data" => [],
            "errorCode" => 4,
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
    final public static function wrongCustom(string $msg = "意外错误", int $errorCode = 5, Throwable $e = null): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 500,
            "errorCode" => $errorCode,
            "data" => [],
            "details" => [
                "exception_type" => get_class($e),
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ],
            "trace" => $e->getTrace(),
        ]);
    }

    /**
     * 异常错误
     * @param \Throwable $e
     * @param string $msg
     * @param int $errorCode
     * @return JsonResponse
     */
    final public static function wrongException(Throwable $e, string $msg = "意外错误", int $errorCode = 6): JsonResponse
    {
        return response()->json([
            "msg" => $msg,
            "status" => 500,
            "errorCode" => $errorCode,
            "data" => [],
            "details" => [
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            ],
            "trace" => $e->getTraceAsString(),
        ],
            500);
    }
}
