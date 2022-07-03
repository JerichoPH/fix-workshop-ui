<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
| last update time: 2021-08-22 21:53:52
*/

$api = app('Dingo\Api\Routing\Router');
$api->version(['v1',], function ($api) {
    // 基础数据
    $api->group(['prefix' => 'basic', 'namespace' => 'App\Http\Controllers\V1\Basic'], function ($api) {
        // 种类
        $api->group(['prefix' => 'category'], function ($api) {
            $api->get('/init', 'CategoryController@getInit')->name('api.category.init.get');  #
            $api->get('/', 'CategoryController@index')->name('api.category.index');  // 种类列表
            $api->get('/create', 'CategoryController@create')->name('api.category.create');  // 种类新建页面
            $api->post('/', 'CategoryController@store')->name('api.category.store');  // 种类新建
            $api->get('/{unique_code}', 'CategoryController@show')->name('api.category.show');  // 种类详情页面
            $api->get('/{unique_code}/edit', 'CategoryController@edit')->name('api.category.edit');  // 种类编辑页面
            $api->put('/{unique_code}', 'CategoryController@update')->name('api.category.update');  // 种类编辑
            $api->delete('/{unique_code}', 'CategoryController@destroy')->name('api.category.destroy');  // 种类删除
        });

        // 类型
        $api->group(['prefix' => 'entireModel'], function ($api) {
            $api->get('/', 'EntireModelController@index')->name('api.entireModel.index');  // 类型列表
            $api->get('/create', 'EntireModelController@create')->name('api.entireModel.create');  // 类型新建页面
            $api->post('/', 'EntireModelController@store')->name('api.entireModel.store');  // 类型新建
            $api->get('/{unique_code}', 'EntireModelController@show')->name('api.entireModel.show');  // 类型详情页面
            $api->get('/{unique_code}/edit', 'EntireModelController@edit')->name('api.entireModel.edit');  // 类型编辑页面
            $api->put('/{unique_code}', 'EntireModelController@update')->name('api.entireModel.update');  // 类型编辑
            $api->delete('/{unique_code}', 'EntireModelController@destroy')->name('api.entireModel.destroy');  // 类型删除
        });

        // 子类
        $api->group(['prefix' => 'subModel'], function ($api) {
            $api->get('/', 'SubModelController@index')->name('api.subModel.index');  // 子类列表
            $api->get('/create', 'SubModelController@create')->name('api.subModel.create');  // 子类新建页面
            $api->post('/', 'SubModelController@store')->name('api.subModel.store');  // 子类新建
            $api->get('/{unique_code}', 'SubModelController@show')->name('api.subModel.show');  // 子类详情页面
            $api->get('/{unique_code}/edit', 'SubModelController@edit')->name('api.subModel.edit');  // 子类编辑页面
            $api->put('/{unique_code}', 'SubModelController@update')->name('api.subModel.update');  // 子类编辑
            $api->delete('/{unique_code}', 'SubModelController@destroy')->name('api.subModel.destroy');  // 子类删除
        });

        // 部件类型
        $api->group(['prefix' => 'partModel'], function ($api) {
            $api->get('/', 'PartModelController@index')->name('api.partModel.index');  // 部件类型列表
            $api->get('/create', 'PartModelController@create')->name('api.partModel.create');  // 部件类型新建页面
            $api->post('/', 'PartModelController@store')->name('api.partModel.store');  // 部件类型新建
            $api->get('/{unique_code}', 'PartModelController@show')->name('api.partModel.show');  // 部件类型详情页面
            $api->get('/{unique_code}/edit', 'PartModelController@edit')->name('api.partModel.edit');  // 部件类型编辑页面
            $api->put('/{unique_code}', 'PartModelController@update')->name('api.partModel.update');  // 部件类型编辑
            $api->delete('/{unique_code}', 'PartModelController@destroy')->name('api.partModel.destroy');  // 部件类型删除
        });

        // 供应商管理
        $api->group(['prefix' => 'factory'], function ($api) {
            $api->get('/', 'FactoryController@index')->name('api.Factory.index');  // 供应商管理 列表
            $api->get('/create', 'FactoryController@create')->name('api.Factory.create');  // 供应商管理 新建页面
            $api->post('/', 'FactoryController@store')->name('api.Factory.store');  // 供应商管理 新建
            $api->get('/{unique_code}', 'FactoryController@show')->name('api.Factory.show');  // 供应商管理 详情页面
            $api->get('/{unique_code}/edit', 'FactoryController@edit')->name('api.Factory.edit');  // 供应商管理 编辑页面
            $api->put('/{unique_code}', 'FactoryController@update')->name('api.Factory.update');  // 供应商管理 编辑
            $api->delete('/{unique_code}', 'FactoryController@destroy')->name('api.Factory.destroy');  // 供应商管理 删除
        });

        // 现场车间
        $api->group(['prefix' => 'sceneWorkshop'], function ($api) {
            $api->get('/init', 'SceneWorkshopController@getInit')->name('api.sceneWorkshop.init.get');  // 从数据中台备份到本地
            $api->get('/', 'SceneWorkshopController@index')->name('api.sceneWorkshop.index');  // 现场车间 列表
            $api->get('/create', 'SceneWorkshopController@create')->name('api.sceneWorkshop.create');  // 现场车间 新建页面
            $api->post('/', 'SceneWorkshopController@store')->name('api.sceneWorkshop.store');  // 现场车间 新建
            $api->get('/{unique_code}', 'SceneWorkshopController@show')->name('api.sceneWorkshop.show');  // 现场车间 详情页面
            $api->get('/{unique_code}/edit', 'SceneWorkshopController@edit')->name('api.sceneWorkshop.edit');  // 现场车间 编辑页面
            $api->put('/{unique_code}', 'SceneWorkshopController@update')->name('api.sceneWorkshop.update');  // 现场车间 编辑
            $api->delete('/{unique_code}', 'SceneWorkshopController@destroy')->name('api.sceneWorkshop.destroy');  // 现场车间 删除
        });

        // 站场
        $api->group(['prefix' => 'station'], function ($api) {
            $api->get('/init', 'StationController@getInit')->name('api.station.init.get');  // 从数据中台备份到本地
            $api->get('/', 'StationController@index')->name('api.station.index');  // 站场 列表
            $api->get('/create', 'StationController@create')->name('api.station.create');  // 站场 新建页面
            $api->post('/', 'StationController@store')->name('api.station.store');  // 站场 新建
            $api->get('/{unique_code}', 'StationController@show')->name('api.station.show');  // 站场 详情页面
            $api->get('/{unique_code}/edit', 'StationController@edit')->name('api.station.edit');  // 站场 编辑页面
            $api->put('/{unique_code}', 'StationController@update')->name('api.station.update');  // 站场 编辑
            $api->delete('/{unique_code}', 'StationController@destroy')->name('api.station.destroy');  // 站场 删除
        });

        // 族群
        // $api->group(['prefix' => 'race'], function ($api) {
        //     $api->get('/', 'RaceController@index')->name('api.race.index');  // 族群 列表
        //     $api->get('/create', 'RaceController@create')->name('api.race.create');  // 族群 新建页面
        //     $api->post('/', 'RaceController@store')->name('api.race.store');  // 族群 新建
        //     $api->get('/{unique_code}', 'RaceController@show')->name('api.race.show');  // 族群 详情页面
        //     $api->get('/{unique_code}/edit', 'RaceController@edit')->name('api.race.edit');  // 族群 编辑页面
        //     $api->put('/{unique_code}', 'RaceController@update')->name('api.race.update');  // 族群 编辑
        //     $api->delete('/{unique_code}', 'RaceController@destroy')->name('api.race.destroy');  // 族群 删除
        // });
    });

    // 转辙机
    $api->group(['prefix' => 'pointSwitch', 'namespace' => 'App\Http\Controllers\V1'], function ($api) {
        $api->get('newIn/{factory_device_code}', 'PointSwitchController@newIn')->name('pda.pointSwitch.newIn');  // 获取新入所设备转辙机
        $api->put('updateEpcAndFixing/{epc}', 'PointSwitchController@updateEpcAndFixing')->name('pda.pointSwitch.updateEpcAndFixing');  // 修改EPC且检修入所
        $api->get('queryForChangePart', 'PointSwitchController@queryForChangePart')->name('pda.pointSwitch.queryForChangePart');  // 查询设备（更换部件）
        $api->put('changePart/{entire_identity_code}/{part_identity_code}', 'PointSwitchController@changePart')->name('pda.pointSwitch.changePart');  // 更换部件
    });

    // 周期修
    $api->group(['prefix' => 'cycleFix', 'namespace' => 'App\Http\Controllers\V1'], function ($api) {
        $api->get('months', 'CycleFixController@months')->name('pda.cycleFix.months');  // 获取月份列表
        $api->get('locations/{code}', 'CycleFixController@locations')->name('pda.cycleFix.locations');  // 根据设备编号获取需要轮修的设备位置
        $api->post('print/{code}', 'CycleFixController@print')->name('pda.cycleFix.print');  // 打印位置条码
    });

    // 出入所
    $api->group(['prefix' => 'warehouse', 'namespace' => 'App\Http\Controllers\V1\Warehouse'], function ($api) {
        $api->post('report/batch', 'ReportController@store')->name('pda.warehouse.report.store');  // 通过流水号提交批量扫码
        $api->post('report/rfid', 'ReportController@rfid')->name('pda.warehouse.report.rfid');  // 通过RFID提交批量扫码

        $api->group(['middleware' => ['api-check']], function ($api) {
            // $api->group(['middleware' => ['api-check-jwt']], function ($api) {
            $api->post('in', 'PostController@in')->name('pda.warehouse.in');  // 入库
            $api->post('stock', 'PostController@stock')->name('pda.warehouse.stock');  // 盘点
        });

        // 设备
        $api->group(['prefix' => 'entireInstance'], function ($api) {
            $api->get('/{id}', 'EntireInstanceController@show')->name('pda.warehouse.entireInstance.show');  // 通过厂编号或所编号获取设备列表
            $api->get('', 'EntireInstanceController@index')->name('api.warehouse.entireInstance.index');  // 获取设备列表（用于出入所）
            $api->post('batchBindingRFIDWithIdentityCode', 'EntireInstanceController@batchBindingRFIDWithIdentityCode')
                ->name('pda.warehouse.entireInstance.batchBindingRFIDWithIdentityCode');  // 获取设备列表（用于出入所）
        });

        $api->group(['prefix' => 'storage'], function ($api) {
            $api->post('scanInBatch', 'StorageController@postScanInBatch')->name('pda.warehouse.storageScanInBatch.post');  // 扫码入库
        });
    });

    $api->group(['prefix' => 'entireInstance', 'namespace' => 'App\Http\Controllers\V1'], function ($api) {
        $api->get('/forPointSwitchQuery', 'EntireInstanceController@forPointSwitchQuery')->name('pda.entireInstance.forPointSwitchQuery.show');  // 转辙机专用详情
        $api->get('/{code}', 'EntireInstanceController@show')->name('pda.entireInstance.show');  // 设备履历
    });

    // $api->group(['prefix' => 'storage', 'namespace' => 'App\Http\Controllers'], function ($api) {
    //    $api->get('/', 'StorageController@index')->name('api.warehouse.index');  // 出入库列表
    //    $api->get('/create', 'StorageController@create')->name('api.warehouse.create');  // 出入库新建页面
    //    $api->post('/', 'StorageController@store')->name('api.warehouse.store');  // 出入库新建
    //    $api->get('/{id}', 'StorageController@show')->name('api.warehouse.show');  // 出入库详情页面
    //    $api->get('/{id}/edit', 'StorageController@edit')->name('api.warehouse.edit');  // 出入库编辑页面
    //    $api->put('/{id}', 'StorageController@update')->name('api.warehouse.update');  // 出入库编辑
    //    $api->delete('/{id}', 'StorageController@destroy')->name('api.warehouse.destroy');  // 出入库删除
    // });

    // 登录
    $api->post('login', 'App\Http\Controllers\V1\AccountController@login')->name('api.login');  // 登录
    $api->get('pdaBaseInfo', 'App\Http\Controllers\V1\PDABaseInfoController@index')->name('api.pdaBaseInfo.index');  // 更新手持终端基础信息
    $api->get('pdaBaseInfo/check', 'App\Http\Controllers\V1\PDABaseInfoController@isNeedUpload')->name('api.pdaBaseInfo.isNeedUpload');  // 更新手持终端基础信息

    // 微信小程序
    $api->group(['prefix' => 'wechatMiniApp', 'namespace' => 'App\Http\Controllers\V1'], function ($api) {
        $api->get('wechatOpenIdByJsCode', 'WechatMiniAppController@getWechatOpenIdByJsCode')->name('api.WechatMiniApp.getWechatOpenIdByJsCode');  // 微信小程序登陆
        $api->post('correctMaintainLocation', 'WechatMiniAppController@postCorrectMaintainLocation')->name('api.WechatMiniApp.postCorrectMaintainLocation');  // 纠正上道位置
        $api->get('stationInstallLocationCodesByWechatOpenId', 'WechatMiniAppController@getStationInstallLocationCodesByWechatOpenId')->name('api.WechatMiniApp.getStationInstallLocationCodesByWechatOpenId');  // 获取员工绑定记录
        $api->get('sceneWorkshopsByParagraphUniqueCode/{paragraphUniqueCode?}', 'WechatMiniAppController@getSceneWorkshopsByParagraphUniqueCode')->name('api.WechatMiniApp.getSceneWorkshops');  // 现场车间列表
        $api->get('stationsBySceneWorkshopUniqueCode/{sceneWorkshopUniqueCode}', 'WechatMiniAppController@getStationsBySceneWorkshopUniqueCode')->name('api.WechatMiniApp.getStationsBySceneWorkshopUniqueCode');  // 根据现场车间代码获取车站
        $api->get('stationsByName', 'WechatMiniAppController@getStationsByName')->name('api.WechatMiniApp.getStationsByName');  // 通过名称获取车站
        $api->get('subModels', 'WechatMiniAppController@getSubModels')->name('api.WechatMiniApp.getSubModels');  // 获取种类型
        $api->get('subModelsByName', 'WechatMiniAppController@getSubModelsByName')->name('api.WechatMiniApp.getSubModelsByName');  // 获取根据名称获取型号
        $api->get('checkStationInstallUser', 'WechatMiniAppController@getCheckStationInstallUser')->name('api.WechatMiniApp.getCheckStationInstallUser'); // 检查员工是否已经注册
        $api->post('registerStationInstallUser', 'WechatMiniAppController@postRegisterStationInstallUser')->name('api.WechatMiniApp.postRegisterStationInstallUser');  // 注册员工
        $api->get('paragraphs', 'WechatMiniAppController@getBasicParagraphs')->name('api.WechatMiniApp.getBasicParagraphs');  // 获取电务段列表
        $api->get('stationLocationsByWechatOpenId', 'WechatMiniAppController@getStationLocationsByWechatOpenId')->name('api.WechatMiniApp.getStationLocationsByWechatOpenId');  // 根据微信openid获取车站补登记录
        $api->post('stationLocation', 'WechatMiniAppController@postStationLocation')->name('api.WechatMiniApp.postStationLocation');  // 补登车站信息
        $api->post('collectDeviceOrder', 'WechatMiniAppController@postCollectDeviceOrder')->name('api.WechatMiniApp.postCollectDeviceOrder');  // 生成采集器材单（保存之前采集的器材）
        $api->post('collectDeviceOrderEntireInstance', 'WechatMiniAppController@postCollectDeviceOrderEntireInstance')->name('api.WechatMiniApp.postCollectDeviceOrderEntireInstance');  // 采集器材基础信息
        $api->get('downloadCollectDeviceOrder/{sn}', 'WechatMiniAppController@getDownloadCollectDeviceOrder')->name('api.WechatMiniApp.getDownloadCollectDeviceOrder');  // 下载基础信息采集单Excel
        $api->get('factories', 'WechatMiniAppController@getFactories')->name('api.WechatMiniApp.getFactories');  // 获取供应商列表
        $api->any('test', 'WechatMiniAppController@anyTest')->name('api.WechatMiniApp.anyTest');  // 测试
        $api->post('subModel', 'WechatMiniAppController@postSubModel')->name('api.WechatMiniApp.postSubModel');  // 添加种类型
        $api->get('accessToken', 'WechatMiniAppController@getAccessToken')->name('api.WechatMiniApp.getAccessToken');  // 获取access_token
        $api->get('jsApiTicket', 'WechatMiniAppController@getJsApiTicket')->name('api.WechatMiniApp.getJsApiTicket');  // 获取js_api_ticket
        $api->get('jsApiSignature', 'WechatMiniAppController@getJsApiSignature')->name('api.WechatMiniApp.getJsApiSignature');  // 获取js_api_signature

        $api->get('positionWithInstallTier', 'WechatMiniAppController@getPositionWithInstallTier')->name('api.WechatMiniApp.positionWithInstallTier'); // 器材根据位置编码获取层
        $api->group(['prefix' => 'tmpMaterialCollection'], function ($api) {
            # 数据采集
            $api->post('/', 'WechatMiniAppController@storeTmpEntireInstanceCollection')->name('api.WechatMiniApp.tmpEntireInstanceCollection.store'); # 临时-数据采集-添加
            $api->put('/{id}', 'WechatMiniAppController@updateTmpEntireInstanceCollection')->name('api.WechatMiniApp.tmpEntireInstanceCollection.update'); # 临时-数据采集-编辑
            $api->delete('/{id}', 'WechatMiniAppController@destroyTmpEntireInstanceCollection')->name('api.WechatMiniApp.tmpEntireInstanceCollection.destroy'); # 临时-数据采集-删除
        });
        // 数据定位
        $api->group(['prefix' => 'collectionOrder'], function ($api) {
            $api->group(['prefix' => 'station'], function ($api) {
                $api->get('/', 'WechatMiniAppController@indexCollectionOrderStation')->name('api.WechatMiniApp.collectionOrder.indexCollectionOrderStation');  #  数据定位（车站）-列表
                $api->post('/', 'WechatMiniAppController@storeCollectionOrderStation')->name('api.WechatMiniApp.collectionOrder.storeCollectionOrderStation');  #  数据定位（车站）-添加
            });

            $api->group(['prefix' => 'location'], function ($api) {
                $api->get('/', 'WechatMiniAppController@indexCollectionOrderLocation')->name('api.WechatMiniApp.collectionOrder.location.index'); # 数据定位-列表
                $api->post('/', 'WechatMiniAppController@storeCollectionOrderLocation')->name('api.WechatMiniApp.collectionOrder.location.store'); # 数据定位-添加
            });

            $api->get('download', 'WechatMiniAppController@downloadCollectionOrder')->name('api.WechatMiniApp.collectionOrder.download'); # 数据采集-下载
            $api->post('/', 'WechatMiniAppController@storeCollectionOrder')->name('api.WechatMiniApp.collectionOrder.store'); # 数据采集-添加
        });
        // 室外设备拍照
        $api->group(['prefix' => 'collectionImage',], function ($api) {
            $api->post('/', 'WechatMiniAppController@getCollectionImages')->name('api.WechatMiniApp.getCollectionImages.index');  // 查看上传照片
            $api->post('/', 'WechatMiniAppController@postCollectionImages')->name('api.WechatMiniApp.postCollectionImages.store');  // 上传采集照片
        });
    });

    // 监控大屏（不需要JWT）
    $api->group(['prefix' => 'monitor', 'namespace' => 'App\Http\Controllers\V1'], function ($api) {
        $api->get('basicInfo', 'MonitorController@getBasicInfo')->name('api.monitor.getBasicInfo');  // 基础信息
        $api->get('status', 'MonitorController@getStatus')->name('api.monitor.getStatus');  // 状态统计（左上）
        $api->get('property', 'MonitorController@getProperty')->name('api.monitor.getProperty');  // 资产统计（左中）
        $api->get('installing', 'MonitorController@getInstalling')->name('api.monitor.getInstalling');  // 备品统计（左下）
        $api->get('breakdown', 'MonitorController@getBreakdown')->name('api.monitor.getBreakdown');  // 故障统计（右上）
        $api->get('scraped', 'MonitorController@getScraped')->name('api.monitor.getScraped');  // 超期统计（右中）
        $api->get('maintain', 'MonitorController@getMaintain')->name('api.monitor.getMaintain');  // 台长统计（右中）
        $api->get('entireInstance', 'MonitorController@getEntireInstances')->name('api.monitor.getEntireInstances');  // 设备列表
    });

    // PDA（不需要JWT验证）
    $api->group(['prefix' => 'pda', 'namespace' => 'App\Http\Controllers\V1'], function ($api) {
        $api->post('register', 'PDAController@postRegister')->name('api.pda.postRegister');  // 注册
        $api->post('login', 'PDAController@postLogin')->name('api.pda.postLogin');  // 登录
        $api->get('accounts', 'PDAController@getAccounts')->name('api.pda.getAccounts');  // 用户列表
        $api->any('makeSign', 'PDAController@anyMakeSign')->name('api.pda.anyMakeSign');  // 签名
        $api->any('checkSign', 'PDAController@anyCheckSign')->name('api.pda.anyTestSign');  // 验签
        $api->get('maintains', 'PDAController@getMaintains')->name('api.pda.getMaintains');  // 基础数据->车间车站
        $api->get('workAreas', 'PDAController@getWorkAreas')->name('api.pda.getWorkAreas');  // 获取工区列表
        $api->get('ranks', 'PDAController@getRanks')->name('api.pda.getRanks'); // 获取职务列表
        $api->get('entireInstanceByIdentityCode/{identityCode}', 'PDAController@getEntireInstance')->name('api.pda.getEntireInstance');  // 根据唯一编号获取设备器材详情
        $api->put('entireInstanceByIdentityCode/{identity_code}', 'PDAController@putEntireInstance')->name('api.pda.putEntireInstance');  // 根据唯一编号修改设备器材详情
        $api->post('sparesStatistics', 'PDAController@postSparesStatistics')->name('api.pda.postSparesStatistics');  // 备品统计
    });

    // PDA（需要JWT验证）
    $api->group(['prefix' => 'pda', 'namespace' => 'App\Http\Controllers\V1', 'middleware' => 'api-check-jwt'], function ($api) {
        $api->get('checkProject/{id?}', 'PDAController@getCheckProject')->name('api.pda.checkProject');  // 检修项目列表、详情
        $api->get('checkPlan/{sn?}', 'PDAController@getCheckPlan')->name('api.pda.checkPlan');  // 检修计划列表、详情

        $api->get('entireInstanceForCreateTaskStationCheckOrder', 'PDAController@getEntireInstanceForCreateTaskStationCheckOrder')->name('api.pda.getEntireInstanceForCreateTaskStationCheckOrder');  // 获取设备列表 用于创建临时任务
        $api->get('taskStationCheckStatisticForProject', 'PDAController@getTaskStationCheckStatisticForProject')->name('api.pda.taskStationCheckOrder.getTaskStationCheckOrderStatisticForProject');  // 现场检修任务单统计
        $api->get('taskStationCheckOrder', 'PDAController@getTaskStationCheckOrders')->name('api.pda.taskStationCheckOrder.getTaskStationCheckOrders');  // 获取现场检修任务列表
        $api->post('taskStationCheckOrder', 'PDAController@postTaskStationCheckOrder')->name('api.pda.taskStationCheckOrder.postTaskStationCheckOrder');  // 新建现场检修任务
        $api->put('taskStationCheckOrder', 'PDAController@putTaskStationCheckOrder')->name('api.pda.taskStationCheckOrder.putTaskStationCheckOrder');  // 编辑现场检修任务
        $api->delete('taskStationCheckOrder', 'PDAController@deleteTaskStationCheckOrder')->name('api.pda.taskStationCheckOrder.deleteTaskStationCheckOrder');  // 删除现场检修任务
        $api->get('taskStationCheckOrder/{sn}', 'PDAController@getTaskStationCheckOrder')->name('api.pda.taskStationCheckOrder.getTaskStationCheckOrder');  // 获取现场检修任务详情
        $api->post('taskStationCheckEntireInstance/{sn}', 'PDAController@postTaskStationCheckEntireInstance')->name('api.pda.taskStationCheckOrder.postTaskStationCheckEntireInstance');  // 添加现场检修任务设备
        $api->get('taskStationCheckEntireInstances', 'PDAController@getTaskStationCheckEntireInstances')->name('api.pda.taskStationCheckOrder.getTaskStationCheckEntireInstances');  // 获取现场检修任务
        $api->delete('taskStationCheckEntireInstance/{sn}', 'PDAController@deleteTaskStationCheckEntireInstance')->name('api.pda.taskStationCheckOrder.deleteTaskStationCheckEntireInstance');  // 现场检修任务 删除设备

        $api->get('stationInstallLocationRecords', 'PDAController@getStationInstallLocationRecords')->name('api.pda.getStationInstallLocationRecords');  // 获取设备安装定位记录
        $api->get('sceneWorkshops', 'PDAController@getSceneWorkshops')->name('api.pda.SceneWorkshops');  // 获取现场车间列表
        $api->post('correctMaintainLocation', 'PDAController@postCorrectMaintainLocation')->name('api.pda.postCorrectMaintainLocation');  // 纠正设备上道位置
        $api->get('entireInstances/', 'PDAController@getEntireInstances')->name('api.pda.getEntireInstances');  // 获取设备列表
        $api->get('breakdownOrders', 'PDAController@getBreakdownOrders')->name('api.pda.getBreakdownOrders');  // 获取故障修出入所任务列表
        $api->get('factories', 'PDAController@getFactories')->name('api.pda.getFactories');  // 获取供应商列表
        $api->get('sourceTypes', 'PDAController@getSourceTypes')->name('api.pda.getSourceTypes');  // 获取来源类型列表
        $api->get('lines', 'PDAController@getLines')->name('api.pda.getLines');  // 获取线别列表

        // 获取车站列表
        $api->get('stations', 'PDAController@getStations')->name('api.pda.getStations');  // 获取车站列表

        // 基础数据->种类型
        $api->get('types', 'PDAController@getTypes')->name('api.pda.getTypes');  // 基础数据->种类型

        // 基础数据->仓库位置
        $api->get('locations', 'PDAController@getLocations')->name('api.pda.getLocations');  // 基础数据->仓库位置

        // 搜索->报废
        $api->post('scrapOfSearch', 'PDAController@postScrapOfSearch')->name('api.pda.postScrapOfSearch');  // 搜索->报废
        // 报废
        $api->post('scrap', 'PDAController@postScrap')->name('api.pda.postScrap');  // 报废

        // 搜索->报损
        $api->post('frmLossOfSearch', 'PDAController@postFrmLossOfSearch')->name('api.pda.postFrmLossOfSearch');  // 搜索->报损
        // 报损
        $api->post('frmLoss', 'PDAController@postFrmLoss')->name('api.pda.postFrmLoss');  // 报损

        // 搜索->通用入所
        $api->post('workshopInOfSearch', 'PDAController@postWorkshopInOfSearch')->name('api.pda.postWorkshopInOfSearch');  // 搜索->通用入所
        // 通用入所
        $api->post('workshopIn', 'PDAController@postWorkshopIn')->name('api.pda.postWorkshopIn');  // 通用入所
        $api->post('sceneBackIn', 'PDAController@postSceneBackIn')->name('api.pda.postSceneBackIn');  // 现场退回
        $api->post("signImg", "PDAController@PostSignImg")->name("api.pad.PostSignImg");  // 出入所单签字

        // 搜索->通用出所(判断是否有安装位置)
        $api->post('workshopOutOfSearch', 'PDAController@postWorkshopOutOfSearch')->name('api.pda.postWorkshopOutOfSearch');  // 搜索->通用出所(判断是否有安装位置)
        // 通用出所
        $api->post('workshopOut', 'PDAController@postWorkshopOut')->name('api.pda.postWorkshopOut');  // 通用出所
        $api->get('prepareWarehouseReport', 'PDAController@getPrepareWarehouseReport')->name('api.pda.getPrepareWarehouseReport');  // 获取出所/待出所单
        $api->get('warehouseReportEntireInstances', 'PDAController@getWarehouseReportEntireInstances')->name('api.pda.getWarehouseReportEntireInstances');  // 获取出所/待出所单设备
        $api->get('warehouseReportEntireInstances', 'PDAController@postWarehouseReportEntireInstances')->name('api.pda.postWarehouseReportEntireInstances');  // 根据待出所单出所

        // 搜索->入库
        $api->post('warehouseInOfSearch', 'PDAController@postWarehouseInOfSearch')->name('api.pda.postWarehouseInOfSearch');  // 搜索->入库
        // 入库
        $api->post('warehouseIn', 'PDAController@postWarehouseIn')->name('api.pda.postWarehouseIn');  // 入库

        // 盘点
        $api->group(['prefix' => 'takeStock'], function ($api) {
            $api->post('ready', 'PDAController@takeStockReady')->name('api.pda.takeStock.ready'); // 根据盘点区域编码获取设备数据
            $api->post('scanCode', 'PDAController@takeStockScanCode')->name('api.pda.takeStock.scanCode'); // 盘点扫码
            $api->post('', 'PDAController@takeStock')->name('api.pda.takeStock'); // 盘点差异分析
        });

        // 搜索->上道
        $api->get('installed', 'PDAController@getInstalled')->name('api.pda.getInstalled');  // 搜索->上道
        // 上道
        $api->post('installed', 'PDAController@postInstalled')->name('api.pda.postInstalled');  // 上道
        $api->post('inDoorInstalledStrictAndInstalling/{entire_instance_identity_code}', 'PDAController@postInDoorInstalledStrictAndInstalling')->name('api.pda.postInDoorInstalledStrictAndInstalling');  // 室内上道（严格模式）和备品入柜（严格模式）
        $api->post('outDoorInstalledUnStrictAndInDoorInstalledStrict/{entire_instance_identity_code}', 'PDAController@postOutDoorInstalledUnStrictAndInDoorInstalledStrict')->name('api.pda.postOutDoorInstalledUnStrictAndInDoorInstalledStrict');  // 室外上道和室内上道（非严格模式）
        $api->post('bindInstallPosition/{entire_instance_identity_code}', 'PDAController@postBindInstallPosition')->name('api.pda.postBindInstallPosition');  // 现场绑定室内上道位置

        // 搜索->下道
        $api->get('uninstall', 'PDAController@getUnInstall')->name('api.pda.getUnInstall');  // 搜索->下道
        // 下道
        $api->post('uninstall', 'PDAController@postUnInstall')->name('api.pda.postUnInstall');  // 下道

        // 搜索->现场备品入柜
        $api->get('installing', 'PDAController@getInstalling')->name('api.pda.getInstalling');  // 搜索->现场备品入库
        // 现场备品入柜
        $api->post('installing', 'PDAController@postInstalling')->name('api.pda.postSceneWarehouseIn');  // 现场备品入库

        // 搜索->整件or部件
        $api->post('bindOfSearch', 'PDAController@postBindOfSearch')->name('api.pda.postBindOfSearch');  // 搜索->整件or部件
        // 部件绑定/解绑/换绑
        $api->post('bind', 'PDAController@postBind')->name('api.pda.postBind');  // 部件绑定/解绑/换绑

        // 搜索(整件设备编码/种类型编码or库房位置编码)
        $api->post('search', 'PDAController@postSearch')->name('api.pda.postSearch');  // 搜索(整件设备编码/种类型编码or库房位置编码)

        // V250获取任务列表
        $api->post('taskList', 'PDAController@postTaskList')->name('api.pda.postTaskList');  // V250获取任务列表

        // V250获取任务列表基础信息
        $api->post('listDetails', 'PDAController@postListDetails')->name('api.pda.postListDetails');  // V250获取任务列表基础信息

        // V250获取待出所单(新站)
        $api->post('workshopStayOut', 'PDAController@postWorkshopStayOut')->name('api.pda.postWorkshopStayOut');  // V250获取待出所单(新站)

        // V250获取待出所单设备详情(新站)
        $api->post('workshopStayOutEntireInstances', 'PDAController@postWorkshopStayOutEntireInstances')->name('api.pda.postWorkshopStayOutEntireInstances');  // V250获取待出所单设备详情(新站)

        // V250出所(新站)
        $api->post('passWorkshopOut', 'PDAController@passWorkshopOut')->name('api.pda.passWorkshopOut');  // V250出所(新站)

        // 设备验收
        $api->post('checkDevice', 'PDAController@postCheckDevice')->name('api.pda.postCheckDevice');  // 设备验收

        // V250根据工区获取人员
        $api->post('personnel', 'PDAController@postPersonnel')->name('api.pda.postPersonnel');  // V250根据工区获取人员

        // V250检修分配
        $api->post('overhaul', 'PDAController@postOverhaul')->name('api.pda.postOverhaul');  // V250检修分配

        // V250搜索->检修完成
        $api->post('overhaulOfSearch', 'PDAController@postOverhaulOfSearch')->name('api.pda.postOverhaulOfSearch');  // 搜索->检修完成

        // V250检修完成
        $api->post('completeOverhaul', 'PDAController@postCompleteOverhaul')->name('api.pda.postCompleteOverhaul');  // V250检修完成

        // 人员
        $api->get('account/{id}', 'PDAController@getAccount')->name('api.pda.getAccount');  // 人员详情
        $api->get('accounts', 'PDAController@getAccounts')->name('api.pda.getAccounts');  // 人员列表

        // 上道位置
        $api->group(['prefix' => 'installPosition'], function ($api) {
            $api->get('byInstallTierUniqueCode/{tier_unique_code}', 'PDAController@getInstallPositionsByInstallTierUniqueCode')->name('api.pda.getInstallPositionsByInstallTierUniqueCode');  // 获取上道位置
        });

        // 转辙机整部件绑定
        $api->group(['prefix' => 'bind',], function ($api) {
            $api->get('/entireInstance/{identity_code}', 'PDAController@getEntireInstanceForBind')->name('api.pad.bind.getEntireInstanceForBind');  // 获取整件信息（绑定）
            $api->get('/partInstance/{entire_instance_identity_code}', 'PDAController@getPartInstanceForBind')->name('api.pad.bind.getPartInstanceForBind');  // 获取部件信息（绑定）
            $api->post('/partInstances/{entire_instance_identity_code}', 'PDAController@postBindPartInstances')->name('api.pad.bind.postBindPartInstances');  // 批量绑定部件
            $api->delete('/partInstance/{identity_code}', 'PDAController@deleteUnbindPartInstance')->name('api.pad.bind.deleteUnbindPartInstance');  // 解绑部件
        });

        $api->get('installShelf', 'PDAController@getInstallShelfWithIndex')->name('api.pda.getInstallShelves'); # 获取上道位置机房/排/架
        $api->get('installPosition', 'PDAController@getInstallPositionWithIndex')->name('api.pda.getInstallPosition'); # 获取上道位置架/层/位

        $api->get('fixWorkflows/{entire_instance_identity_code}', 'PDAController@getFixWorkflows')->name('api.pda.getFixWorkflows');  // 根据设备器材唯一编号获取检修单列表

        $api->get("entireInstanceLogsByOperatorId/{operator_id}", "PDAController@GetEntireInstanceLogsByOperatorId")->name("api.pda.GetEntireInstanceLogsByOperatorId");  // 根据操作人编号获取日志
    });

    // 需要验证jwt
    $api->group(['middleware' => ['api-check']], function ($api) {
        $api->get('jwt', 'App\Http\Controllers\V1\AccountController@jwt')->name('api.jwt');  // 测试jwt是否通过

        // 设备整件
        $api->group(['prefix' => 'entireInstance', 'namespace' => 'App\Http\Controllers\V1'], function ($api) {
            $api->get('/forPointSwitchQuery', 'EntireInstanceController@forPointSwitchQuery')->name('api.entireInstance.forPointSwitchQuery');  // 转辙机专区专用查询设备
            $api->get('/asBatch', 'EntireInstanceController@getAsBatch')->name('api.entireInstance.asBatch.get');  // 批量入所准备获取设备唯一编号列表
            $api->post('/asBatch', 'EntireInstanceController@postAsBatch')->name('api.entireInstance.qasBatch.post');  // 批量入所准备获取设备唯一编号列表
            $api->post('fixing', 'EntireInstanceController@fixing')->name('api.entireInstance.fixing');  // 入所
            $api->post('station', 'EntireInstanceController@station')->name('api.entireInstance.station');  // 出所
            $api->post('scrap', 'EntireInstanceController@scrap')->name('api.entireInstance.scrap');  // 报废
            $api->any('install', 'EntireInstanceController@install')->name('api.entireInstance.install');  // 室外上道安装
            $api->any('installs', 'EntireInstanceController@installs')->name('api.entireInstance.installs');  // 室内上道安装
            $api->put('uninstall', 'EntireInstanceController@uninstall')->name('api.entireInstance.uninstall');  // 下道
            $api->post('/bindingRfidTid/{identityCode}', 'EntireInstanceController@bindingRfidTid')->name('entireInstance.bindingRfidTid');  // 设备整件绑定RFID TID
            $api->get('/', 'EntireInstanceController@index')->name('api.entireInstance.index');  // 设备整件列表
            $api->get('/create', 'EntireInstanceController@create')->name('api.entireInstance.create');  // 设备整件新建页面
            $api->post('/', 'EntireInstanceController@store')->name('api.entireInstance.store');  // 设备整件新建
            $api->get('/{id}', 'EntireInstanceController@show')->name('api.entireInstance.show');  // 设备整件详情页面
            $api->get('/{id}/edit', 'EntireInstanceController@edit')->name('api.entireInstance.edit');  // 设备整件编辑页面
            $api->put('/{id}', 'EntireInstanceController@update')->name('api.entireInstance.update');  // 设备整件编辑
            $api->delete('/{id}', 'EntireInstanceController@delete')->name('api.entireInstance.delete');  // 设备整件删除
        });

        // 设备类型
        $api->group(['prefix' => 'entireModel'], function ($api) {
            $api->get('standby', 'App\Http\Controllers\V1\EntireModelController@standby')->name('api.entireModel.standby');  // 备品备件
            $api->post('installWithStandby', 'App\Http\Controllers\V1\EntireModelController@installWithStandby')->name('api.entireModel.installWithStandby');  // 上道（备品查询）
        });

        // 检修单
        $api->group(['prefix' => 'fixWorkflow'], function ($api) {
            $api->get('entireInstance', 'App\Http\Controllers\V1\FixWorkflowController@entireInstance')->name('api.fixWorkflow.entireInstance');  // 通过设备获取检修历史
            $api->get('/{serialNumber}', 'App\Http\Controllers\V1\FixWorkflowController@show')->name('api.fixWorkflow.show');  // 检修单详情
            $api->get('record', 'App\Http\Controllers\V1\FixWorkflowController@record')->name('api.fixWorkflow.record');  // 设备履历
        });
    });

    // 辉煌
    $api->group(['prefix' => 'HH'], function ($api) {
        // 自由访问
        $api->get('entireInstancesByStationName', 'App\Http\Controllers\V1\HHController@getEntireInstancesByStationName')->name('api.HH.api1.entireInstancesByStationName.get');  // api1
        $api->post('fixWorkflowsByEIID', 'App\Http\Controllers\V1\HHController@postFixWorkflowsByEIID')->name('api.HH.fixWorkflowsByEIID.post');  // api2
        $api->get('recodesByProcessSN/{fix_workflow_process_sn}', 'App\Http\Controllers\V1\HHController@getRecodesByProcessSN')->name('api.HH.recodesByProcessSN.get');  // api3
        $api->post('breakdownExplain', 'App\Http\Controllers\V1\HHController@postBreakdownExplain')->name('HH.breakdownExplain.post');  // api4
        $api->post('entireInstanceLogsByEIID', 'App\Http\Controllers\V1\HHController@postEntireInstanceLogsByEIID')->name('api.HH.entireInstanceLogsByEIID.post');  // api5
        $api->get('entireInstance/{identityCode}', 'App\Http\Controllers\V1\HHController@getEntireInstance')->name('api.HH.entireInstance.get');  // 测试1
        $api->get('fixWorkflows/{identity_code}', 'App\Http\Controllers\V1\HHController@getFixWorkflows')->name('api.HH.getFixWorkflows');  //  根据设备器材编号获取检修单历史记录
    });

    // 第三方
    $api->group(['prefix' => 'thirdParty'], function ($api) {  // 第三方
        // 自由访问
        $api->post('login', 'App\Http\Controllers\V1\ThirdPartyController@login')->name('login');  // 登录

        // 需要登录
        $api->group(['middleware' => ['api-check-jwt']], function ($api) {
            $api->any('test', 'App\Http\Controllers\V1\ThirdPartyController@test')->name('test');  // 测试
            $api->post('installQ/{id}', 'App\Http\Controllers\V1\ThirdPartyController@installQ')->name('installQ');  // 安装设备单元
            $api->post('installS/{id}', 'App\Http\Controllers\V1\ThirdPartyController@installS')->name('installS');  // 安装关键器件
            $api->put('password', 'App\Http\Controllers\V1\ThirdPartyController@password')->name('password');  // 修改密码
            $api->get('fixWorkflows/{type}/{id}', 'App\Http\Controllers\V1\ThirdPartyController@fixWorkflows')->name('fixWorkflow');  // 检修历史
            $api->get('entireInstance/{type}/{id}', 'App\Http\Controllers\V1\ThirdPartyController@entireInstance')->name('entireInstance');  // 设备详情
        });
    });

    // 仁昊 detector doesn't nee jwt authorization
    $api->post('rh/file', 'App\Http\Controllers\V1\RHController@postFile')->name('api.rh.postFile');  // ren hao fix work upload file

    // 标准检测台
    // $detector_router_prefix = "";
    switch(env("ORGANIZATION_CODE")){
        case "B050":
            $detector_router_prefix = "detector50220616";
            break;
        default:
            $detector_router_prefix = "detector";
            break;
    }
    $api->group(['prefix' => $detector_router_prefix, 'middleware' => 'CheckDetectorUserSecretKey',], function ($api) {
        $api->get('/', 'App\Http\Controllers\V1\DetectorController@index')->name('api.detector.index');  // test page
        $api->post('/', 'App\Http\Controllers\V1\DetectorController@store')->name('api.detector.store');  // stand detector store testing data
        $api->get('/entireInstance', 'App\Http\Controllers\V1\DetectorController@getEntireInstance')->name('api.detector.getEntireInstance');  // find entire instance return exists and kinds name
        $api->post('/file', 'App\Http\Controllers\V1\DetectorController@postFile')->name('api.detector.postFile');  // stand detector store testing file
        $api->post('/excel', 'App\Http\Controllers\V1\DetectorController@postExcel')->name('api.detector.postExcel');  // use excel upload detector store test data
        $api->get('/checkExists/{code}', 'App\Http\Controllers\V1\DetectorController@getExists')->name('api.detector.getCheckExists'); // check entire instance exists
        $api->post('/login', 'App\HttpControllers\V1\DetectorController@postLogin')->name('api.detector.postLogin');  // login
    });

    // bim
    $api->group(['prefix' => 'bim', 'namespace' => 'App\Http\Controllers\V1'], function ($api) {
        $api->get('/entireInstances', 'BIMController@getEntireInstances')->name('api.bim.getEntireInstances');  // 获取设备列表
        $api->get('/entireInstance/{identity_code}', 'BIMController@getEntireInstance')->name('api.bim.getEntireInstance');  // 获取设备详情
        $api->get('/entireInstanceLogs/{identity_code}', 'BIMController@getEntireInstanceLogs')->name('api.bim.getEntireInstanceLogs');  // 获取设备日志
    });

    # 同步
    $api->group([
        "prefix" => "sync",
        "namespace" => "App\Http\Controllers\V1",
        "middleware" => "CheckParagraphSyncBasicData",
    ], function ($api) {
        $api->post("equipment/type", "SyncController@PostEquipmentType")->name("api.sync.PostEquipmentType"); // 同步器材种类型
        $api->post("facility/type", "SyncController@PostFacilityType")->name("api.sync.PostFacilityType");  // 同步设备种类型列表
        $api->post("line", "SyncController@PostLine")->name("api.sync.PostLine"); // 同步线别列表
        $api->post("workshop", "SyncController@PostWorkshop")->name("api.sync.PostWorkshop"); // 同步车间列表
        $api->post("station", "SyncController@PostStation")->name("api.sync.PostStation"); // 同步车站列表
        $api->post("factory", "SyncController@PostFactory")->name("api.sync.PostFactory"); // 同步供应商
        $api->post("workArea", "SyncController@PostWorkArea")->name("api.sync.PostWorkArea");  // 同步工区
        $api->post("installLocation", "SyncController@PostInstallLocation")->name("api.sync.PostInstallLocation");  // 同步上道位置
        $api->post("source", "SyncController@PostSource")->name("api.sync.PostSource");  // 同步来源名称
        $api->post("location", "SyncController@PostLocation")->name("api.sync.PostLocation");  // 同步仓库位置
    });

    // bi
    $api->group([
        'prefix' => 'bi',
        'namespace' => 'App\Http\Controllers\V1',
    ], function ($api) {
        $api->get('equipmentStatusStatistics', 'BIController@getEquipmentStatusStatistics')->name('api.bi.getEquipmentStatusStatistics');  // 器材备品统计
        $api->get('equipmentCountBySceneWorkshop', 'BIController@getEquipmentCountBySceneWorkshop')->name('api.bi.getEquipmentCountBySceneWorkshop');  // 器材数量统计（车间分组）
        $api->get('facilityAndOverdueStatistics', 'BIController@getFacilityAndOverdueStatistics')->name('api.bi.getFacilityAndOverdueStatistics');  // 设备数量和超期数量统计
        $api->get('equipmentAndOverdueStatistics', 'BIController@getEquipmentAndOverdueStatistics')->name('api.bi.getEquipmentAndOverdueStatistics');  // 器材数量和超期数量统计
        $api->get('standbyAndBreakdownStatistics', 'BIController@getStandbyAndBreakdownStatistics')->name('api.bi.getStandbyAndBreakdownStatistics');  // 设备、器材备品和故障统计
        $api->get('facility/{identity_code}', 'BIController@getFacility')->name('api.bi.getFacility');  // 设备详情
        $api->get('equipment/{identity_code}', 'BIController@getEquipment')->name('api.bi.getEquipment');  // 器材详情
        $api->get('logs', 'BIController@getLogs')->name('api.bi.getLogs');  // 根据唯一编号获取日志
    });

    // *** v2 ***
    $api->group([
        "prefix" => "v2",
        "namespace" => "App\Http\Controllers\V2",
        "name" => "api.v2.",
    ], function ($api) {
        // 器材
        $api->group([
            "prefix" => "entire",
            "namespace" => "Entire",
            "name" => "Entire.",
        ], function ($api) {
            $api->group([
                "prefix" => "instance",
                "name" => "Instance:",
            ], function ($api) {
                $api->get("{identity_code}", "InstanceController@show")->name("show");  // 详情
            });
        });

        // 故障修
        $api->group([
            "prefix" => "breakdown",
            "namespace" => "Breakdown",
            "name" => "breakdown:",
        ], function ($api) {
            $api->post("sceneBreakdownDescription/{entireInstanceIdentityCode}", "PostController@PostSceneBreakdownDescription")->name("PostSceneBreakdownDescription");  // 现场故障描述
            $api->post("unInstall", "PostController@PostUnInstall")->name("PostUnInstall");  // 下道
        });

        // 段中心相关
        $api->group([
            "prefix" => "paragraphCenter",
            "namespace" => "ParagraphCenter",
            "name" => "ParagraphCenter.",
        ], function ($api) {
            // 检修模板
            $api->group([
                "prefix" => "measurement",
                "name" => "Measurement:"
            ], function ($api) {
                $api->post("/", "MeasurementController@store")->name("store");  // 创建
                $api->put("/{serial_number}", "MeasurementController@update")->name("update");  // 编辑
            });
            // 检修模板步骤
            $api->group([
                "prefix" => "measurementStep",
                "name" => "MeasurementStep:"
            ], function ($api) {
                $api->get("/", "MeasurementStepController@index")->name("index");  // 列表
            });
            // 检修
            $api->group([
                "prefix" => "manualFix",
                "name" => "ManualFix",
            ], function ($api) {
                $api->get("/{serial_number}", "ManualFixController@show")->name("show");  // 检修单详情
                $api->post("/", "ManualFixController@store")->name("store");  // 保存
            });
        });
    });
});
