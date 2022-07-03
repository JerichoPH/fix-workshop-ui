<?php

namespace App\Http;

use App\Http\Middleware\ApiCheckJwtMiddleware;
use App\Http\Middleware\ApiCheckMiddleware;
use App\Http\Middleware\ApiCheckPermissionMiddleware;
use App\Http\Middleware\CheckDetectorUserSecretKeyMiddleware;
use App\Http\Middleware\CheckJwtMiddleware;
use App\Http\Middleware\CheckParagraphSyncBasicDataMiddleware;
use App\Http\Middleware\CheckPermissionMiddleware;
use App\Http\Middleware\CheckSignMiddleware;
use App\Http\Middleware\CorsHttpMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\CrossHttpMiddleware;
use App\Http\Middleware\GetCurrentMenuMiddleware;
use App\Http\Middleware\GetIpMiddleware;
use App\Http\Middleware\ThrottleRequestsMiddleware;
use App\Http\Middleware\WebCheckJwtMiddleware;
use App\Http\Middleware\WebCheckLoginMiddleware;
use App\Http\Middleware\WebCheckPermissionMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Overtrue\LaravelWeChat\Middleware\OAuthAuthenticate;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
        CorsMiddleware::class, # 允许跨域
        GetIpMiddleware::class,  # 获取访问IP地址
        ThrottleRequestsMiddleware::class,  # 白名单控制
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
//            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            CorsMiddleware::class, # 允许跨域
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'web-check-jwt' => WebCheckJwtMiddleware::class,  # 检查jwt(web)
        'api-check-jwt' => ApiCheckJwtMiddleware::class,  # 检查jwt(api)
        'api-check' => ApiCheckMiddleware::class,  # 登录
        'web-check-permission' => WebCheckPermissionMiddleware::class,  # 检查权限(web)
        'api-check-permission' => ApiCheckPermissionMiddleware::class,  # 检查权限(api)
        'web-check-login' => WebCheckLoginMiddleware::class,  # 检查web是否登录
        'web-get-current-menu'=>GetCurrentMenuMiddleware::class,  # 根据当前URL获取菜单
        'wechat-oauth' => OAuthAuthenticate::class,  # 获取微信授
        'cors'=>CorsMiddleware::class, # 允许跨域
        'CheckSignMiddleware'=>CheckSignMiddleware::class,  # 接口验签
        'CheckParagraphSyncBasicData'=>CheckParagraphSyncBasicDataMiddleware::class,  // 检查是否允许段中心同步基础数据
        "CheckDetectorUserSecretKey"=>CheckDetectorUserSecretKeyMiddleware::class,  // 检查检测台厂家密码
    ];
}
