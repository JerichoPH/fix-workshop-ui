<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        // 程序升级附件
        'appUpgradeAccessories' => [
            'driver' => 'local',
            'root' => storage_path('app/public/appUpgradeAccessories'),
            'url' => '/storage/appUpgradeAccessories',
            'visibility' => 'public',
        ],

        // 机柜照片
        'collectImage' => [
            'driver' => 'local',
            'root' => storage_path('app/public/collectImages'),
            'url' => env('APP_URL') . '/storage/collectImages',
            'visibility' => 'public',
        ],

        // 临时任务
        'tempTask' => [
            'driver' => 'local',
            'root' => storage_path('tempTask/accessory'),
        ],

        // 设备导入
        'deviceInputExcel' => [
            'driver' => 'local',
            'root' => storage_path('deviceInputExcel'),
        ],

        //  上传检修人、验收人
        'uploadFixerCheckerExcel' => [
            'driver' => 'local',
            'root' => storage_path('app/public/updateFixerCheckerExcel'),
            'url' => env('APP_URL') . 'storage/updateFixerCheckerExcel',
            'visibility' => 'public',
        ],

        // 赋码Excel
        'uploadCreateExcel' => [
            'driver' => 'local',
            'root' => storage_path('app/public/uploadCreateExcel'),
            'url' => env('APP_URL') . 'storage/uploadCreateExcel',
            'visibility' => 'public',
        ],

        // 批量修改Excel
        'uploadEditExcel' => [
            'driver' => 'local',
            'root' => storage_path('app/public/uploadEditExcel'),
            'url' => env('APP_URL') . '/storage/uploadEditExcel',
            'visibility' => 'public',
        ],

        'fixWorkflowInput' => [
            'driver' => 'local',
            'root' => storage_path('fixWorkflowInput')
        ],

        'temporary' => [
            'driver' => 'local',
            'root' => storage_path('temporary')
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        // 缓存
        'statistics' => [
            'driver' => 'local',
            'root' => storage_path('app/statistics'),
            'visibility' => 'public',
        ],

        // 出入所签字
        'warehouseSignImages' => [
            'driver' => 'local',
            'root' => storage_path('app/public/warehouseSignImages'),
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],
    ],
];
