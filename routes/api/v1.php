<?php

use Illuminate\Support\Facades\Route;

Route::prefix('authorization')
    ->name('Authorization:')
    ->group(function () {
        Route::post('login', 'AuthorizationController@PostLogin')->name('PostLogin');  // 登陆
    });

Route::prefix('test')
    ->name('Test:')
    ->group(function () {
        Route::post('', 'TestController@store')->name('store');
    });