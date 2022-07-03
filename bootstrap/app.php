<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

// $paragraph_code = strtoupper($_GET['pc']) ?? '';
// $paragraph_code_session = $_SESSION['pc'] ?? '';
// if (!$paragraph_code_session && !$paragraph_code) {
//     // 场景1：session和get中都没有pc参数，不做任何处理，中间件中会返回错误
//     return '<h1>错误：段参数丢失</h1>';
// } elseif (!$paragraph_code_session && $paragraph_code) {
//     // 场景2：session中没有pc参数，但是get中有，将pc参数同步到session中
//     $_SESSION['pc'] = $paragraph_code;
// } elseif ($paragraph_code_session && !$paragraph_code) {
//     // 场景3：session中有pc参数，但get中没有，使用session中的pc参数
//     $paragraph_code = $_SESSION['pc'];
// } else {
//     // 场景4：session和get参数中都有pc参数，将pc参数同步到session中
//     $_SESSION['pc'] = $paragraph_code;
// }
// $app->loadEnvironmentFrom("/.env.{$paragraph_code}");

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
