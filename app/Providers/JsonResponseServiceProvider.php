<?php

namespace App\Providers;

use App\Services\JsonResponseService;
use Illuminate\Support\ServiceProvider;

class JsonResponseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(JsonResponseService::class, function () {
            return new JsonResponseService();
        });
    }
}
