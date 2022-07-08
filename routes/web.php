<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

// 无需登陆
Route::prefix("authorization")
    ->name("Authorization:")
    ->group(function () {
        Route::get("login", "AuthorizationController@GetLogin")->name("GetLogin");  // 登陆页面
        Route::post("login", "AuthorizationController@PostLogin")->name("PostLogin");  // 登录
    });

// 需要登录
Route::prefix("")
    ->middleware("CheckLoginMiddleware")
    ->group(function () {
        // 主页
        Route::prefix("")
            ->name("Home:")
            ->group(function () {
                Route::get("", "HomeController@Index")->name("Index");  // 主页
            });

        // 用户
        Route::prefix("account")
            ->name("Account:")
            ->group(function () {
                Route::get("", "AccountController@Index")->name("Index");  // 用户列表
                Route::get("create", "AccountController@Create")->name("Create");  // 新建用户页面
                Route::post("", "AccountController@Store")->name("Store");  // 新建用户
                Route::get("{uuid}/edit", "AccountController@Edit")->name("Edit");  // 编辑用户页面
                Route::put("{uuid}", "AccountController@Update")->name("Update");  // 编辑用户
                Route::delete("{uuid}", "AccountController@Destroy")->name("Destroy");  // 删除用户
            });
    });

