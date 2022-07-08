<?php

use Illuminate\Support\Facades\Route;

Route::prefix("authorization")
    ->name("Authorization:")
    ->group(function () {
        Route::post("login", "AuthorizationController@PostLogin")->name("PostLogin");  // 登陆
    });