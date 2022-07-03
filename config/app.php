<?php

return [

    'organization_codes' => [
        'B048' => '广州',
        'B049' => '长沙',
        'B050' => '怀化',
        'B051' => '衡阳',
        'B052' => '惠州',
        'B053' => '肇庆',
        'B074' => '海口',
    ],
    // 当前段
    'code' => [
        'B049' => '长沙'
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

//    'timezone' => 'UTC',
    'timezone' => 'Asia/Shanghai',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

//    'locale' => 'en',
    'locale' => 'zh',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */
        \Milon\Barcode\BarcodeServiceProvider::class,  # 条形码

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'RbacFacade' => \App\Facades\Rbac::class,
        'AlarmFacade' => \App\Facades\Alarm::class,
        'OrganizationLevelFacade' => \App\Facades\OrganizationLevelFacade::class,
        'TestLogFacade' => \App\Facades\TestLog::class,
        'EasyWechatFacade' => \Overtrue\LaravelWeChat\Facade::class,
        'WechatReceiveMessageFacade' => \App\Facades\WechatReceiveMessage::class,
        'ReportSensorFacade' => \App\Facades\ReportSensor::class,
        'EntireInstanceCountFacade' => \App\Facades\EntireInstanceCountFacade::class,
        'WarehouseReportFacade' => \App\Facades\WarehouseReportFacade::class,
        'CodeFacade' => \App\Facades\CodeFacade::class,
        'FixWorkflowCycleFacade' => \App\Facades\FixWorkflowCycleFacade::class,
        'AutoCollectFacade' => \App\Facades\AutoCollect::class,
        'ExcelWriterFacade' => \App\Facades\ExcelWriter::class,
        'EntireInstanceFacade' => \App\Facades\EntireInstanceFacade::class,
        'FixWorkflowFacade' => \App\Facades\FixWorkflowFacade::class,
        'DetectingFacade' => \App\Facades\Detecting::class,
        'EntireInstanceLogFacade' => \App\Facades\EntireInstanceLogFacade::class,
        'PartInstanceFacade' => \App\Facades\PartInstanceFacade::class,
        'ExcelReaderFacade' => \App\Facades\ExcelReader::class,
        'MeasurementFacade' => \App\Facades\MeasurementFacade::class,
        'FactoryFacade' => \App\Facades\FactoryFacade::class,
        'SuPuRuiApiFacade' => \App\Facades\SuPuRuiApi::class,
        'SuPuRuiSdkFacade' => \App\Facades\SuPuRuiSdk::class,
        'SuPuRuiTestFacade' => \App\Facades\SuPuRuiTest::class,
        'EntireModelFacade' => \App\Facades\EntireModelFacade::class,
        'FixWorkflowExcelFacade' => \App\Facades\FixWorkflowExcelFacade::class,
        'EveryMonthExcelFacade' => \App\Facades\EveryMonthExcelFacade::class,
        'QualityFacade' => \App\Facades\QualityFacade::class,
        'SuPuRuiLocationFacade' => \App\Facades\SuPuRuiLocationFacade::class,
        'TemporaryFacade' => \App\Facades\TemporaryFacade::class,
        'DingResponseFacade' => \App\Facades\DingResponseFacade::class,
        'CycleFixFacade' => \App\Facades\CycleFixFacade::class,
        'StatisticsFacade' => \App\Facades\StatisticsFacade::class,
        'QueryFacade' => \App\Facades\QueryConditionFacade::class,
        'DNS1D' => \Milon\Barcode\Facades\DNS1DFacade::class,
        'DNS2D' => \Milon\Barcode\Facades\DNS2DFacade::class,

    ],

];
