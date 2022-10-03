<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the 'web' middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

// 无需登陆
Route::prefix('authorization')
    ->name('Authorization:')
    ->group(function () {
        Route::get('login', 'AuthorizationController@getLogin')->name('getLogin');  // 登陆页面
        Route::post('login', 'AuthorizationController@postLogin')->name('postLogin');  // 登录
        Route::get('logout', 'AuthorizationController@getLogout')->name('getLogout');  // 退出登录
        Route::post('logout', 'AuthorizationController@postLogout')->name('postLogout');  // 退出登录
        Route::get('menus', 'AuthorizationController@getMenus')->name('getMenus');  // 获取当前用户菜单
    });

// 需要登录
Route::prefix('')
    ->middleware('CheckLoginMiddleware')
    ->group(function () {
        // 主页
        Route::prefix('')
            ->name('Home:')
            ->group(function () {
                Route::get('', 'HomeController@index')->name('index');  // 主页
            });

        // 用户
        Route::prefix('account')
            ->name('Account:')
            ->group(function () {
                Route::get('', 'AccountController@index')->name('index');  // 用户列表
                Route::get('create', 'AccountController@create')->name('create');  // 新建用户页面
                Route::post('', 'AccountController@store')->name('store');  // 新建用户
                Route::get('{uuid}', 'AccountController@show')->name('show');  // 用户详情
                Route::get('{uuid}/edit', 'AccountController@edit')->name('edit');  // 编辑用户页面
                Route::put('{uuid}', 'AccountController@update')->name('update');  // 编辑用户
                Route::put('{uuid}/updatePassword', 'AccountController@updatePassword')->name('updatePassword');  // 编辑用户密码
                Route::delete('{uuid}', 'AccountController@destroy')->name('destroy');  // 删除用户
            });

        // 角色
        Route::prefix('rbacRole')
            ->name('RbacRole:')
            ->group(function () {
                Route::get('', 'RbacRoleController@index')->name('index');  // 角色列表
                Route::get('create', 'RbacRoleController@create')->name('create');  // 新建角色页面
                Route::get('{uuid}', 'RbacRoleController@show')->name('show');  // 角色详情
                Route::get('{uuid}/edit', 'RbacRoleController@edit')->name('edit');  // 角色详情
                Route::post('', 'RbacRoleController@store')->name('store');  // 新建角色
                Route::put('{uuid}', 'RbacRoleController@update')->name('update'); // 编辑角色
                Route::delete('{uuid}', 'RbacRoleController@destroy')->name('destroy');  // 删除角色
                Route::get('{uuid}/bind', 'RbacRoleController@getBind')->name('getBind');  // 角色绑定管理页面
                Route::put('{uuid}/bindAccounts', 'RbacRoleController@putBindAccounts')->name('putBindAccounts'); // 角色绑定用户
                Route::put('{uuid}/bindRbacPermissions', 'RbacRoleController@putBindRbacPermissions')->name('putBindRbacPermissions'); // 角色绑定权限
                Route::put('{uuid}/bindRbacPermissionsByRbacPermissionGroup', 'RbacRoleController@putBindRbacPermissionsByRbacPermissionGroup')->name('putBindRbacPermissionsByRbacPermissionGroup'); // 角色绑定权限（根据权限分组）
            });

        // 权限分组
        Route::prefix('rbacPermissionGroup')
            ->name('RbacPermissionGroup:')
            ->group(function () {
                Route::get('', 'RbacPermissionGroupController@index')->name('index');  // 权限分组列表
                Route::get('create', 'RbacPermissionGroupController@create')->name('create');  // 新建权限分组页面
                Route::get('{uuid}', 'RbacPermissionGroupController@show')->name('show');  // 权限分组详情
                Route::get('{uuid}/edit', 'RbacPermissionGroupController@edit')->name('edit');  // 权限分组详情
                Route::post('', 'RbacPermissionGroupController@store')->name('store');  // 新建权限分组角色
                Route::put('{uuid}', 'RbacPermissionGroupController@update')->name('update'); // 权限分组角色
                Route::delete('{uuid}', 'RbacPermissionGroupController@destroy')->name('destroy');  // 删除权限分组
            });

        // 权限
        Route::prefix('rbacPermission')
            ->name('RbacPermission:')
            ->group(function () {
                Route::get('', 'RbacPermissionController@index')->name('index');  // 权限列表
                Route::get('create', 'RbacPermissionController@create')->name('create');  // 新建权限页面
                Route::get('{uuid}', 'RbacPermissionController@show')->name('show');  // 权限详情
                Route::get('{uuid}/edit', 'RbacPermissionController@edit')->name('edit');  // 权限详情
                Route::post('', 'RbacPermissionController@store')->name('store');  // 新建权限角色
                Route::put('{uuid}', 'RbacPermissionController@update')->name('update'); // 权限角色
                Route::delete('{uuid}', 'RbacPermissionController@destroy')->name('destroy');  // 删除权限
                Route::post('resource', 'RbacPermissionController@postResource')->name('postResource');  // 批量添加资源权限
            });

        // 菜单
        Route::prefix('menu')
            ->name('Menu:')
            ->group(function () {
                Route::get('', 'MenuController@index')->name('index');  // 菜单列表
                Route::get('create', 'MenuController@create')->name('create');  // 新建菜单页面
                Route::post('', 'MenuController@store')->name('store');  // 新建菜单
                Route::get('{uuid}', 'MenuController@show')->name('show');  // 菜单详情
                Route::get('{uuid}/edit', 'MenuController@edit')->name('edit');  // 编辑菜单页面
                Route::put('{uuid}', 'MenuController@update')->name('update');  // 编辑菜单
                Route::delete('{uuid}', 'MenuController@destroy')->name('destroy');  // 删除菜单
            });

        // 组织机构
        // 路局
        Route::prefix('organizationRailway')
            ->name('OrganizationRailway:')
            ->group(function () {
                Route::get('', 'OrganizationRailwayController@index')->name('index');  // 路局列表
                Route::get('create', 'OrganizationRailwayController@create')->name('create');  // 新建路局页面
                Route::get('{uuid}', 'OrganizationRailwayController@show')->name('show');  // 路局详情
                Route::get('{uuid}/edit', 'OrganizationRailwayController@edit')->name('edit');  // 路局详情
                Route::post('', 'OrganizationRailwayController@store')->name('store');  // 路局角色
                Route::put('{uuid}', 'OrganizationRailwayController@update')->name('update'); // 路局角色
                Route::delete('{uuid}', 'OrganizationRailwayController@destroy')->name('destroy');  // 删除路局
                Route::put('{uuid}/bindLocationLines', 'OrganizationRailwayController@putBindLocationLines')->name('putBindLocationLines');  // 绑定线别
            });

        // 站段
        Route::prefix('organizationParagraph')
            ->name('OrganizationParagraph:')
            ->group(function () {
                Route::get('', 'OrganizationParagraphController@index')->name('index');  // 站段列表
                Route::get('create', 'OrganizationParagraphController@create')->name('create');  // 新建站段页面
                Route::get('{uuid}', 'OrganizationParagraphController@show')->name('show');  // 站段详情
                Route::get('{uuid}/edit', 'OrganizationParagraphController@edit')->name('edit');  // 站段详情
                Route::post('', 'OrganizationParagraphController@store')->name('store');  // 站段角色
                Route::put('{uuid}', 'OrganizationParagraphController@update')->name('update'); // 站段角色
                Route::delete('{uuid}', 'OrganizationParagraphController@destroy')->name('destroy');  // 删除站段
            });

        // 车间类型
        Route::prefix('organizationWorkshopType')
            ->name('OrganizationWorkshopType:')
            ->group(function () {
                Route::get('', 'OrganizationWorkshopTypeController@index')->name('index');  // 车间类型列表
                Route::get('create', 'OrganizationWorkshopTypeController@create')->name('create');  // 新建车间类型页面
                Route::get('{uuid}', 'OrganizationWorkshopTypeController@show')->name('show');  // 车间类型详情
                Route::get('{uuid}/edit', 'OrganizationWorkshopTypeController@edit')->name('edit');  // 车间类型详情
                Route::post('', 'OrganizationWorkshopTypeController@store')->name('store');  // 车间类型角色
                Route::put('{uuid}', 'OrganizationWorkshopTypeController@update')->name('update'); // 车间类型角色
                Route::delete('{uuid}', 'OrganizationWorkshopTypeController@destroy')->name('destroy');  // 删除车间类型
            });

        // 车间
        Route::prefix('organizationWorkshop')
            ->name('OrganizationWorkshop:')
            ->group(function () {
                Route::get('', 'OrganizationWorkshopController@index')->name('index');  // 车间列表
                Route::get('create', 'OrganizationWorkshopController@create')->name('create');  // 新建车间页面
                Route::get('{uuid}', 'OrganizationWorkshopController@show')->name('show');  // 车间详情
                Route::get('{uuid}/edit', 'OrganizationWorkshopController@edit')->name('edit');  // 车间详情
                Route::post('', 'OrganizationWorkshopController@store')->name('store');  // 车间角色
                Route::put('{uuid}', 'OrganizationWorkshopController@update')->name('update'); // 车间角色
                Route::delete('{uuid}', 'OrganizationWorkshopController@destroy')->name('destroy');  // 删除车间
            });

        // 工区类型
        Route::prefix('organizationWorkAreaType')
            ->name('OrganizationWorkAreaType:')
            ->group(function () {
                Route::get('', 'OrganizationWorkAreaTypeController@index')->name('index');  // 工区类型列表
                Route::get('create', 'OrganizationWorkAreaTypeController@create')->name('create');  // 新建工区类型页面
                Route::get('{uuid}', 'OrganizationWorkAreaTypeController@show')->name('show');  // 工区类型详情
                Route::get('{uuid}/edit', 'OrganizationWorkAreaTypeController@edit')->name('edit');  // 工区类型详情
                Route::post('', 'OrganizationWorkAreaTypeController@store')->name('store');  // 工区类型角色
                Route::put('{uuid}', 'OrganizationWorkAreaTypeController@update')->name('update'); // 工区类型角色
                Route::delete('{uuid}', 'OrganizationWorkAreaTypeController@destroy')->name('destroy');  // 删除工区类型
            });

        // 工区专业
        Route::prefix('organizationWorkAreaProfession')
            ->name('OrganizationWorkAreaProfession:')
            ->group(function () {
                Route::get('', 'OrganizationWorkAreaProfessionController@index')->name('index');  // 工区专业列表
                Route::get('create', 'OrganizationWorkAreaProfessionController@create')->name('create');  // 新建工区专业页面
                Route::get('{uuid}', 'OrganizationWorkAreaProfessionController@show')->name('show');  // 工区专业详情
                Route::get('{uuid}/edit', 'OrganizationWorkAreaProfessionController@edit')->name('edit');  // 工区专业详情
                Route::post('', 'OrganizationWorkAreaProfessionController@store')->name('store');  // 工区专业角色
                Route::put('{uuid}', 'OrganizationWorkAreaProfessionController@update')->name('update'); // 工区专业角色
                Route::delete('{uuid}', 'OrganizationWorkAreaProfessionController@destroy')->name('destroy');  // 删除工区专业
            });

        // 工区
        Route::prefix('organizationWorkArea')
            ->name('OrganizationWorkArea:')
            ->group(function () {
                Route::get('', 'OrganizationWorkAreaController@index')->name('index');  // 工区列表
                Route::get('create', 'OrganizationWorkAreaController@create')->name('create');  // 新建工区页面
                Route::get('{uuid}', 'OrganizationWorkAreaController@show')->name('show');  // 工区详情
                Route::get('{uuid}/edit', 'OrganizationWorkAreaController@edit')->name('edit');  // 工区详情
                Route::post('', 'OrganizationWorkAreaController@store')->name('store');  // 工区角色
                Route::put('{uuid}', 'OrganizationWorkAreaController@update')->name('update'); // 工区角色
                Route::delete('{uuid}', 'OrganizationWorkAreaController@destroy')->name('destroy');  // 删除工区
            });

        // 使用地点
        // 线别
        Route::prefix('locationLine')
            ->name('LocationLine:')
            ->group(function () {
                Route::get('', 'LocationLineController@index')->name('index');  // 线别列表
                Route::get('create', 'LocationLineController@create')->name('create');  // 新建线别页面
                Route::get('{uuid}', 'LocationLineController@show')->name('show');  // 线别详情
                Route::get('{uuid}/edit', 'LocationLineController@edit')->name('edit');  // 线别详情
                Route::post('', 'LocationLineController@store')->name('store');  // 线别角色
                Route::put('{uuid}', 'LocationLineController@update')->name('update'); // 线别角色
                Route::delete('{uuid}', 'LocationLineController@destroy')->name('destroy');  // 删除线别
                Route::put('{uuid}/bindOrganizationRailways', 'LocationLineController@putBindOrganizationRailways')->name('putBindOrganizationRailways');  // 绑定路局
            });

        // 站场
        Route::prefix('locationStation')
            ->name('LocationStation:')
            ->group(function () {
                Route::get('', 'LocationStationController@index')->name('index');  // 站场列表
                Route::get('create', 'LocationStationController@create')->name('create');  // 新建站场页面
                Route::get('{uuid}', 'LocationStationController@show')->name('show');  // 站场详情
                Route::get('{uuid}/edit', 'LocationStationController@edit')->name('edit');  // 站场详情
                Route::post('', 'LocationStationController@store')->name('store');  // 站场角色
                Route::put('{uuid}', 'LocationStationController@update')->name('update'); // 站场角色
                Route::delete('{uuid}', 'LocationStationController@destroy')->name('destroy');  // 删除站场
                Route::put('{uuid}/bindLocationLines', 'LocationStationController@putBindLocationLines')->name('putBindLocationLines');  // 绑定线别
            });

        // 道口
        Route::prefix('locationRailroadGradeCross')
            ->name('LocationRailroadGradeCross:')
            ->group(function () {
                Route::get('', 'LocationRailroadGradeCrossController@index')->name('index');  // 道口列表
                Route::get('create', 'LocationRailroadGradeCrossController@create')->name('create');  // 新建道口页面
                Route::get('{uuid}', 'LocationRailroadGradeCrossController@show')->name('show');  // 道口详情
                Route::get('{uuid}/edit', 'LocationRailroadGradeCrossController@edit')->name('edit');  // 道口详情
                Route::post('', 'LocationRailroadGradeCrossController@store')->name('store');  // 道口角色
                Route::put('{uuid}', 'LocationRailroadGradeCrossController@update')->name('update'); // 道口角色
                Route::delete('{uuid}', 'LocationRailroadGradeCrossController@destroy')->name('destroy');  // 删除道口
                Route::put('{uuid}/bindLocationLines', 'LocationRailroadGradeCrossController@putBindLocationLines')->name('putBindLocationLines');  // 绑定线别
            });

        // 区间
        Route::prefix('locationSection')
            ->name('LocationSection:')
            ->group(function () {
                Route::get('', 'LocationSectionController@index')->name('index');  // 区间列表
                Route::get('create', 'LocationSectionController@create')->name('create');  // 新建区间页面
                Route::get('{uuid}', 'LocationSectionController@show')->name('show');  // 区间详情
                Route::get('{uuid}/edit', 'LocationSectionController@edit')->name('edit');  // 区间详情
                Route::post('', 'LocationSectionController@store')->name('store');  // 区间角色
                Route::put('{uuid}', 'LocationSectionController@update')->name('update'); // 区间角色
                Route::delete('{uuid}', 'LocationSectionController@destroy')->name('destroy');  // 删除区间
                Route::put('{uuid}/bindLocationLines', 'LocationSectionController@putBindLocationLines')->name('putBindLocationLines');  // 绑定线别
            });

        // 中心
        Route::prefix('locationCenter')
            ->name('LocationCenter:')
            ->group(function () {
                Route::get('', 'LocationCenterController@index')->name('index');  // 中心列表
                Route::get('create', 'LocationCenterController@create')->name('create');  // 新建中心页面
                Route::get('{uuid}', 'LocationCenterController@show')->name('show');  // 中心详情
                Route::get('{uuid}/edit', 'LocationCenterController@edit')->name('edit');  // 中心详情
                Route::post('', 'LocationCenterController@store')->name('store');  // 中心角色
                Route::put('{uuid}', 'LocationCenterController@update')->name('update'); // 中心角色
                Route::delete('{uuid}', 'LocationCenterController@destroy')->name('destroy');  // 删除中心
                Route::put('{uuid}/bindLocationLines', 'LocationCenterController@putBindLocationLines')->name('putBindLocationLines');  // 绑定线别
            });

        // 仓库位置
        Route::prefix('positionDepotStorehouse')
            ->name('PositionDepotStorehouse:')
            ->group(function () {
                Route::get('', 'PositionDepotStorehouseController@index')->name('index');  // 仓库位置列表
                Route::get('create', 'PositionDepotStorehouseController@create')->name('create');  // 新建仓库位置页面
                Route::get('{uuid}', 'PositionDepotStorehouseController@show')->name('show');  // 仓库位置详情
                Route::get('{uuid}/edit', 'PositionDepotStorehouseController@edit')->name('edit');  // 仓库位置详情
                Route::post('', 'PositionDepotStorehouseController@store')->name('store');  // 仓库位置角色
                Route::put('{uuid}', 'PositionDepotStorehouseController@update')->name('update'); // 仓库位置角色
                Route::delete('{uuid}', 'PositionDepotStorehouseController@destroy')->name('destroy');  // 删除仓库位置
            });
    });

