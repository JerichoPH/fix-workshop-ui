<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'b048' => [
            'driver' => 'mysql',
            'host' => env("DB_HOST"),
            'port' => env("DB_PORT"),
            'database' => 'b048_maintain',
            'username' => 'root',
            'password' => 'root',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'b049' => [
            'driver' => 'mysql',
            'host' => env("DB_HOST"),
            'port' => env("DB_PORT"),
            'database' => 'b049_maintain',
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'b050' => [
            'driver' => 'mysql',
            'host' => env("DB_HOST"),
            'port' => env("DB_PORT"),
            'database' => 'b050_maintain',
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'b051' => [
            'driver' => 'mysql',
            'host' => env("DB_HOST"),
            'port' => env("DB_PORT"),
            'database' => 'b051_maintain',
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'b052' => [
            'driver' => 'mysql',
            'host' => env("DB_HOST"),
            'port' => env("DB_PORT"),
            'database' => 'b052_maintain',
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'b053' => [
            'driver' => 'mysql',
            'host' => env("DB_HOST"),
            'port' => env("DB_PORT"),
            'database' => 'b053_maintain',
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'b074' => [
            'driver' => 'mysql',
            'host' => env("DB_HOST"),
            'port' => env("DB_PORT"),
            'database' => 'b074_maintain',
            'username' => env("DB_USERNAME"),
            'password' => env("DB_PASSWORD"),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'paragraph_center'=>[
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'bi',
            'username' => 'root',
            'password' => 'root',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'fix_kind'=>[
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'fix_kind',
            'username' => 'root',
            'password' => 'root',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'huizhou_warehouse' => [
            'driver' => 'mysql',
            'host' => '118.24.187.103',
            'port' => '3306',
            'database' => 'huizhou_warehouse',
            'username' => 'root',
            'password' => 'zces@1234',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'pro_b050' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => 'pro_b050_maintain',
            'username' => 'root',
            'password' => 'root',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'pro_b052' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => 'pro_b052_maintain',
            'username' => 'root',
            'password' => 'root',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'mysql_as_rpc' => [
            'driver' => 'mysql',
            'host' => env('DB_RPC_HOST'),
            'port' => '3306',
            'database' => env('DB_RPC_DATABASE'),
            'username' => env('DB_RPC_USERNAME'),
            'password' => env('DB_RPC_PASSWORD'),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('SQLSRV_HOST', '127.0.0.1'),
            'port' => env('SQLSRV_PORT', 1433),
            'database' => env('SQLSRV_DATABASE', 'ZPW2000AK'),
            'username' => env('SQLSRV_USERNAME', 'sa'),
            'password' => env('SQLSRV_PASSWORD', 'LM521149sql'),
            'charset' => env('SQLSRV_CHARSET', 'Chinese_PRC_CI_AI'),
            'prefix' => '',
        ],

        'huaxin' => [
            'driver' => 'sqlsrv',
            'host' => env('HX_SQLSRV_HOST', '127.0.0.1'),
            'port' => env('HX_SQLSRV_PORT', 1433),
            'database' => env('HX_SQLSRV_DATABASE', 'Dwqcgl'),
            'username' => env('HX_SQLSRV_USERNAME', 'sa'),
            'password' => env('HX_SQLSRV_PASSWORD', 'Zces@1234'),
            'charset' => env('HX_SQLSRV_CHARSET', 'Chinese_PRC_CI_AI'),
            'prefix' => '',
        ],

        'supurui' => [
            'driver' => 'mysql',
            'host' => env('SUPURUI_DB_HOST', '192.168.253.1'),
            'port' => env('SUPURUI_DB_PORT', '3306'),
            'database' => env('SUPURUI_DB_DATABASE', 'gcdb'),
            'username' => env('SUPURUI_DB_USERNAME', 'reader'),
            'password' => env('SUPURUI_DB_PASSWORD', 'Ks8hTg2$6.2Idnm257'),
            'unix_socket' => env('SUPURUI_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        'gcdb' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => env('GCDB_DB_DATABASE', 'gcdb'),
            'username' => env('GCDB_DB_USERNAME', 'root'),
            'password' => env('GCDB_DB_PASSWORD', ''),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => 'predis',

        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
            'read_write_timeout' => 60,
        ],

    ],

];
