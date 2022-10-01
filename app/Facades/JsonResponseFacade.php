<?php

namespace App\Facades;

use App\Services\JsonResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * Class JsonResponseFacade
 * @package App\Facades
 * @method static dump(...$data): JsonResponse
 * @method static ok($content = null, $pagination = null, string $msg = "OK", ...$details): JsonResponse
 * @method static created($data = null, string $msg = '新建成功', ...$details): JsonResponse
 * @method static updated($data = null, string $msg = '编辑成功', ...$details): JsonResponse
 * @method static deleted($data = null, string $msg = '删除成功', ...$details): JsonResponse
 * @method static wrongEmpty(string $msg = '数据不存在', ...$details): JsonResponse
 * @method static wrongForbidden(string $msg, ...$details): JsonResponse
 * @method static wrongUnLogin(string $msg = '未登录', ...$details): JsonResponse
 * @method static wrongUnauthorized(string $msg = '授权失败', ...$details): JsonResponse
 * @method static wrongValidate(string $msg): JsonResponse
 * @method static wrongCustom(string $msg = '意外错误', int $errorCode = 1, Throwable $e = null): JsonResponse
 * @method static wrongException(Throwable $e, string $msg = '意外错误', $errorCode = 1): JsonResponse
 */
class JsonResponseFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JsonResponseService::class;
    }
}
