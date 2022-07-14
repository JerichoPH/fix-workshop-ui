<?php

namespace App\Providers;

use App\Services\TextService;
use Illuminate\Support\ServiceProvider;

class TextServiceProvider extends ServiceProvider
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
        $this->app->singleton(TextServiceProvider::class,function(){
            return new TextService();
        });
    }
}
