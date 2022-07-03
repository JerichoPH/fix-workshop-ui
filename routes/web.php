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

use App\Facades\RpcDeviceStatusFacade;
use App\Facades\RpcMaintainFacade;
use App\Facades\RpcPlanAndFinishFacade;
use App\Facades\RpcPropertyFacade;
use App\Facades\RpcQualityFacade;
use App\Facades\RpcScrapedFacade;
use App\Facades\RpcTestFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// 不需要登录
Route::group(['prefix' => ''], function () {
    Route::get('batchBindingRFIDWithIdentityCode', 'IndexController@getBatchBindingRFIDWithIdentityCode')->name('index.test.get');  // 测试.get
    Route::get('/temp1', 'IndexController@getTemp1')->name('index.getTemp1');
    Route::post('test', 'IndexController@postTest')->name('index.test.post');  // 测试.post
    Route::get('test', 'IndexController@getTest')->name('index.test.get');  // 测试.get
    Route::get('test/sse', 'IndexController@getTestSSE')->name('index.getTestSSE');  // 测试SSE
    Route::get('test/lp', 'IndexController@getLP')->name('index.getLP');  // 获取长轮询信息
    Route::get('test/sqlserver', 'IndexController@getTestSqlServer')->name('index.getTestSqlServer');  // sqlserver测试
    // Route::get('display/entireInstance/{identity_code}', 'Display\EntireInstanceController@show')->name('web.display.entireInstance.show');  // 设备器材详情页
    Route::get('installLocation/shelf/{install_shelf_unique_code}/canvas', 'InstallLocationController@getCanvas')->name('installLocation.shelf.canvas');  # shelf canvas
    Route::get('installLocation/shelf/{station_unique_code}/canvases', 'InstallLocationController@getCanvases')->name('installLocation.shelf.canvases');  // shelf canvases
    Route::get('entire/instance/standbyStatistics/{entire_instance_identity_code}', 'Entire\InstanceController@getStandbyStatistics')->name('web.entire.instance.getStandbyStatistics');  // 当前设备备品统计
});

// 账号
Route::group(['prefix' => 'account'], function () {
    Route::get("uploadPassword", "AccountController@getUploadPassword")->name("account.getUploadPassword");  // 修改密码页面
    Route::put('{id}/editPassword', 'AccountController@putEditPassword')->name('account.putEditPassword');  // 修改密码
    Route::get('downloadUploadCreateAccountByParagraphExcelTemplate', 'AccountController@getDownloadUploadCreateAccountByParagraphExcelTemplate')->name('account.getDownloadUploadCreateAccountByParagraphExcelTemplate');  // 下载批量上传人员模板
    Route::get('uploadCreateAccountByParagraph', 'AccountController@getUploadCreateAccountByParagraph')->name('account.getUploadCreateAccountByParagraph');  // 批量上传人员页面(电务段)
    Route::post('uploadCreateAccountByParagraph', 'AccountController@postUploadCreateAccountByParagraph')->name('account.postUploadCreateAccountByParagraph');  // 批量上传人员(电务段)
    Route::get('downloadUploadCreateAccountBySceneExcelTemplate', 'AccountController@getDownloadUploadCreateAccountBySceneExcelTemplate')->name('account.getDownloadUploadCreateAccountBySceneExcelTemplate');  // 下载批量上传人员模板
    Route::get('uploadCreateAccountByScene', 'AccountController@getUploadCreateAccountByScene')->name('account.getUploadCreateAccountByScene');  // 批量上传人员页面(现场)
    Route::post('uploadCreateAccountByScene', 'AccountController@postUploadCreateAccountByScene')->name('account.postUploadCreateAccountByScene');  // 批量上传人员(现场)
    Route::post('avatar', 'AccountController@avatar')->name('avatar.post');  // 上传头像
    Route::post('bindRoles/{accountOpenId}', 'AccountController@bindRoles')->name('account-bindRoles.name');  // 用户绑定角色
    Route::post('backupToGroup', 'AccountController@postBackupToGroup')->name('account.backupToGroup.post');  // 备份到数据中台
    Route::get('/', 'AccountController@index')->name('account.index');  // 账号列表
    Route::get('/create', 'AccountController@create')->name('account.create');  // 账号新建页面
    Route::post('/', 'AccountController@store')->name('account.store');  // 账号新建
    Route::get('/{id}', 'AccountController@show')->name('account.show');  // 账号详情页面
    Route::get('/{id}/edit', 'AccountController@edit')->name('account.edit');  // 账号编辑页面
    Route::put('/{id}', 'AccountController@update')->name('account.update');  // 账号编辑
    Route::delete('/{id}', 'AccountController@destroy')->name('account.destroy');  // 账号删除
});

Route::post('register', 'AccountController@register')->name('account-register.post');  // 注册
Route::get('register', 'AccountController@getRegister')->name('account-register.get');  // 注册页
Route::post('login', 'AccountController@login')->name('account-login.post');  // 登录
Route::get('login', 'AccountController@getLogin')->name('account-login.get');  // 登陆页
// Route::get('code/email', 'CodeController@email');  // 申请邮件验证码
Route::put('forget', 'AccountController@forget')->name('account-forget.put');  // 忘记密码
Route::get('forget', 'AccountController@getForget')->name('account-forget.get');  // 忘记密码页面
Route::put('password', 'AccountController@password')->name('account-password.put');  // 修改密码
Route::get('logout', 'AccountController@logout')->name('account-logout.get');  // 退出登录
Route::get('profile', 'AccountController@profile')->name('account-profile.get');  // 个人中心
Route::group(['prefix' => 'download'], function () {
    // 下载
    Route::get('in', 'DownloadController@in');
    Route::get('out', 'DownloadController@out');
});
Route::resource('detecting', 'DetectingController');  // 检测台
Route::post('req', 'ReqController@index');  // 检测台
Route::get('sql', 'SqlserverController@index');  // sqlserver
Route::group(['prefix' => 'rpcDownloadReport'], function () {  // 电务处下载统计资料
    Route::get('/planAndFinish', 'RpcDownloadReportController@planAndFinish')->name('rpcDownloadReport.planAndFinish');  // 周期修计划（种类）
    Route::get('/planAndFinishWithCategory', 'RpcDownloadReportController@planAndFinishWithCategory')->name('rpcDownloadReport.planAndFinishWithCategory');  // 周期修计划（类型）
    Route::get('/planAndFinishWithEntireModel', 'RpcDownloadReportController@planAndFinishWithEntireModel')->name('rpcDownloadReport.planAndFinishWithEntireModel');  // 周期修计划（型号和子类）
    Route::get('/quality', 'RpcDownloadReportController@quality')->name('rpcDownloadReport.quality');  // 质量报告
    Route::get('/scraped', 'RpcDownloadReportController@scraped')->name('rpcDownloadReport.scraped');  // 超期使用
});

// 种类型管理
Route::group(['prefix' => 'basic', 'namespace' => 'Basic'], function () {
    // 种类
    Route::group(['prefix' => 'category'], function () {
        Route::get('/', 'CategoryController@index')->name('category.index');  // 种类 列表
        Route::get('/create', 'CategoryController@create')->name('category.create');  // 种类 新建页面
        Route::post('/', 'CategoryController@store')->name('category.store');  // 种类 新建
        Route::get('/{unique_code}', 'CategoryController@show')->name('category.show');  // 种类 详情页面
        Route::get('/{unique_code}/edit', 'CategoryController@edit')->name('category.edit');  // 种类 编辑页面
        Route::put('/{unique_code}', 'CategoryController@update')->name('category.update');  // 种类 编辑
        Route::delete('/{unique_code}', 'CategoryController@destroy')->name('category.destroy');  // 种类 删除
    });

    // 类型
    Route::group(['prefix' => 'entireModel'], function () {
        Route::get('/', 'EntireModelController@index')->name('EntireModel.index');  // 类型和子类 列表
        Route::get('/create', 'EntireModelController@create')->name('EntireModel.create');  // 类型和子类 新建页面
        Route::post('/', 'EntireModelController@store')->name('EntireModel.store');  // 类型和子类 新建
        Route::get('/{unique_code}', 'EntireModelController@show')->name('EntireModel.show');  // 类型和子类 详情页面
        Route::get('/{unique_code}/edit', 'EntireModelController@edit')->name('EntireModel.edit');  // 类型和子类 编辑页面
        Route::put('/{unique_code}', 'EntireModelController@update')->name('EntireModel.update');  // 类型和子类 编辑
        Route::delete('/{unique_code}', 'EntireModelController@destroy')->name('EntireModel.destroy');  // 类型和子类 删除
    });

    // 子类
    Route::group(['prefix' => 'subModel'], function () {
        Route::get('/entireModelByCategoryId/{category_id}', 'SubModelController@getEntireModelByCategoryId')->name('entireModelByCategoryId');  // 根据种类编号获取类型列表
        Route::get('/', 'SubModelController@index')->name('SubModel.index');  // 子类 列表
        Route::get('/create', 'SubModelController@create')->name('SubModel.create');  // 子类 新建页面
        Route::post('/', 'SubModelController@store')->name('SubModel.store');  // 子类 新建
        Route::get('/{unique_code}', 'SubModelController@show')->name('SubModel.show');  // 子类 详情页面
        Route::get('/{unique_code}/edit', 'SubModelController@edit')->name('SubModel.edit');  // 子类 编辑页面
        Route::put('/{unique_code}', 'SubModelController@update')->name('SubModel.update');  // 子类 编辑
        Route::delete('/{unique_code}', 'SubModelController@destroy')->name('SubModel.destroy');  // 子类 删除
    });

    // 部件类型
    Route::group(['prefix' => 'partModel'], function () {
        Route::get('/entireModelByCategoryId/{category_id}', 'SubModelController@getEntireModelByCategoryId')->name('entireModelByCategoryId');  // 根据种类编号获取类型列表
        Route::get('/', 'PartModelController@index')->name('PartModel.index');  // 部件类型 列表
        Route::get('/create', 'PartModelController@create')->name('PartModel.create');  // 部件类型 新建页面
        Route::post('/', 'PartModelController@store')->name('PartModel.store');  // 部件类型 新建
        Route::get('/{unique_code}', 'PartModelController@show')->name('PartModel.show');  // 部件类型 详情页面
        Route::get('/{unique_code}/edit', 'PartModelController@edit')->name('PartModel.edit');  // 部件类型 编辑页面
        Route::put('/{unique_code}', 'PartModelController@update')->name('PartModel.update');  // 部件类型 编辑
        Route::delete('/{unique_code}', 'PartModelController@destroy')->name('PartModel.destroy');  // 部件类型 删除
    });
});

// Rpc通信
Route::group(['prefix' => 'rpc'], function () {

    // 测试
    Route::post('test', function () {
        RpcTestFacade::init();
    });

    // 设备状态统计
    Route::post('deviceStatus', function () {
        RpcDeviceStatusFacade::init();
    });

    // 计划与完成情况统计
    Route::post('planAndFinish', function () {
        RpcPlanAndFinishFacade::init();
    });

    // 质量报告统计
    Route::post('quality', function () {
        RpcQualityFacade::init();
    });

    // 超期使用
    Route::post('scraped', function () {
        RpcScrapedFacade::init();
    });

    // 台账统计
    Route::post('maintain', function () {
        RpcMaintainFacade::init();
    });

    // 资产管理统计
    Route::post('property', function () {
        RpcPropertyFacade::init();
    });
});

// 需要登录
Route::group(['middleware' => ['web-check-login', 'web-get-current-menu']], function () {
    // 无需权限验证
    Route::group(['prefix' => ''], function () {
        Route::get('/', 'IndexController@index')->name('index.index');  // 列表
        Route::get('reportData', 'IndexController@getReportData')->name('index.report');  // 首页用报表
        Route::get('monitor', 'IndexController@monitor')->name('index.monitor');  // 台账可视化
        // Route::get('makeMaintain', 'IndexController@makeMaintain')->name('index.makeMaintain');  // 生成台账

        // 监控大屏
        Route::group(['prefix' => 'monitor'], function () {
            // Route::get('/', 'MonitorController@index')->name('web.monitor.index');  // 监控大屏
            Route::get('leftTop', 'IndexController@monitorWithLeftTop')->name('index.monitor.left.top');  // 监控大屏-左上-设备状态统计
            Route::get('leftMiddle', 'IndexController@monitorWithLeftMiddle')->name('index.monitor.left.middle');  // 监控大屏-左中-资产管理
            Route::get('leftBottom', 'IndexController@monitorWithLeftBottom')->name('index.monitor.left.bottom');  // 监控大屏-左下-仓出入所统计
            Route::get('rightTop', 'IndexController@monitorWithRightTop')->name('index.monitor.right.top');  // 监控大屏-右上-周期修
            Route::get('rightMiddle', 'IndexController@monitorWithRightMiddle')->name('index.monitor.right.middle');  // 监控大屏-右中-超期使用
            Route::get('rightBottom', 'IndexController@monitorWithRightBottom')->name('index.monitor.right.bottom');  // 监控大屏-右下-车间/车站列表
            Route::get('material', 'IndexController@monitorWithMaterial')->name('index.monitor.material');  // 监控大屏-设备列表
            Route::get('materialModal', 'IndexController@monitorWithMaterialModal')->name('index.monitor.material.modal');  // 监控大屏-设备列表-模态框
            Route::get('materialModal/{unique_code}', 'IndexController@monitorWithMaterialShowModal')->name('index.monitor.materialShow.modal');  // 监控大屏-设备详情-模态框
        });

        // station wiki
        Route::group(['prefix' => 'stationWiki'], function () {
            Route::get('/{station_unique_code}', 'StationWikiController@show')->name('web.stationWiki.index');  // station wiki show page
            Route::get('/{station_unique_code}/installingStatistics/{type}/', 'StationWikiController@getInstallingStatistics')->name('stationWiki.getInstallingStatistics');  // installing statistics: station, near station, scene workshop, fix workshop
            Route::get('/{station_unique_code}/logs/{excel_type?}', 'StationWikiController@getLogs')->name('stationWiki.getLogs');  // logs: install, breakdown, alarm
            Route::get('/{station_unique_code}/maintainStatistics', 'StationWikiController@getMaintainStatistics')->name('stationWiki.getMaintainStatistics');  // maintain statistics
            Route::get('/{station_unique_code}/maintainStatisticsByCategoryUniqueCode', 'StationWikiController@getMaintainStatisticsByCategoryUniqueCode')->name('stationWiki.getMaintainStatisticsByCategoryUniqueCode');  // maintain statistics by category unique code
            Route::get('/{station_unique_code}/standbyStatistics/{type?}', 'StationWikiController@getStandbyStatistics')->name('stationWiki.getStandbyStatistics');  // standby statistics
            Route::get('/{station_unique_code}/scrapedStatistics', 'StationWikiController@getScrapedStatistics')->name('stationWiki.getScrapedStatistics');  // scraped statistics
            Route::get('/{station_unique_code}/scrapedStatisticsByCategoryUniqueCode', 'StationWikiController@getScrapedStatisticsByCategoryUniqueCode')->name('stationWiki.getScrapedStatisticsByCategoryUniqueCode');  // scraped statistics by category unique code
            Route::get('/{station_unique_code}/entireInstanceAlarmLogs', 'StationWikiController@getEntireInstanceAlarmLogs')->name('stationWiki.getEntireInstanceAlarmLogs');  // entire instance alarms
        });

        // entire instance alarm logs
        Route::group(['prefix' => 'entireInstanceAlarmLog'], function () {
            Route::get('/', 'EntireInstanceAlarmLogController@index')->name('web.entireInstanceAlarmLog.index');  // entire instance alarm logs index page
            Route::get('/create', 'EntireInstanceAlarmLogController@create')->name('web.entireInstanceAlarmLog.create');  // entire instance alarm logs create page
            Route::post('/', 'EntireInstanceAlarmLogController@store')->name('web.entireInstanceAlarmLog.store');  // entire instance alarm logs create
            Route::get('/{id}', 'EntireInstanceAlarmLogController@show')->name('web.entireInstanceAlarmLog.show');  // entire instance alarm logs detail page
            Route::get('/{id}/edit', 'EntireInstanceAlarmLogController@edit')->name('web.entireInstanceAlarmLog.edit');  // entire instance alarm logs edit page
            Route::put('/{id}', 'EntireInstanceAlarmLogController@update')->name('web.entireInstanceAlarmLog.update');  // entire instance alarm logs edit
            Route::delete('/{id}', 'EntireInstanceAlarmLogController@destroy')->name('web.entireInstanceAlarmLog.destroy');  // entire instance alarm logs delete
            Route::put('/{id}/manualRelease', 'EntireInstanceAlarmLogController@putManualRelease')->name('web.entireInstanceAlarmLog.putManualRelease');  // manual release entire instance alarm log
        });

        // warehouse report display board
        Route::group(['prefix' => 'warehouseReportDisplayBoard'], function () {
            Route::get('/{work_area_unique_code}', 'WarehouseReportDisplayBoardController@show')->name('web.warehouseReportDisplayBoard.show');  // warehouse report display board detail page
            Route::get('/{work_area_unique_code}/cycleFixStatistics', 'WarehouseReportDisplayBoardController@getCycleFixStatistics')->name('web.warehouseReportDisplayBoardController.getCycleFixStatistics');  // cycle fix statistics
            Route::get('/{work_area_unique_code}/showCycleFix', 'WarehouseReportDisplayBoardController@showCycleFix')->name('web.warehouseReportDisplayBoard.showCycleFix');  // cycle fix display page
            Route::get('/{work_area_unique_code}/warehouseStatistics/{time_type?}', 'WarehouseReportDisplayBoardController@getWarehouseStatistics')->name('web.warehouseReportDisplayBoard.getWarehouseStatistics');  // warehouse report statistics
            Route::get('/{work_area_unique_code}/showWarehouseReport/{time_type}', 'WarehouseReportDisplayBoardController@showWarehouseReport')->name('web.warehouseReportDisplayBoard.showWarehouseReport');  // warehouse report page
        });

        Route::put('spotCheckFailedFixWorkflow/{fixWorkflowId}', 'Measurement\FixWorkflowController@spotCheckFailed')->name('spotCheckFailedFixWorkflow.put');  // 标记工单抽检不通过
        Route::get('addPartInstance', 'Measurement\FixWorkflowController@getAddPartInstance')->name('addPartInstance.get');  // 新增配件所属页面
        Route::post('addPartInstance', 'Measurement\FixWorkflowController@postAddPartInstance')->name('addPartInstance.post');  // 新增派件所属
        Route::get('changePartInstance', 'Measurement\FixWorkflowController@getChangePartInstance')->name('changePartInstance.get');  // 更换配件所属页面
        Route::post('changePartInstance', 'Measurement\FixWorkflowController@postChangePartInstance')->name('changePartInstance.post');  // 更换派件所属
        Route::get('downloadProcurementPartTemplateExcel', 'Warehouse\Procurement\PartController@downloadProcurementPartTemplateExcel')->name('downloadProcurementPartTemplateExcel.get');  // 下载零件采购单模板
        Route::get('scrapWarehouseProductInstance/{warehouseProductInstanceId}', 'Warehouse\Product\InstanceController@getScrapWarehouseProductInstance')->name('scrapWarehouseProductInstance.get');  // 设备报废
        Route::get('processWarehouseProductPlan/{warehouseProductPlanId}', 'Warehouse\Product\PlanController@getProcessWarehouseProductPlan')->name('processWarehouseProductPlan.get');  // 处理排期
        Route::get('fixToOut/{fixWorkflowId}', 'Warehouse\Report\ProductController@getFixToOut')->name('fixToOut.get');  // 送检出所
        Route::get('fixToOutFinish/{fixWorkflowId}', 'Warehouse\Report\ProductController@getFixToOutFinish')->name('fixToOutFinish.get');  // 送检出所完成
        Route::get('setWarehouseProductInstanceIsUsing/{warehouseProductInstanceId}', 'Warehouse\Product\InstanceController@getWarehouseProductInstance')->name('setWarehouseProductInstanceIsUsing.get');  // 设置设备为主要设备
        Route::get('getWarehouseProductPartByCategoryOpenCode/{categoryOpenCode}', 'Warehouse\Product\PartController@byCategoryOpenCode')->name('getWarehouseProductPartByCategoryId.get');  // 根据设备类型编号获取零件列表
        Route::get('downloadWarehouseReportInOrderTemplateExcel', 'Warehouse\Report\InOrderController@downloadTemplateExcel')->name('downloadWarehouseReportInOrderTemplateExcel.get');  // 下载入库单Excel模板
        Route::get('downloadWarehouseReportOutOrderTemplateExcel', 'Warehouse\Report\OutOrderController@downloadTemplateExcel')->name('downloadWarehouseReportOutOrderTemplateExcel.get');  // 下载出库单Excel模板
        Route::get('downloadConfirmTemplateExcel', 'Warehouse\Report\OutOrderController@downloadConfirmTemplateExcel')->name('downloadConfirmTemplateExcel.get');  // 下载出库安装确认Excel模板
        Route::post('confirmWarehouseReportOutProductInstance', 'Warehouse\Report\OutOrderController@confirmWarehouseReportOutProductInstance')->name('confirmWarehouseReportOutProductInstance.post');  // 上传出库安装确认单
        Route::post('uninstallPartInstance', 'Measurement\FixWorkflowController@postUninstallPartInstance')->name('uninstallPartInstance.post');  // 卸载部件
        Route::post('scrapPartInstance', 'Measurement\FixWorkflowController@postScrapPartInstance')->name('scrapPartInstance.post');  // 报废部件
    });

    // 需要权限验证
    Route::group(['middleware' => 'web-check-permission'], function () {

        // 种类型
        Route::group(['prefix' => 'kind'], function () {
            Route::get('byIdentityCodes', 'KindController@getByIdentityCodes')->name('kind.getByIdentityCodes');  // 根据唯一编号组获取种类型名称
            Route::put('category/{unique_code}/nickname', 'KindController@putCategoryNickname')->name('kind.putCategoryNickname');  // 编辑种类别名
            Route::put('entireModel/{unique_code}/nickname', 'KindController@putEntireModelNickname')->name('kind.putEntireModelNickname');  // 编辑类型别名
            Route::put('subModel/{unique_code}/nickname', 'KindController@putSubModelNickname')->name('kind.putSubModelNickname');  // 编辑型号别名
            Route::put('nicknames', 'KindController@putNicknames')->name('kinds.putNicknames');  // 批量修改种类型别名
        });

        // 器材种类型管理
        Route::group(['prefix' => 'kindQ'], function () {
            Route::get('/search', 'KindQController@getSearch')->name('web.KindQ.getSearch');  // 搜索种类型
            Route::get('', 'KindQController@index')->name('web.KindQ.index');
            Route::get('/category/{unique_code}', 'KindQController@getCategory')->name('web.KindQ.getCategory');
            Route::post('/category', 'KindQController@postCategory')->name('web.KindQ.postCategory');
            Route::put('/category', 'KindQController@putCategory')->name('web.KindQ.putCategory');
            Route::get('/entireModel/{unique_code}', 'KindQController@getEntireModel')->name('web.KindQ.getEntireModel');
            Route::post('/entireModel', 'KindQController@postEntireModel')->name('web.KindQ.postEntireModel');
            Route::put('/entireModel', 'KindQController@putEntireModel')->name('web.KindQ.putEntireModel');
            Route::get('/subModel/{unique_code}', 'KindQController@getSubModel')->name('web.KindQ.getSubModel');
            Route::post('/subModel', 'KindQController@postSubModel')->name('web.KindQ.postSubModel');
            Route::put('/subModel', 'KindQController@putSubModel')->name('web.KindQ.putSubModel');
        });

        // 设备种类型管理
        Route::group(['prefix' => 'kindS'], function () {
            Route::get('/search', 'KindSController@getSearch')->name('web.KindS.getSearch');  // 搜索种类型
            Route::get('', 'KindSController@index')->name('web.KindS.index');
            Route::get('/category/{unique_code}', 'KindSController@getCategory')->name('web.KindS.getCategory');
            Route::post('/category', 'KindSController@postCategory')->name('web.KindS.postCategory');
            Route::put('/category', 'KindSController@putCategory')->name('web.KindS.putCategory');
            Route::get('/entireModel/{unique_code}', 'KindSController@getEntireModel')->name('web.KindS.getEntireModel');
            Route::post('/entireModel', 'KindSController@postEntireModel')->name('web.KindS.postEntireModel');
            Route::put('/entireModel', 'KindSController@putEntireModel')->name('web.KindS.putEntireModel');
            Route::get('/partModel/{unique_code}', 'KindSController@getPartModel')->name('web.KindS.getPartModel');
            Route::post('/partModel', 'KindSController@postPartModel')->name('web.KindS.postPartModel');
            Route::put('/partModel', 'KindSController@putPartModel')->name('web.KindS.putPartModel');
        });

        // 仓库管理
        Route::group(['prefix' => 'storehouse', 'namespace' => 'Storehouse'], function () {

            // 仓库
            Route::prefix("post")
                ->name("web.storehouse.post:")
                ->group(function () {
                    Route::get("/", "PostController@index")->name("index");
                });

            // 位置管理
            Route::group(['prefix' => 'location'], function () {
                // 仓
                Route::group(['prefix' => 'storehouse'], function () {
                    Route::get('/', 'LocationController@storehouseWithIndex')->name('storehouse.location.storehouse.index');  // 位置管理获取仓-列表
                    Route::post('/', 'LocationController@storehouseWithStore')->name('storehouse.location.storehouse.store');  // 位置管理仓-添加
                    Route::put('/{id}', 'LocationController@storehouseWithUpdate')->name('storehouse.location.storehouse.update');  // 位置管理仓-编辑
                    Route::delete('/{id}', 'LocationController@storehouseWithDestroy')->name('storehouse.location.storehouse.destroy');  // 位置管理仓-删除
                });
                // 区
                Route::group(['prefix' => 'area'], function () {
                    Route::get('/', 'LocationController@areaWithIndex')->name('storehouse.location.area.index');  // 位置管理获取区-列表
                    Route::post('/', 'LocationController@areaWithStore')->name('storehouse.location.area.store');  // 位置管理区-添加
                    Route::put('/{id}', 'LocationController@areaWithUpdate')->name('storehouse.location.area.update');  // 位置管理区-编辑
                    Route::delete('/{id}', 'LocationController@areaWithDestroy')->name('storehouse.location.area.destroy');  // 位置管理区-删除
                });
                // 排
                Route::group(['prefix' => 'platoon'], function () {
                    Route::get('/', 'LocationController@platoonWithIndex')->name('storehouse.location.platoon.index');  // 位置管理获取排json
                    Route::post('/', 'LocationController@platoonWithStore')->name('storehouse.location.platoon.store');  // 位置管理排添加
                    Route::put('/{id}', 'LocationController@platoonWithUpdate')->name('storehouse.location.platoon.update');  // 位置管理排编辑
                    Route::delete('/{id}', 'LocationController@platoonWithDestroy')->name('storehouse.location.platoon.destroy');  // 位置管理排删除
                });
                // 架
                Route::group(['prefix' => 'shelf'], function () {
                    Route::get('/{shelf_unique_code}/canvas', 'LocationController@getCanvas')->name('location.shelf.canvas');  #  show canvas
                    Route::post('uploadImage', 'LocationController@uploadImageWithShelf')->name('location.shelf.uploadImage');  // 位置管理架-上传图片
                    Route::get('/', 'LocationController@shelfWithIndex')->name('storehouse.location.shelf.index');  // 位置管理获取架json
                    Route::post('/', 'LocationController@shelfWithStore')->name('storehouse.location.shelf.store');  // 位置管理架添加
                    Route::put('/{id}', 'LocationController@shelfWithUpdate')->name('storehouse.location.shelf.update');  // 位置管理架编辑
                    Route::delete('/{id}', 'LocationController@shelfWithDestroy')->name('storehouse.location.shelf.destroy');  // 位置管理架删除
                });
                // 层
                Route::group(['prefix' => 'tier'], function () {
                    Route::get('/', 'LocationController@tierWithIndex')->name('storehouse.location.tier.index');  // 位置管理获取层json
                    Route::post('/', 'LocationController@tierWithStore')->name('storehouse.location.tier.store');  // 位置管理层添加
                    Route::put('/{id}', 'LocationController@tierWithUpdate')->name('storehouse.location.tier.update');  // 位置管理层编辑
                    Route::delete('/{id}', 'LocationController@tierWithDestroy')->name('storehouse.location.tier.destroy');  // 位置管理层删除
                });
                // 位
                Route::group(['prefix' => 'position'], function () {
                    Route::get('/', 'LocationController@positionWithIndex')->name('storehouse.location.position.index'); // 位置管理获取位json
                    Route::post('/', 'LocationController@positionWithStore')->name('storehouse.location.position.store'); // 位置管理位保存
                    Route::put('/{id}', 'LocationController@positionWithUpdate')->name('storehouse.location.position.update'); // 位置管理位编辑
                    Route::delete('/{id}', 'LocationController@positionWithDestroy')->name('storehouse.location.position.destroy'); // 位置管理位删除
                });

                Route::get('/upload', 'LocationController@getUpload')->name('storehouse.getUpload');  // 下载批量上传模板
                Route::post('/upload', 'LocationController@postUpload')->name('storehouse.postUpload');  // 批量上传
                Route::get('/', 'LocationController@index')->name('storehouse.location.index');  // 位置管理列表
                Route::get('/create', 'LocationController@create')->name('storehouse.location.create');  // 位置管理新建页面
                Route::post('/', 'LocationController@store')->name('storehouse.location.store');  // 位置管理新建
                Route::get('/{id}', 'LocationController@show')->name('storehouse.location.show');  // 位置管理详情页面
                Route::get('/{id}/edit', 'LocationController@edit')->name('storehouse.location.edit');  // 位置管理编辑页面
                Route::put('/{id}', 'LocationController@update')->name('storehouse.location.update');  // 位置管理编辑
                Route::delete('/{id}', 'LocationController@destroy')->name('storehouse.location.destroy');  // 位置管理删除
                Route::get('getImg/{identityCode}', 'LocationController@getImg')->name('storehouse.getImg.get');  // 位置管理获取图片
                Route::get('img2/{location_unique_code}', 'LocationController@getImg2')->name('web.storehouse.location.getImg2');  // 获取仓库定位图2
            });

            // 入库-报废-报损
            Route::group(['prefix' => 'index'], function () {

                // 临时仓库设备
                Route::group(['prefix' => 'tmpMaterial'], function () {
                    Route::post('store', 'IndexController@tmpWarehouseMaterialStore')->name('storehouse.index.tmp.store');  // 仓库管理-设备-临时-新建
                    Route::delete('destroyWithCode', 'IndexController@tmpWarehouseMaterialWithCodeDestroy')->name('storehouse.index.tmp.destroy');  // 仓库管理-设备-临时-删除-编码
                });
                // 入库
                Route::group(['prefix' => 'in'], function () {
                    Route::get('order', 'IndexController@inWithOrder')->name('storehouse.index.in.order');  // 仓库管理-入库单查询
                    Route::get('confirm', 'IndexController@inWithConfirm')->name('storehouse.index.in.confirm');  // 仓库管理-入库-确认入库
                    Route::get('/', 'IndexController@inWithIndex')->name('storehouse.index.in.index');  // 仓库管理-入库-列表
                    Route::post('/', 'IndexController@inWithStore')->name('storehouse.index.in.store');  // 仓库管理-入库-新建
                    Route::delete('/{id}', 'IndexController@inWithDestroy')->name('storehouse.index.in.destroy');  // 仓库管理-入库-删除
                });
                // 报废
                Route::group(['prefix' => 'scrap'], function () {
                    Route::get('/', 'IndexController@getScrap')->name('storehouse.index.getScrap');  // 仓库管理 报废扫码页面
                    Route::delete('/', 'IndexController@destroy')->name('storehouse.index.destroy');  // 仓库管理 报废
                    Route::get('/entireInstance', 'IndexController@getEntireInstance')->name('storehouse.index.getEntireInstance');  // 获取设备器材信息
                    Route::get('order', 'IndexController@scrapWithOrder')->name('storehouse.index.scrap.order');  // 仓库管理-报废单查询
                    Route::post('confirm', 'IndexController@scrapWithConfirm')->name('storehouse.index.scrap.confirm');  // 仓库管理-报废-确认报废
                    Route::get('instance', 'IndexController@scrapWithInstance')->name('storehouse.index.scrap.instance');  // 仓库管理-报废-设备列表
                });

                Route::get('material', 'IndexController@material')->name('storehouse.index.material');  // 仓库管理-仓储设备
                Route::get('/{id}', 'IndexController@show')->name('storehouse.index.show');  // 仓库管理-详情
            });

            // 盘点
            Route::group(['prefix' => 'takeStock'], function () {
                Route::get('startTakeStock', 'TakeStockController@startTakeStock')->name('storehouse.takeStock.startTakeStock');  // 盘点管理开始盘点
                Route::get('takeStockReady', 'TakeStockController@takeStockReady')->name('storehouse.takeStock.takeStockReady');  // 盘点管理准备盘点
                Route::get('takeStockMaterialStore/{realStockIdentityCode}', 'TakeStockController@takeStockMaterialStore')->name('storehouse.takeStock.takeStockMaterialStore');  // 盘点管理盘点扫码保存
                Route::delete('takeStockMaterialDestory/{id}', 'TakeStockController@takeStockMaterialDestory')->name('storehouse.takeStock.takeStockMaterialDestory');  // 盘点管理盘点扫码移除
                Route::get('takeStock/{takeStockUniqueCode}', 'TakeStockController@takeStock')->name('storehouse.takeStock.takeStock');  // 盘点管理盘点
                Route::get('showWithSubModel/{takeStockUniqueCode}/{categoryUniqueCode}', 'TakeStockController@showWithSubModel')->name('storehouse.takeStock.showWithSubModel');  // 根据种类获取型号差异列表
                Route::get('showWithMaterial/{takeStockUniqueCode}/{subModelUniqueCode}', 'TakeStockController@showWithMaterial')->name('storehouse.takeStock.showWithMaterial');  // 根据型号获取差异设备信息

                Route::get('/', 'TakeStockController@index')->name('storehouse.takeStock.index');  // 盘点管理列表
                Route::get('/{takeStockUniqueCode}', 'TakeStockController@show')->name('storehouse.takeStock.show');  // 盘点管理详情页面
            });

            // 送修
            Route::group(['prefix' => 'sendRepair'], function () {
                Route::get('downloadSendRepairFile/{id}/{file_type}', 'SendRepairController@downloadSendRepairFile')->name('sendRepair.download');  // 下载送修文件
                Route::post('uploadRepair', 'SendRepairController@postUploadRepair')->name('storehouse.sendRepair.postUploadRepair');  // 送修 上传送修报告
                Route::get('uploadRepair', 'SendRepairController@getUploadRepair')->name('storehouse.sendRepair.getUploadRepair');  // 送修 上传送修报告页面
                Route::get('instanceWithSendRepair/{sendRepairUniqueCode}', 'SendRepairController@instanceWithSendRepair')->name('storehouse.sendRepair.instance'); // 送修单设备列表
                Route::put('updateSendRepairWithInstanceFaultStatus', 'SendRepairController@updateSendRepairWithInstanceFaultStatus')->name('sendRepair.instance.faultStatus.update');  // 送修 更改送修设备故障状态
                Route::get('sendRepairWithCheck', 'SendRepairController@sendRepairWithCheck')->name('storehouse.sendRepair.check');  // 送修 送修设备验收页面
                Route::put('sendRepairWithCheck/{materialUniqueCode}', 'SendRepairController@sendRepairWithDoCheck')->name('storehouse.sendRepair.do.check');  // 送修 送修设备验收
                Route::get('instance', 'SendRepairController@sendRepairWithInstance')->name('storehouse.sendRepair.instance');  // 送修设备列表
                Route::group(['prefix' => 'tmpMaterial'], function () {
                    Route::post('/', 'SendRepairController@tmpMaterialWithStore')->name('storehouse.sendRepair.tmpMaterial.store');  // 送修-添加临时设备
                    Route::put('/{identityCode}', 'SendRepairController@tmpMaterialWithUpdate')->name('storehouse.sendRepair.tmpMaterial.update');  // 送修-编辑临时设备
                    Route::delete('/{identityCode}', 'SendRepairController@tmpMaterialWithDestroy')->name('storehouse.sendRepair.tmpMaterial.destroy');  // 送修-删除临时设备
                });

                Route::group(['prefix' => 'tmpBreakdownLog'], function () {
                    Route::get('/create', 'SendRepairController@tmpBreakdownLogWithCreate')->name('storehouse.sendRepair.tmpBreakdownLog.create');  // 送修-故障模态框
                    Route::post('/', 'SendRepairController@tmpBreakdownLogWithStore')->name('storehouse.sendRepair.tmpBreakdownLog.store');  // 送修-添加临时故障类型
                });

                Route::get('/', 'SendRepairController@index')->name('storehouse.sendRepair.index');  // 送修列表
                Route::get('/create', 'SendRepairController@create')->name('storehouse.sendRepair.create');  // 送修新建页面
                Route::post('/', 'SendRepairController@store')->name('storehouse.sendRepair.store');  // 送修新建
                Route::get('/{uniqueCode}', 'SendRepairController@show')->name('storehouse.sendRepair.show');  // 送修详情页面
                Route::get('/{id}/edit', 'SendRepairController@edit')->name('storehouse.sendRepair.edit');  // 送修编辑页面
                Route::put('/{id}', 'SendRepairController@update')->name('storehouse.sendRepair.update');  // 送修编辑
                Route::delete('/{id}', 'SendRepairController@destroy')->name('storehouse.sendRepair.destroy');  // 送修删除
            });
        });

        // 搜索
        Route::group(['prefix' => 'query'], function () {
            Route::put('batchUpdate', 'QueryController@putBatchUpdate')->name('query.putBatchUpdate');  // 批量修改
            Route::get('statistics', 'QueryController@getStatistics')->name('query.getStatistics');  // 搜索统计
            Route::get('entireModels/{categoryUniqueCode}', 'QueryController@entireModels')->name('query.entireModels');  // 根据种类获取类型
            Route::get('subModels/{entireModels}', 'QueryController@subModels')->name('query.subModels');  // 根据类型获取型号
            Route::get('stations', 'QueryController@stations')->name('query.stations');  // 根据现场车间获取车站
            Route::get('accounts/{workArea}', 'QueryController@accounts')->name('query.accounts');  // 根据工区获取人员
            // Route::get('/', 'QueryController@index')->name('query.index');  // 搜索列表
            Route::get('', 'QueryController@index')->name('query.index'); // 搜索2
            Route::get('/create', 'QueryController@create')->name('query.create');  // 搜索新建页面
            Route::post('/', 'QueryController@store')->name('query.store');  // 搜索新建
            Route::get('/{id}', 'QueryController@show')->name('query.show');  // 搜索详情页面
            Route::get('/{id}/edit', 'QueryController@edit')->name('query.edit');  // 搜索编辑页面
            Route::put('/{id}', 'QueryController@update')->name('query.update');  // 搜索编辑
            Route::delete('/{id}', 'QueryController@destroy')->name('query.destroy');  // 搜索删除
        });

        // 机构
        Route::group(['prefix' => 'organization'], function () {
            Route::get('/', 'OrganizationController@index')->name('organization.index');  // 机构列表
            Route::get('/create', 'OrganizationController@create')->name('organization.create');  // 机构新建页面
            Route::post('/', 'OrganizationController@store')->name('organization.store');  // 机构新建
            Route::get('/{id}', 'OrganizationController@show')->name('organization.show');  // 机构详情页面
            Route::get('/{id}/edit', 'OrganizationController@edit')->name('organization.edit');  // 机构编辑页面
            Route::put('/{id}', 'OrganizationController@update')->name('organization.update');  // 机构编辑
            Route::delete('/{id}', 'OrganizationController@destroy')->name('organization.destroy');  // 机构删除
        });

        // 种类
        Route::group(['prefix' => 'category'], function () {
            Route::get('subModel/{entireModelUniqueCode}', 'CategoryController@getSubModel')->name('category.subModel');  // 获得型号
            Route::get('entireModel/{categoryUniqueCode}', 'CategoryController@getEntireModel')->name('category.entireModel');  // 获得类型
            Route::get('getSubModelWithCategory', 'CategoryController@getSubModelWithCategory')->name('category.getSubModelWithCategory');  // 获得型号
            Route::get('/', 'CategoryController@index')->name('category.index');  // 种类列表
            Route::get('create', 'CategoryController@create')->name('category.create');  // 种类新建页面
            Route::post('/', 'CategoryController@store')->name('category.store');  // 种类新建
            Route::get('/{id}', 'CategoryController@show')->name('categoryItem.show');  // 种类详情页面
            Route::get('/{id}/edit', 'CategoryController@edit')->name('category.edit');  // 种类编辑页面
            Route::put('/{id}', 'CategoryController@update')->name('category.update');  // 种类编辑
            Route::delete('/{id}', 'CategoryController@destroy')->name('category.destroy');  // 种类删除
        });

        // 供应商
        Route::group(['prefix' => 'factory'], function () {
            Route::get('/backupFromSPAS', 'FactoryController@getBackupFromSPAS')->name('Factory.backupFromSPAS');  // 种类 从数据中台备份
            Route::get('batch', 'FactoryController@getBatch')->name('factory.batch.get');  // 批量导入供应商页面
            Route::post('batch', 'FactoryController@postBatch')->name('factory.batch.post');  // 批量导入供应商
            Route::get('/', 'FactoryController@index')->name('factory.index');  // 供应商列表
            Route::get('create', 'FactoryController@create')->name('factory.create');  // 供应商新建页面
            Route::post('/', 'FactoryController@store')->name('factory.store');  // 供应商新建
            Route::get('/{id}', 'FactoryController@show')->name('factory.show');  // 供应商详情页面
            Route::get('/{id}/edit', 'FactoryController@edit')->name('factory.edit');  // 供应商编辑页面
            Route::put('/{id}', 'FactoryController@update')->name('factory.update');  // 供应商编辑
            Route::delete('/{id}', 'FactoryController@destroy')->name('factory.destroy');  // 供应商删除
        });

        // 状态
        Route::group(['prefix' => 'status'], function () {
            Route::get('/', 'StatusController@index')->name('status.index');  // 状态列表
            Route::get('/create', 'StatusController@create')->name('status.create');  // 状态新建页面
            Route::post('/', 'StatusController@store')->name('status.store');  // 状态新建
            Route::get('/{id}', 'StatusController@show')->name('status.show');  // 状态详情页面
            Route::get('/{id}/edit', 'StatusController@edit')->name('status.edit');  // 状态编辑页面
            Route::put('/{id}', 'StatusController@update')->name('status.update');  // 状态编辑
            Route::delete('/{id}', 'StatusController@destroy')->name('status.destroy');  // 状态删除
        });

        // 权限
        Route::group(['prefix' => 'rbac', 'namespace' => 'Rbac'], function () {
            // 菜单
            Route::group(['prefix' => 'menu'], function () {
                Route::get('/', 'MenuController@index')->name('menu.index');  // 菜单列表
                Route::get('/create', 'MenuController@create')->name('menu.create');  // 菜单新建页面
                Route::post('/', 'MenuController@store')->name('menu.store');  // 菜单新建
                Route::get('/{id}', 'MenuController@show')->name('menu.show');  // 菜单详情页面
                Route::get('/{id}/edit', 'MenuController@edit')->name('menu.edit');  // 菜单编辑页面
                Route::put('/{id}', 'MenuController@update')->name('menu.update');  // 菜单编辑
                Route::delete('/{id}', 'MenuController@destroy')->name('menu.destroy');  // 菜单删除
            });

            // 权限
            Route::group(['prefix' => 'permission'], function () {
                Route::get('/', 'PermissionController@index')->name('permission.index');  // 权限列表
                Route::get('/create', 'PermissionController@create')->name('permission.create');  // 权限新建页面
                Route::post('/', 'PermissionController@store')->name('permission.store');  // 权限新建
                Route::get('/{id}', 'PermissionController@show')->name('permission.show');  // 权限详情页面
                Route::get('/{id}/edit', 'PermissionController@edit')->name('permission.edit');  // 权限编辑页面
                Route::put('/{id}', 'PermissionController@update')->name('permission.update');  // 权限编辑
                Route::delete('/{id}', 'PermissionController@destroy')->name('permission.destroy');  // 权限删除
            });

            // 权限分组
            Route::group(['prefix' => 'permissionGroup'], function () {
                Route::get('/', 'PermissionGroupController@index')->name('permissionGroup.index');  // 权限分组列表
                Route::get('/create', 'PermissionGroupController@create')->name('permissionGroup.create');  // 权限分组新建页面
                Route::post('/', 'PermissionGroupController@store')->name('permissionGroup.store');  // 权限分组新建
                Route::get('/{id}', 'PermissionGroupController@show')->name('permissionGroup.show');  // 权限分组详情页面
                Route::get('/{id}/edit', 'PermissionGroupController@edit')->name('permissionGroup.edit');  // 权限分组编辑页面
                Route::put('/{id}', 'PermissionGroupController@update')->name('permissionGroup.update');  // 权限分组编辑
                Route::delete('/{id}', 'PermissionGroupController@destroy')->name('permissionGroup.destroy');  // 权限分组删除
            });

            // 角色
            Route::group(['prefix' => 'role'], function () {
                Route::get('/', 'RoleController@index')->name('role.index');  // 角色列表
                Route::get('/create', 'RoleController@create')->name('role.create');  // 角色新建页面
                Route::post('/', 'RoleController@store')->name('role.store');  // 角色新建
                Route::get('/{id}', 'RoleController@show')->name('role.show');  // 角色详情页面
                Route::get('/{id}/edit', 'RoleController@edit')->name('role.edit');  // 角色编辑页面
                Route::put('/{id}', 'RoleController@update')->name('role.update');  // 角色编辑
                Route::delete('/{id}', 'RoleController@destroy')->name('role.destroy');  // 角色删除
            });
            Route::post('roleBindPermissions/{roleId}', 'RoleController@bindPermissions')->name('roleBindPermissions.post');  // 角色绑定权限
            Route::post('menuBindRoles/{menuId}', 'MenuController@bindRoles')->name('menuBindRoles.post');  // 菜单绑定角色

            // 角色 → 用户
            Route::group(['prefix' => 'roleAccount'], function () {
                Route::get('/', 'PivotRoleAccountController@index')->name('roleAccount.index');  // 角色 → 用户列表
                Route::get('/create', 'PivotRoleAccountController@create')->name('roleAccount.create');  // 角色 → 用户新建页面
                Route::post('/', 'PivotRoleAccountController@store')->name('roleAccount.store');  // 角色 → 用户新建
                Route::get('/{id}', 'PivotRoleAccountController@show')->name('roleAccount.show');  // 角色 → 用户详情页面
                Route::get('/{id}/edit', 'PivotRoleAccountController@edit')->name('roleAccount.edit');  // 角色 → 用户编辑页面
                Route::put('/{id}', 'PivotRoleAccountController@update')->name('roleAccount.update');  // 角色 → 用户编辑
                Route::delete('/{id}', 'PivotRoleAccountController@destroy')->name('roleAccount.destroy');  // 角色 → 用户删除
            });

            // 角色 → 权限
            Route::group(['prefix' => 'rolePermission'], function () {
                Route::get('/', 'PivotRolePermissionController@index')->name('rolePermission.index');  // 角色 → 权限列表
                Route::get('/create', 'PivotRolePermissionController@create')->name('rolePermission.create');  // 角色 → 权限新建页面
                Route::post('/', 'PivotRolePermissionController@store')->name('rolePermission.store');  // 角色 → 权限新建
                Route::get('/{id}', 'PivotRolePermissionController@show')->name('rolePermission.show');  // 角色 → 权限详情页面
                Route::get('/{id}/edit', 'PivotRolePermissionController@edit')->name('rolePermission.edit');  // 角色 → 权限编辑页面
                Route::put('/{id}', 'PivotRolePermissionController@update')->name('rolePermission.update');  // 角色 → 权限编辑
                Route::delete('/{id}', 'PivotRolePermissionController@destroy')->name('rolePermission.destroy');  // 角色 → 权限删除
            });

            // 角色 → 菜单
            Route::group(['prefix' => 'roleMenu'], function () {
                Route::get('/', 'PivotRoleMenuController@index')->name('roleMenu.index');  // 角色 → 菜单列表
                Route::get('/create', 'PivotRoleMenuController@create')->name('roleMenu.create');  // 角色 → 菜单新建页面
                Route::post('/', 'PivotRoleMenuController@store')->name('roleMenu.store');  // 角色 → 菜单新建
                Route::get('/{id}', 'PivotRoleMenuController@show')->name('roleMenu.show');  // 角色 → 菜单详情页面
                Route::get('/{id}/edit', 'PivotRoleMenuController@edit')->name('roleMenu.edit');  // 角色 → 菜单编辑页面
                Route::put('/{id}', 'PivotRoleMenuController@update')->name('roleMenu.update');  // 角色 → 菜单编辑
                Route::delete('/{id}', 'PivotRoleMenuController@destroy')->name('roleMenu.destroy');  // 角色 → 菜单删除
            });
        });

        // 车间/车站
        Route::group(['prefix' => 'maintain'], function () {
            Route::get('station', 'MaintainController@getStation')->name('maintain.station.get');  // 获取车站
            Route::get('getWorkshop/{lineId}', 'MaintainController@getWorkshop')->name('maintain.getWorkshop.get');  // 获取车间
            Route::get('/backupFromSPAS', 'MaintainController@getBackupFromSPAS')->name('maintain.backupFromSPAS');  // 种类 从数据中台备份
            Route::post('/DownloadExcel', 'MaintainController@DownloadExcel')->name('DownloadExcel.post');  // 车间/车站 下载Excel
            Route::post('/UploadExcel', 'MaintainController@UploadExcel')->name('UploadExcel.post');  // 车间/车站 上传Excel导入设备验收日期
            Route::post('/distance', 'MaintainController@distance')->name('distance.post');  // 计算全部车间车站距离
            Route::get('/report', 'MaintainController@report')->name('maintain.report');  // 统计列表页
            Route::get('/', 'MaintainController@index')->name('maintain.index');  // 台账列表
            Route::get('create', 'MaintainController@create')->name('maintain.create');  // 台账新建页面
            Route::post('/', 'MaintainController@store')->name('maintain.store');  // 台账新建
            Route::get('/{identityCode}', 'MaintainController@show')->name('maintain.show');  // 台账详情页面
            Route::get('/{identityCode}/edit', 'MaintainController@edit')->name('maintain.edit');  // 台账编辑页面
            Route::put('/{identityCode}', 'MaintainController@update')->name('maintain.update');  // 台账编辑
            Route::delete('/{identityCode}', 'MaintainController@destroy')->name('maintain.destroy');  // 台账删除
        });

        // 车站电子图纸
        Route::group(['prefix' => 'stationElectricImage'], function () {
            Route::get('', 'StationElectricImageController@index')->name('stationElectricImage.index');  // 车站电子图纸 车站列表
            Route::get('/{maintain_station_unique_code}', 'StationElectricImageController@show')->name('stationElectricImage.show');  // 车站电子图纸 详情
            Route::post('{maintain_station_unique_code}', 'StationElectricImageController@store')->name('stationElectricImage.store');  // 车站电子图纸 上传图片
            Route::delete('{id}', 'StationElectricImageController@destroy')->name('stationElectricImage.destroy');  // 车站电子图纸 删除图片
        });

        // 检修车间
        Route::group(['prefix' => 'repairBase', 'namespace' => 'RepairBase'], function () {
            // 新购
            Route::group(['prefix' => 'buyInOrder'], function () {
                Route::get('entireInstances', 'BuyInOrderController@getEntireInstances')->name('repairBase.buyInOrder.entireInstances.get');  // 获取入所计划设备列表
                Route::post('entireInstances', 'BuyInOrderController@postEntireInstances')->name('repairBase.buyInOrder.entireInstances.post');  // 将设备添加到入所计划
                Route::delete('entireInstances', 'BuyInOrderController@deleteEntireInstances')->name('repairBase.buyInOrder.entireInstances.delete'); // 将设备从入所计划中删除
                Route::post('scanEntireInstances', 'BuyInOrderController@postScanEntireInstances')->name('repairBase.buyInOrder.scanEntireInstance.post');  // 添加已扫码标记
                Route::delete('scanEntireInstances', 'BuyInOrderController@deleteScanEntireInstances')->name('repairBase.buyInOrder.scanEntireInstance.delete');  // 去除已扫码标记
                Route::get('printLabel/{serial_number}', 'BuyInOrderController@getPrintLabel')->name('repairBase.buyInOrder.printLabel');  // 打印标签
                Route::put('done/{serial_number}', 'BuyInOrderController@putDone')->name('repairBase.buyInOrder.postDone');  // 标记计划完成
                Route::post('bindEntireInstance', 'BuyInOrderController@postBindEntireInstance')->name('repairBase.buyInOrder.postBindEntireInstance');  // 绑定新设备到老设备
                Route::delete('bindEntireInstance', 'BuyInOrderController@deleteBindEntireInstance')->name('repairBase.buyInOrder.deleteBindEntireInstance');  // 删除绑定新设备到老设备
                Route::delete('bindEntireInstances', 'BuyInOrderController@deleteBindEntireInstances')->name('repairBase.buyInOrder.deleteBindEntireInstances');  // 删除所有绑定
                Route::post('autoBindEntireInstance', 'BuyInOrderController@postAutoBindEntireInstance')->name('repairBase.buyInOrder.postAutoBindEntireInstance');  // 自动分配新设备到老设备
                Route::post('autoBindEntireInstances', 'BuyInOrderController@postAutoBindEntireInstances')->name('repairBase.buyInOrder.postAutoBindEntireInstances');  // 全选自动分配新设备到老设备
                Route::get('mission', 'BuyInOrderController@getMission')->name('repairBase.buyInOrder.getMission');  // 新购任务分配页面
                Route::post('mission', 'BuyInOrderController@postMission')->name('repairBase.buyInOrder.postMission');  // 保存新购任务分配
                Route::post('warehouse', 'BuyInOrderController@postWarehouse')->name('repairBase.buyInOrder.postWarehouse');  // 出入所
                Route::get('/', 'BuyInOrderController@index')->name('repairBase.buyInOrder.index');  // 新购 列表
                Route::get('create', 'BuyInOrderController@create')->name('repairBase.buyInOrder.create');  // 新购 新建页面
                Route::post('/', 'BuyInOrderController@store')->name('repairBase.buyInOrder.store');  // 新购 新建
                Route::get('{serial_number}', 'BuyInOrderController@show')->name('repairBase.buyInOrder.show');  // 新购 详情页面
                Route::get('{serial_number}/edit', 'BuyInOrderController@edit')->name('repairBase.buyInOrder.edit');  // 新购 编辑页面
                Route::put('{serial_number}', 'BuyInOrderController@update')->name('repairBase.buyInOrder.update');  // 新购 编辑
                Route::delete('{serial_number}', 'BuyInOrderController@destroy')->name('repairBase.buyInOrder.destroy');  // 新购 删除
            });

            // 更换型号
            Route::group(['prefix' => 'exchangeModelOrder'], function () {
                Route::post('entireInstances', 'ExchangeModelOrderController@postEntireInstances')->name('repairBase.exchageModelOrder.entireInstances.post');  // 按照计划单已经写入的模型，导入设备列表
                Route::post('models', 'ExchangeModelOrderController@postModels')->name('repairBase.exchangeModelOrder.models.post');  // 将设备添加到入所计划
                Route::delete('models', 'ExchangeModelOrderController@deleteModels')->name('repairBase.exchangeModelOrder.models.delete'); // 将设备从入所计划中删除
                Route::post('scanEntireInstances', 'ExchangeModelOrderController@postScanEntireInstances')->name('repairBase.exchangeModelOrder.scanEntireInstance.post');  // 添加已扫码标记
                Route::delete('scanEntireInstances', 'ExchangeModelOrderController@deleteScanEntireInstances')->name('repairBase.exchangeModelOrder.scanEntireInstance.delete');  // 去除已扫码标记
                Route::get('printLabel/{serial_number}', 'ExchangeModelOrderController@getPrintLabel')->name('repairBase.exchangeModelOrder.printLabel');  // 打印标签
                Route::put('done/{serial_number}', 'ExchangeModelOrderController@putDone')->name('repairBase.exchangeModelOrder.postDone');  // 标记计划完成
                Route::post('bindEntireInstance', 'ExchangeModelOrderController@postBindEntireInstance')->name('repairBase.exchangeModelOrder.postBindEntireInstance');  // 绑定新设备到老设备
                Route::delete('bindEntireInstance', 'ExchangeModelOrderController@deleteBindEntireInstance')->name('repairBase.exchangeModelOrder.deleteBindEntireInstance');  // 删除绑定新设备到老设备
                Route::delete('bindEntireInstances', 'ExchangeModelOrderController@deleteBindEntireInstances')->name('repairBase.exchangeModelOrder.deleteBindEntireInstances');  // 删除所有绑定
                Route::post('autoBindEntireInstance', 'ExchangeModelOrderController@postAutoBindEntireInstance')->name('repairBase.exchangeModelOrder.postAutoBindEntireInstance');  // 自动分配新设备到老设备
                Route::post('autoBindEntireInstances', 'ExchangeModelOrderController@postAutoBindEntireInstances')->name('repairBase.exchangeModelOrder.postAutoBindEntireInstances');  // 全选自动分配新设备到老设备
                Route::get('mission', 'ExchangeModelOrderController@getMission')->name('repairBase.exchangeModelOrder.getMission');  // 更换型号任务分配页面
                Route::post('mission', 'ExchangeModelOrderController@postMission')->name('repairBase.exchangeModelOrder.postMission');  // 保存更换型号任务分配
                Route::post('warehouse', 'ExchangeModelOrderController@postWarehouse')->name('repairBase.exchangeModelOrder.postWarehouse');  // 出入所
                Route::get('/', 'ExchangeModelOrderController@index')->name('repairBase.exchangeModelOrder.index');  // 更换型号 列表
                Route::get('create/{serial_number}', 'ExchangeModelOrderController@create')->name('repairBase.exchangeModelOrder.create');  // 更换型号 新建页面
                Route::post('/', 'ExchangeModelOrderController@store')->name('repairBase.exchangeModelOrder.store');  // 更换型号 新建
                Route::get('{serial_number}', 'ExchangeModelOrderController@show')->name('repairBase.exchangeModelOrder.show');  // 更换型号 详情页面
                Route::get('{serial_number}/edit', 'ExchangeModelOrderController@edit')->name('repairBase.exchangeModelOrder.edit');  // 更换型号 编辑页面
                Route::put('{serial_number}', 'ExchangeModelOrderController@update')->name('repairBase.exchangeModelOrder.update');  // 更换型号 编辑
                Route::delete('{serial_number}', 'ExchangeModelOrderController@destroy')->name('repairBase.exchangeModelOrder.destroy');  // 更换型号 删除
            });

            // 故障修
            Route::group(['prefix' => 'breakdownOrder'], function () {
                Route::put("{id}/fixDutyOfficer", "BreakdownOrderController@PutFixDutyOfficer")->name("repairBase.breakdownOrder:PutFixDutyOfficer");  // 设置返修责任人
                Route::put("{id}/bindBreakdownTypesWhenWarehouseIn", "BreakdownOrderController@PutBindBreakdownTypesWhenWarehouseIn")->name("repairBase.breakdownOrder:PutBindBreakdownTypesWhenWarehouseIn");  // 设置入所故障类型
                Route::put("{id}/source", "BreakdownOrderController@PutSource")->name("repairBase.breakdownOrder:PutSource");  // 设置来源（线别、车间、车站）
                Route::post('breakdownType', 'BreakdownOrderController@postBreakdownType')->name('repairBase.breakdownOrder.postBreakdownType');  // 设置故障类型
                Route::get('breakdownLog', 'BreakdownOrderController@getBreakdownLog')->name('repairBase.breakdownOrder.breakdownLog.get');  // 获取临时设备故障类型和日志
                Route::put('breakdownLog', 'BreakdownOrderController@putBreakdownLog')->name('repairBase.breakdownOrder.breakdownLog.put');  // 编辑临时设备与故障描述
                Route::post('stationBreakdownExplain/{id}', 'BreakdownOrderController@postStationBreakdownExplain')->name('repairBase.breakdownOrder.postStationBreakdownExplain');  // 保存现场故障描述
                Route::get('tmpEntireInstance/{id}', 'BreakdownOrderController@getTempEntireInstance')->name('repairBase.breakdownOrder.getTempEntireInstance');  // 获取设备信息
                Route::get('entireInstances', 'BreakdownOrderController@getEntireInstances')->name('repairBase.breakdownOrder.entireInstances.get');  // 获取入所计划设备列表
                Route::post('entireInstances', 'BreakdownOrderController@postEntireInstances')->name('repairBase.breakdownOrder.entireInstances.post');  // 将设备添加到入所计划
                Route::put('entireInstances/{id}', 'BreakdownOrderController@updateEntireInstances')->name('repairBase.breakdownOrder.entireInstances.update');  // 修改故障修入所计划设备
                Route::delete('entireInstances', 'BreakdownOrderController@deleteEntireInstances')->name('repairBase.breakdownOrder.entireInstances.delete'); // 将设备从入所计划中删除
                Route::post('scanEntireInstances', 'BreakdownOrderController@postScanEntireInstances')->name('repairBase.breakdownOrder.scanEntireInstance.post');  // 添加已扫码标记
                Route::delete('scanEntireInstances', 'BreakdownOrderController@deleteScanEntireInstances')->name('repairBase.breakdownOrder.scanEntireInstance.delete');  // 去除已扫码标记
                Route::get('printLabel/{serial_number}', 'BreakdownOrderController@getPrintLabel')->name('repairBase.breakdownOrder.printLabel');  // 打印标签
                Route::put('done/{serial_number}', 'BreakdownOrderController@putDone')->name('repairBase.breakdownOrder.postDone');  // 标记计划完成
                Route::post('bindEntireInstance', 'BreakdownOrderController@postBindEntireInstance')->name('repairBase.breakdownOrder.postBindEntireInstance');  // 绑定新设备到老设备
                Route::delete('bindEntireInstance', 'BreakdownOrderController@deleteBindEntireInstance')->name('repairBase.breakdownOrder.deleteBindEntireInstance');  // 删除绑定新设备到老设备
                Route::delete('bindEntireInstances', 'BreakdownOrderController@deleteBindEntireInstances')->name('repairBase.breakdownOrder.deleteBindEntireInstances');  // 删除所有绑定
                Route::post('autoBindEntireInstance', 'BreakdownOrderController@postAutoBindEntireInstance')->name('repairBase.breakdownOrder.postAutoBindEntireInstance');  // 自动分配新设备到老设备
                Route::post('autoBindEntireInstances', 'BreakdownOrderController@postAutoBindEntireInstances')->name('repairBase.breakdownOrder.postAutoBindEntireInstances');  // 全选自动分配新设备到老设备
                Route::get('mission', 'BreakdownOrderController@getMission')->name('repairBase.breakdownOrder.getMission');  // 故障修任务分配页面
                Route::post('mission', 'BreakdownOrderController@postMission')->name('repairBase.breakdownOrder.postMission');  // 保存故障修任务分配
                Route::post('warehouse/{serial_number}', 'BreakdownOrderController@PostWarehouse')->name('repairBase.breakdownOrder.PostWarehouse');  // 出入所
                Route::get('', 'BreakdownOrderController@index')->name('repairBase.breakdownOrder.index');  // 故障修 列表
                Route::get('create', 'BreakdownOrderController@create')->name('repairBase.breakdownOrder.create');  // 故障修 新建页面
                Route::post('', 'BreakdownOrderController@store')->name('repairBase.breakdownOrder.store');  // 故障修 新建
                Route::get('{serial_number}', 'BreakdownOrderController@show')->name('repairBase.breakdownOrder.show');  // 故障修 详情页面
                Route::get('{serial_number}/edit', 'BreakdownOrderController@edit')->name('repairBase.breakdownOrder.edit');  // 故障修 编辑页面
                Route::put('{serial_number}', 'BreakdownOrderController@update')->name('repairBase.breakdownOrder.update');  // 故障修 编辑
                Route::delete('{serial_number}', 'BreakdownOrderController@destroy')->name('repairBase.breakdownOrder.destroy');  // 故障修 删除
            });

            // 故障修带入所器材
            Route::prefix("breakdownOrderTempEntireInstance")
                ->group(function () {
                    Route::put("{id}", "BreakdownOrderTempEntireInstanceController@Put")->name("web.repairBase.breakdownOrderTempEntireInstance:Put");  // 编辑待故障修入所器材
                });

            // 故障修器材
            Route::group(['prefix' => 'breakdownOrderEntireInstance'], function () {
                Route::get('', 'BreakdownOrderEntireInstanceController@index')->name('web.breakdownOrderEntireInstance.index');  // 故障修设备 列表
                Route::get('create', 'BreakdownOrderEntireInstanceController@create')->name('web.breakdownOrderEntireInstance.create');  // 故障修设备 新建页面
                Route::post('', 'BreakdownOrderEntireInstanceController@store')->name('web.breakdownOrderEntireInstance.store');  // 故障修设备 新建
                Route::get('{id}', 'BreakdownOrderEntireInstanceController@show')->name('web.breakdownOrderEntireInstance.show');  // 故障修设备 详情页面
                Route::get('{id}/edit', 'BreakdownOrderEntireInstanceController@edit')->name('web.breakdownOrderEntireInstance.edit');  // 故障修设备 编辑页面
                Route::put('{id}', 'BreakdownOrderEntireInstanceController@update')->name('web.breakdownOrderEntireInstance.update');  // 故障修设备 编辑
                Route::delete('{id}', 'BreakdownOrderEntireInstanceController@destroy')->name('web.breakdownOrderEntireInstance.destroy');  // 故障修设备 删除
            });

            // 故障报告文件
            Route::group(['prefix' => 'breakdownReportFile'], function () {
                Route::get('{id}/download', 'BreakdownReportFileController@getDownload')->name('web.breakdownReportFile.getDownload'); // 故障报告文件 下载
                Route::get('', 'BreakdownReportFileController@index')->name('web.breakdownReportFile.index');  // 故障报告文件 列表
                Route::get('create', 'BreakdownReportFileController@create')->name('web.breakdownReportFile.create');  // 故障报告文件 新建页面
                Route::post('', 'BreakdownReportFileController@store')->name('web.breakdownReportFile.store');  // 故障报告文件 新建
                Route::get('{id}', 'BreakdownReportFileController@show')->name('web.breakdownReportFile.show');  // 故障报告文件 详情页面
                Route::get('{id}/edit', 'BreakdownReportFileController@edit')->name('web.breakdownReportFile.edit');  // 故障报告文件 编辑页面
                Route::put('{id}', 'BreakdownReportFileController@update')->name('web.breakdownReportFile.update');  // 故障报告文件 编辑
                Route::delete('{id}', 'BreakdownReportFileController@destroy')->name('web.breakdownReportFile.destroy');  // 故障报告文件 删除
            });

            // 高频修
            Route::group(['prefix' => 'highFrequencyOrder'], function () {
                Route::get('entireInstances', 'HighFrequencyOrderController@getEntireInstances')->name('repairBase.highFrequencyOrder.entireInstances.get');  // 获取入所计划设备列表
                Route::post('entireInstances', 'HighFrequencyOrderController@postEntireInstances')->name('repairBase.highFrequencyOrder.entireInstances.post');  // 将设备添加到入所计划
                Route::delete('entireInstances', 'HighFrequencyOrderController@deleteEntireInstances')->name('repairBase.highFrequencyOrder.entireInstances.delete'); // 将设备从入所计划中删除
                Route::post('scanEntireInstances', 'HighFrequencyOrderController@postScanEntireInstances')->name('repairBase.highFrequencyOrder.scanEntireInstance.post');  // 添加已扫码标记
                Route::delete('scanEntireInstances', 'HighFrequencyOrderController@deleteScanEntireInstances')->name('repairBase.highFrequencyOrder.scanEntireInstance.delete');  // 去除已扫码标记
                Route::get('printLabel/{serial_number}', 'HighFrequencyOrderController@getPrintLabel')->name('repairBase.highFrequencyOrder.printLabel');  // 打印标签
                Route::put('done/{serial_number}', 'HighFrequencyOrderController@putDone')->name('repairBase.highFrequencyOrder.postDone');  // 标记计划完成
                Route::post('bindEntireInstance', 'HighFrequencyOrderController@postBindEntireInstance')->name('repairBase.highFrequencyOrder.postBindEntireInstance');  // 绑定新设备到老设备
                Route::delete('bindEntireInstance', 'HighFrequencyOrderController@deleteBindEntireInstance')->name('repairBase.highFrequencyOrder.deleteBindEntireInstance');  // 删除绑定新设备到老设备
                Route::delete('bindEntireInstances', 'HighFrequencyOrderController@deleteBindEntireInstances')->name('repairBase.highFrequencyOrder.deleteBindEntireInstances');  // 删除所有绑定
                Route::post('autoBindEntireInstance', 'HighFrequencyOrderController@postAutoBindEntireInstance')->name('repairBase.highFrequencyOrder.postAutoBindEntireInstance');  // 自动分配新设备到老设备
                Route::post('autoBindEntireInstances', 'HighFrequencyOrderController@postAutoBindEntireInstances')->name('repairBase.highFrequencyOrder.postAutoBindEntireInstances');  // 全选自动分配新设备到老设备
                Route::get('mission', 'HighFrequencyOrderController@getMission')->name('repairBase.highFrequencyOrder.getMission');  // 高频修任务分配页面
                Route::post('mission', 'HighFrequencyOrderController@postMission')->name('repairBase.highFrequencyOrder.postMission');  // 保存高频修任务分配
                Route::post('warehouse', 'HighFrequencyOrderController@postWarehouse')->name('repairBase.highFrequencyOrder.postWarehouse');  // 出入所
                Route::get('', 'HighFrequencyOrderController@index')->name('repairBase.highFrequencyOrder.index');  // 高频修 列表
                Route::get('create/{serial_number}', 'HighFrequencyOrderController@create')->name('repairBase.highFrequencyOrder.create');  // 高频修 新建页面
                Route::post('', 'HighFrequencyOrderController@store')->name('repairBase.highFrequencyOrder.store');  // 高频修 新建
                Route::get('{serial_number}', 'HighFrequencyOrderController@show')->name('repairBase.highFrequencyOrder.show');  // 高频修 详情页面
                Route::get('{serial_number}/edit', 'HighFrequencyOrderController@edit')->name('repairBase.highFrequencyOrder.edit');  // 高频修 编辑页面
                Route::put('{serial_number}', 'HighFrequencyOrderController@update')->name('repairBase.highFrequencyOrder.update');  // 高频修 编辑
                Route::delete('{serial_number}', 'HighFrequencyOrderController@destroy')->name('repairBase.highFrequencyOrder.destroy');  // 高频修 删除
            });

            // 状态修出所
            Route::group(['prefix' => 'fixOut'], function () {
                Route::get('/', 'FixOutController@index')->name('fixOut.index');  // 状态修出所 列表
                Route::get('/create', 'FixOutController@create')->name('fixOut.create');  // 状态修出所 新建页面
                Route::post('/', 'FixOutController@store')->name('fixOut.store');  // 状态修出所 新建
                Route::get('/{id}', 'FixOutController@show')->name('fixOut.show');  // 状态修出所 详情页面
                Route::get('/{id}/edit', 'FixOutController@edit')->name('fixOut.edit');  // 状态修出所 编辑页面
                Route::put('/{id}', 'FixOutController@update')->name('fixOut.update');  // 状态修出所 编辑
                Route::delete('/{id}', 'FixOutController@destroy')->name('fixOut.destroy');  // 状态修出所 删除
            });

            // 周期修出所
            Route::group(['prefix' => 'planOut'], function () {
                Route::get('/checkEntireInstanceForContinuous', 'PlanOutController@getCheckEntireInstanceForContinuous')->name('planOut.getCheckEntireInstanceForContinuous');  // 检查设备器材（连续扫码）
                Route::post('/scanCycleFixOutForContinuous', 'PlanOutController@storeScanCycleFixOutForContinuous')->name('planOut.scanCycleFixOutForContinuous.store');  // 周期修出所（连续扫码）

                Route::get('/billWithClose/{billId}', 'PlanOutController@billWithClose')->name('planOut.billWithClose');  // 任务关闭
                Route::get('/billWithOpen/{billId}', 'PlanOutController@billWithOpen')->name('planOut.billWithOpen');  // 任务开启

                Route::get('/scanCycleFixOut/{id}', 'PlanOutController@getCycleFixOut')->name('planOut.scanCycleFixOut.show');  // 出所扫码页面 (周期修出所）
                Route::delete('/scanCycleFixOut/{id}', 'PlanOutController@destroyScanCycleFixOut')->name('planOut.scanCycleFixOut.destroy');  // 出所删除扫码 (周期修出所）
                Route::put('/scanCycleFixOut/{billId}', 'PlanOutController@updateScanCycleFixOut')->name('planOut.scanCycleFixOut.update');  // 出所扫码 (周期修出所）
                Route::post('/scanCycleFixOut', 'PlanOutController@storeScanCycleFixOut')->name('planOut.scanCycleFixOut.store');  // 确认出所 (周期修出所）

                Route::get('/cycleFixWithStation', 'PlanOutController@cycleFixWithStation')->name('planOut.cycleFix.index');  // 出所计划 (周期修任务列表-车站）
                Route::get('/cycleFixWithMonth', 'PlanOutController@cycleFixWithMonth')->name('planOut.cycleFix.index');  // 出所计划 (周期修任务列表-月份）
                Route::get('/cycleFix', 'PlanOutController@cycleFix')->name('planOut.cycleFix.index');  // 出所计划 (周期修任务列表）
                Route::get('/cycleFix/create', 'PlanOutController@getCreateCycleFix')->name('planOut.cycleFix.create');  // 出所计划（周期修出所）
                Route::post('/cycleFix', 'PlanOutController@postCycleFix')->name('planOut.cycleFix.store');  // 生成出所计划（周期修出所）
                Route::get('/cycleFix/{id}', 'PlanOutController@getShowCycleFix')->name('planOut.cycleFix.show');  // 生成出所计划（周期修出所）
                Route::put('/cycleFix/{id}', 'PlanOutController@updateCycleFix')->name('planOut.cycleFix.update');  // 出所计划替换设备
                Route::put('/replaces/{billId}', 'PlanOutController@cycleFixWithReplaces')->name('planOut.cycleFix.replaces');  // 出所计划批量替换设备
                Route::delete('/replaces/{billId}', 'PlanOutController@cycleFixWithUnReplaces')->name('planOut.cycleFix.unreplaces');  // 出所计划取消替换设备


                Route::get('/normal', 'PlanOutController@getSelectStationNormal')->name('planOut.selectStationNormal');  // 出所计划（状态修或故障修）
                Route::get('/', 'PlanOutController@index')->name('planOut.index');  // 出所计划 列表
                Route::get('/create', 'PlanOutController@create')->name('planOut.create');  // 出所计划 新建页面
                Route::post('/', 'PlanOutController@store')->name('planOut.store');  // 出所计划 新建
                Route::get('/{serial_number}', 'PlanOutController@show')->name('planOut.show');  // 出所计划 详情页面
                Route::get('/{serial_number}/edit', 'PlanOutController@edit')->name('planOut.edit');  // 出所计划 编辑页面
                Route::put('/{serial_number}', 'PlanOutController@update')->name('planOut.update');  // 出所计划 编辑
                Route::delete('/{serial_number}', 'PlanOutController@destroy')->name('planOut.destroy');  // 出所计划 删除
            });

            // 站改
            Route::group(['prefix' => 'stationReform'], function () {
                Route::get('/', 'StationReformOrderController@index')->name('repairBase.stationReform.index');  // 站改列表
                Route::get('/create', 'StationReformOrderController@create')->name('repairBase.stationReform.create');  // 站改新建页面
                Route::post('/', 'StationReformOrderController@store')->name('repairBase.stationReform.store');  // 站改新建
                Route::get('/{id}', 'StationReformOrderController@show')->name('repairBase.stationReform.show');  // 站改详情页面
                Route::get('/{id}/edit', 'StationReformOrderController@edit')->name('repairBase.stationReform.edit');  // 站改编辑页面
                Route::put('/{id}', 'StationReformOrderController@update')->name('repairBase.stationReform.update');  // 站改编辑
                Route::delete('/{id}', 'StationReformOrderController@destroy')->name('repairBase.stationReform.destroy');  // 站改删除
            });
        });

        // 仓库
        Route::group(['prefix' => 'warehouse', 'namespace' => 'Warehouse'], function () {
            // 成品
            Route::group(['prefix' => 'produces'], function () {
                Route::get('/', 'Product\PostController@index')->name('warehouseProduces.index');  // 成品列表
                Route::get('/create', 'Product\PostController@create')->name('warehouseProduces.create');  // 成品新建页面
                Route::post('/', 'Product\PostController@store')->name('warehouseProduces.store');  // 成品新建
                Route::get('/{id}', 'Product\PostController@show')->name('warehouseProduces.show');  // 成品详情页面
                Route::get('/{id}/edit', 'Product\PostController@edit')->name('warehouseProduces.edit');  // 成品编辑页面
                Route::put('/{id}', 'Product\PostController@update')->name('warehouseProduces.update');  // 成品编辑
                Route::delete('/{id}', 'Product\PostController@destroy')->name('warehouseProduces.destroy');  // 成品删除
            });

            Route::group(['prefix' => 'product', 'namespace' => 'Product'], function () {
                Route::resource('part', 'PartController');  // 零件
                Route::resource('pivot', 'PivotController');  // 中间表
                Route::resource('instance', 'InstanceController');  // 设备实例表
                Route::resource('planProcess', 'PlanProcessController');  // 排期维护记录
            });

            Route::group(['prefix' => 'procurement', 'namespace' => 'Procurement'], function () {
                Route::resource('part', 'PartController');  // 零件采购单
                Route::resource('partInstance', 'PartInstanceController');  // 零件采购单实例
            });

            // 出所计划
            Route::group(['prefix' => 'planOut'], function () {
                Route::get('getStationBySceneWorkshop/{sceneWorkshopUniqueCode}', function (string $uniqueCode) {
                    return response()->json(
                        DB::table('maintains')
                            ->where('deleted_at', null)
                            ->where('parent_unique_code', $uniqueCode)
                            ->pluck('name', 'unique_code')
                    );
                })
                    ->name('plan.getStationBySceneWorkshop');  // 通过现场车间获取车站
                Route::get('/', 'PlanController@index')->name('plan.index');  // 列表
                Route::get('in/{entireInstanceIdentityCode}', 'PlanController@getIn')->name('warehouse.plan.in.get');  // 入库页面
                Route::post('in/{entireInstanceIdentityCode}', 'PlanController@postIn')->name('warehouse.plan.in.post');  // 入库
                Route::get('makeFixWorkflow/{entireInstanceIdentityCode}', 'PlanController@getMakeFixWorkflow')->name('warehouse.plan.makeFixWorkflow.get');  // 生成检修单页面
                Route::post('makeFixWorkflow/{entireInstanceIdentityCode}', 'PlanController@postMakeFixWorkflow')->name('warehouse.plan.makeFixWorkflow.post');  // 生成检修单
            });

            // 即将到期
            Route::group(['prefix' => 'goingToDie'], function () {
                Route::get('/', 'GoingToDieController@index')->name('goingToDie.index');  // 列表
            });

            // 库存及相关报表
            Route::group(['prefix' => 'report'], function () {
                Route::get('in', 'ReportController@getIn')->name('warehouseReport.getIn');  // 办理入所页面
                Route::get('out', 'ReportController@getOut')->name('warehouseReport.getOut');  // 办理出所页面
                Route::post('in', 'ReportController@postIn')->name('warehouseReport.postIn');  // 办理入所
                Route::post('out', 'ReportController@postOut')->name('warehouseReport.postOut');  // 办理出所
                Route::post('projectOut', 'ReportController@postProjectOut')->name('warehouseReport.postProjectOut');  // 工程出所
                Route::get('scanEntireInstances', 'ReportController@getScanEntireInstances')->name('warehouseReport.getScanEntireInstances');  // 已扫码设备器材列表
                Route::post('scan', 'ReportController@postScan')->name('warehouseReport.postScan');  // 扫码添加设备器材
                Route::delete('scanOne/{identity_code}', 'ReportController@deleteScanOne')->name('warehouseReport.deleteScanOne');  // 删除已扫码单设备器材
                Route::delete('scanAll', 'ReportController@deleteScanAll')->name('warehouseReport.deleteScanAll');  // 清空已扫码设备器材

                Route::post('oldLocationAndNewEntireInstanceForPrint', 'ReportController@storeOldLocationAndNewEntireInstanceForPrint')->name('warehouseReport.storeOldLocationAndNewEntireInstanceForPrint');  // 打印旧位置和新编号
                Route::post('identityCodeWithPrint', 'ReportController@storeIdentityCodeWithPrint')->name('warehouseReport.identityCodeWithPrint.store');  // 打印标签页面
                Route::get('printLabel/{serial_number}', 'ReportController@printLabel')->name('warehouseReport.printLabel');  // 打印标签页面
                Route::get('pointSwitchModifyLocation/{identityCode}', 'ReportController@getPointSwitchModifyLocation')->name('warehouseReport.getPointSwitchModifyLocation');  // 改写转辙机位置页面
                Route::post('pointSwitchModifyLocation/{identityCode}', 'ReportController@postPointSwitchModifyLocation')->name('warehouseReport.getPointSwitchModifyLocation');  // 改写转辙机位置
                Route::get('printNormalLabel', 'ReportController@getPrintNormalLabel')->name('warehouseReport.getPrintNormalLabel');  // 打印普通标签（页面）
                Route::post('printNormalLabel', 'ReportController@postPrintNormalLabel')->name('warehouseReport.postPrintNormalLabel');  // 打印普通标签
                Route::delete('printNormalLabel', 'ReportController@destroyPrintNormalLabel')->name('warehouseReport.destroyPrintNormalLabel');  // 打印普通标签 删除


                Route::get('quality', 'ReportController@quality')->name('warehouseReport.quality');  // 质量报告
                Route::get('print/{warehouseReportSerialNumber}', 'ReportController@print')->name('warehouseReport.print');  // 打印页面
                // Route::get('scanInBatch', 'ReportController@getScanInBatch')->name('warehouseReport.scanInBatch.get');  // 批量扫码入所 页面
                // Route::post('scanInBatch', 'ReportController@postScanInBatch')->name('warehouseReport.scanInBatch.post');  // 批量扫码入所
                Route::get('scanBatch', 'ReportController@getScanBatch')->name('warehouseReport.scanBatch.get');  // 通用入所->批量扫码入所
                Route::get('scanBatchOut', 'ReportController@getScanBatchOut')->name('warehouseReport.scanBatchOut.get');  // 通用出所->批量扫码出所
                Route::post('scanBatch', 'ReportController@postScanBatch')->name('warehouseReport.scanBatch.post');  // 入所扫码
                Route::post('scanBatchOut', 'ReportController@postScanBatchOut')->name('warehouseReport.scanBatchOut.post');  // 出所扫码
                Route::delete('scanBatch/{id}', 'ReportController@deleteScanBatch')->name('warehouseReport.scanBatch.delete');  // 删除扫码设备
                Route::post('scanBatchWarehouse', 'ReportController@postScanBatchWarehouse')->name('warehouseReport.scanBatchWarehouse.post');  // 出入所

                Route::get('modelOutBatch', 'ReportController@modelOutBatch')->name('warehouseReport.modeOutBatch.get');  // 批量出所弹出框
                Route::post('outBatch', 'ReportController@postOutBatch')->name('warehouseReport.outBatch.post');  // 批量出所
                Route::post('cleanBatch', 'ReportController@postCleanBatch')->name('warehouseReport.cleanBatch');  // 清空批量表
                Route::post('makeFixWorkflow', 'ReportController@postMakeFixWorkflow')->name('warehouseReport.makeFixWorkflow');  // 生成检修单
                Route::post('deleteBatch', 'ReportController@postDeleteBatch')->name('warehouseReport.destroyBatch');  // 删除批量单项
                Route::post('inBatch', 'ReportController@postInBatch')->name('warehouseReport.inBatch');  // 批量入所
                Route::post('makeFixWorkflowBatch', 'ReportController@postMakeFixWorkflowBatch')->name('warehouseReport.makeFixWorkflowBatch');  // 批量生成检修单
                Route::get('/', 'ReportController@index')->name('warehouseReport.index');  // 列表
                Route::get('create', 'ReportController@create')->name('warehouseReport.create');  // 新建页面
                Route::post('/', 'ReportController@store')->name('warehouseReport.store');  // 新建
                Route::get('/{warehouseReportSerialNumber}', 'ReportController@show')->name('warehouseReport.show');  // 详情页面
                Route::get('/{warehouseReportSerialNumber}/edit', 'ReportController@edit')->name('warehouseReport.edit');  // 编辑页面
                Route::put('/{warehouseReportSerialNumber}', 'ReportController@update')->name('warehouseReport.update');  // 编辑
                Route::delete('/{warehouseReportSerialNumber}', 'ReportController@destroy')->name('warehouseReport.destroy');  // 删除
            });

            // 状态修
            Route::group(['prefix' => 'breakdownOrder'], function () {
                Route::get('outWithEntireInstance', 'BreakdownOrderController@outWithEntireInstanceIndex')->name('breakdownOrder.entireInstance.index');  // 状态修 出所设备页面
                Route::post('outWithEntireInstance/{identityCode}', 'BreakdownOrderController@outWithEntireInstanceStore')->name('breakdownOrder.entireInstance.store');  // 状态修 出所设备 添加
                Route::put('outWithEntireInstance', 'BreakdownOrderController@outWithEntireInstanceUpdate')->name('breakdownOrder.entireInstance.update');  // 状态修 出所设备 编辑 替换
                Route::delete('outWithEntireInstance', 'BreakdownOrderController@outWithEntireInstanceDestories')->name('breakdownOrder.entireInstance.destories');  // 状态修 出所设备 批量删除

                Route::get('outWithModal', 'BreakdownOrderController@outWithModal')->name('breakdownOrder.index.modal');  // 状态修 出所 模态框
                Route::get('outWithScan/{code}', 'BreakdownOrderController@outWithScan')->name('breakdownOrder.index.scan');  // 状态修 出所页面 扫码
                Route::get('out', 'BreakdownOrderController@indexForOut')->name('breakdownOrder.out.index');  // 状态修 出所页面
                Route::post('out', 'BreakdownOrderController@outStore')->name('breakdownOrder.out.store');  // 状态修 出所页面
            });

            // 仓库存储
            Route::group(['prefix' => 'storage'], function () {
                Route::get('scanInBatch', 'StorageController@getScanInBatch')->name('storageScanInBatch.get');  // 扫码入库页面
                Route::post('scanInBatch', 'StorageController@postScanInBatch')->name('storageScanInBatch.post');  // 扫码入库
                Route::get('/stock', 'StorageController@getStock')->name('storageStock.get');  // 盘点页面
                Route::post('/stock', 'StorageControler@postStock')->name('storageStock.post');  // 盘点
                Route::get('/', 'StorageController@index')->name('storage.index');  // 仓库存储列表
                Route::get('/create', 'StorageController@create')->name('storage.create');  // 仓库存储新建页面
                Route::post('/', 'StorageController@store')->name('storage.store');  // 仓库存储新建
                Route::get('/{subModelName}', 'StorageController@show')->name('storage.show');  // 仓库存储详情页面
                Route::get('/{id}/edit', 'StorageController@edit')->name('storage.edit');  // 仓库存储编辑页面
                Route::put('/{id}', 'StorageController@update')->name('storage.update');  // 仓库存储编辑
                Route::delete('/{id?}', 'StorageController@destroy')->name('storage.destroy');  // 仓库存储删除
            });
        });

        // 检测标准值
        Route::group(['prefix' => 'measurements', 'namespace' => 'Measurement'], function () {
            Route::get('/batch/report', 'PostController@getBatchReport')->name('measurement.batchReport.get'); // 批量导入报告
            Route::get('/batch', 'PostController@getBatch')->name('measurement.batch.get');  // 批量导入页面
            Route::post('/batch', 'PostController@postBatch')->name('measurement.batch.post');  // 批量导入
            Route::get('/', 'PostController@index')->name('measurement.index');  // 列表
            Route::get('create', 'PostController@create')->name('measurement.create');  // 新建页面
            Route::post('/', 'PostController@store')->name('measurement.store');  // 新建
            Route::get('/{serialNumber}', 'PostController@show')->name('measurement.show');  // 详情页面
            Route::get('/{serialNumber}/edit', 'PostController@edit')->name('measurement.edit');  // 编辑页面
            Route::put('/{serialNumber}', 'PostController@update')->name('measurement.update');  // 编辑
            Route::delete('/{serialNumber}', 'PostController@destroy')->name('measurement.destroy');  // 删除
        });

        // 检修单
        Route::group(['prefix' => 'measurement', 'namespace' => 'Measurement'], function () {
            // 检修单
            Route::group(['prefix' => 'fixWorkflow'], function () {
                Route::get('batchUploadFixerAndChecker', 'FixWorkflowController@getBatchUploadFixerAndChecker')->name('fixWorkflow.batchUploadFixerAndChecker.get');  // 批量导入检修人和验收人页面
                Route::post('batchUploadFixerAndChecker', 'FixWorkflowController@postBatchUploadFixerAndChecker')->name('fixWorkflow.batchUploadFixerAndChecker.post');  // 批量导入检修人和验收人页面

                Route::get('uploadCheck', 'FixWorkflowController@getUploadCheck')->name('fixWorkflow.uploadCheck.get');  // 打开上传检测页面
                Route::post('uploadCheck', 'FixWorkflowController@postUploadCheck')->name('fixWorkflow.uploadCheck.post');  // 打开上传检测页面
                Route::get('downloadCheck/{id}', 'FixWorkflowController@downloadCheck')->name('fixWorkflow.download');  // 检测台文件下载

                Route::post('updateBreakdownType/{fixWorkflowSerialNumber}', 'FixWorkflowController@postUpdateBreakdownType')->name('fixWorkflowProcess.updateBreakdownType');  // 更新设备故障类型
                Route::get('forceInstall', 'FixWorkflowController@getForceInstall')->name('fixWorkflow.forceInstall.get');  // 打开强制出所页面
                Route::post('forceInstall/{fixWorkflowSerialNumber}', 'FixWorkflowController@postForceInstall')->name('fixWorkflow.forceInstall.post');  // 强制出所
                Route::put('fixed/{fixWorkflowSerialNumber}', 'FixWorkflowController@fixed')->name('fixWorkflow.fixed.put');  // 标记工单完成
                Route::get('install', 'FixWorkflowController@getInstall')->name('fixWorkflow.install.get');  #
                Route::post('install/{serialNumber?}', 'FixWorkflowController@postInstall')->name('fixWorkflow.install.post');  // 出库安装
                Route::get('returnFactory/{fixWorkflowSerialNumber}', 'FixWorkflowController@getReturnFactory')->name('fixWorkflow.returnFactory.get');  // 返厂维修页面
                Route::post('returnFactory/{fixWorkflowSerialNumber}', 'FixWorkflowController@postReturnFactory')->name('fixWorkflow.returnFactory.post');  // 返厂维修
                Route::get('factoryReturn/{fixWorkflowSerialNumber}', 'FixWorkflowController@getFactoryReturn')->name('fixWorkflow.factoryReturn.get');  // 返厂回所页面
                Route::post('factoryReturn/{fixWorkflowSerialNumber}', 'FixWorkflowController@postFactoryReturn')->name('fixWorkflow.factoryReturn.post');  // 返厂回所
                Route::get('in/{fixWorkflowSerialNumber}', 'FixWorkflowController@getIn')->name('fixWorkflow.in.get');  // 检修单：入所 页面
                Route::post('in/{fixWorkflowSerialNumber}', 'FixWorkflowController@postIn')->name('fixWorkflow.in.post');  // 检修单：入所
                Route::get('check', 'FixWorkflowController@check')->name('fixWorkflow.check');  // 验收页面
                Route::get('/', 'FixWorkflowController@index')->name('fixWorkflow.index');  // 检修单列表
                Route::get('create', 'FixWorkflowController@create')->name('fixWorkflow.create');  // 检修单新建页面
                Route::post('/', 'FixWorkflowController@store')->name('fixWorkflow.store');  // 检修单新建
                Route::get('/{serialNumber}', 'FixWorkflowController@show')->name('fixWorkflow.show');  // 检修单详情页面
                Route::get('/{serialNumber}/edit', 'FixWorkflowController@edit')->name('fixWorkflow.edit');  // 检修单编辑页面
                Route::put('/{serialNumber}', 'FixWorkflowController@update')->name('fixWorkflow.update');  // 检修单编辑
                Route::delete('/{serialNumber}', 'FixWorkflowController@destroy')->name('fixWorkflow.destroy');  // 检修单删除
            });

            // 检测单
            Route::group(['prefix' => 'fixWorkflowProcess'], function () {
                Route::get('part', 'FixWorkflowProcessController@getPart')->name('fixWorkflowProcess.part');  // 部件检测页面
                Route::get('/', 'FixWorkflowProcessController@index')->name('fixWorkflowProcess.index');  // 检测单列表
                Route::get('create', 'FixWorkflowProcessController@create')->name('fixWorkflowProcess.create');  // 检测单新建页面
                Route::post('/', 'FixWorkflowProcessController@store')->name('fixWorkflowProcess.store');  // 检测单新建
                Route::get('/{fixWorkflowProcessSerialNumber}', 'FixWorkflowProcessController@show')->name('fixWorkflowProcess.show');  // 详检测单情页面
                Route::get('/{fixWorkflowProcessSerialNumber}/edit', 'FixWorkflowProcessController@edit')->name('fixWorkflowProcess.edit');  // 检测单编辑页面
                Route::put('/{fixWorkflowProcessSerialNumber}', 'FixWorkflowProcessController@update')->name('fixWorkflowProcess.update');  // 检测单编辑
                Route::delete('/{fixWorkflowProcessSerialNumber}', 'FixWorkflowProcessController@destroy')->name('fixWorkflowProcess.destroy');  // 检测单删除
            });

            // 检测记录
            Route::group(['prefix' => 'fixWorkflowRecord'], function () {
                Route::post('/saveMeasuredValue', 'FixWorkflowRecordController@saveMeasuredValue')->name('fixWorkflowRecord.saveMeasuredValue');  // 保存部件检测数据
                Route::get('/saveMeasuredExplain', 'FixWorkflowRecordController@getSaveMeasuredExplain')->name('fixWorkflowRecord.saveMeasuredExplain.get');  // 保存部件实测模糊描述
                Route::post('/saveMeasuredExplain', 'FixWorkflowRecordController@postSaveMeasuredExplain')->name('fixWorkflowRecord.saveMeasuredExplain.post');  // 保存部件实测模糊描述
                Route::get('/bindingFixWorkflowProcess/{fixWorkflowProcessSerialNumber}', 'FixWorkflowRecordController@getBindingFixWorkflowProcess')->name('fixWorkflowRecord.bindingFixWorkflowProcess.get');  // 测试数据绑定到测试单页面
                Route::post('/bindingFixWorkflowProcess/{fixWorkflowProcessSerialNumber}', 'FixWorkflowRecordController@postBindingFixWorkflowProcess')->name('fixWorkflowRecord.bindingFixWorkflowProcess.post');  // 测试数据绑定到测试单
                Route::get('/boundFixWorkflowProcess/{fixWorkflowProcessSerialNumber}', 'FixWorkflowRecordController@getBoundFixWorkflowProcess')->name('fixWorkflowRecord.boundFixWorkflowProcess.get');  // 解除测试单与测试数据关系页面
                Route::post('/cancelBoundFixWorkflowProcess/{fixWorkflowProcessSerialNumber}', 'FixWorkflowRecordController@postCancelBoundFixWorkflowProcess')->name('fixWorkflowRecord.cancelBoundFixWorkflowProcess.post');  // 解除测试单与测试数据关系页面
                Route::post('/saveProcessor/{fixWorkflowProcessSerialNumber}', 'FixWorkflowRecordController@postSaveProcessor')->name('fixWorkflowRecord.saveProcessor.post');  // 记录检测人
                Route::post('/saveProcessedAt/{fixWorkflowProcessSerialNumber}', 'FixWorkflowRecordController@postSaveProcessedAt')->name('fixWorkflowRecord.saveProcessedAt.post');  // 记录检测时间
                Route::get('/', 'FixWorkflowRecordController@index')->name('fixWorkflowRecord.index');  // 检测记录列表
                Route::get('create', 'FixWorkflowRecordController@create')->name('fixWorkflowRecord.create');  // 检测记录新建页面
                Route::post('/', 'FixWorkflowRecordController@store')->name('fixWorkflowRecord.store');  // 检测记录新建
                Route::get('/{serialNumber}', 'FixWorkflowRecordController@show')->name('fixWorkflowRecord.show');  // 检测记录详情页面
                Route::get('/{serialNumber}/edit', 'FixWorkflowRecordController@edit')->name('fixWorkflowRecord.edit');  // 检测记录编辑页面
                Route::put('/{serialNumber}', 'FixWorkflowRecordController@update')->name('fixWorkflowRecord.update');  // 检测记录编辑
                Route::delete('/{serialNumber}', 'FixWorkflowRecordController@destroy')->name('fixWorkflowRecord.destroy');  // 检测记录删除
            });
        });

        // 部件和整件对应关系
        Route::group(['prefix' => 'pivotEntireModelAndPartModel'], function () {
            Route::get('/', 'PivotEntireModelAndPartModelController@index')->name('pivotEntireModelAndPartModel.index');  // 部件列表
            Route::get('/create', 'PivotEntireModelAndPartModelController@create')->name('pivotEntireModelAndPartModel.create');  // 部件新建页面
            Route::post('/', 'PivotEntireModelAndPartModelController@store')->name('pivotEntireModelAndPartModel.store');  // 部件新建
            Route::get('/{id}', 'PivotEntireModelAndPartModelController@show')->name('pivotEntireModelAndPartModel.show');  // 部件详情页面
            Route::get('/{id}/edit', 'PivotEntireModelAndPartModelController@edit')->name('pivotEntireModelAndPartModel.edit');  // 部件编辑页面
            Route::put('/{id}', 'PivotEntireModelAndPartModelController@update')->name('pivotEntireModelAndPartModel.update');  // 部件编辑
            Route::delete('/{id}', 'PivotEntireModelAndPartModelController@destroy')->name('pivotEntireModelAndPartModel.destroy');  // 部件删除
        });

        // 整件
        Route::group(['prefix' => 'entire', 'namespace' => 'Entire'], function () {
            // 上传检修人验收人
            Route::prefix("updateFixerCheckerOrder")
                ->name("web.EntireInstanceUpdateFixerCheckerOrder:")
                ->group(function () {
                    Route::get("","UpdateFixerCheckerOrderController@Index")->name("index");
                    Route::post("","UpdateFixerCheckerOrderController@Store")->name("store");
                    Route::get("{uuid}","UpdateFixerCheckerOrderController@Show")->name("show");
                });

            // 报废单
            Route::prefix("scrapOrder")
                ->name("ScrapOrder:")
                ->group(function () {
                    Route::get("", "ScrapOrderController@index")->name("index");  // 报废单列表
                    Route::get("create", "ScrapOrderController@create")->name("create");  // 新建单页面
                    Route::post("", "ScrapOrderController@store")->name("store");  // 保存报废单
                    Route::get("{serial_number}/edit", "ScrapOrderController@edit")->name("edit");  // 报废单详情
                    Route::delete("", "ScrapOrderController@detroy")->name("destroy");  // 删除报废单
                });

            // 报废器材
            Route::prefix("scrapTempEntireInstance")
                ->name("ScrapTempEntireInstance:")
                ->group(function () {
                    Route::get("", "ScrapTempEntireInstanceController@index")->name("index");  // 待报废器材列表
                    Route::post("", "ScrapTempEntireInstanceController@store")->name("store");  // 扫码添加待报废器材
                    Route::delete("{identity_code}", "ScrapTempEntireInstanceController@destroy")->name("destroy");  // 删除待报废器材
                });

            // 整件部件绑定
            Route::group(['prefix' => 'bind'], function () {
                Route::delete('/{identity_code}/unbindPartInstance', 'BindController@deleteUnbindPartInstance')->name('web.bind.deleteUnbindPartInstance');  // 证件部件绑定 拆除部件
                Route::get('/entireInstance/{identity_code}', 'BindController@getEntireInstance')->name('web.bind.getEntireInstance');  // 整件部件绑定 获取整件数据
                Route::get('/partInstance/{identity_code}', 'BindController@getPartInstance')->name('web.bind.getPartInstance');  // 证件部件绑定 获取部件数据
                Route::get('/partInstances/{entire_instance_identity_code}', 'BindController@getPartInstances')->name('web.bind.getPartInstances');  // 证件部件绑定 根据整件编号获取部件列表
                Route::get('/', 'BindController@index')->name('web.bind.index');  // 整件部件绑定 index page
                Route::post('/{entire_instance_identity_code}/', 'BindController@postBindPartInstances')->name('web.bind.postBindPartInstances');  // 整件部件绑定 绑定所有部件
            });

            // 整件种类型
            Route::group(['prefix' => 'kind'], function () {
                Route::get('/', 'KindController@index')->name('web.kind.index');  // 整件种类型 列表
                Route::post('/category', 'KindController@postCategory')->name('web.kind.postCategory');  // 整件种类型 添加种类
                Route::post('/entireModel', 'KindController@postEntireModel')->name('web.kind.postEntireModel');  // 整件种类型 添加类型
                Route::post('/subModel', 'KindController@postSubModel')->name('web.kind.postSubModel');  // 整件种类型 添加子类
                Route::get('/{unique_code}/category', 'KindController@getCategory')->name('web.kind.getCategory');  // 整件种类型 获取种类
                Route::put('/category', 'KindController@putCategory')->name('web.kind.putCategory');  // 整件种类型 修改种类
                Route::get('/{unique_code}/entireModel', 'KindController@getEntireModel')->name('web.kind.getEntireModel');  // 整件种类型 获取类型
                Route::put('/entireModel', 'KindController@putEntireModel')->name('web.kind.putEntireModel');  // 整件种类型 修改类型
                Route::get('/{unique_code}/subModel', 'KindController@getSubModel')->name('web.kind.getSubModel');  // 整件种类型 获取子类
                Route::put('/subModel', 'KindController@putSubModel')->name('web.kind.putSubModel');  // 整件种类型 编辑子类
            });

            // 整件型号
            Route::group(['prefix' => 'model'], function () {
                Route::get('nextEntireModelUniqueCode/{categoryUniqueCode}', 'ModelController@getNextEntireModelUniqueCode')->name('entire-model.getNextEntireModelUniqueCode');
                Route::get('getEntireModelByCategoryUniqueCode/{categoryUniqueCode}', 'ModelController@getEntireModelByCategoryUniqueCode')->name('entire-model.getEntireModelByCategoryUniqueCode');  // 根据种类代码获取类型
                Route::get('getSubModelByEntireModel/{entireModelUniqueCode}', 'ModelController@getSubModelByEntireModel')->name('entire-model.getSubModelByEntireModel');  // 根据类型获取型号或子类
                Route::get('batchFactory', 'ModelController@getBatchFactory')->name('entire-model.batchFactory.get');  // 批量导入工厂页面
                Route::post('batchFactory', 'ModelController@postBatchFactory')->name('entire-model.batchFactory.post');  // 批量导入工厂
                Route::get('batch', 'ModelController@getBatch')->name('entire-model.batch.get');  // 批量导入页面
                Route::post('batch', 'ModelController@postBatch')->name('entire-model.batch.post');  // 批量导入
                Route::get('/', 'ModelController@index')->name('entire-model.index');  // 列表
                Route::get('create', 'ModelController@create')->name('entire-model.create');  // 新建页面
                Route::post('/', 'ModelController@store')->name('entire-model.store');  // 新建
                Route::get('/{uniqueCode}', 'ModelController@show')->name('entire-model.show');  // 详情页面
                Route::get('/{uniqueCode}/edit', 'ModelController@edit')->name('entire-model.edit');  // 编辑页面
                Route::put('/{uniqueCode}', 'ModelController@update')->name('entire-model.update');  // 编辑
                Route::delete('/{uniqueCode}', 'ModelController@destroy')->name('entire-model.destroy');  // 删除
            });

            // 子类
            Route::group(['prefix' => 'subModel'], function () {
                Route::get('nextUniqueCode/{entireModelUniqueCode}', 'SubModelController@getNextUniqueCode')->name('web.subModel.getNextUniqueCode.get');
                Route::get('/', 'SubModelController@index')->name('web.subModel.index');  // 子类 列表
                Route::get('/create', 'SubModelController@create')->name('web.subModel.create');  // 子类 新建页面
                Route::post('/', 'SubModelController@store')->name('web.subModel.store');  // 子类 新建
                Route::get('/{id}', 'SubModelController@show')->name('web.subModel.show');  // 子类 详情页面
                Route::get('/{id}/edit', 'SubModelController@edit')->name('web.subModel.edit');  // 子类 编辑页面
                Route::put('/{id}', 'SubModelController@update')->name('web.subModel.update');  // 子类 编辑
                Route::delete('/{id}', 'SubModelController@destroy')->name('web.subModel.destroy');  // 子类 删除
            });

            // 日志
            Route::group(['prefix' => 'log'], function () {
                Route::get('/', 'LogController@index')->name('web.log.index');  // 日志 列表
                Route::get('/create', 'LogController@create')->name('web.log.create');  // 日志 新建页面
                Route::post('/', 'LogController@store')->name('web.log.store');  // 日志 新建
                Route::post('log/{identityCode}', 'LogController@postLog')->name('web.log.log');  // 日志补录
                Route::get('/{identity_code}', 'LogController@show')->name('web.log.show');  // 日志 详情页面
                Route::get('/{identity_code}/edit', 'LogController@edit')->name('web.log.edit');  // 日志 编辑页面
                Route::put('/{identity_code}', 'LogController@update')->name('web.log.update');  // 日志 编辑
                Route::delete('/{identity_code}', 'LogController@destroy')->name('web.log.destroy');  // 日志 删除
            });

            // 整件实例
            Route::group(['prefix' => 'instance'], function () {
                Route::put('updateBatch', 'InstanceController@putUpdateBatch')->name('web.entire.instance.putUpdateBatch');  // 批量修改
                Route::get('/{sn}/uploadEditDeviceReport', 'InstanceController@getUploadEditDeviceReport')->name('web.entire.instance.getUploadEditDeviceReport');  // 批量编辑报告页面
                Route::get('/uploadEditHistory', 'InstanceController@getUploadEditHistory')->name('web.entire.instance.getUploadEditHistory');  // 批量修改历史记录
                Route::get('/downloadUploadEditDeviceExcelTemplate', 'InstanceController@getDownloadUploadEditDeviceExcelTemplate')->name('web.entire.instance.getDownloadUploadEditDeviceExcelTemplate');  // 下载批量修改设备模板
                Route::get('/uploadEditDevice', 'InstanceController@getUploadEditDevice')->name('web.entire.instance.getUploadEditDevice');  // 设备批量修改页面
                Route::post('/uploadEditDevice', 'InstanceController@postUploadEditDevice')->name('web.entire.instance.postUploadEditDevice');  // 设备批量修改
                Route::get('installedAndInstalling', 'InstanceController@getInstalledAndInstall')->name('entire-instance.installedAndInstalling.get');  // 只显示上道和备品设备的列表，专给监控大屏使用
                Route::get('trashed', 'InstanceController@getTrashed')->name('entire-instance.trashed.get');  // 回收站
                Route::post('refresh', 'InstanceController@postRefresh')->name('entire-instance.refresh.post');  // 恢复
                Route::post('delete', 'InstanceController@postDelete')->name('entire-instance.deleted.post');  // 批量删除设备
                Route::get('upload', 'InstanceController@getUpload')->name('entire-instance.upload.get');  // 批量导入页面
                Route::post('upload', 'InstanceController@postUpload')->name('entire-instance.upload.post');  // 批量导入
                Route::get('getFactoryByEntireModelUniqueCode/{entireModelUniqueCode}', 'InstanceController@getFactoryByEntireModelUniqueCode')->name('entire-getFactoryByEntireModelUniqueCode');  // 根据类型或子类获取生产厂家
                Route::get('oldNumberToNew', 'InstanceController@getOldNumberToNew')->name('entire-oldNumberToNew.get');  // 旧编码更新到新编码页面
                Route::post('oldNumberToNew', 'InstanceController@postOldNumberToNew')->name('entire-oldNumberToNew.post');  // 旧编码更新到新编码
                Route::get('batch', 'InstanceController@getBatch')->name('entire-instance.batch.get');  // 批量上传页面
                Route::post('batch', 'InstanceController@postBatch')->name('entire-instance.batch.post');  // 批量上传
                Route::get('fixing', 'InstanceController@getFixing')->name('entire-instance.fixing.get');  // 检修入所页面
                Route::post('fixing', 'InstanceController@postFixing')->name('entire-instance.fixing.post');  // 检修入所页面
                Route::get('fixingIn/{identityCode}', 'InstanceController@getFixingIn')->name('entire-instance.fixingIn.get');  // 检修入所页面
                Route::post('fixingIn/{identityCode}', 'InstanceController@postFixingIn')->name('entire-instance.fixingIn.post');  // 检修入所页面
                Route::any('scrap/{identityCode}', 'InstanceController@scrap')->name('entire-instance.scrap');  // 报废整件
                Route::get('install', 'InstanceController@getInstall')->name('entire-instance.install.get');  // 安装出所页面
                Route::post('install/{entireInstanceIdentityCode}', 'InstanceController@postInstall')->name('entire-instance.install.post');  // 安装出所
                Route::get('deviceDynamicStatus', 'InstanceController@getDeviceDynamicStatus')->name('getDeviceDynamicStatus.get');  // 动态设备状态
                Route::get('/', 'InstanceController@index')->name('entire-instance.index');  // 列表
                Route::get('create', 'InstanceController@create')->name('entire-instance.create');  // 新建页面
                Route::post('/', 'InstanceController@store')->name('entire-instance.store');  // 新建
                Route::get('/{identityCode}', 'InstanceController@show')->name('entire-instance.show');  // 详情页面
                Route::get('/{identityCode}/edit', 'InstanceController@edit')->name('entire-instance.edit');  // 编辑页面
                Route::put('/{identityCode}', 'InstanceController@update')->name('entire-instance.update');  // 编辑
                Route::delete('/{identityCode}', 'InstanceController@destroy')->name('entire-instance.destroy');  // 删除
            });

            Route::group(['prefix' => 'modelIdCode'], function () {
                Route::get('/', 'ModelIdCodeController@index')->name('modelIdCode.index');  // 列表
                Route::get('create', 'ModelIdCodeController@create')->name('modelIdCode.create');  // 新建页面
                Route::post('/', 'ModelIdCodeController@store')->name('modelIdCode.store');  // 新建
                Route::get('/{code}', 'ModelIdCodeController@show')->name('modelIdCode.show');  // 详情页面
                Route::get('/{code}/edit', 'ModelIdCodeController@edit')->name('modelIdCode.edit');  // 编辑页面
                Route::put('/{code}', 'ModelIdCodeController@update')->name('modelIdCode.update');  // 编辑
                Route::delete('/{code}', 'ModelIdCodeController@destroy')->name('modelIdCode.destroy');  // 删除
            });

            // 设备锁
            Route::group(['prefix' => 'instanceLock'], function () {
                Route::get('/', 'InstanceLockController@index')->name('instanceLockController.index');  // 设备锁 列表页
            });

            // 整件赋码
            Route::group(['prefix' => 'tagging'], function () {
                Route::get("/start", "TaggingController@getStart")->name("web.entire.tagging.getStart");  // 设备器材赋码 选择赋码方式页面
                Route::get('/downloadOutDeviceCollateExcel/{serial_number}', 'TaggingController@getDownloadOutDeviceCollateExcel')->name('web.entire.tagging.getDownloadOutDeviceCollateExcel');  // 下载室外位置采集单
                Route::get('/create', 'TaggingController@create')->name('web.entire.tagging.create');  // 设备器材 整件赋码页面
                Route::post('/', 'TaggingController@store')->name('web.entire.tagging.store');  // 设备器材 整件赋码
                Route::post('/rollback/{id}', 'TaggingController@postRollback')->name('web.entire.tagging.postRollback');  // 设备器材 回退
                Route::get('/reportShow/{id}', 'TaggingController@getReportShow')->name('web.entire.tagging.getReportShow');  // 设备器材 详情
                Route::get('/report', 'TaggingController@getReport')->name('web.entire.tagging.getReport');  // 设备器材赋码 记录
                Route::get('/downloadUploadCreateDeviceExcelTemplate', 'TaggingController@getDownloadUploadCreateDeviceExcelTemplate')->name('web.entire.tagging.getDownloadUploadCreateDeviceExcelTemplate');  // 设备器材 下载赋码模板excel
                Route::get('/uploadCreateDevice', 'TaggingController@getUploadCreateDevice')->name('web.entire.tagging.getUploadCreateDevice');  // 设备器材赋码 上传页面
                Route::post('/uploadCreateDevice', 'TaggingController@postUploadCreateDevice')->name('web.entire.tagging.postUploadCreateDevice');  // 设备器材赋码 上传
                Route::get('/{serial_number}/uploadCreateDeviceReport', 'TaggingController@getUploadCreateDeviceReport')->name('web.entire.tagging.getUploadCreateDeviceReport');  // 设备器材赋码 上传结果页面
                Route::get('/{serial_number}/downloadCreateDeviceErrorExcel', 'TaggingController@getDownloadCreateDeviceErrorExcel')->name('web.entire.tagging.getDownloadCreateDeviceErrorExcel');  // 设备器材赋码 下载上传结果报告Excel
            });
        });

        // 部件
        Route::group(['prefix' => 'part', 'namespace' => 'Part'], function () {
            // 部件赋码
            Route::group(['prefix' => 'tagging'], function () {
                Route::get('/create', 'TaggingController@create')->name('web.part.tagging.create');  // 设备器材 整件赋码页面
                Route::post('/', 'TaggingController@store')->name('web.part.tagging.store');  // 设备器材 整件赋码
                Route::post('/rollback/{id}', 'TaggingController@postRollback')->name('web.part.tagging.postRollback');  // 设备器材 回退
                Route::get('/reportShow/{id}', 'TaggingController@getReportShow')->name('web.part.tagging.getReportShow');  // 设备器材 详情
                Route::get('/report', 'TaggingController@getReport')->name('web.part.tagging.getReport');  // 设备器材赋码 记录
                Route::get('/downloadUploadCreateDeviceExcelTemplate', 'TaggingController@getDownloadUploadCreateDeviceExcelTemplate')->name('web.part.tagging.getDownloadUploadCreateDeviceExcelTemplate');  // 设备器材 下载赋码模板excel
                Route::get('/uploadCreateDevice', 'TaggingController@getUploadCreateDevice')->name('web.part.tagging.getUploadCreateDevice');  // 设备器材赋码 上传页面
                Route::post('/uploadCreateDevice', 'TaggingController@postUploadCreateDevice')->name('web.part.tagging.postUploadCreateDevice');  // 设备器材赋码 上传
                Route::get('/{serial_number}/uploadCreateDeviceReport', 'TaggingController@getUploadCreateDeviceReport')->name('web.part.tagging.getUploadCreateDeviceReport');  // 设备器材赋码 上传结果页面
                Route::get('/{serial_number}/downloadCreateDeviceErrorExcel', 'TaggingController@getDownloadCreateDeviceErrorExcel')->name('web.part.tagging.getDownloadCreateDeviceErrorExcel');  // 设备器材赋码 下载上传结果报告Excel
            });

            // 部件种类型
            Route::group(['prefix' => 'kind'], function () {
                Route::get('/', 'KindController@index')->name('web.kind.index');  // 整件种类型 列表
                Route::post('/category', 'KindController@postCategory')->name('web.kind.postCategory');  // 整件种类型 添加种类
                Route::post('/entireModel', 'KindController@postEntireModel')->name('web.kind.postEntireModel');  // 整件种类型 添加类型
                Route::post('/subModel', 'KindController@postSubModel')->name('web.kind.postSubModel');  // 整件种类型 添加子类
                Route::get('/{unique_code}/category', 'KindController@getCategory')->name('web.kind.getCategory');  // 整件种类型 获取种类
                Route::put('/category', 'KindController@putCategory')->name('web.kind.putCategory');  // 整件种类型 修改种类
                Route::get('/{unique_code}/entireModel', 'KindController@getEntireModel')->name('web.kind.getEntireModel');  // 整件种类型 获取类型
                Route::put('/entireModel', 'KindController@putEntireModel')->name('web.kind.putEntireModel');  // 整件种类型 修改类型
                Route::get('/{unique_code}/subModel', 'KindController@getSubModel')->name('web.kind.getSubModel');  // 整件种类型 获取子类
                Route::put('/subModel', 'KindController@putSubModel')->name('web.kind.putSubModel');  // 整件种类型 编辑子类
            });

            // 部件种类
            Route::group(['prefix' => 'category'], function () {
                Route::get('/', 'CategoryController@index')->name('partCategory.index');  // 部件种类列表
                Route::get('/create', 'CategoryController@create')->name('partCategory.create');  // 部件种类新建页面
                Route::post('/', 'CategoryController@store')->name('partCategory.store');  // 部件种类新建
                Route::get('/{id}', 'CategoryController@show')->name('partCategory.show');  // 部件种类详情页面
                Route::get('/{id}/edit', 'CategoryController@edit')->name('partCategory.edit');  // 部件种类编辑页面
                Route::put('/{id}', 'CategoryController@update')->name('partCategory.update');  // 部件种类编辑
                Route::delete('/{id}', 'CategoryController@destroy')->name('partCategory.destroy');  // 部件种类删除
            });

            // 部件型号
            Route::group(['prefix' => 'model'], function () {
                Route::get('/', 'ModelController@index')->name('part-model.index');  // 部件型号列表
                Route::get('create', 'ModelController@create')->name('part-model.create');  // 部件型号新建页面
                Route::post('/', 'ModelController@store')->name('part-model.store');  // 部件型号新建
                Route::get('/{uniqueCode}', 'ModelController@show')->name('part-model.show');  // 部件型号详情页面
                Route::get('/{uniqueCode}/edit', 'ModelController@edit')->name('part-model.edit');  // 部件型号编辑页面
                Route::put('/{uniqueCode}', 'ModelController@update')->name('part-model.update');  // 部件型号编辑
                Route::delete('/{uniqueCode}', 'ModelController@destroy')->name('part-model.destroy');  // 部件型号删除
            });

            // 部件实例
            Route::group(['prefix' => 'instance'], function () {
                Route::get('/buyIn', 'InstanceController@getBuyIn')->name('part-instance.buyIn.get');  // 采购导入页面
                Route::post('/buyIn', 'InstanceController@postBuyIn')->name('part-instance.buyIn.post');  // 采购导入
                Route::get('/batch', 'InstanceController@getBatch')->name('part-instance.batch.get');  // 批量导入页面
                Route::post('/batch', 'InstanceController@postBatch')->name('part-instance.batch.post');  // 批量导入
                Route::get('/fixWorkflowRecode/{identityCode}', 'InstanceController@getFixWorkflowRecode')->name('part-instance.fixWorkflowRecode.get');  // 检测单页面
                Route::post('saveMeasuredValue/{identityCode}', 'InstanceController@saveMeasuredValue')->name('part-instance.saveMeasuredValue.post');  // 保存检测数据
                Route::get('/', 'InstanceController@index')->name('part-instance.index');  // 部件实例列表
                Route::get('create', 'InstanceController@create')->name('part-instance.create');  // 部件实例新建页面
                Route::post('/', 'InstanceController@store')->name('part-instance.store');  // 部件实例新建
                Route::get('/{identityCode}', 'InstanceController@show')->name('part-instance.show');  // 部件实例详情页面
                Route::get('/{identityCode}/edit', 'InstanceController@edit')->name('part-instance.edit');  // 部件实例编辑页面
                Route::put('/{identityCode}', 'InstanceController@update')->name('part-instance.update');  // 部件实例编辑
                Route::delete('/{identityCode}', 'InstanceController@destroy')->name('part-instance.destroy');  // 部件实例删除
            });
        });

        // 搜索
        Route::group(['prefix' => 'search'], function () {
            Route::get('search/bindCrossroadNumber/{bind_crossroad_number}', 'SearchController@getBindCrossroadNumber')->name('search.getBindCrossroadNumber');  // 绑定设备页面
            Route::get('/bindDevice/{bindDeviceCode}', 'SearchController@getBindDevice')->name('search.bindDevice');  // 绑定设备页面
            Route::get('/{entireInstanceIdentityCode}/timeline', 'SearchController@getTimeLine')->name('search.timeline');  // 搜索结果页面（时间线）
            Route::get('/', 'SearchController@index')->name('search.index');  // 搜索列表页面
            Route::post('/', 'SearchController@store')->name('search.store');  // 搜索条件
            Route::get('/{entireInstanceIdentityCode}', 'SearchController@show')->name('search.show');  // 搜索结果详情页面
        });

        // 二维码
        Route::group(['prefix' => 'qrcode'], function () {
            Route::get("printStorehouseQrCode", "QrCodeController@GetPrintStorehouseQrCode")->name("qrcode.GetPrintStorehouseQrCode");  // 打印仓二维码
            Route::get("printAreaQrCode", "QrCodeController@GetPrintAreaQrCode")->name("qrcode.GetPrintAreaQrCode");  // 打印区二维码
            Route::get("printPlatoonQrCode", "QrCodeController@GetPrintPlatoonQrCode")->name("qrcode.GetPrintPlatoonQrCode");  // 打印排二维码
            Route::get("printShelfQrCode", "QrCodeController@GetPrintShelfQrCode")->name("qrcode.GetPrintShelfQrCode");  // 打印架二维码
            Route::get("printTierQrCode", "QrCodeController@GetPrintTierQrCode")->name("qrcode.GetPrintTierQrCode");  // 打印层二维码
            Route::get('orCodeLocation', 'QrCodeController@printQrCodeWithLocation')->name('qrcode.printQrCode.location');  // 生成打印二维码页面仓库位置

            Route::get('printQrCodeMaterial', 'QrCodeController@getPrintQrCodeMaterial')->name('qrcode.getPrintQrMaterial');
            Route::get('installLocation', 'QrCodeController@qrcodeWithInstallLocation')->name('qrcode.install.location');  // 生成上道位置二维码页面
            Route::get('printLabel', 'QrCodeController@printLabel')->name('qrcode.printLabel');  // 生成打印标签
            Route::get('printQrCode', 'QrCodeController@printQrCode')->name('qrcode.printQrCode');  // 生成打印二维码页面
            Route::get('printOldLocationAndNewEntireInstance', 'QrCodeController@getPrintOldLocationAndNewEntireInstance')->name('qrcode.getPrintOldLocationAndNewEntireInstance');  // 打印新位置和老编号
            Route::get('printQrCodeAndLocation', 'QrCodeController@printQrCodeAndLocation')->name('qrcode.printQrCodeAndLocation');  // 生成打印二维码页面（带位置）
            Route::get('generateQrcode', 'QrCodeController@generateQrcode')->name('qrcode.generateQrcode');  // 生成二维码接口
            Route::get('parse', 'QrCodeController@parse')->name('qrcode.index');  // 解析二维码
            Route::get('make', 'QrCodeController@make')->name('qrcode.create');  // 创建二维码
            Route::get('{entireInstanceIdentityCode}', 'QrCodeController@show')->name('qrcode.show');  // 展示二维码
        });

        // 条形码
        Route::group(['prefix' => 'barcode'], function () {
            Route::get('printSerialNumber', 'BarCodeController@getPrintSerialNumber')->name('barcode.getPrintSerialNumber');  // 打印所编号条形码
            Route::post('printSerialNumber', 'BarCodeController@postPrintSerialNumber')->name('barcode.postPrintSerialNumber');  // 保存需要打印的条形码
            Route::get('/parse', 'BarCodeController@parse')->name("barcode.index");  // 解析条形码
            Route::get('/{entireInstanceIdentityCode}', "BarCodeController@show")->name("barcode.show");  // 展示条形码
        });

        // 报表
        Route::group(['prefix' => 'report'], function () {
            // 故障入所统计
            Route::prefix("breakdown")
                ->name("web.report.breakdown:")
                ->namespace("Report")
                ->group(function () {
                    Route::get("/", "BreakdownController@index")->name("index");
                });

            // 临时生产任务
            Route::get('temporaryTask/production/main', 'ReportController@getTemporaryTaskProductionMain')->name('temporaryTaskProductionMain.index');  // 临时生产任务 详情
            Route::get('temporaryTask/production/main/withCategory/{category_unique_code}', 'ReportController@getTemporaryTaskProductionMainWithCategory')->name('temporaryTaskProductionMainWithCategory.index');  // 临时生产任务 指定种类
            Route::get('temporaryTask/production/main/withEntireModel/{entire_model_unique_code}', 'ReportController@getTemporaryTaskProductionMainWithEntireModel')->name('temporaryTaskProductionMainWithEntireModel.index');  // 临时生产任务 指定类型
            Route::get('temporaryTask/production/main/withSubModel/{sub_model_unique_code}', 'ReportController@getTemporaryTaskProductionMainWithSubModel')->name('temporaryTaskProductionMainWithSubModel.index');  // 临时生产任务 指定型号

            // 周期修
            Route::get('cycleFix', 'Report\CycleFixController@cycleFix')->name('report.cycleFix.index');  // 周期修
            Route::get('cycleFixWithCategory/{categoryUniqueCode?}', 'Report\CycleFixController@cycleFixWithCategory')->name('reportCycleFix.withCategory');  // 周期修（种类视角）
            Route::get('cycleFixWithEntireModelAsMission/{entireModelUniqueCode?}', 'Report\CycleFixController@cycleFixWithEntireModelAsMission')->name('reportCycleFix.cycleFixWithEntireModelAsMission');  // 周期修（类型视角）
            Route::get('cycleFixWithEntireModelAsPlan2/{entireModelUniqueCode}', 'Report\CycleFixController@cycleFixWithEntireModelAsPlan2')->name('reportCycleFix.cycleFixWithEntireModelAsPlan2');  // 周期修（计划分配）
            Route::get('cycleFixWithEntireModelAsPlan/{categoryUniqueCode}', 'Report\CycleFixController@cycleFixWithEntireModelAsPlan')->name('reportCycleFix.cycleFixWithEntireModelAsPlan');  // 周期修（计划分配）
            Route::post('savePlan', 'Report\CycleFixController@savePlan')->name('reportCycleFix.savePlan');  // 周期修 保存计划
            Route::get('makeExcelWithPlan', 'Report\CycleFixController@makeExcelWithPlan')->name('reportCycleFix.makeExcelWithPlan');  // 周期修 生成计划Excel
            Route::get('cycleFix/reportForCategoriesWithYear', 'Report\CycleFixController@getReportForCategoriesWithYear')->name('reportCycleFix.getReportForCategoriesWithYear');  // 获取全年所有种类数据
            Route::get('cycleFix/reportForCategoryWithYear/{categoryUniqueCode}', 'Report\CycleFixController@getReportForCategoryWithYear')->name('reportCycleFix.getReportForCategoryWithYear');  // 获取全年指定种类数据
            Route::get('ripeYear', 'ReportController@ripeYear')->name('ripe.ripeYear');  // 一次过检 年度
            Route::get('ripeQuarter', 'ReportController@ripeQuarter')->name('ripe.ripeQuarter');  // 一次过检 季度
            Route::get('ripeMonth', 'ReportController@ripeMonth')->name('ripe.ripeMonth');  // 一次过检 月度
            Route::get('ripeCategoryYear/{category_unique_code}', 'ReportController@ripeCategoryYear')->name('ripe.ripeCategoryYear');  // 一次过检 指定种类 年度
            Route::get('ripeCategoryQuarter/{category_unique_code}', 'ReportController@ripeCategoryQuarter')->name('ripe.ripeCategoryQuarter');  // 一次过检 指定种类 季度
            Route::get('ripeCategoryMonth/{category_unique_code}', 'ReportController@ripeCategoryMonth')->name('ripe.ripeCategoryMonth');  // 一次过检 指定种类 月度
            Route::get('ripeEntireModelYear/{category_unique_code}', 'ReportController@ripeEntireModelYear')->name('ripe.ripeEntireModelYear');  // 一次过检 指定类型 年度
            Route::get('ripeEntireModelQuarter/{category_unique_code}', 'ReportController@ripeEntireModelQuarter')->name('ripe.ripeEntireModelQuarter');  // 一次过检 指定类型 季度
            Route::get('ripeEntireModelMonth/{category_unique_code}', 'ReportController@ripeEntireModelMonth')->name('ripe.ripeEntireModelMonth');  // 一次过检 指定类型 月度
            Route::get('ripeEntireInstance', 'ReportController@ripeEntireInstance')->name('report.ripeEntireInstance');  // 一次过检 设备列表
            Route::get('/', 'ReportController@index')->name('report.index');  // 列表
            Route::get('/workshop', 'ReportController@workshop')->name('report.workshop');  // 现场车间
            Route::get('/station/{stationName}', 'ReportController@station')->name('report.station');  // 车站
            Route::get('/onlyOnceFixed/{categoryUniqueCode}', 'ReportController@onlyOnceFixed')->name('reportOnlyOnceFixed');  // 一次过检
            Route::get('/work', 'ReportController@work')->name('report-work');  // 工作报表
            Route::get('fixWorkflow/{categoryCategory}/{entireModelUniqueCode?}', 'ReportController@fixWorkflow')->name('report.fixWorkflow');  // 检修单完成情况
            Route::get('download/{type}/{date?}', 'ReportController@download')->name('report.download');  // 下载统计数据


            // 质量报告
            Route::get('quality', 'Report\QualityController@quality')->name('report.quality.index');  // 质量报告
            Route::get('qualityCategory/{categoryUniqueCode}', 'Report\QualityController@qualityCategory')->name('report.quality.category');  // 质量报告 - 种类
            Route::get('qualitySceneWorkshop/{sceneWorkshopUniqueCode}', 'Report\QualityController@qualitySceneWorkshop')->name('report.quality.sceneWorkshop');  // 质量报告 - 现场车间
            Route::get('qualityStation/{sceneWorkshopUniqueCode}/{stationUniqueCode}', 'Report\QualityController@qualityStation')->name('report.quality.station');  // 质量报告 - 现场车间 - 车站
            Route::get('qualityBreakdownTypeWithCategory/{categoryUniqueCode}', 'Report\QualityController@qualityBreakdownTypeWithCategory')->name('report.quality.category.breakdown');  // 质量报告 - 种类 - 故障类型
            Route::get('qualityEntireInstance', 'Report\QualityController@qualityEntireInstance')->name('report.quality.entireInstance');  // 质量报告 设备列表

            // 资产管理
            Route::get('property', 'Report\PropertyController@property')->name('report.property.index');  // 资产管理
            Route::get('propertyCategory/{categoryUniqueCode}', 'Report\PropertyController@propertyCategory')->name('report.property.category');  // 资产管理（指定种类）
            Route::get('propertySubModel', 'Report\PropertyController@propertySubModel')->name('report.property.subModel');  // 资产管理（指定型号）

            // 超期使用
            Route::get('scraped', 'Report\ScrapedController@scraped')->name('report.scraped.index');  // 超期使用
            Route::get('scrapedWithCategory/{category_unique_code}', 'Report\ScrapedController@scrapedWithCategory')->name('report.scraped.category');  // 超期使用
            Route::get('scrapedWithEntireModel/{entire_model_unique_code}', 'Report\ScrapedController@scrapedWithEntireModel')->name('report.scraped.entireModel');  // 超期使用
            Route::get('scrapedWithSubModel/{model_unique_code?}', 'Report\ScrapedController@scrapedWithSubModel')->name('report.scraped.subModel');  // 超期使用

            // 台账
            Route::get('maintain', 'Report\MaintainController@index')->name('report-maintain.index');  // 台账
            Route::get('stationsWithSceneWorkshop/{sceneWorkshopUniqueCode}', 'Report\MaintainController@getStationsWithSceneWorkshop')->name('report-maintain.stationsWithSceneWorkshop');  // 某现场车间下车站统计
            Route::get('maintainEntireInstances', 'Report\MaintainController@getMaintainEntireInstances')->name('report-maintain.entireInstances');  // 台账设备列表
            Route::get('sceneWorkshopEntireInstances/{sceneWorkshopUniqueCode}', 'Report\MaintainController@sceneWorkshopEntireInstances')->name('reportEntire-maintain.sceneWorkshopEntireInstances');  // 现场车间统计，获取设备列表
            Route::get('entireModelsWithSceneWorkshopEntireInstances/{entireModelUniqueCode}', 'Report\MaintainController@entireModelsWithSceneWorkshopEntireInstances')->name('reportEntire-maintain.entireModelsWithSceneWorkshopEntireInstances');  // 根据种类获取类型列表（现场车间-设备列表）
            Route::get('subModelsWithSceneWorkshopEntireInstances/{subModelUniqueCode}', 'Report\MaintainController@subModelsWithSceneWorkshopEntireInstances')->name('reportEntire-maintain.subModelsWithSceneWorkshopEntireInstances');  // 根据类型获取型号和子类列表（现场车间-设备列表）
            Route::get('sceneWorkshop2/{sceneWorkshopUniqueCode}', 'Report\MaintainController@sceneWorkshop2')->name('reportEntire-maintain.sceneWorkshop2');  // 现场车间统计
            Route::get('sceneWorkshop/{sceneWorkshopUniqueCode}', 'Report\MaintainController@sceneWorkshop')->name('reportEntire-maintain.sceneWorkshop');  // 现场车间统计
            Route::get('sceneWorkshopWithAllCategory2/{sceneWorkshopUniqueCode}', 'Report\MaintainController@sceneWorkshopWithAllCategory2')->name('reportEntire-maintain.sceneWorkshopWithAllCategory2');  // 现场车间统计 全部种类
            Route::get('sceneWorkshopWithAllCategory/{sceneWorkshopUniqueCode}', 'Report\MaintainController@sceneWorkshopWithAllCategory')->name('reportEntire-maintain.sceneWorkshopWithAllCategory');  // 现场车间统计 全部种类
        });

        // 整件类型额外测试项关系
        Route::group(['prefix' => 'pivotEntireModelAndExtraTag'], function () {
            Route::get('/', 'PivotEntireModelAndExtraTagController@index')->name('pivotEntireModelAndExtraTag.index');  // 整件类型额外测试项关系列表
            Route::get('/create', 'PivotEntireModelAndExtraTagController@create')->name('pivotEntireModelAndExtraTag.create');  // 整件类型额外测试项关系新建页面
            Route::post('/', 'PivotEntireModelAndExtraTagController@store')->name('pivotEntireModelAndExtraTag.store');  // 整件类型额外测试项关系新建
            Route::get('/{id}', 'PivotEntireModelAndExtraTagController@show')->name('pivotEntireModelAndExtraTag.show');  // 整件类型额外测试项关系详情页面
            Route::get('/{id}/edit', 'PivotEntireModelAndExtraTagController@edit')->name('pivotEntireModelAndExtraTag.edit');  // 整件类型额外测试项关系编辑页面
            Route::put('/{id}', 'PivotEntireModelAndExtraTagController@update')->name('pivotEntireModelAndExtraTag.update');  // 整件类型额外测试项关系编辑
            Route::delete('/{id}', 'PivotEntireModelAndExtraTagController@destroy')->name('pivotEntireModelAndExtraTag.destroy');  // 整件类型额外测试项关系删除
        });

        // 整件实例额外测试项关系
        Route::group(['prefix' => 'pivotEntireInstanceAndExtraTag'], function () {
            Route::get('/', 'PivotEntireInstanceAndExtraTagController@index')->name('pivotEntireInstanceAndExtraTag.index');  // 整件实例额外测试项关系列表
            Route::get('/create', 'PivotEntireInstanceAndExtraTagController@create')->name('pivotEntireInstanceAndExtraTag.create');  // 整件实例额外测试项关系新建页面
            Route::post('/', 'PivotEntireInstanceAndExtraTagController@store')->name('pivotEntireInstanceAndExtraTag.store');  // 整件实例额外测试项关系新建
            Route::get('/{id}', 'PivotEntireInstanceAndExtraTagController@show')->name('pivotEntireInstanceAndExtraTag.show');  // 整件实例额外测试项关系详情页面
            Route::get('/{id}/edit', 'PivotEntireInstanceAndExtraTagController@edit')->name('pivotEntireInstanceAndExtraTag.edit');  // 整件实例额外测试项关系编辑页面
            Route::put('/{id}', 'PivotEntireInstanceAndExtraTagController@update')->name('pivotEntireInstanceAndExtraTag.update');  // 整件实例额外测试项关系编辑
            Route::delete('/{id}', 'PivotEntireInstanceAndExtraTagController@destroy')->name('pivotEntireInstanceAndExtraTag.destroy');  // 整件实例额外测试项关系删除
        });

        // 类型与工厂绑定关系
        Route::group(['prefix' => 'pivotEntireModelAndFactory'], function () {
            Route::get('/', 'PivotEntireModelAndFactoryController@index')->name('pivotEntireModelAndFactory.index');  // 类型与工厂绑定关系列表
            Route::get('/create', 'PivotEntireModelAndFactoryController@create')->name('pivotEntireModelAndFactory.create');  // 类型与工厂绑定关系新建页面
            Route::post('/', 'PivotEntireModelAndFactoryController@store')->name('pivotEntireModelAndFactory.store');  // 类型与工厂绑定关系新建
            Route::get('/{id}', 'PivotEntireModelAndFactoryController@show')->name('pivotEntireModelAndFactory.show');  // 类型与工厂绑定关系详情页面
            Route::get('/{id}/edit', 'PivotEntireModelAndFactoryController@edit')->name('pivotEntireModelAndFactory.edit');  // 类型与工厂绑定关系编辑页面
            Route::put('/{id}', 'PivotEntireModelAndFactoryController@update')->name('pivotEntireModelAndFactory.update');  // 类型与工厂绑定关系编辑
            Route::delete('/{id}', 'PivotEntireModelAndFactoryController@destroy')->name('pivotEntireModelAndFactory.destroy');  // 类型与工厂绑定关系删除
        });

        // 故障描述
        Route::group(['prefix' => 'breakdownLog'], function () {
            Route::get('/', 'BreakdownLogController@index')->name('web.breakdownLog.index');  // 故障描述 列表
            Route::get('/create', 'BreakdownLogController@create')->name('web.breakdownLog.create');  // 故障描述 新建页面
            Route::post('/', 'BreakdownLogController@store')->name('web.breakdownLog.store');  // 故障描述 新建
            Route::get('/{id}', 'BreakdownLogController@show')->name('web.breakdownLog.show');  // 故障描述 详情页面
            Route::get('/{id}/edit', 'BreakdownLogController@edit')->name('web.breakdownLog.edit');  // 故障描述 编辑页面
            Route::put('/{id}', 'BreakdownLogController@update')->name('web.breakdownLog.update');  // 故障描述 编辑
            Route::delete('/{id}', 'BreakdownLogController@destroy')->name('web.breakdownLog.destroy');  // 故障描述 删除
        });

        // 故障类型
        Route::group(['prefix' => 'breakdownType'], function () {
            Route::get('/', 'BreakdownTypeController@index')->name('web.breakdownType.index');  // 故障类型 列表
            Route::get('/create', 'BreakdownTypeController@create')->name('web.breakdownType.create');  // 故障类型 新建页面
            Route::post('/', 'BreakdownTypeController@store')->name('web.breakdownType.store');  // 故障类型 新建
            Route::get('/{id}', 'BreakdownTypeController@show')->name('web.breakdownType.show');  // 故障类型 详情页面
            Route::get('/{id}/edit', 'BreakdownTypeController@edit')->name('web.breakdownType.edit');  // 故障类型 编辑页面
            Route::put('/{id}', 'BreakdownTypeController@update')->name('web.breakdownType.update');  // 故障类型 编辑
            Route::delete('/{id}', 'BreakdownTypeController@destroy')->name('web.breakdownType.destroy');  // 故障类型 删除
        });

        // 消息
        Route::group(['prefix' => 'message'], function () {
            Route::get('/input', 'MessageController@getInput')->name('message.input.index');  // 消息 列表 收件箱
            Route::get('send', 'MessageController@getSend')->name('message.send.index');  // 消息 列表 发件箱
            Route::put('/markStar/{message_id}', 'MessageController@putMarkStar')->name('message.markStar');  // 消息 星标s
            Route::get('/reply', 'MessageController@getReply')->name('message.reply.create');  // 消息 回复 页面
            Route::get('/', 'MessageController@index')->name('message.index');  // 消息 列表
            Route::get('/create', 'MessageController@create')->name('message.create');  // 消息 新建页面
            Route::post('/', 'MessageController@store')->name('message.store');  // 消息 新建
            Route::get('/{id}', 'MessageController@show')->name('message.show');  // 消息 详情页面
            Route::get('/{id}/edit', 'MessageController@edit')->name('message.edit');  // 消息 编辑页面
            Route::put('/{id}', 'MessageController@update')->name('message.update');  // 消息 编辑
            Route::delete('/{id}', 'MessageController@destroy')->name('message.destroy');  // 消息 删除
        });

        // 临时生产任务
        Route::group(['prefix' => 'tempTask'], function () {
            Route::put('/{id}/caa', 'TempTaskController@putCAA')->name('web.tempTask.putCAA');  // 临时生产任务 验收
            Route::put('/{id}/publish', 'TempTaskController@putPublish')->name('web.tempTask.putPublish');  // 临时生产任务 发布
            Route::get('/', 'TempTaskController@index')->name('web.tempTask.index');  // 临时生产任务 列表
            Route::get('/create', 'TempTaskController@create')->name('web.tempTask.create');  // 临时生产任务 新建页面
            Route::post('/', 'TempTaskController@store')->name('web.tempTask.store');  // 临时生产任务 新建
            Route::get('/{id}', 'TempTaskController@show')->name('web.tempTask.show');  // 临时生产任务 详情页面
            Route::get('/{id}/edit', 'TempTaskController@edit')->name('web.tempTask.edit');  // 临时生产任务 编辑页面
            Route::put('/{id}', 'TempTaskController@update')->name('web.tempTask.update');  // 临时生产任务 编辑
            Route::delete('/{id}', 'TempTaskController@destroy')->name('web.tempTask.destroy');  // 临时生产任务 删除
        });

        // 临时生产任务附件
        Route::group(['prefix' => 'tempTaskAccessory'], function () {
            Route::get('/download/{id}', 'TempTaskAccessoryController@getDownload')->name('web.tempTask.getDownload');
            Route::delete('/{id}', 'TempTaskAccessoryController@destroy')->name('web.tempTaskAccessory.destroy');  // 临时生产任务附件 删除
        });

        // 临时生产子任务
        Route::group(['prefix' => 'tempTaskSubOrder'], function () {
            Route::get('/{id}/bindEntireInstance', 'TempTaskSubOrderController@getBindEntireInstance')->name('web.tempTaskSubOrder.getBindEntireInstance');  // 临时生产任务 绑定位置页面
            Route::post('/bindEntireInstance', 'TempTaskSubOrderController@postBindEntireInstance')->name('web.tempTaskSubOrder.postBindEntireInstance');  // 临时生产任务 绑定位置
            Route::delete('/bindEntireInstance', 'TempTaskSubOrderController@deleteBindEntireInstance')->name('web.tempTaskSubOrder.deleteBindEntireInstance');  // 临时生产任务 解绑
            Route::delete('/bindEntireInstances', 'TempTaskSubOrderController@deleteBindEntireInstances')->name('web.tempTaskSubOrder.deleteBindEntireInstances');  // 临时生产任务 全部解绑
            Route::post('/autoBindEntireInstance', 'TempTaskSubOrderController@postAutoBindEntireInstance')->name('web.tempTaskSubOrder.postAutoBindEntireInstance');  // 临时生产任务 自动绑定
            Route::post('/autoBindEntireInstances', 'TempTaskSubOrderController@postAutoBindEntireInstances')->name('web.tempTaskSubOrder.postAutoBindEntireInstances');  // 临时生产任务 自动绑定全部
            Route::get('/help', 'TempTaskSubOrderController@getHelp')->name('web.tempTaskSubOrder.getHelp');  // 获取帮助信息
            Route::get('/{id}/warehouse', 'TempTaskSubOrderController@getWarehouse')->name('web.tempTaskSubOrder.getWarehouse');  // 临时生产任务 出入所扫码页面
            Route::post('/{id}/warehouse', 'TempTaskSubOrderController@postWarehouse')->name('web.tempTaskSubOrder.postWarehouse');  // 临时生产任务 出入所
            Route::put('/{id}/delivery', 'TempTaskSubOrderController@putDelivery')->name('web.tempTaskSubOrder.putDelivery');  // 临时生产子任务 任务交付
            Route::get('/', 'TempTaskSubOrderController@index')->name('web.tempTaskSubOrder.index');  // 临时生产子任务 列表
            Route::get('/create', 'TempTaskSubOrderController@create')->name('web.tempTaskSubOrder.create');  // 临时生产子任务 新建页面
            Route::post('/', 'TempTaskSubOrderController@store')->name('web.tempTaskSubOrder.store');  // 临时生产子任务 新建
            Route::get('/{id}', 'TempTaskSubOrderController@show')->name('web.tempTaskSubOrder.show');  // 临时生产子任务 详情页面
            Route::get('/{id}/edit', 'TempTaskSubOrderController@edit')->name('web.tempTaskSubOrder.edit');  // 临时生产子任务 编辑页面
            Route::put('/{id}', 'TempTaskSubOrderController@update')->name('web.tempTaskSubOrder.update');  // 临时生产子任务 编辑
            Route::delete('/{id}', 'TempTaskSubOrderController@destroy')->name('web.tempTaskSubOrder.destroy');  // 临时生产子任务 删除
        });

        // 临时生产子任务 型号
        Route::group(['prefix' => 'tempTaskSubOrderModel'], function () {
            Route::get('/', 'TempTaskSubOrderModelController@index')->name('web.tempTaskSubOrderModel.index');  // 临时生产子任务 型号 列表
            Route::get('/create', 'TempTaskSubOrderModelController@create')->name('web.tempTaskSubOrderModel.create');  // 临时生产子任务 型号 新建页面
            Route::post('/', 'TempTaskSubOrderModelController@store')->name('web.tempTaskSubOrderModel.store');  // 临时生产子任务 型号 新建
            Route::get('/{id}', 'TempTaskSubOrderModelController@show')->name('web.tempTaskSubOrderModel.show');  // 临时生产子任务 型号 详情页面
            Route::get('/{id}/edit', 'TempTaskSubOrderModelController@edit')->name('web.tempTaskSubOrderModel.edit');  // 临时生产子任务 型号 编辑页面
            Route::put('/{id}', 'TempTaskSubOrderModelController@update')->name('web.tempTaskSubOrderModel.update');  // 临时生产子任务 型号 编辑
            Route::delete('/{id}', 'TempTaskSubOrderModelController@destroy')->name('web.tempTaskSubOrderModel.destroy');  // 临时生产子任务 型号 删除
        });

        // 临时生产子任务 设备
        Route::group(['prefix' => 'tempTaskSubOrderEntireInstance'], function () {
            Route::get('/entireInstances', 'TempTaskSubOrderEntireInstanceController@getEntireInstances')->name('web.tempTaskSubOrderEntireInstance.getEntireInstances');  // 获取设备列表
            Route::post('/entireInstances', 'TempTaskSubOrderEntireInstanceController@postEntireInstances')->name('web.tempTaskSubOrderEntireInstance.postEntireInstances');  // 获取设备列表 添加设备到任务单中
            Route::post('/{newEntireInstanceIdentityCode}/scanForWarehouse', 'TempTaskSubOrderEntireInstanceController@postScanForWarehouse')->name('web.tempTaskSubOrderEntireInstance.postScanForWarehouse');  // 临时生产子任务 设备 出入所扫码
            Route::delete('/{tempTaskSubOrderEntireInstanceId}/scanForWarehouse', 'TempTaskSubOrderEntireInstanceController@deleteScanForWarehouse')->name('web.tempTaskSubOrderEntireInstance.deleteScanForWarehouse');  // 临时生产子任务 设备 出入所扫码 删除
            Route::delete('/entireInstances', 'TempTaskSubOrderEntireInstanceController@deleteEntireInstances')->name('web.tempTaskSubOrderEntireInstance.deleteEntireInstances');  // 临时声场任务 设备 删除
            Route::get('/', 'TempTaskSubOrderEntireInstanceController@index')->name('web.tempTaskSubOrderEntireInstance.index');  // 临时生产子任务 设备 列表
            Route::get('/create', 'TempTaskSubOrderEntireInstanceController@create')->name('web.tempTaskSubOrderEntireInstance.create');  // 临时生产子任务 设备 新建页面
            Route::post('/', 'TempTaskSubOrderEntireInstanceController@store')->name('web.tempTaskSubOrderEntireInstance.store');  // 临时生产子任务 设备 新建
            Route::get('/{id}', 'TempTaskSubOrderEntireController@show')->name('web.tempTaskSubOrderEntireInstance.show');  // 临时生产子任务 设备 详情页面
            Route::get('/{id}/edit', 'TempTaskSubOrderEntireInstanceController@edit')->name('web.tempTaskOrderSubEntireInstance.edit');  // 临时生产子任务 设备 编辑页面
            Route::put('/{id}', 'TempTaskSubOrderEntireInstanceController@update')->name('web.tempTaskSubOrderEntireInstance.update');  // 临时生产子任务 设备 编辑
            Route::delete('/{id}', 'TempTaskSubOrderEntireInstanceController@destroy')->name('web.tempTaskSubOrderEntireInstance.destroy');  // 临时生产子任务 设备 删除
        });

        // 公文管理
        Route::group(['prefix' => 'officialDocument'], function () {
            Route::get('/', 'OfficialDocumentController@index')->name('OfficialDocument.index');  // 公文管理 列表
            Route::get('/create', 'OfficialDocumentController@create')->name('OfficialDocument.create');  // 公文管理 新建页面
            Route::post('/', 'OfficialDocumentController@store')->name('OfficialDocument.store');  // 公文管理 新建
            Route::get('/{id}', 'OfficialDocumentController@show')->name('OfficialDocument.show');  // 公文管理 详情页面
            Route::get('/{id}/edit', 'OfficialDocumentController@edit')->name('OfficialDocument.edit');  #公文管理  编辑页面
            Route::put('/{id}', 'OfficialDocumentController@update')->name('OfficialDocument.update');  // 公文管理 编辑
            Route::delete('/{id}', 'OfficialDocumentController@destroy')->name('OfficialDocument.destroy');  // 公文管理 删除
        });

        // 线别管理
        Route::group(['prefix' => 'line'], function () {
            Route::get('/', 'LineController@index')->name('line.index');  // 线别管理 列表
            Route::get('/create', 'LineController@create')->name('line.create');  // 线别管理 新建页面
            Route::post('/', 'LineController@store')->name('line.store');  // 线别管理 新建
            Route::get('/{id}', 'LineController@show')->name('line.show');  // 线别管理 详情页面
            Route::get('/{id}/edit', 'LineController@edit')->name('line.edit');  #线别管理  编辑页面
            Route::put('/{id}', 'LineController@update')->name('line.update');  // 线别管理 编辑
            Route::delete('/{id}', 'LineController@destroy')->name('line.destroy');  // 线别管理 删除
        });

        // 检修管理
        Route::group(['prefix' => 'fixMissionOrder'], function () {
            Route::get('/', 'FixMissionOrderController@index')->name('fixMissionOrder.index');  // 检修管理 列表
            Route::post('/{workAreaId}', 'FixMissionOrderController@fixMissionOrder')->name('fixMissionOrder.index');  // 检修管理 根据工区获取检修时间
            Route::get('/DownloadExcel', 'FixMissionOrderController@DownloadExcel')->name('DownloadExcel.index');  // 检修管理 下载Excel
            Route::post('/UploadExcel', 'FixMissionOrderController@UploadExcel')->name('UploadExcel.post');  // 检修管理 上传Excel导入设备验收日期
            Route::get('/create', 'FixMissionOrderController@create')->name('fixMissionOrder.create');  // 检修管理 新建页面
            Route::post('/', 'FixMissionOrderController@store')->name('fixMissionOrder.store');  // 检修管理 新建
            Route::get('/{id}', 'FixMissionOrderController@show')->name('fixMissionOrder.show');  // 检修管理 详情页面
            Route::get('/{id}/edit', 'FixMissionOrderController@edit')->name('fixMissionOrder.edit');  #检修管理  编辑页面
            Route::put('/{id}', 'FixMissionOrderController@update')->name('fixMissionOrder.update');  // 检修管理 编辑
            Route::delete('/{id}', 'FixMissionOrderController@destroy')->name('fixMissionOrder.destroy');  // 检修管理 删除
        });

        // 数据采集
        Route::group(['prefix' => 'collectDeviceOrder'], function () {
            Route::get('/{serial_number}/download', 'CollectDeviceOrderController@getDownload')->name('web.collectDeviceOrder.getDownload');  // 数据采集 下载Excel
            Route::get('/', 'CollectDeviceOrderController@index')->name('web.collectDeviceOrder.index');  // 数据采集 列表
            Route::get('/create', 'CollectDeviceOrderController@create')->name('web.collectDeviceOrder.create');  // 数据采集 新建页面
            Route::post('/', 'CollectDeviceOrderController@store')->name('web.collectDeviceOrder.store');  // 数据采集 新建
            Route::get('/{serial_number}', 'CollectDeviceOrderController@show')->name('web.collectDeviceOrder.show');  // 数据采集 详情页面
            Route::get('/{serial_number}/edit', 'CollectDeviceOrderController@edit')->name('web.collectDeviceOrder.edit');  // 数据采集 编辑页面
            Route::put('/{serial_number}', 'CollectDeviceOrderController@update')->name('web.collectDeviceOrder.update');  // 数据采集 编辑
            Route::delete('/{serial_number}', 'CollectDeviceOrderController@destroy')->name('web.collectDeviceOrder.destroy');  // 数据采集 删除
        });

        // 2.5.0新版任务单
        Route::group(['prefix' => 'v250TaskOrder'], function () {
            Route::post('/{sn}/delivery', 'V250TaskOrderController@postDelivery')->name('web.v250TaskOrder.postDelivery');  // 2.5.0新版任务 交付任务
            Route::get('/{sn}/downloadEditDeviceErrorExcel', 'V250TaskOrderController@getDownloadEditDeviceErrorExcel')->name('web.v250TaskOrder.getDownloadEditDeviceErrorExcel');  // 2.5.0新版任务 下载上传设备数据补充excel错误报告
            Route::get('/{sn}/downloadCheckDeviceErrorExcel', 'V250TaskOrderController@getDownloadCheckDeviceErrorExcel')->name('web.v250TaskOrder.getDownloadCheckDeviceErrorExcel');  // 2.5.0新版任务 下载上传验收设备excel错误报告
            Route::get('/{sn}/downloadInstallLocationErrorExcel', 'V250TaskOrderController@getDownloadInstallLocationErrorExcel')->name('web.v250TaskOrder.getDownloadInstallLocationErrorExcel');  // 2.5.0新版任务 下载上传上道位置excel错误报告
            Route::get('/{sn}/downloadCreateDeviceErrorExcel', 'V250TaskOrderController@getDownloadCreateDeviceErrorExcel')->name('web.v250TaskOrder.getDownloadCreateDeviceErrorExcel');  // 2.5.0新版任务 下载设备赋码excel错误报告
            Route::get('/downloadUploadEditDeviceExcelTemplate', 'V250TaskOrderController@getDownloadUploadEditDeviceExcelTemplate')->name('web.v250TaskOrder.getDownloadUploadEditDeviceExcelTemplate');  // 2.5.0新版任务单 下载上传设备数据补充Excel模板
            Route::get('/downloadUploadCheckDeviceExcelTemplate', 'V250TaskOrderController@getDownloadUploadCheckDeviceExcelTemplate')->name('web.v250TaskOrder.getDownloadUploadCheckDeviceExcelTemplate');  // 2.5.0新版任务单 下载上传验收设备Excel模板
            Route::get('/downloadUploadInstallLocationExcelTemplate', 'V250TaskOrderController@getDownloadUploadInstallLocationExcelTemplate')->name('web.v250TaskOrder.getDownloadUploadInstallLocationExcelTemplate');  // 2.5.0新版任务单 下载上传上到位置Excel模板
            Route::get('/downloadUploadCreateDeviceExcelTemplate', 'V250TaskOrderController@getDownloadUploadCreateDeviceExcelTemplate')->name('web.v250TaskOrder.getDownloadUploadCreateDeviceExcelTemplate');  // 2.5.0新版任务单 下载上传设备Excel模板
            Route::get('/{sn}/uploadEditDeviceReport', 'V250TaskOrderController@getUploadEditDeviceReport')->name('web.v250TaskOrder.getUploadEditDeviceReport');  // 2.5.0新版任务 设备数据补充结果页面
            Route::get('/{sn}/uploadEditDevice', 'V250TaskOrderController@getUploadEditDevice')->name('web.v250TaskOrder.getUploadEditDevice');  // 2.5.0新版任务单 设备数据补充信息页面
            Route::post('/{sn}/uploadEditDevice', 'V250TaskOrderController@postUploadEditDevice')->name('web.v250TaskOrder.postUploadEditDevice');  // 2.5.0新版任务单 设备数据补充信息
            Route::get('/{sn}/uploadCheckDeviceReport', 'V250TaskOrderController@getUploadCheckDeviceReport')->name('web.v250TaskOrder.getUploadCheckDeviceReport');  // 2.5.0新版任务 设备验收结果页面
            Route::get('/{sn}/uploadCheckDevice', 'V250TaskOrderController@getUploadCheckDevice')->name('web.v250TaskOrder.getUploadCheckDevice');  // 2.5.0新版任务单 设备验收信息页面
            Route::post('/{sn}/uploadCheckDevice', 'V250TaskOrderController@postUploadCheckDevice')->name('web.v250TaskOrder.postUploadCheckDevice');  // 2.5.0新版任务单 设备验收信息
            Route::get('/{sn}/uploadInstallLocation', 'V250TaskOrderController@getUploadInstallLocation')->name('web.v250TaskOrder.getUploadInstallLocation');  // 2.5.0新版任务单 上传位置信息页面
            Route::post('/{sn}/uploadInstallLocation', 'V250TaskOrderController@postUploadInstallLocation')->name('web.v250TaskOrder.postUploadInstallLocation');  // 2.5.0新版任务单 上传位置信息
            Route::get('/{sn}/uploadCreateDeviceReport', 'V250TaskOrderController@getUploadCreateDeviceReport')->name('web.v250TaskOrder.getUploadCreateDeviceReport');  // 2.5.0新版任务 新品赋码结果页面
            Route::get('/{sn}/uploadCreateDevice', 'V250TaskOrderController@getUploadCreateDevice')->name('web.v250TaskOrder.getUploadCreateDevice');  // 2.5.0新版任务单 新品赋码页面
            Route::post('/{sn}/uploadCreateDevice', 'V250TaskOrderController@postUploadCreateDevice')->name('web.v250TaskOrder.postUploadCreateDevice');  // 2.5.0新版任务单 新品赋码
            Route::get('/', 'V250TaskOrderController@index')->name('web.v250TaskOrder.index');  // 2.5.0新版任务单 列表
            Route::get('/create', 'V250TaskOrderController@create')->name('web.v250TaskOrder.create');  // 2.5.0新版任务单 新建页面
            Route::post('/', 'V250TaskOrderController@store')->name('web.v250TaskOrder.store');  // 2.5.0新版任务单 新建
            Route::get('/{sn}', 'V250TaskOrderController@show')->name('web.v250TaskOrder.show');  // 2.5.0新版任务单 详情页面
            Route::get('/{sn}/edit', 'V250TaskOrderController@edit')->name('web.v250TaskOrder.edit');  // 2.5.0新版任务单 编辑页面
            Route::put('/{sn}', 'V250TaskOrderController@update')->name('web.v250TaskOrder.update');  // 2.5.0新版任务单 编辑
            Route::delete('/{sn}', 'V250TaskOrderController@destroy')->name('web.v250TaskOrder.destroy');  // 2.5.0新版任务单 删除
        });

        // 2.5.0新版任务设备
        Route::group(['prefix' => 'v250TaskEntireInstance'], function () {
            Route::delete('/{sn}/items', 'V250TaskEntireInstanceController@deleteItems')->name('web.V250TaskEntireInstance.deleteItems');  // 2.5.0新版任务设备 批量删除
            Route::get('/', 'V250TaskEntireInstanceController@index')->name('web.v250TaskEntireInstance.index');  // 2.5.0新版任务设备 列表
            Route::get('/create', 'V250TaskEntireInstanceController@create')->name('web.v250TaskEntireInstance.create');  // 2.5.0新版任务设备 新建页面
            Route::post('/', 'V250TaskEntireInstanceController@store')->name('web.v250TaskEntireInstance.store');  // 2.5.0新版任务设备 新建
            Route::get('/{id}', 'V250TaskEntireInstanceController@show')->name('web.v250TaskEntireInstance.show');  // 2.5.0新版任务设备 详情页面
            Route::get('/{id}/edit', 'V250TaskEntireInstanceController@edit')->name('web.v250TaskEntireInstance.edit');  // 2.5.0新版任务设备 编辑页面
            Route::put('/{id}', 'V250TaskEntireInstanceController@update')->name('web.v250TaskEntireInstance.update');  // 2.5.0新版任务设备 编辑
            Route::delete('/{id}', 'V250TaskEntireInstanceController@destroy')->name('web.v250TaskEntireInstance.destroy');  // 2.5.0新版任务设备 删除
            Route::post('/{sn}/judgeService', 'V250TaskEntireInstanceController@judgeService')->name('web.v250TaskEntireInstance.judgeService');  // 2.5.0新版任务设备 检修分配判断
            Route::post('/{sn}/storeService', 'V250TaskEntireInstanceController@storeService')->name('web.v250TaskEntireInstance.storeService');  // 2.5.0新版任务设备 检修分配
            Route::post('/{sn}/judgeWorkshopOut', 'V250TaskEntireInstanceController@judgeWorkshopOut')->name('web.v250TaskEntireInstance.judgeWorkshopOut');  // 2.5.0新版任务设备 添加待出所单->设备状态判断
            Route::post('/{sn}/workshopOut', 'V250TaskEntireInstanceController@workshopOut')->name('web.v250TaskEntireInstance.workshopOut');  // 2.5.0新版任务设备 出所
        });

        // 2.5.0新站任务->现场退回(入所)
        Route::group(['prefix' => 'v250WorkshopIn'], function () {
            Route::get('/', 'V250WorkshopInController@index')->name('web.v250WorkshopIn.index');  // 2.5.0新站任务->现场退回(入所) 列表
            Route::get('/create', 'V250WorkshopInController@create')->name('web.v250WorkshopIn.create');  // 2.5.0新站任务->现场退回(入所) 新建页面
            Route::post('/', 'V250WorkshopInController@store')->name('web.v250WorkshopIn.store');  // 2.5.0新站任务->现场退回(入所) 新建
            Route::get('/{id}', 'V250WorkshopInController@show')->name('web.v250WorkshopIn.show');  // 2.5.0新站任务->现场退回(入所) 详情页面
            Route::get('/{id}/edit', 'V250WorkshopInController@edit')->name('web.v250WorkshopIn.edit');  // 2.5.0新站任务->现场退回(入所) 编辑页面
            Route::put('/{id}', 'V250WorkshopInController@update')->name('web.v250WorkshopIn.update');  // 2.5.0新站任务->现场退回(入所) 编辑
            Route::delete('/{id}', 'V250WorkshopInController@destroy')->name('web.v250WorkshopIn.destroy');  // 2.5.0新站任务->现场退回(入所) 删除
            Route::post('/{sn}/destroyAll', 'V250WorkshopInController@destroyAll')->name('web.v250WorkshopIn.destroyAll');  // 2.5.0新站任务->现场退回(入所) 清空
            Route::post('/{sn}/scanCode', 'V250WorkshopInController@scanCode')->name('web.v250WorkshopIn.scanCode');  // 2.5.0新站任务->现场退回(入所) 扫码判断
            Route::post('/{sn}/workshopIn', 'V250WorkshopInController@workshopIn')->name('web.v250WorkshopIn.workshopIn');  // 2.5.0新站任务->现场退回(入所) 入所操作

            // Route::get('print/{warehouseReportSerialNumber}', 'V250WorkshopInController@print')->name('warehouseReport.print');  // 打印页面
            // Route::get('scanBatch', 'V250WorkshopInController@getScanBatch')->name('warehouseReport.scanBatch.get');  // 批量扫码入所 页面2
            // Route::post('scanBatch', 'V250WorkshopInController@postScanBatch')->name('warehouseReport.scanBatch.post');  // 批量扫码入所2
            // Route::delete('scanBatch/{id}', 'V250WorkshopInController@deleteScanBatch')->name('warehouseReport.scanBatch.delete');  // 删除扫码设备
            // Route::post('scanBatchWarehouse', 'V250WorkshopInController@postScanBatchWarehouse')->name('warehouseReport.scanBatchWarehouse.post');  // 出入所
        });

        // 2.5.0新站任务->出所
        Route::group(['prefix' => 'v250WorkshopOut'], function () {
            Route::get('/', 'V250WorkshopOutController@index')->name('web.v250WorkshopOut.index');  // 2.5.0新站任务->出所 列表
            Route::get('/create', 'V250WorkshopOutController@create')->name('web.v250WorkshopOut.create');  // 2.5.0新站任务->出所 新建页面
            Route::post('/', 'V250WorkshopOutController@store')->name('web.v250WorkshopOut.store');  // 2.5.0新站任务->出所 新建
            Route::get('/{id}', 'V250WorkshopOutController@show')->name('web.v250WorkshopOut.show');  // 2.5.0新站任务->出所 详情页面
            Route::get('/{id}/edit', 'V250WorkshopOutController@edit')->name('web.v250WorkshopOut.edit');  // 2.5.0新站任务->出所 编辑页面
            Route::put('/{id}', 'V250WorkshopOutController@update')->name('web.v250WorkshopOut.update');  // 2.5.0新站任务->出所 编辑
            Route::delete('/{id}', 'V250WorkshopOutController@destroy')->name('web.v250WorkshopOut.destroy');  // 2.5.0新站任务->出所 删除
            Route::post('/{sn}/destroyAll', 'V250WorkshopOutController@destroyAll')->name('web.v250WorkshopOut.destroyAll');  // 2.5.0新站任务->出所 清空
            Route::post('/{sn}/scanCode', 'V250WorkshopOutController@scanCode')->name('web.v250WorkshopOut.scanCode');  // 2.5.0新站任务->出所 扫码判断
            Route::post('/{sn}/judge', 'V250WorkshopOutController@judge')->name('web.v250WorkshopOut.judge');  // 2.5.0新站任务->出所 打开出所模态框判断
            Route::post('/{sn}/workshopOut', 'V250WorkshopOutController@workshopOut')->name('web.v250WorkshopOut.workshopOut');  // 2.5.0新站任务->出所 出所操作
        });

        // 2.5.0任务->检修分配
        Route::group(['prefix' => 'v250Overhaul'], function () {
            Route::get('/', 'V250OverhaulController@index')->name('web.v250Overhaul.index');  // 2.5.0任务->检修分配 列表
            Route::get('/create', 'V250OverhaulController@create')->name('web.v250Overhaul.create');  // 2.5.0任务->检修分配 新建页面
            Route::post('/', 'V250OverhaulController@store')->name('web.v250Overhaul.store');  // 2.5.0任务->检修分配 新建
            Route::get('/{id}', 'V250OverhaulController@show')->name('web.v250Overhaul.show');  // 2.5.0任务->检修分配 详情页面
            Route::get('/{id}/edit', 'V250OverhaulController@edit')->name('web.v250Overhaul.edit');  // 2.5.0任务->检修分配 编辑页面
            Route::put('/{id}', 'V250OverhaulController@update')->name('web.v250Overhaul.update');  // 2.5.0任务->检修分配 编辑
            Route::post('/{sn}/storeOverhaul', 'V250OverhaulController@storeOverhaul')->name('web.v250Overhaul.storeOverhaul');  // 2.5.0任务->任务内检修分配
            Route::post('/{sn}/storeOverhaul1', 'V250OverhaulController@storeOverhaul1')->name('web.v250Overhaul.storeOverhaul1');  // 2.5.0任务->所内检修分配
            Route::delete('/{id}', 'V250OverhaulController@destroy')->name('web.v250Overhaul.destroy');  // 2.5.0任务->检修分配 删除
        });

        // 2.5.0任务->检修统计设备列表
        Route::group(['prefix' => 'v250OverhaulEntireInstance'], function () {
            Route::get('/', 'V250OverhaulEntireInstanceController@index')->name('web.v250OverhaulEntireInstance.index');  // 2.5.0任务->检修统计设备列表 列表
            Route::get('/create', 'V250OverhaulEntireInstanceController@create')->name('web.v250OverhaulEntireInstance.create');  // 2.5.0任务->检修统计设备列表 新建页面
            Route::post('/', 'V250OverhaulEntireInstanceController@store')->name('web.v250OverhaulEntireInstance.store');  // 2.5.0任务->检修统计设备列表 新建
            Route::get('/{id}', 'V250OverhaulEntireInstanceController@show')->name('web.v250OverhaulEntireInstance.show');  // 2.5.0任务->检修统计设备列表 详情页面
            Route::get('/{id}/edit', 'V250OverhaulEntireInstanceController@edit')->name('web.v250OverhaulEntireInstance.edit');  // 2.5.0检修统计设备列表->检修分配 编辑页面
            Route::put('/{id}', 'V250OverhaulEntireInstanceController@update')->name('web.v250OverhaulEntireInstance.update');  // 2.5.0检修统计设备列表->检修分配 编辑
            Route::post('/completeOverhaul', 'V250OverhaulEntireInstanceController@completeOverhaul')->name('web.v250OverhaulEntireInstance.completeOverhaul');  // 2.5.0检修统计设备列表->检修完成
            Route::post('/cancelOverhaul', 'V250OverhaulEntireInstanceController@cancelOverhaul')->name('web.v250OverhaulEntireInstance.cancelOverhaul');  // 2.5.0检修统计设备列表->取消检修分配
            Route::post('/{sn}/storeOverhaul1', 'V250OverhaulEntireInstanceController@storeOverhaul1')->name('web.v250OverhaulEntireInstance.storeOverhaul1');  // 2.5.0检修统计设备列表->所内检修分配
            Route::delete('/{id}', 'V250OverhaulEntireInstanceController@destroy')->name('web.v250OverhaulEntireInstance.destroy');  // 2.5.0检修统计设备列表->检修分配 删除
        });

        // 2.5.0任务->利旧
        Route::group(['prefix' => 'v250UseOld'], function () {
            Route::get('/', 'V250UseOldController@index')->name('web.v250UseOld.index');  // 2.5.0任务->利旧 列表
            Route::get('/create', 'V250UseOldController@create')->name('web.v250UseOld.create');  // 2.5.0任务->利旧 新建页面
            Route::post('/', 'V250UseOldController@store')->name('web.v250UseOld.store');  // 2.5.0任务->利旧 新建
            Route::get('/{id}', 'V250UseOldController@show')->name('web.v250UseOld.show');  // 2.5.0任务->利旧 详情页面
            Route::get('/{id}/edit', 'V250UseOldController@edit')->name('web.v250UseOld.edit');  // 2.5.0任务->利旧 编辑页面
            Route::put('/{id}', 'V250UseOldController@update')->name('web.v250UseOld.update');  // 2.5.0任务->利旧 编辑
            Route::delete('/{id}', 'V250UseOldController@destroy')->name('web.v250UseOld.destroy');  // 2.5.0任务->利旧 删除
        });

        // 现场检修任务单
        Route::group(['prefix' => 'taskStationcheckPlan'], function () {
            Route::get('/', 'CheckPlanController@index')->name('web.taskStationcheckPlan.index');  // 现场检修任务单 列表
            Route::get('/create', 'CheckPlanController@create')->name('web.taskStationcheckPlan.create');  // 现场检修任务单 新建页面
            Route::post('/', 'CheckPlanController@store')->name('web.taskStationcheckPlan.store');  // 现场检修任务单 新建
        });

        // 现场检修任务设备
        Route::group(['prefix' => 'taskStationCheckEntireInstance'], function () {
            Route::get('/{id}/images', 'TaskStationCheckEntireInstanceController@getImages')->name('web.taskStationCheckEntireInstance.getImages');  // 现场检修任务设备 获取设备工作图片
            Route::get('', 'TaskStationCheckEntireInstanceController@index')->name('web.taskStationCheckEntireInstance.index');  // 现场检修任务设备 列表
            Route::get('create', 'TaskStationCheckEntireInstanceController@create')->name('web.taskStationCheckEntireInstance.create');  // 现场检修任务设备 新建页面
            Route::post('', 'TaskStationCheckEntireInstanceController@store')->name('web.taskStationCheckEntireInstance.store');  // 现场检修任务设备 新建
            Route::get('{id}', 'TaskStationCheckEntireInstanceController@show')->name('web.taskStationCheckEntireInstance.show');  // 现场检修任务设备 详情页面
            Route::get('{id}/edit', 'TaskStationCheckEntireInstanceController@edit')->name('web.taskStationCheckEntireInstance.edit');  // 现场检修任务设备 编辑页面
            Route::put('{id}', 'TaskStationCheckEntireInstanceController@update')->name('web.taskStationCheckEntireInstance.update');  // 现场检修任务设备 编辑
            Route::delete('{id}', 'TaskStationCheckEntireInstanceController@destroy')->name('web.taskStationCheckEntireInstance.destroy');  // 现场检修任务设备 删除
        });

        // 2.5.0换型任务
        Route::group(['prefix' => 'v250ChangeModel'], function () {
            Route::get('/', 'V250ChangeModelController@index')->name('web.v250ChangeModel.index');  // 2.5.0换型任务 列表
            Route::get('/create', 'V250ChangeModelController@create')->name('web.v250ChangeModel.create');  // 2.5.0换型任务 新建页面
            Route::post('/', 'V250ChangeModelController@store')->name('web.v250ChangeModel.store');  // 2.5.0换型任务 新建
            // Route::get('/{sn}', 'V250ChangeModelController@show')->name('web.v250ChangeModel.show');  // 2.5.0换型任务 详情页面
            Route::get('/{sn}/edit', 'V250ChangeModelController@edit')->name('web.v250ChangeModel.edit');  // 2.5.0换型任务 编辑页面
            Route::put('/{sn}', 'V250ChangeModelController@update')->name('web.v250ChangeModel.update');  // 2.5.0换型任务 编辑
            Route::delete('/{sn}', 'V250ChangeModelController@destroy')->name('web.v250ChangeModel.destroy');  // 2.5.0换型任务 删除
            Route::post('/{sn}/delivery', 'V250ChangeModelController@postDelivery')->name('web.v250ChangeModel.postDelivery');  // 2.5.0换型任务 交付任务
            Route::get('/changeModelList', 'V250ChangeModelController@changeModelList')->name('web.v250ChangeModel.changeModelList');  // 2.5.0换型任务 列表页
            Route::post('/changeModel', 'V250ChangeModelController@changeModel')->name('web.v250ChangeModel.changeModel');  // 2.5.0换型任务 换型操作

        });

        // 2.5.0状态修任务
        Route::group(['prefix' => 'v250UnCycleFix'], function () {
            Route::get('/', 'V250UnCycleFixController@index')->name('web.v250UnCycleFix.index');  // 2.5.0状态修任务 列表
            Route::get('/create', 'V250UnCycleFixController@create')->name('web.v250UnCycleFix.create');  // 2.5.0状态修任务 新建页面
            Route::post('/', 'V250UnCycleFixController@store')->name('web.v250UnCycleFix.store');  // 2.5.0状态修任务 新建
            // Route::get('/{sn}', 'V250UnCycleFixController@show')->name('web.v250UnCycleFix.show');  // 2.5.0状态修任务 详情页面
            Route::get('/{sn}/edit', 'V250UnCycleFixController@edit')->name('web.v250UnCycleFix.edit');  // 2.5.0状态修任务 编辑页面
            Route::put('/{sn}', 'V250UnCycleFixController@update')->name('web.v250UnCycleFix.update');  // 2.5.0状态修任务 编辑
            Route::delete('/{sn}', 'V250UnCycleFixController@destroy')->name('web.v250UnCycleFix.destroy');  // 2.5.0状态修任务 删除
            Route::post('/{sn}/delivery', 'V250UnCycleFixController@postDelivery')->name('web.v250UnCycleFix.postDelivery');  // 2.5.0状态修任务 交付任务
            Route::get('/unCycleFixList', 'V250UnCycleFixController@unCycleFixList')->name('web.v250UnCycleFix.unCycleFixList');  // 2.5.0状态修任务 列表页
            Route::post('/unCycleFix', 'V250UnCycleFixController@unCycleFix')->name('web.v250UnCycleFix.unCycleFix');  // 2.5.0状态修任务 添加状态修设备操作
        });

        // 2.5.0回收任务
        Route::group(['prefix' => 'v250Recycle'], function () {
            Route::get('/', 'V250RecycleController@index')->name('web.v250Recycle.index');  // 2.5.0回收任务 列表
            Route::get('/create', 'V250RecycleController@create')->name('web.v250Recycle.create');  // 2.5.0回收任务 新建页面
            Route::post('/', 'V250RecycleController@store')->name('web.v250Recycle.store');  // 2.5.0回收任务 新建
            Route::get('/{sn}', 'V250RecycleController@show')->name('web.v250Recycle.show');  // 2.5.0回收任务 详情页面
            Route::get('/{sn}/edit', 'V250RecycleController@edit')->name('web.v250Recycle.edit');  // 2.5.0回收任务 编辑页面
            Route::put('/{sn}', 'V250RecycleController@update')->name('web.v250Recycle.update');  // 2.5.0回收任务 编辑
            Route::delete('/{sn}', 'V250RecycleController@destroy')->name('web.v250Recycle.destroy');  // 2.5.0回收任务 删除

            Route::post('/{sn}/delivery', 'V250RecycleController@postDelivery')->name('web.v250Recycle.postDelivery');  // 2.5.0回收任务 交付任务
        });

        // 现场检修
        Route::group(['prefix' => 'task', 'namespace' => 'Task'], function () {
            // 检修项目管理
            Route::group(['prefix' => 'checkProject'], function () {
                Route::get('project', 'CheckProjectController@getProject')->name('task.checkProject.project');  // 根据类型获取列表
                Route::get('/', 'CheckProjectController@index')->name('task.checkProject.index');  // 列表
                Route::get('/create', 'CheckProjectController@create')->name('task.checkProject.create');  // 新建页面
                Route::post('/', 'CheckProjectController@store')->name('task.checkProject.store');  // 新建
                Route::get('/{id}', 'CheckProjectController@show')->name('task.checkProject.show');  // 详情页面
                Route::get('/{id}/edit', 'CheckProjectController@edit')->name('task.checkProject.edit');  // 编辑页面
                Route::put('/{id}', 'CheckProjectController@update')->name('task.checkProject.update');  // 编辑
                Route::delete('/{id}', 'CheckProjectController@destroy')->name('task.checkProject.destroy');  // 删除
            });
            // 检修计划管理
            Route::group(['prefix' => 'checkPlan'], function () {
                // 设备
                Route::group(['prefix' => 'instance'], function () {
                    Route::get('/', 'CheckPlanController@instanceWithIndex')->name('task.checkPlan.instance.index');  // 列表
                    Route::post('/', 'CheckPlanController@instanceWithStore')->name('task.checkPlan.instance.store');  // 新建
                    Route::delete('/', 'CheckPlanController@instanceWithDestroy')->name('task.checkPlan.instance.destroy');  // 删除
                });
                Route::get('/', 'CheckPlanController@index')->name('task.checkPlan.index');  // 列表
                Route::get('/create', 'CheckPlanController@create')->name('task.checkPlan.create');  // 新建页面
                Route::post('/', 'CheckPlanController@store')->name('task.checkPlan.store');  // 新建
            });
            // 任务管理
            Route::group(['prefix' => 'checkOrder'], function () {
                // 设备道岔
                Route::group(['prefix' => 'instance'], function () {
                    Route::get('/', 'CheckOrderController@instanceWithIndex')->name('task.checkOrder.instance.index');  // 列表
                    Route::post('/', 'CheckOrderController@instanceWithStore')->name('task.checkOrder.instance.store');  // 新建
                    Route::delete('/', 'CheckOrderController@instanceWithDestroy')->name('task.checkOrder.instance.destroy');  // 删除
                });

                Route::get('statisticForInstance', 'CheckOrderController@statisticForInstance')->name('task.checkOrder.statisticForInstance');  // 检修统计-设备列表
                Route::get('statisticForProject', 'CheckOrderController@statisticForProject')->name('task.checkOrder.statisticForProject');  // 检修统计
                Route::get('/', 'CheckOrderController@index')->name('task.checkOrder.index');  // 列表
                Route::get('/create', 'CheckOrderController@create')->name('task.checkOrder.create');  // 新建页面-分配任务
                Route::post('/', 'CheckOrderController@store')->name('task.checkOrder.store');  // 新建-分配任务
            });
        });

        // 机柜管理
        Route::group(['prefix' => 'equipmentCabinet'], function () {
            Route::get('/{id}/bindEntireInstance', 'EquipmentCabinetController@getBindEntireInstance')->name('web.equipmentCabinet.getBindEntireInstance');  // 机柜管理 绑定设备页面
            Route::post('/{id}/bindEntireInstance', 'EquipmentCabinetController@postBindEntireInstance')->name('web.equipmentCabinet.postBindEntireInstance'); // 机柜管理 添加绑定设备
            Route::put('/{id}/bindEntireInstance', 'EquipmentCabinetController@putBindEntireInstance')->name('web.equipmentCabinet.putBindEntireInstance'); // 机柜管理 编辑绑定设备
            Route::get('/', 'EquipmentCabinetController@index')->name('web.equipmentCabinet.index');  // 机柜管理 列表
            Route::get('/create', 'EquipmentCabinetController@create')->name('web.equipmentCabinet.create');  // 机柜管理 新建页面
            Route::post('/', 'EquipmentCabinetController@store')->name('web.equipmentCabinet.store');  // 机柜管理 新建
            Route::get('/{id}', 'EquipmentCabinetController@show')->name('web.equipmentCabinet.show');  // 机柜管理 详情页面
            Route::get('/{id}/edit', 'EquipmentCabinetController@edit')->name('web.equipmentCabinet.edit');  // 机柜管理 编辑页面
            Route::put('/{id}', 'EquipmentCabinetController@update')->name('web.equipmentCabinet.update');  // 机柜管理 编辑
            Route::delete('/{id}', 'EquipmentCabinetController@destroy')->name('web.equipmentCabinet.destroy');  // 机柜管理 删除
        });

        // 机柜层管理
        Route::group(['prefix' => 'combinationLocationRow'], function () {
            Route::get('/', 'CombinationLocationRowController@index')->name('web.combinationLocationRow.index');  // 机柜层管理 列表
            Route::get('/create', 'CombinationLocationRowController@create')->name('web.combinationLocationRow.create');  // 机柜层管理 新建页面
            Route::post('/', 'CombinationLocationRowController@store')->name('web.combinationLocationRow.store');  // 机柜层管理 新建
            Route::get('/{id}', 'CombinationLocationRowController@show')->name('web.combinationLocationRow.show');  // 机柜层管理 详情页面
            Route::get('/{id}/edit', 'CombinationLocationRowController@edit')->name('web.combinationLocationRow.edit');  // 机柜层管理 编辑页面
            Route::put('/{id}', 'CombinationLocationRowController@update')->name('web.combinationLocationRow.update');  // 机柜层管理 编辑
            Route::delete('/{id}', 'CombinationLocationRowController@destroy')->name('web.combinationLocationRow.destroy');  // 机柜层管理 删除
        });

        // 机柜位管理
        Route::group(['prefix' => 'combinationLocationCol'], function () {
            Route::get('/', 'CombinationLocationColController@index')->name('web.combinationLocationCol.index');  // 机柜位管理 列表
            Route::get('/create', 'CombinationLocationColController@create')->name('web.combinationLocationCol.create');  // 机柜位管理 新建页面
            Route::post('/', 'CombinationLocationColController@store')->name('web.combinationLocationCol.store');  // 机柜位管理 新建
            Route::get('/{id}', 'CombinationLocationColController@show')->name('web.combinationLocationCol.show');  // 机柜位管理 详情页面
            Route::get('/{id}/edit', 'CombinationLocationColController@edit')->name('web.combinationLocationCol.edit');  // 机柜位管理 编辑页面
            Route::put('/{id}', 'CombinationLocationColController@update')->name('web.combinationLocationCol.update');  // 机柜位管理 编辑
            Route::delete('/{id}', 'CombinationLocationColController@destroy')->name('web.combinationLocationCol.destroy');  // 机柜位管理 删除
        });

        // 上道位置管理
        Route::group(['prefix' => 'installLocation'], function () {
            Route::get("test", "InstallLocationController@GetTest")->name("installLocation.GetTest");  // 上道位置对比

            Route::get('rooms', 'InstallLocationController@getInstallRooms')->name('installLocation.getInstallRooms');  // 根据车站获取机房列表
            Route::get('platoons', 'InstallLocationController@getInstallPlatoons')->name('installLocation.getInstallPlatoons');  // 根据机房获取排列表
            Route::get('shelves', 'InstallLocationController@getInstallShelves')->name('installLocation.getInstallShelves');  // 根据排获取柜列表
            Route::get('tiers', 'InstallLocationController@getInstallTiers')->name('installLocation.getInstallTiers');  // 根据柜获取层列表
            Route::get('positions', 'InstallLocationController@getInstallPositions')->name('installLocation.getInstallPositions');  // 根据层获取位列表

            // 机房
            Route::group(['prefix' => 'room'], function () {
                Route::get('/', 'InstallLocationController@roomWithIndex')->name('installLocation.room.index');  // 机房列表
                Route::post('/', 'InstallLocationController@roomWithStore')->name('installLocation.room.store');  // 机房新建
                Route::put('/{id}', 'InstallLocationController@roomWithUpdate')->name('installLocation.room.update');  // 机房编辑
                Route::delete('/{id}', 'InstallLocationController@roomWithDestroy')->name('installLocation.room.destroy');  // 机房删除
            });
            // 排
            Route::group(['prefix' => 'platoon'], function () {
                Route::get('platoonWithStation', 'InstallLocationController@getPlatoonWithStation')->name('installLocation.platoon.station');  // 根据车站获取排
                Route::get('/', 'InstallLocationController@platoonWithIndex')->name('installLocation.platoon.index');  // 排列表
                Route::post('/', 'InstallLocationController@platoonWithStore')->name('installLocation.platoon.store');  // 排新建
                Route::put('/{id}', 'InstallLocationController@platoonWithUpdate')->name('installLocation.platoon.update');  // 排编辑
                Route::delete('/{id}', 'InstallLocationController@platoonWithDestroy')->name('installLocation.platoon.destroy');  // 排删除
            });
            // 架（柜）
            Route::group(['prefix' => 'shelf'], function () {
                Route::post('uploadImage', 'InstallLocationController@uploadImageWithShelf')->name('installLocation.shelf.uploadImage');  # 上传图片
                Route::get('/', 'InstallLocationController@shelfWithIndex')->name('installLocation.shelf.index');  // 架（柜）列表
                Route::post('/', 'InstallLocationController@shelfWithStore')->name('installLocation.shelf.store');  // 架（柜）新建
                Route::put('/{id}', 'InstallLocationController@shelfWithUpdate')->name('installLocation.shelf.update');  // 架（柜）编辑
                Route::delete('/{id}', 'InstallLocationController@shelfWithDestroy')->name('installLocation.shelf.destroy');  // 架（柜）删除
            });
            // 层
            Route::group(['prefix' => 'tier'], function () {
                Route::get('/', 'InstallLocationController@tierWithIndex')->name('installLocation.tier.index');  // 层列表
                Route::post('/', 'InstallLocationController@tierWithStore')->name('installLocation.tier.store');  // 层新建
                Route::put('/{id}', 'InstallLocationController@tierWithUpdate')->name('installLocation.tier.update');  // 层编辑
                Route::delete('/{id}', 'InstallLocationController@tierWithDestroy')->name('installLocation.tier.destroy');  // 层删除
            });
            // 位
            Route::group(['prefix' => 'position'], function () {
                Route::get('/', 'InstallLocationController@positionWithIndex')->name('installLocation.position.index');  // 位列表
                Route::post('/', 'InstallLocationController@positionWithStore')->name('installLocation.position.store');  // 位新建
                Route::put('/{id}', 'InstallLocationController@positionWithUpdate')->name('installLocation.position.update');  // 位编辑
                Route::delete('/{id}', 'InstallLocationController@positionWithDestroy')->name('installLocation.position.destroy');  // 位删除
            });

            Route::post("/syncToElectricWorkshop", "InstallLocationController@PostSyncToElectricWorkshop")->name("installLocation.PostSyncToElectricWorkshop");  // 同步到电子车间
            Route::get('/upload', 'InstallLocationController@getUpload')->name('installLocation.getUpload');  // 下载批量上传模板Excel
            Route::post('/upload', 'InstallLocationController@postUpload')->name('installLocation.postUpload');  // 批量上传位置
            Route::get('/', 'InstallLocationController@index')->name('installLocation.index');  // 上道位置管理列表
            Route::get('/create', 'InstallLocationController@create')->name('installLocation.create');  // 上道位置管理新建页面
            Route::post('/', 'InstallLocationController@store')->name('installLocation.store');  // 上道位置管理新建
            Route::get('/{id}', 'InstallLocationController@show')->name('installLocation.show');  // 上道位置管理详情页面
            Route::get('/{id}/edit', 'InstallLocationController@edit')->name('installLocation.edit');  // 上道位置管理编辑页面
            Route::put('/{id}', 'InstallLocationController@update')->name('installLocation.update');  // 上道位置管理编辑
            Route::delete('/{id}', 'InstallLocationController@destroy')->name('installLocation.destroy');  // 上道位置管理删除
        });

        // 数据采集单
        Route::group(['prefix' => 'collectionOrder'], function () {
            Route::post('markNeedDelete', 'CollectionOrderController@postMarkNeedDelete')->name('collectionOrder.postMarkNeedDelete');  // 标注需要删除的
            Route::post('uploadInDevice', 'CollectionOrderController@postUploadInDevice')->name('collectionOrder.postUploadInDevice');  // 上传定位采集单（室内）
            Route::post('uploadOutDevice', 'CollectionOrderController@postUploadOutDevice')->name('collectionOrder.postUploadOutDevice');  // 上传定位采集单（室外）
            Route::get('downloadErrorExcel', 'CollectionOrderController@getDownloadErrorExcel')->name('collectionOrder.getDownloadErrorExcel');  // 下载错误报告
            Route::get('downloadExcel/{unique_code}', 'CollectionOrderController@downloadExcel')->name('collectionOrder.downloadExcel');  // 数据采集单下载Excel
            Route::get('makeExcel/{unique_code}', 'CollectionOrderController@makeExcel')->name('collectionOrder.makeExcel');  // 数据采集制作Excel

            Route::group(['prefix' => 'material'], function () {
                Route::get('/', 'CollectionOrderController@indexWithCollectOrderMaterial')->name('collectionOrder.material.index');  // 数据采集单器材列表页面
            });
            Route::get('/', 'CollectionOrderController@index')->name('collectionOrder.index');  // 数据采集单列表
            Route::get('/create', 'CollectionOrderController@create')->name('collectionOrder.create');  // 数据采集单新建页面
            Route::post('/', 'CollectionOrderController@store')->name('collectionOrder.store');  // 数据采集单新建
            Route::get('/{id}', 'CollectionOrderController@show')->name('collectionOrder.show');  // 数据采集单详情页面
            Route::get('/{id}/edit', 'CollectionOrderController@edit')->name('collectionOrder.edit');  // 数据采集单编辑页面
            Route::put('/{id}', 'CollectionOrderController@update')->name('collectionOrder.update');  // 数据采集单编辑
            Route::delete('/{id}', 'CollectionOrderController@destroy')->name('collectionOrder.destroy');  // 数据采集单删除
        });

        // 上道
        Route::group(['prefix' => 'installed'], function () {
            Route::get('/', 'InstalledController@index')->name('installed.index');  // 上道 列表
            Route::post('/', 'InstalledController@store')->name('installed.store');  // 上道
            Route::get('/history', 'InstalledController@getHistory')->name('installed.getHistory');  // 上下道历史记录
        });

        // 下道
        Route::group(['prefix' => 'uninstall'], function () {
            Route::post('/scan', 'UnInstallController@postScan')->name('uninstall.postScan');  // 下道 扫描设备器材
            Route::get('/', 'UnInstallController@index')->name('uninstall.index');  // 下道 列表
            Route::post('/', 'UnInstallController@store')->name('uninstall.store');  // 下道
        });

        // 现场备品入柜
        Route::group(['prefix' => 'installing'], function () {
            Route::post('/scan', 'InstallingController@postScan')->name('installing.postScan');  // 现场配品入柜 扫描设备器材
            Route::get('/', 'InstallingController@index')->name('installing.index');  // 现场备品入柜 列表
            Route::post('/', 'InstallingController@store')->name('installing.store');  // 现场备品入柜 新建
        });

        // 超期和周期修提醒
        Route::group(['prefix' => 'scrapedAndCycleFixPlanWarning'], function () {
            Route::get('/', 'ScrapedAndCycleFixPlanWarningController@index')->name('web.ScrapedAndCycleFixPlanWarning.index');  //  index page
        });

        // 提醒（超期、周期修提醒、成品超6个月检测提醒）
        Route::name("web.Remind:")->get("remind", "RemindController@index")->name("index");

        // entire model image
        Route::group(['prefix' => 'entireModelImage'], function () {
            Route::get('/', 'EntireModelImageController@index')->name('web.entireModelImage.index');  // entire model image list or page
            Route::post('/', 'EntireModelImageController@store')->name('web.entireModelImage.store');  // entire model image create
            Route::delete('/{id}', 'EntireModelImageController@destroy')->name('web.entireModelImage.destroy');  // entire model image delete
        });

        // part model image
        Route::group(['prefix' => 'partModelImage'], function () {
            Route::get('/', 'PartModelImageController@index')->name('web.partModelImage.index');  // part model image list or page
            Route::post('/', 'PartModelImageController@store')->name('web.partModelImage.store');  // part model image create
            Route::delete('/{id}', 'PartModelImageController@destroy')->name('web.partModelImage.destroy');  // part model image delete
        });

        // 仓库-排
        Route::group(['prefix' => 'platoon'], function () {
            Route::get('/', 'PlatoonController@index')->name('web.platoon.index');  // 仓库-排 index page
            Route::get('/create', 'PlatoonController@create')->name('web.platoon.create');  // 仓库-排 create page
            Route::post('/', 'PlatoonController@store')->name('web.platoon.store');  // 仓库-排 create
            Route::get('/{id}', 'PlatoonController@show')->name('web.platoon.show');  // 仓库-排 detail page
            Route::get('/{id}/edit', 'PlatoonController@edit')->name('web.platoon.edit');  // 仓库-排 edit page
            Route::put('/{id}', 'PlatoonController@update')->name('web.platoon.update');  // 仓库-排 edit
            Route::delete('/{id}', 'PlatoonController@destroy')->name('web.platoon.destroy');  // 仓库-排 delete
        });

        // 仓库-架
        Route::group(['prefix' => 'shelf'], function () {
            Route::get('/', 'ShelfController@index')->name('web.shelf.index');  // 仓库-架 index page
            Route::get('/create', 'ShelfController@create')->name('web.shelf.create');  // 仓库-架 create page
            Route::post('/', 'ShelfController@store')->name('web.shelf.store');  // 仓库-架 create
            Route::get('/{id}', 'ShelfController@show')->name('web.shelf.show');  // 仓库-架 detail page
            Route::get('/{id}/edit', 'ShelfController@edit')->name('web.shelf.edit');  // 仓库-架 edit page
            Route::put('/{id}', 'ShelfController@update')->name('web.shelf.update');  // 仓库-架 edit
            Route::delete('/{id}', 'ShelfController@destroy')->name('web.shelf.destroy');  // 仓库-架 delete
        });

        // 仓库-层
        Route::group(['prefix' => 'tier'], function () {
            Route::get('/', 'TierController@index')->name('web.tier.index');  // 仓库层 index page
            Route::get('/create', 'TierController@create')->name('web.tier.create');  // 仓库层 create page
            Route::post('/', 'TierController@store')->name('web.tier.store');  // 仓库层 create
            Route::get('/{id}', 'TierController@show')->name('web.tier.show');  // 仓库层 detail page
            Route::get('/{id}/edit', 'TierController@edit')->name('web.tier.edit');  // 仓库层 edit page
            Route::put('/{id}', 'TierController@update')->name('web.tier.update');  // 仓库层 edit
            Route::delete('/{id}', 'TierController@destroy')->name('web.tier.destroy');  // 仓库层 delete
        });

        // 仓库-位
        Route::group(['prefix' => 'position'], function () {
            Route::get('/', 'PositionController@index')->name('web.position.index');  // 仓库位置 index page
            Route::get('/create', 'PositionController@create')->name('web.position.create');  // 仓库位置 create page
            Route::post('/', 'PositionController@store')->name('web.position.store');  // 仓库位置 create
            Route::get('/{id}', 'PositionController@show')->name('web.position.show');  // 仓库位置 detail page
            Route::get('/{id}/edit', 'PositionController@edit')->name('web.position.edit');  // 仓库位置 edit page
            Route::put('/{id}', 'PositionController@update')->name('web.position.update');  // 仓库位置 edit
            Route::delete('/{id}', 'PositionController@destroy')->name('web.position.destroy');  // 仓库位置 delete
        });

        // 来源类型
        Route::group(['prefix' => 'sourceType'], function () {
            Route::get('/', 'SourceTypeController@index')->name('web.sourceType.index');  // 来源类型 index page
            Route::get('/create', 'SourceTypeController@create')->name('web.sourceType.create');  // 来源类型 create page
            Route::post('/', 'SourceTypeController@store')->name('web.sourceType.store');  // 来源类型 create
            Route::get('/{id}', 'SourceTypeController@show')->name('web.sourceType.show');  // 来源类型 detail page
            Route::get('/{id}/edit', 'SourceTypeController@edit')->name('web.sourceType.edit');  // 来源类型 edit page
            Route::put('/{id}', 'SourceTypeController@update')->name('web.sourceType.update');  // 来源类型 edit
            Route::delete('/{id}', 'SourceTypeController@destroy')->name('web.sourceType.destroy');  // 来源类型 delete
        });

        // 来源名称
        Route::group(['prefix' => 'sourceName'], function () {
            Route::get('/', 'SourceNameController@index')->name('web.sourceName.index');  // 来源名称 index page
            Route::get('/create', 'SourceNameController@create')->name('web.sourceName.create');  // 来源名称 create page
            Route::post('/', 'SourceNameController@store')->name('web.sourceName.store');  // 来源名称 create
            Route::get('/{id}', 'SourceNameController@show')->name('web.sourceName.show');  // 来源名称 detail page
            Route::get('/{id}/edit', 'SourceNameController@edit')->name('web.sourceName.edit');  // 来源名称 edit page
            Route::put('/{id}', 'SourceNameController@update')->name('web.sourceName.update');  // 来源名称 edit
            Route::delete('/{id}', 'SourceNameController@destroy')->name('web.sourceName.destroy');  // 来源名称 delete
        });

        // 材料
        Route::group(['prefix' => 'material'], function () {
            Route::post('/tagging', 'MaterialController@postTagging')->name('web.material.postTagging');  // 材料 赋码
            Route::put('/batch', 'MaterialController@putBatch')->name('web.material.putBatch');  // 材料 批量修改
            Route::get('/', 'MaterialController@index')->name('web.material.index');  // 材料 index page
            Route::get('/create', 'MaterialController@create')->name('web.material.create');  // 材料 create page
            Route::post('/', 'MaterialController@store')->name('web.material.store');  // 材料 create
            Route::get('/{identity_code}', 'MaterialController@show')->name('web.material.show');  // 材料 detail page
            Route::get('/{identity_code}/edit', 'MaterialController@edit')->name('web.material.edit');  // 材料 edit page
            Route::put('/{identity_code}', 'MaterialController@update')->name('web.material.update');  // 材料 edit
            Route::delete('/{identity_code}', 'MaterialController@destroy')->name('web.material.destroy');  // 材料 delete
        });

        // 材料类型
        Route::group(['prefix' => 'materialType'], function () {
            Route::get('/', 'MaterialTypeController@index')->name('web.materialType.index');  // 材料类型 index page
            Route::get('/create', 'MaterialTypeController@create')->name('web.materialType.create');  // 材料类型 create page
            Route::post('/', 'MaterialTypeController@store')->name('web.materialType.store');  // 材料类型 create
            Route::get('/{id}', 'MaterialTypeController@show')->name('web.materialType.show');  // 材料类型 detail page
            Route::get('/{id}/edit', 'MaterialTypeController@edit')->name('web.materialType.edit');  // 材料类型 edit page
            Route::put('/{id}', 'MaterialTypeController@update')->name('web.materialType.update');  // 材料类型 edit
            Route::delete('/{id}', 'MaterialTypeController@destroy')->name('web.materialType.destroy');  // 材料类型 delete
        });

        // 材料出入库单
        Route::group(['prefix' => 'materialStorehouseOrder'], function () {
            Route::get('/in', 'MaterialStorehouseOrderController@getIn')->name('web.materialStorehouseOrder.getIn');  // 材料出入库单 入库页面
            Route::post('/in', 'MaterialStorehouseOrderController@postIn')->name('web.materialStorehouseOrder.postIn');  // 材料出入库单 入库
            Route::get('/out', 'MaterialStorehouseOrderController@getOut')->name('web.materialStorehouseOrder.getOut');  // 材料出入库单 出库页面
            Route::post('/out', 'MaterialStorehouseOrderController@postOut')->name('web.materialStorehouseOrder.postOut');  // 材料出入库单 出库
            Route::post('/scan', 'MaterialStorehouseOrderController@postScan')->name('web.materialStorehouseOrder.postScan');  // 材料出入库单 扫码
            Route::post('/append', 'MaterialStorehouseOrderController@postAppend')->name('web.materialStorehouseOrder.postAppend');  // 材料 添加
            Route::delete('/{identity_code}/scan', 'MaterialStorehouseOrderController@deleteScan')->name('web.materialStorehouseOrder.deleteScan');  // 材料出入库单 取消扫码
            Route::get('/', 'MaterialStorehouseOrderController@index')->name('web.materialStorehouseOrder.index');  // 材料出入库单 列表
            Route::get('/create', 'MaterialStorehouseOrderController@create')->name('web.materialStorehouseOrder.create');  // 材料出入库单 新建页面
            Route::post('/', 'MaterialStorehouseOrderController@store')->name('web.materialStorehouseOrder.store');  // 材料出入库单 新建
            Route::get('/{serial_number}', 'MaterialStorehouseOrderController@show')->name('web.materialStorehouseOrder.show');  // 材料出入库单 详情页面
            Route::get('/{serial_number}/edit', 'MaterialStorehouseOrderController@edit')->name('web.materialStorehouseOrder.edit');  // 材料出入库单 编辑页面
            Route::put('/{serial_number}', 'MaterialStorehouseOrderController@update')->name('web.materialStorehouseOrder.update');  // 材料出入库单 编辑
            Route::delete('/{serial_number}', 'MaterialStorehouseOrderController@destroy')->name('web.materialStorehouseOrder.destroy');  // 材料出入库单 删除
        });

        // 中心
        Route::prefix("centre")->name("web.centre:")->group(function () {
            Route::get("", "CentreController@index")->name("index");  // 列表
            Route::post("", "CentreController@store")->name("store");  // 保存
            Route::get("/{unique_code}", "CentreController@show")->name("show");  // 详情
            Route::put("/{unique_code}", "CentreController@update")->name("update");  // 编辑
            // Route::delete("/{unique_code}","CentreController@destroy")->name("destroy");  // 删除
        });

        // 段中心相关页面
        Route::prefix("bi")->name("web.bi:")->group(function () {
            Route::get("modelCat", "BiController@getModelCat")->name("getModelCat");  // 申请种类型页面
            Route::get("modelAly", "BiController@getModelAly")->name("getModelAly");  // 申请种类型列表页面
        });

        // 唯一编号绑定所编号
        Route::prefix("bindSerialNumber")->name("web.bindSerialNumber:")->group(function () {
            Route::get("/", "BindSerialNumberController@index")->name("index");  // 列表
            Route::post("/", "BindSerialNumberController@store")->name("store");  // 保存
        });

        // 工区管理
        Route::prefix("workArea")->name("web.WorkArea:")->group(function () {
            Route::get("/", "WorkAreaController@index")->name("index");  // 工区管理 列表
            Route::get("/{unique_code}", "WorkAreaController@show")->name("show");  // 工区管理 详情
            Route::post("/", "WorkAreaController@store")->name("store");  // 工区管理 新建
            Route::put("/{unique_code}", "WorkAreaController@update")->name("update");  // 工区管理 编辑
        });

        // 段中心相关
        Route::prefix("paragraphCenter")->name("web.ParagraphCenter.")->namespace("ParagraphCenter")->group(function () {
            // 检修模板
            Route::prefix("measurement")->name("Measurement:")->group(function () {
                Route::get("/", "MeasurementController@index")->name("index");  // 列表页面
                Route::get("/create", "MeasurementController@create")->name("create");  // 新建页面
                Route::get("/{serial_number}", "MeasurementController@show")->name("show");  // 详情页面
                Route::get("/{serial_number}/edit", "MeasurementController@edit")->name("edit");  // 编辑页面
                Route::delete("/{serial_number}", "MeasurementController@destroy")->name("destroy");  // 删除
            });
            // 检修模板步骤
            Route::prefix("measurementStep")->name("MeasurementStep:")->group(function () {
                Route::get("/", "MeasurementStepController@index")->name("index");
            });
            // 手工检修
            Route::prefix("manualFix")->name("ManualFixController:")->group(function () {
                Route::get("/", "ManualFixController@index")->name("index");  // 列表
                Route::get("/create", "ManualFixController@create")->name("create");  // 创建检修
            });
        });

        // 程序更新
        Route::prefix("appUpgrade")->name("web.AppUpgrade:")->group(function () {
            Route::get("/", "AppUpgradeController@Index")->name("Index");  // 列表页面
            Route::get("/create", "AppUpgradeController@Create")->name("Create");  // 新建页面
            Route::get("/{uniqueCode}", "AppUpgradeController@Show")->name("Show");  // 详情页面
            Route::get("/{uniqueCode}/edit", "AppUpgradeController@Edit")->name("Edit");  // 编辑页面
            Route::put("/{uniqueCode}", "AppUpgradeController@Update")->name("Update");  // 编辑
            Route::post("/", "AppUpgradeController@Store")->name("Store");  // 保存
            Route::delete("/{uniqueCode}", "AppUpgradeController@Destroy")->name("Destroy");  // 删除
            Route::delete("/{identityCode}/accessory", "AppUpgradeController@DeleteAccessory")->name("DeleteAccessory");  // 删除附件
        });

    });
});
