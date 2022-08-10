<?php

namespace App\Facades;

use App\Services\JsonResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * Class JsonResponseFacade
 * @package App\Facades
 * @method static Dump(...$data): JsonResponse
 * @method static Dict($data = [], string $msg = 'OK', ...$details): JsonResponse
 * @method static Data($data = [], string $msg = 'OK', ...$details): JsonResponse
 * @method static OK($msg = 'OK'): JsonResponse
 * @method static Created($data = [], string $msg = '新建成功', ...$details): JsonResponse
 * @method static Updated($data = [], string $msg = '编辑成功', ...$details): JsonResponse
 * @method static Deleted($data = [], string $msg = '删除成功', ...$details): JsonResponse
 * @method static WrongEmpty(string $msg = '数据不存在', ...$details): JsonResponse
 * @method static WrongForbidden(string $msg, ...$details): JsonResponse
 * @method static WrongUnLogin(string $msg = '未登录', ...$details): JsonResponse
 * @method static WrongUnauthorized(string $msg = '授权失败', ...$details): JsonResponse
 * @method static WrongValidate(string $msg): JsonResponse
 * @method static WrongCustom(string $msg = '意外错误', int $errorCode = 1, Throwable $e = null): JsonResponse
 * @method static WrongException(Throwable $e, string $msg = '意外错误', $errorCode = 1): JsonResponse
 */
class JsonResponseFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JsonResponseService::class;
    }
}
