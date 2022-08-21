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
        Route::get("menus", "AuthorizationController@GetMenus")->name("GetMenus");  // 获取当前用户菜单
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
                Route::get("{uuid}/bind", "RbacRoleController@GetBind")->name("GetBind");  // 角色绑定管理页面
                Route::put("{uuid}/bindAccounts", "RbacRoleController@PutBindAccounts")->name("PutBindAccounts"); // 角色绑定用户
                Route::put("{uuid}/bindPermissions", "RbacRoleController@PutBindPermissions")->name("PutBindPermissions"); // 角色绑定权限
            });

        // 权限分组
        Route::prefix("rbacPermissionGroup")
            ->name("RbacPermissionGroup:")
            ->group(function () {
                Route::get("", "RbacPermissionGroupController@Index")->name("Index");  // 权限分组列表
                Route::get("create", "RbacPermissionGroupController@Create")->name("Create");  // 新建权限分组页面
                Route::get("{uuid}", "RbacPermissionGroupController@Show")->name("Show");  // 权限分组详情
                Route::get("{uuid}/edit", "RbacPermissionGroupController@Edit")->name("Edit");  // 权限分组详情
                Route::post("", "RbacPermissionGroupController@Store")->name("Store");  // 新建权限分组角色
                Route::put("{uuid}", "RbacPermissionGroupController@Update")->name("Update"); // 权限分组角色
                Route::delete("{uuid}", "RbacPermissionGroupController@Destroy")->name("Destroy");  // 删除权限分组
            });

        // 权限
        Route::prefix("rbacPermission")
            ->name("RbacPermission:")
            ->group(function () {
                Route::get("", "RbacPermissionController@Index")->name("Index");  // 权限列表
                Route::get("create", "RbacPermissionController@Create")->name("Create");  // 新建权限页面
                Route::get("{uuid}", "RbacPermissionController@Show")->name("Show");  // 权限详情
                Route::get("{uuid}/edit", "RbacPermissionController@Edit")->name("Edit");  // 权限详情
                Route::post("", "RbacPermissionController@Store")->name("Store");  // 新建权限角色
                Route::put("{uuid}", "RbacPermissionController@Update")->name("Update"); // 权限角色
                Route::delete("{uuid}", "RbacPermissionController@Destroy")->name("Destroy");  // 删除权限
                Route::post("resource", "RbacPermissionController@PostResource")->name("PostResource");  // 批量添加资源权限
            });

        // 菜单
        Route::prefix("menu")
            ->name("Menu:")
            ->group(function () {
                Route::get("", "MenuController@Index")->name("Index");  // 菜单列表
                Route::get("create", "MenuController@Create")->name("Create");  // 新建菜单页面
                Route::post("", "MenuController@Store")->name("Store");  // 新建菜单
                Route::get("{uuid}", "MenuController@Show")->name("Show");  // 菜单详情
                Route::get("{uuid}/edit", "MenuController@Edit")->name("Edit");  // 编辑菜单页面
                Route::put("{uuid}", "MenuController@Update")->name("Update");  // 编辑菜单
                Route::delete("{uuid}", "MenuController@Destroy")->name("Destroy");  // 删除菜单
            });

        // 组织机构
        // 路局
        Route::prefix("organizationRailway")
            ->name("OrganizationRailway:")
            ->group(function () {
                Route::get("", "OrganizationRailwayController@Index")->name("Index");  // 路局列表
                Route::get("create", "OrganizationRailwayController@Create")->name("Create");  // 新建路局页面
                Route::get("{uuid}", "OrganizationRailwayController@Show")->name("Show");  // 路局详情
                Route::get("{uuid}/edit", "OrganizationRailwayController@Edit")->name("Edit");  // 路局详情
                Route::post("", "OrganizationRailwayController@Store")->name("Store");  // 路局角色
                Route::put("{uuid}", "OrganizationRailwayController@Update")->name("Update"); // 路局角色
                Route::delete("{uuid}", "OrganizationRailwayController@Destroy")->name("Destroy");  // 删除路局
                Route::put("{uuid}/bindLocationLines", "OrganizationRailwayController@PutBindLocationLines")->name("PutBindLocationLines");  // 绑定线别
            });

        // 站段
        Route::prefix("organizationParagraph")
            ->name("OrganizationParagraph:")
            ->group(function () {
                Route::get("", "OrganizationParagraphController@Index")->name("Index");  // 站段列表
                Route::get("create", "OrganizationParagraphController@Create")->name("Create");  // 新建站段页面
                Route::get("{uuid}", "OrganizationParagraphController@Show")->name("Show");  // 站段详情
                Route::get("{uuid}/edit", "OrganizationParagraphController@Edit")->name("Edit");  // 站段详情
                Route::post("", "OrganizationParagraphController@Store")->name("Store");  // 站段角色
                Route::put("{uuid}", "OrganizationParagraphController@Update")->name("Update"); // 站段角色
                Route::delete("{uuid}", "OrganizationParagraphController@Destroy")->name("Destroy");  // 删除站段
            });

        // 车间类型
        Route::prefix("organizationWorkshopType")
            ->name("OrganizationWorkshopType:")
            ->group(function () {
                Route::get("", "OrganizationWorkshopTypeController@Index")->name("Index");  // 车间类型列表
                Route::get("create", "OrganizationWorkshopTypeController@Create")->name("Create");  // 新建车间类型页面
                Route::get("{uuid}", "OrganizationWorkshopTypeController@Show")->name("Show");  // 车间类型详情
                Route::get("{uuid}/edit", "OrganizationWorkshopTypeController@Edit")->name("Edit");  // 车间类型详情
                Route::post("", "OrganizationWorkshopTypeController@Store")->name("Store");  // 车间类型角色
                Route::put("{uuid}", "OrganizationWorkshopTypeController@Update")->name("Update"); // 车间类型角色
                Route::delete("{uuid}", "OrganizationWorkshopTypeController@Destroy")->name("Destroy");  // 删除车间类型
            });

        // 车间
        Route::prefix("organizationWorkshop")
            ->name("OrganizationWorkshop:")
            ->group(function () {
                Route::get("", "OrganizationWorkshopController@Index")->name("Index");  // 车间列表
                Route::get("create", "OrganizationWorkshopController@Create")->name("Create");  // 新建车间页面
                Route::get("{uuid}", "OrganizationWorkshopController@Show")->name("Show");  // 车间详情
                Route::get("{uuid}/edit", "OrganizationWorkshopController@Edit")->name("Edit");  // 车间详情
                Route::post("", "OrganizationWorkshopController@Store")->name("Store");  // 车间角色
                Route::put("{uuid}", "OrganizationWorkshopController@Update")->name("Update"); // 车间角色
                Route::delete("{uuid}", "OrganizationWorkshopController@Destroy")->name("Destroy");  // 删除车间
            });

        // 工区类型
        Route::prefix("organizationWorkAreaType")
            ->name("OrganizationWorkAreaType:")
            ->group(function () {
                Route::get("", "OrganizationWorkAreaTypeController@Index")->name("Index");  // 工区类型列表
                Route::get("create", "OrganizationWorkAreaTypeController@Create")->name("Create");  // 新建工区类型页面
                Route::get("{uuid}", "OrganizationWorkAreaTypeController@Show")->name("Show");  // 工区类型详情
                Route::get("{uuid}/edit", "OrganizationWorkAreaTypeController@Edit")->name("Edit");  // 工区类型详情
                Route::post("", "OrganizationWorkAreaTypeController@Store")->name("Store");  // 工区类型角色
                Route::put("{uuid}", "OrganizationWorkAreaTypeController@Update")->name("Update"); // 工区类型角色
                Route::delete("{uuid}", "OrganizationWorkAreaTypeController@Destroy")->name("Destroy");  // 删除工区类型
            });

        // 工区专业
        Route::prefix("organizationWorkAreaProfession")
            ->name("OrganizationWorkAreaProfession:")
            ->group(function () {
                Route::get("", "OrganizationWorkAreaProfessionController@Index")->name("Index");  // 工区专业列表
                Route::get("create", "OrganizationWorkAreaProfessionController@Create")->name("Create");  // 新建工区专业页面
                Route::get("{uuid}", "OrganizationWorkAreaProfessionController@Show")->name("Show");  // 工区专业详情
                Route::get("{uuid}/edit", "OrganizationWorkAreaProfessionController@Edit")->name("Edit");  // 工区专业详情
                Route::post("", "OrganizationWorkAreaProfessionController@Store")->name("Store");  // 工区专业角色
                Route::put("{uuid}", "OrganizationWorkAreaProfessionController@Update")->name("Update"); // 工区专业角色
                Route::delete("{uuid}", "OrganizationWorkAreaProfessionController@Destroy")->name("Destroy");  // 删除工区专业
            });

        // 工区
        Route::prefix("organizationWorkArea")
            ->name("OrganizationWorkArea:")
            ->group(function () {
                Route::get("", "OrganizationWorkAreaController@Index")->name("Index");  // 工区列表
                Route::get("create", "OrganizationWorkAreaController@Create")->name("Create");  // 新建工区页面
                Route::get("{uuid}", "OrganizationWorkAreaController@Show")->name("Show");  // 工区详情
                Route::get("{uuid}/edit", "OrganizationWorkAreaController@Edit")->name("Edit");  // 工区详情
                Route::post("", "OrganizationWorkAreaController@Store")->name("Store");  // 工区角色
                Route::put("{uuid}", "OrganizationWorkAreaController@Update")->name("Update"); // 工区角色
                Route::delete("{uuid}", "OrganizationWorkAreaController@Destroy")->name("Destroy");  // 删除工区
            });

        // 使用处所
        // 线别
        Route::prefix("locationLine")
            ->name("LocationLine:")
            ->group(function () {
                Route::get("", "LocationLineController@Index")->name("Index");  // 线别列表
                Route::get("create", "LocationLineController@Create")->name("Create");  // 新建线别页面
                Route::get("{uuid}", "LocationLineController@Show")->name("Show");  // 线别详情
                Route::get("{uuid}/edit", "LocationLineController@Edit")->name("Edit");  // 线别详情
                Route::post("", "LocationLineController@Store")->name("Store");  // 线别角色
                Route::put("{uuid}", "LocationLineController@Update")->name("Update"); // 线别角色
                Route::delete("{uuid}", "LocationLineController@Destroy")->name("Destroy");  // 删除线别
                Route::put("{uuid}/bindOrganizationRailways", "LocationLineController@PutBindOrganizationRailways")->name("PutBindOrganizationRailways");  // 绑定路局
            });

        // 站场
        Route::prefix("locationStation")
            ->name("LocationStation:")
            ->group(function () {
                Route::get("", "LocationStationController@Index")->name("Index");  // 站场列表
                Route::get("create", "LocationStationController@Create")->name("Create");  // 新建站场页面
                Route::get("{uuid}", "LocationStationController@Show")->name("Show");  // 站场详情
                Route::get("{uuid}/edit", "LocationStationController@Edit")->name("Edit");  // 站场详情
                Route::post("", "LocationStationController@Store")->name("Store");  // 站场角色
                Route::put("{uuid}", "LocationStationController@Update")->name("Update"); // 站场角色
                Route::delete("{uuid}", "LocationStationController@Destroy")->name("Destroy");  // 删除站场
                Route::put("{uuid}/bindLocationLines", "LocationStationController@PutBindLocationLines")->name("PutBindLocationLines");  // 绑定线别
            });

        // 道口
        Route::prefix("locationRailroadGradeCross")
            ->name("LocationRailroadGradeCross:")
            ->group(function () {
                Route::get("", "LocationRailroadGradeCrossController@Index")->name("Index");  // 道口列表
                Route::get("create", "LocationRailroadGradeCrossController@Create")->name("Create");  // 新建道口页面
                Route::get("{uuid}", "LocationRailroadGradeCrossController@Show")->name("Show");  // 道口详情
                Route::get("{uuid}/edit", "LocationRailroadGradeCrossController@Edit")->name("Edit");  // 道口详情
                Route::post("", "LocationRailroadGradeCrossController@Store")->name("Store");  // 道口角色
                Route::put("{uuid}", "LocationRailroadGradeCrossController@Update")->name("Update"); // 道口角色
                Route::delete("{uuid}", "LocationRailroadGradeCrossController@Destroy")->name("Destroy");  // 删除道口
                Route::put("{uuid}/bindLocationLines", "LocationRailroadGradeCrossController@PutBindLocationLines")->name("PutBindLocationLines");  // 绑定线别
            });

        // 区间
        Route::prefix("locationSection")
            ->name("LocationSection:")
            ->group(function () {
                Route::get("", "LocationSectionController@Index")->name("Index");  // 区间列表
                Route::get("create", "LocationSectionController@Create")->name("Create");  // 新建区间页面
                Route::get("{uuid}", "LocationSectionController@Show")->name("Show");  // 区间详情
                Route::get("{uuid}/edit", "LocationSectionController@Edit")->name("Edit");  // 区间详情
                Route::post("", "LocationSectionController@Store")->name("Store");  // 区间角色
                Route::put("{uuid}", "LocationSectionController@Update")->name("Update"); // 区间角色
                Route::delete("{uuid}", "LocationSectionController@Destroy")->name("Destroy");  // 删除区间
                Route::put("{uuid}/bindLocationLines", "LocationSectionController@PutBindLocationLines")->name("PutBindLocationLines");  // 绑定线别
            });

        // 中心
        Route::prefix("locationCenter")
            ->name("LocationCenter:")
            ->group(function () {
                Route::get("", "LocationCenterController@Index")->name("Index");  // 中心列表
                Route::get("create", "LocationCenterController@Create")->name("Create");  // 新建中心页面
                Route::get("{uuid}", "LocationCenterController@Show")->name("Show");  // 中心详情
                Route::get("{uuid}/edit", "LocationCenterController@Edit")->name("Edit");  // 中心详情
                Route::post("", "LocationCenterController@Store")->name("Store");  // 中心角色
                Route::put("{uuid}", "LocationCenterController@Update")->name("Update"); // 中心角色
                Route::delete("{uuid}", "LocationCenterController@Destroy")->name("Destroy");  // 删除中心
                Route::put("{uuid}/bindLocationLines", "LocationSectionController@PutBindLocationLines")->name("PutBindLocationLines");  // 绑定线别
            });

    });

