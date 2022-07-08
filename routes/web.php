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
        Route::get("logout", "AuthorizationController@GetLogout")->name("GetLogout");  // 退出登录
        Route::post("logout", "AuthorizationController@GetLogout")->name("PostLogout");  // 退出登录
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
                Route::get("{uuid}", "AccountController@Show")->name("Show");  // 用户详情
                Route::get("{uuid}/edit", "AccountController@Edit")->name("Edit");  // 编辑用户页面
                Route::put("{uuid}", "AccountController@Update")->name("Update");  // 编辑用户
                Route::put("{uuid}/updatePassword", "AccountController@UpdatePassword")->name("UpdatePassword");  // 编辑用户密码
                Route::delete("{uuid}", "AccountController@Destroy")->name("Destroy");  // 删除用户
            });

        // 角色
        Route::prefix("rbacRole")
            ->name("RbacRole:")
            ->group(function () {
                Route::get("", "RbacRoleController@Index")->name("Index");  // 角色列表
                Route::get("create", "RbacRoleController@Create")->name("Create");  // 新建角色页面
                Route::get("{uuid}", "RbacRoleController@Show")->name("Show");  // 角色详情
                Route::get("{uuid}/edit", "RbacRoleController@Edit")->name("Edit");  // 角色详情
                Route::post("", "RbacRoleController@Store")->name("Store");  // 新建角色
                Route::put("{uuid}", "RbacRoleController@Update")->name("Update"); // 编辑角色
                Route::delete("{uuid}", "RbacRoleController@Destroy")->name("Destroy");  // 删除角色
            });
    });

