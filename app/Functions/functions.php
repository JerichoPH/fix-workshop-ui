<?php

namespace App\Factions;

use App\Facades\CommonFacade;
// use App\Facades\FileFacade;
use App\Facades\JsonResponseFacade;
// use App\Models\File;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

function __ok(string $msg): JsonResponse
{
    return JsonResponseFacade::ok($msg);
}

function __ok_dict($content = [], string $msg = '读取成功', ...$details): JsonResponse
{
    return JsonResponseFacade::dict($content, $msg, $details);
}

function __ok_dump(...$content): JsonResponse
{
    return JsonResponseFacade::dump($content);
}

function __ok_created($content = [], string $msg = '添加成功', ...$details): JsonResponse
{
    return JsonResponseFacade::created($content, $msg, $details);
}

function __ok_updated($content = [], string $msg = '编辑成功', ...$details): JsonResponse
{
    return JsonResponseFacade::updated($content, $msg, $details);
}

function __ok_deleted($content = [], string $msg = '删除成功', ...$details): JsonResponse
{
    return JsonResponseFacade::deleted($content, $msg, $details);
}

function __ok_back(string $msg = '')
{
    return back()->with('success', $msg);
}

function __ok_redirect(string $url, string $msg = '')
{
    return redirect($url)->with('success', $msg);
}

function __fail_empty(string $msg = '数据不存在', ...$details): JsonResponse
{
    return JsonResponseFacade::errorEmpty($msg, $details);
}

function __fail_forbidden(string $msg, ...$details): JsonResponse
{
    return JsonResponseFacade::errorForbidden($msg, $details);
}

function __fail_un_login(string $msg = '未登录', ...$details): JsonResponse
{
    return JsonResponseFacade::errorUnLogin($msg, $details);
}

function __fail_unauthorized(string $msg = '未授权', ...$details): JsonResponse
{
    return JsonResponseFacade::errorUnauthorized($msg, $details);
}

function __fail_un_owner(string $msg = '该数据不属于当前用户'): JsonResponse
{
    return JsonResponseFacade::errorUnauthorized($msg);
}

function __fail_validate($msg): JsonResponse
{
    return JsonResponseFacade::errorValidate($msg);
}

function __fail_custom(string $msg = '意外错误', int $errorCode = 1, Throwable $e = null): JsonResponse
{
    return JsonResponseFacade::errorCustom($msg, $errorCode, $e);
}

function __fail_exception(Throwable $e, string $msg = '意外错误', $errorCode = 1): JsonResponse
{
    return JsonResponseFacade::errorException($e, $msg, $errorCode);
}

function __fail_back(string $msg)
{
    return back()->with('danger', $msg);
}

function __fail_redirect(string $url, string $msg)
{
    return redirect($url)->with('danger', $msg);
}

function __handle_exception_with_app_debug(Throwable $e)
{
    return CommonFacade::handleExceptionWithAppDebug($e);
}

// function __file_store_one(UploadedFile $file, string $prefix, string $store_as, string $filesystem_config_name, string $type, Closure $callback = null)
// {
//     return File::storeOne($file, $prefix, $store_as, $filesystem_config_name, $type, $callback);
//     // return FileFacade::storeOne($file, $prefix, $store_as, $filesystem_config_name, $type, $callback);
// }
//
// function __file_store_batch(array $files, string $prefix, string $store_as, string $filesystem_config_name, string $type, Closure $callback = null): array
// {
//     return File::storeBatch($files, $prefix, $store_as, $filesystem_config_name, $type, $callback);
//     // return FileFacade::storeBatch($files, $prefix, $store_as, $filesystem_config_name, $type, $callback);
// }
//
// /**
//  * @throws Exception
//  */
// function __file_replace_one(File $source_file, UploadedFile $file, string $prefix, string $store_as, string $filesystem_config_name, string $type, Closure $callback = null)
// {
//     return File::replaceOne($source_file, $file, $prefix, $store_as, $filesystem_config_name, $type, $callback);
//     return FileFacade::replaceOne($source_file, $file, $prefix, $store_as, $filesystem_config_name, $type, $callback);
// }
