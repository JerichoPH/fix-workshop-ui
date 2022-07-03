<?php

namespace App\Libraries\Super;

use Illuminate\Http\JsonResponse;

class HttpResponseHelper
{
    final public static function data($data): JsonResponse
    {
        return response()->json(['data' => $data, 'status' => 200], 200);
    }

    final public static function created(string $msg): JsonResponse
    {
        return response()->json(['message' => $msg, 'status' => 200], 200);
    }

    final public static function error(string $msg): JsonResponse
    {
        return response()->json(['message' => $msg, 'status' => 500], 200);
    }

    final public static function errorForbidden(string $msg): JsonResponse
    {
        return response()->json(['message' => $msg, 'status' => 403], 200);
    }

    final public static function errorUnauthorized(string $msg = '授权失败'): JsonResponse
    {
        return response()->json(['message' => $msg, 'status' => 401,], 200);
    }

    final public static function errorEmpty(string $msg = '数据不存在'): JsonResponse
    {
        return response()->json(['message' => $msg, 'status' => 404], 200);
    }

    final public static function errorValidate(string $msg): JsonResponse
    {
        return response()->json(['message' => $msg, 'status' => 421], 200);
    }
}
