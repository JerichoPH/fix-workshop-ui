<?php

namespace Jericho;

use Illuminate\Http\JsonResponse;

class HttpResponseHelper
{
    final public static function data($data, $headers = [], $options = 0): JsonResponse
    {
        return response()->json(['status' => 200, 'data' => $data], 200, $headers, $options);
    }

    final public static function created(string $msg, array $details = null, $headers = [], $options = 0): JsonResponse
    {
        return response()->json(['status' => 200, 'message' => $msg, 'details' => $details], 200, $headers, $options);
    }

    final public static function error(string $msg, array $details = null, int $code = 500, $headers = [], $options = 0): JsonResponse
    {
        return response()->json(['status' => $code, 'message' => $msg, 'details' => $details], 200, $headers, $options);
    }

    final public static function errorForbidden(string $msg, array $details = null, $headers = [], $options = 0): JsonResponse
    {
        return response()->json(['status' => 403, 'message' => $msg, 'details' => $details], 200, $headers, $options);
    }

    final public static function errorUnauthorized(string $msg = '授权失败', array $details = null, $headers = [], $options = 0): JsonResponse
    {
        return response()->json(['status' => 401, 'message' => $msg, 'details' => $details], 200, $headers, $options);
    }

    final public static function errorEmpty(string $msg = '数据不存在', array $details = null, $headers = [], $options = 0): JsonResponse
    {
        return response()->json(['status' => 404, 'message' => $msg, 'details' => $details], 200, $headers, $options);
    }

    final public static function errorValidate(string $msg, array $details = null, $headers = [], $options = 0): JsonResponse
    {
        return response()->json(['status' => 422, 'message' => $msg, 'details' => $details], 200, $headers, $options);
    }
}
