<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\KindsFacade;
use App\Facades\OrganizationFacade;
use App\Facades\QueryBuilderFacade;
use App\Facades\WarehouseReportFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceAlarmLog;
use App\Model\FixWorkflow;
use App\Model\TakeStock;
use App\Serializers\BreakdownSerializer;
use App\Serializers\EntireInstanceSerializer;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse as JsonResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Jericho\FileSystem;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;
use Throwable;

class IndexController extends Controller
{
    private $_qualityDir = '';
    private $_deviceDir = '';

    public function __construct()
    {
        $statistics_dir = storage_path('statistics');
        if (!is_dir($statistics_dir)) Storage::disk('local')->makeDirectory('statistics');

        $this->_qualityDir = 'app/quality/breakdownDevice';
        $this->_deviceDir = 'app/basicInfo/deviceTotal';
    }

    /**
     * 获取主页缓存数据
     * @return JsonResponseService
     */
    final public function getReportData(): JsonResponseService
    {
        try {
            $currentYear = date('Y');

            $functions = [
                "deviceDynamicAsStatus" => function () use ($currentYear): array {
                    $category_unique_code = request('category_unique_code', '') ?? '';
                    $filename = ('deviceDynamicAsStatus' . ($category_unique_code ? ".{$category_unique_code}" : ''));

                    $statuses = collect(EntireInstance::$STATUSES);
                    $db_S = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(ei.status) as aggregate',
                            'ei.status as status',
                        ]))
                        ->groupBy(['ei.status',]);

                    $db_Q = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(ei.status) as aggregate',
                            'ei.status as status',
                        ]))
                        ->groupBy(['ei.status',]);
                    $statistics = QueryBuilderFacade::unionAll($db_S, $db_Q)->get();

                    $categories = KindsFacade::getCategories([], function ($db) {
                        return $db->where("is_show", true);
                    });

                    return [
                        'statistics' => $statistics,
                        'statuses' => $statuses,
                        'categories' => $categories,
                    ];
                },
                "warehouseReport" => function () {
                    return WarehouseReportFacade::generateStatisticsFor7Days();
                },
                "property" => function () use ($currentYear): array {
                    // return StatisticsFacade::getOrCreate('index', 'property', function () {
                    // });
                    $db_S = EntireInstanceSerializer::ins([
                        EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(f.unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                            'f.unique_code as factory_unique_code',
                            'f.name as factory_name',
                        ]))
                        ->groupBy(['c.unique_code', 'c.name', 'f.unique_code', 'f.name',]);
                    $db_Q = EntireInstanceSerializer::ins([
                        EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(f.unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                            'f.unique_code as factory_unique_code',
                            'f.name as factory_name',
                        ]))
                        ->groupBy(['c.unique_code', 'c.name', 'f.unique_code', 'f.name',]);
                    $statistics = QueryBuilderFacade::unionAll($db_S, $db_Q)->get();

                    return ['statistics' => $statistics];
                },
                "quality" => function (): array {
                    // return StatisticsFacade::getOrCreate('index', 'quality', function () {
                    // });
                    $file = FileSystem::init(__FILE__);
                    $quality_years = $file->setPath(storage_path($this->_qualityDir))->join('yearList.json')->fromJson();
                    $quality_months = $file->setPath(storage_path($this->_qualityDir))->join('dateList.json')->fromJson();
                    $quality_date_type = request('dateType', 'year');
                    switch ($quality_date_type) {
                        case 'year':
                            $year = request('date', date('Y'));
                            $origin_at = Carbon::createFromDate($year)->startOfYear();
                            $finish_at = Carbon::createFromDate($year)->endOfYear();

                            break;
                        case 'month':
                            $origin_at = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->startOfMonth();
                            $finish_at = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->endOfMonth();
                            break;
                        default:
                            $origin_at = Carbon::now()->startOfYear();
                            $finish_at = Carbon::now()->endOfMonth();
                            break;
                    }

                    // 获取设备器材统计
                    $db_device_statistics_S = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_PART => false,
                        EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(ei.model_unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                            'em.unique_code as entire_model_unique_code',
                            'em.name as entire_model_name',
                            'ei.model_unique_code as model_unique_code',
                            'ei.model_name as model_name',
                            'sc.unique_code as scene_workshop_unique_code',
                            'sc.name as scene_workshop_name',
                            'f.unique_code as factory_unique_code',
                            'f.name as factory_name',
                        ]))
                        ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name', 'sc.unique_code', 'sc.name', 'f.unique_code', 'f.name',]);

                    $db_device_statistics_Q = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_PART => false,
                        EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(sm.unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                            'em.unique_code as entire_model_unique_code',
                            'em.name as entire_model_name',
                            'sm.unique_code as model_unique_code',
                            'sm.name as model_name',
                            'sc.unique_code as scene_workshop_unique_code',
                            'sc.name as scene_workshop_name',
                            'f.unique_code as factory_unique_code',
                            'f.name as factory_name',
                        ]))
                        ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sm.unique_code', 'sm.name', 'sc.unique_code', 'sc.name', 'f.unique_code', 'f.name',]);
                    $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

                    $db_breakdown_statistics_S = BreakdownSerializer::INIT([
                        BreakdownSerializer::$IS_INCLUDE_STATION => true,
                        BreakdownSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                        BreakdownSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                        BreakdownSerializer::$IS_PART => false,
                        BreakdownSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
                    ])
                        ->generateStandardRelationshipS()
                        ->selectRaw(implode(',', [
                            'count(s.unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                            'em.unique_code as entire_model_unique_code',
                            'em.name as entire_model_name',
                            'ei.model_unique_code as model_unique_code',
                            'ei.model_name as model_name',
                            'sc.unique_code as scene_workshop_unique_code',
                            'sc.name as scene_workshop_name',
                            's.unique_code as station_unique_code',
                            's.name as station_name',
                            'f.unique_code as factory_unique_code',
                            'f.name as factory_name',
                        ]))
                        ->whereBetween('rbboei.created_at', [$origin_at, $finish_at])
                        ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name', 'sc.unique_code', 'sc.name', 's.unique_code', 's.name', 'f.unique_code', 'f.name',]);
                    $db_breakdown_statistics_Q = BreakdownSerializer::INIT([
                        BreakdownSerializer::$IS_INCLUDE_STATION => true,
                        BreakdownSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                        BreakdownSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                        BreakdownSerializer::$IS_PART => false,
                        BreakdownSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
                    ])
                        ->generateStandardRelationshipQ()
                        ->selectRaw(implode(',', [
                            'count(s.unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                            'em.unique_code as entire_model_unique_code',
                            'em.name as entire_model_name',
                            'sm.unique_code as model_unique_code',
                            'sm.name as model_name',
                            'sc.unique_code as scene_workshop_unique_code',
                            'sc.name as scene_workshop_name',
                            's.unique_code as station_unique_code',
                            's.name as station_name',
                            'f.unique_code as factory_unique_code',
                            'f.name as factory_name',
                        ]))
                        ->whereBetween('rbboei.created_at', [$origin_at, $finish_at])
                        ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sm.unique_code', 'sm.name', 'sc.unique_code', 'sc.name', 's.unique_code', 's.name', 'f.unique_code', 'f.name',]);
                    $breakdown_statistics = QueryBuilderFacade::unionAll($db_breakdown_statistics_S, $db_breakdown_statistics_Q)->get();

                    return [
                        'device_statistics' => $device_statistics,
                        'breakdown_statistics' => $breakdown_statistics,
                        'quality_date' => request('date', date('Y-m')),
                        'quality_years' => $quality_years,
                        'quality_months' => $quality_months,
                    ];
                },
                "scraped" => function () use ($currentYear): array {
                    // return StatisticsFacade::getOrCreate('index', 'scraped', function () {
                    // });
                    $db_device_statistics_S = EntireInstanceSerializer::ins([
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(c.unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                        ]))
                        ->groupBy(['c.unique_code', 'c.name',]);
                    $db_device_statistics_Q = EntireInstanceSerializer::ins([
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(c.unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                        ]))
                        ->groupBy(['c.unique_code', 'c.name',]);
                    $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

                    $db_scraped_statistics_S = EntireInstanceSerializer::ins([
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(c.unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                        ]))
                        ->where('scarping_at', '<', now())
                        ->groupBy(['c.unique_code', 'c.name',]);
                    $db_scraped_statistics_Q = EntireInstanceSerializer::ins([
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(c.unique_code) as aggregate',
                            'c.unique_code as category_unique_code',
                            'c.name as category_name',
                        ]))
                        ->where('scarping_at', '<', now())
                        ->groupBy(['c.unique_code', 'c.name',]);
                    $scraped_statistics = QueryBuilderFacade::unionAll($db_scraped_statistics_S, $db_scraped_statistics_Q)->get();

                    return [
                        'device_statistics' => $device_statistics,
                        'scraped_statistics' => $scraped_statistics,
                    ];
                },
                "cycleFix" => function () {
                    $rootDir = storage_path('app/cycleFix');
                    switch (request('dateType')) {
                        default:
                        case 'year':
                            $cycleFixDate = request('date', date('Y'));
                            $fileDir = "{$rootDir}/{$cycleFixDate}/statistics.json";
                            if (!is_file("{$rootDir}/yearList.json")) return [[], []];
                            if (!is_file($fileDir)) return [[], []];
                            $timeList = json_decode(file_get_contents("{$rootDir}/yearList.json"), true);
                            # 获取任务内容
                            $originAt = Carbon::create($cycleFixDate, 1, 1)->firstOfYear()->format('Y-m-d');
                            $finishAt = Carbon::create($cycleFixDate, 1, 1)->endOfYear()->format('Y-m-d');
                            $missions = [];
                            $_SQL = "
select sum(rbcfmr.number)                as aggregate,
       rbcfmr.category_unique_code                as cu,
       rbcfmr.category_name                       as cn
from repair_base_cycle_fix_mission_records as rbcfmr
where rbcfmr.completing_at between ? and ?
group by rbcfmr.category_unique_code, rbcfmr.category_name";
                            foreach (DB::select($_SQL, [$originAt, $finishAt]) as $item) {
                                $missions[$item->cu] = $item;
                            }
                            break;
                        case 'month':
                            $time = Carbon::createFromFormat('Y-m', request('date', date('Y-m')));
                            $year = $time->year;
                            $cycleFixDate = $time->format('Y-m');
                            $fileDir = "{$rootDir}/{$year}/{$cycleFixDate}/statistics.json";
                            if (!is_file("{$rootDir}/yearList.json")) return [[], []];
                            if (!is_file($fileDir)) return [[], []];
                            # 获取任务内容
                            $originAt = Carbon::createFromFormat('Y-m-d', "{$cycleFixDate}-01")->firstOfMonth()->format('Y-m-d');
                            $finishAt = Carbon::createFromFormat('Y-m-d', "{$cycleFixDate}-01")->endOfMonth()->format('Y-m-d');
                            $_SQL = "
select sum(rbcfmr.number)                as aggregate,
       rbcfmr.category_unique_code                as cu,
       rbcfmr.category_name                       as cn
from repair_base_cycle_fix_mission_records as rbcfmr
where rbcfmr.completing_at between ? and ?
group by rbcfmr.category_unique_code, rbcfmr.category_name";
                            $missions = [];
                            foreach (DB::select($_SQL, [$originAt, $finishAt]) as $item) {
                                $missions[$item->cu] = $item;
                            }
                            break;
                    }
                    $cycleFixYears = json_decode(file_get_contents("{$rootDir}/yearList.json"), true);
                    $cycleFixMonths = json_decode(file_get_contents("{$rootDir}/dateList.json"), true);
                    $statistics = json_decode(file_get_contents($fileDir), true);
                    return [
                        'cycleFixYears' => $cycleFixYears,
                        'cycleFixMonths' => $cycleFixMonths,
                        'cycleFixDate' => $cycleFixDate,
                        'statistics' => $statistics,
                        'missions' => $missions,
                    ];
                },
                "fixWorkshopMaintain" => function () {
                    // return StatisticsFacade::getOrCreate('index', 'fixWorkshopMaintain', function () {
                    // });
                    return [
                        'statistics' => DB::table('entire_instances as ei')
                            ->selectRaw(implode(',', [
                                'count(ei.status) as aggregate',
                                'ei.status',
                            ]))
                            ->whereNull('ei.deleted_at')
                            ->whereIn('ei.status', ['FIXED', 'FIXING', 'SEND_REPAIR',])
                            ->groupBy(['ei.status',])
                            ->get()
                    ];
                },
                "maintain" => function () {
                    // return StatisticsFacade::getOrCreate('index', 'maintain', function () {
                    // });
                    $sql_S = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(ei.status) as aggregate',
                            'ei.status as status',
                            'sc.unique_code as scene_workshop_unique_code',
                            'sc.name as scene_workshop_name',
                        ]))
                        ->where("sc.is_show", true)->where("s.is_show", true)
                        ->where('ei.maintain_workshop_name', '<>', '')
                        ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                        ->groupBy(['ei.status', 'sc.unique_code', 'sc.name',]);
                    $sql_Q = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(ei.status) as aggregate',
                            'ei.status as status',
                            'sc.unique_code as scene_workshop_unique_code',
                            'sc.name as scene_workshop_name',
                        ]))
                        ->where("sc.is_show", true)
                        ->where('ei.maintain_workshop_name', '<>', '')
                        ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                        ->groupBy(['ei.status', 'sc.unique_code', 'sc.name',]);
                    $statistics = QueryBuilderFacade::unionAll($sql_S, $sql_Q)->get();
                    // $sql = QueryBuilderFacade::unionAllToSql($sql_S, $sql_Q);

                    // $db = EntireInstanceSerializer::INS([
                    //     EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                    //     EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                    //     EntireInstanceSerializer::$IS_PART => false,
                    // ])
                    //     ->generateQueryRelation()
                    //     ->selectRaw(implode(',', [
                    //         'count(ei.status) as aggregate',
                    //         'ei.status as status',
                    //         'sc.unique_code as scene_workshop_unique_code',
                    //         'sc.name as scene_workshop_name',
                    //     ]))
                    //     ->where('ei.maintain_workshop_name', '<>', '')
                    //     ->where('ei.maintain_station_name', '<>', '')
                    //     ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                    //     ->groupBy(['ei.status', 'sc.unique_code', 'sc.name',]);
                    // $statistics = $db->get();
                    // $sql = Log::sqlLanguage(function () use ($db) {
                    //     $db->get();
                    // });

                    return ['statistics' => $statistics,];
                },
                "sceneWorkshop" => function () {
                    // return StatisticsFacade::getOrCreate('index', 'scene_workshop', function () {
                    // });
                    $sql_S = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(ei.identity_code) as aggregate',
                            'sc.unique_code          as scene_workshop_unique_code',
                            'sc.name                 as scene_workshop_name',
                        ]))
                        ->where('ei.maintain_workshop_name', '<>', '')
                        ->where('sc.type', 'SCENE_WORKSHOP')
                        ->where("sc.is_show", true)
                        ->groupBy(['sc.unique_code', 'sc.name',]);
                    $sql_Q = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(ei.identity_code) as aggregate',
                            'sc.unique_code          as scene_workshop_unique_code',
                            'sc.name                 as scene_workshop_name',
                        ]))
                        ->where('ei.maintain_workshop_name', '<>', '')
                        ->where('sc.type', 'SCENE_WORKSHOP')
                        ->where("sc.is_show", true)
                        ->groupBy(['sc.unique_code', 'sc.name',]);
                    $statistics = QueryBuilderFacade::unionAll($sql_S, $sql_Q)->get();

                    return ['statistics' => $statistics,];
                },
                "station" => function () {
                    // return StatisticsFacade::getOrCreate('index', "station." . request('scene_workshop_unique_code'), function () {
                    // });
                    $sql_S = EntireInstanceSerializer::INS([
                        // EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
                        EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(ei.identity_code) as aggregate',
                            's.unique_code           as station_unique_code',
                            's.name                  as station_name',
                        ]))
                        ->where("s.is_show", true)
                        ->groupBy(['s.unique_code', 's.name',]);
                    $sql_Q = EntireInstanceSerializer::INS([
                        // EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
                        EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(ei.identity_code) as aggregate',
                            's.unique_code           as station_unique_code',
                            's.name                  as station_name',
                        ]))
                        ->where("s.is_show", true)
                        ->groupBy(['s.unique_code', 's.name',]);
                    $statistics = QueryBuilderFacade::unionAll($sql_S, $sql_Q)->get();

                    return ['statistics' => $statistics,];
                },
                "standby" => function () {
                    // return StatisticsFacade::getOrCreate('index', 'standby', function () {
                    // });
                    $statistics_by_standby_S = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(ei.model_unique_code) as aggregate',
                            'c.unique_code        as category_unique_code',
                            'c.name               as category_name',
                            'em.unique_code       as entire_model_unique_code',
                            'em.name              as entire_model_name',
                            'ei.model_unique_code as model_unique_code',
                            'ei.model_name        as model_name',
                        ]))
                        ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
                    $statistics_by_standby_Q = EntireInstanceSerializer::INS([
                        EntireInstanceSerializer::$IS_PART => false,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(ei.model_unique_code) as aggregate',
                            'c.unique_code        as category_unique_code',
                            'c.name               as category_name',
                            'em.unique_code       as entire_model_unique_code',
                            'em.name              as entire_model_name',
                            'ei.model_unique_code as model_unique_code',
                            'ei.model_name        as model_name',
                        ]))
                        ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
                    if (!request('scene_workshop_unique_code') && !request('station_unique_code')) {
                        $type = 'FIX_WORKSHOP';
                        $statistics_by_standby = QueryBuilderFacade::unionAll($statistics_by_standby_S->where('ei.status', 'FIXED'), $statistics_by_standby_Q->where('ei.status', 'FIXED'))->get();
                    } else {
                        $type = 'SCENE_WORKSHOP';
                        $statistics_by_standby = QueryBuilderFacade::unionAll($statistics_by_standby_S->where('ei.status', 'INSTALLING'), $statistics_by_standby_Q->where('ei.status', 'INSTALLING'))->get();
                    }
                    return [
                        'statistics' => $statistics_by_standby,
                        'type' => $type,
                    ];
                },
            ];

            if (!array_key_exists(request("type"), $functions)) return response()->json(["message" => "统计方法不存在：" . request("type"),], 403);

            return response()->json(['message' => '读取成功', 'data' => $functions[request("type")](), 'function_name' => request("type"),]);
        } catch (Exception $e) {
            return response()->json(['message' => '意外错误', 'function_name' => request('type'), 'details' => [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     * @throws \Exception
     */
    final public function index()
    {
        $currentYear = date('Y');

        /**
         * 获取左侧快捷按钮的统计
         * @return array
         */
        $shortcutButtonsStatistics = function () {
            $shortcutButtonsStatisticsCurrentMonthFirst = Carbon::now()->firstOfMonth()->toDateString();
            $shortcutButtonsStatisticsCurrentMonthEndless = Carbon::now()->lastOfMonth()->toDateString();
            $shortcutButtonsStatistics = [
                'search' => '',
                # 当月检修比例
                'fixWorkflow' => [
                    'total' => $totalFixWorkflow = intval(FixWorkflow::whereBetween('created_at', [$shortcutButtonsStatisticsCurrentMonthFirst, $shortcutButtonsStatisticsCurrentMonthEndless])->count()),
                    'completed' => $completedFixWorkflow = intval(FixWorkflow::whereBetween('created_at', [$shortcutButtonsStatisticsCurrentMonthFirst, $shortcutButtonsStatisticsCurrentMonthEndless])->where('status', 'FIXED')->count()),
                    'proportion' => $completedFixWorkflow > 0 ? $totalFixWorkflow !== $completedFixWorkflow ? intval(round(floatval($completedFixWorkflow / $totalFixWorkflow), 2) * 100) : 100 : 0,
                ],
                # 当月新设备
                'new' => [
                    'total' => $totalEntireInstance = EntireInstance::whereBetween('created_at', [$shortcutButtonsStatisticsCurrentMonthFirst, $shortcutButtonsStatisticsCurrentMonthEndless])->count(),
                ],
                # 当月周期修
                'fixCycle' => [
                    'total' => $totalFixCycle = intval(FixWorkflow::whereBetween('created_at', [$shortcutButtonsStatisticsCurrentMonthFirst, $shortcutButtonsStatisticsCurrentMonthEndless])->where('is_cycle', true)->count()),
                    'completed' => $completedFixCycle = intval(FixWorkflow::whereBetween('created_at', [$shortcutButtonsStatisticsCurrentMonthFirst, $shortcutButtonsStatisticsCurrentMonthEndless])->where('status', 'FIXED')->where('is_cycle', true)->count()),
                    'proportion' => $completedFixCycle > 0 ? $totalFixCycle !== $completedFixCycle ? intval(round(floatval($completedFixCycle / $totalFixCycle), 2) * 100) : 100 : 0,
                ],
                # 当月质量报告
                'quality' => [],
                # 当月验收
                'check' => [
                    'fixed' => $totalCheck = FixWorkflow::whereBetween('created_at', [$shortcutButtonsStatisticsCurrentMonthFirst, $shortcutButtonsStatisticsCurrentMonthEndless])->where('type', 'FIX')->where('status', 'FIXED')->count(),
                    'checked' => $completedCheck = FixWorkflow::whereBetween('created_at', [$shortcutButtonsStatisticsCurrentMonthFirst, $shortcutButtonsStatisticsCurrentMonthEndless])->where('type', 'CHECK')->where('status', 'FIXED')->count(),
                ]
            ];
            return $shortcutButtonsStatistics;
        };
        $shortcutButtonsStatistics = $shortcutButtonsStatistics();
        if ($shortcutButtonsStatistics['check']['checked'] > 0) {
            if ($shortcutButtonsStatistics['check']['fixed'] != $shortcutButtonsStatistics['check']['checked']) {
                $shortcutButtonsStatistics['check']['proportion'] = intval(round(intval(intval($shortcutButtonsStatistics['check']['fixed']) / $shortcutButtonsStatistics['check']['checked']), 2));
            } else {
                $shortcutButtonsStatistics['check']['proportion'] = 100;
            }
        } else {
            $shortcutButtonsStatistics['check']['proportion'] = 0;
        }

        /**
         * 一次过检 ❌
         * @return array
         * @throws \Exception
         */
        $ripe = function (): array {
            $file = FileSystem::init(__FILE__);
            $root_dir = storage_path("app/一次过检");

            if (request("ripeDateType", "year") == "year") {
                $ripe_date_list = $file->setPath($root_dir)->join("yearList.json")->fromJson();
                $year = request("ripeYear", date("Y"));
                $month = date("m");

                # 加载年度数据
                $statistics = $file->setPath($root_dir)->joins([$year, "年-种类.json"])->fromJson();
            } else {
                $ripe_date_list = $file->setPath($root_dir)->join("dateList.json")->fromJson();
                list($year, $month) = explode("-", request("ripeDate", date("Y-m")));

                # 加载月度数据
                $statistics = $file->setPath($root_dir)->joins([$year, "{$year}-{$month}", "月-种类.json"])->fromJson();
            }
            $categories = array_flip($file->setPath($root_dir)->joins([$year, "种类.json"])->fromJson());

            return [
                $statistics,
                $year,
                "{$year}-{$month}",
                request("ripeDateType", "year"),
                $ripe_date_list,
                $categories,
            ];
        };
        list($ripe_statistics, $ripe_year, $ripe_month, $ripe_date_type, $ripe_date_list, $ripe_categories) = $ripe();

        # 现场车间列表
        $scene_workshops = OrganizationFacade::getSceneWorkshops([], function ($db) {
            return $db->where('is_show', true);
        });

        /**
         * 盘点统计
         * @return array
         */
        $takeStock = function () {
            $takeStockInstances = DB::table('take_stock_instances as tsi')
                ->selectRaw('count(tsi.id) as count, ts.name as take_stock_name, ts.updated_at as take_stock_update_at , tsi.take_stock_unique_code, tsi.difference, tsi.category_unique_code, tsi.category_name')
                ->leftJoin(DB::raw('take_stocks ts'), 'tsi.take_stock_unique_code', '=', 'ts.unique_code')
                ->where('ts.state', 'END')
                ->groupBy(['tsi.take_stock_unique_code', 'ts.name', 'ts.id', 'ts.updated_at', 'tsi.difference', 'tsi.category_name', 'tsi.category_unique_code'])
                ->orderByDesc('ts.id')
                ->limit(5)
                ->get();
            $takeStocks = [];
            foreach ($takeStockInstances as $takeStockInstance) {
                if (!array_key_exists($takeStockInstance->take_stock_unique_code, $takeStocks)) {
                    $takeStocks[$takeStockInstance->take_stock_unique_code] = [
                        'name' => $takeStockInstance->take_stock_name,
                        'categories' => [
                            $takeStockInstance->category_unique_code => [
                                'takeStockUpdateAt' => $takeStockInstance->take_stock_update_at,
                                'takeStockName' => $takeStockInstance->take_stock_name,
                                'categoryName' => $takeStockInstance->category_name,
                                '+' => 0,
                                '-' => 0,
                                '=' => 0,
                            ]
                        ]
                    ];
                }
                if (!array_key_exists($takeStockInstance->category_unique_code, $takeStocks[$takeStockInstance->take_stock_unique_code]['categories'])) {
                    $takeStocks[$takeStockInstance->take_stock_unique_code]['categories'][$takeStockInstance->category_unique_code] = [
                        'categoryName' => $takeStockInstance->category_name,
                        '+' => 0,
                        '-' => 0,
                        '=' => 0,
                    ];
                }
                $takeStocks[$takeStockInstance->take_stock_unique_code]['categories'][$takeStockInstance->category_unique_code][$takeStockInstance->difference] = $takeStockInstance->count;
            }

            return [
                $takeStocks
            ];
        };
        list($takeStocks) = $takeStock();

        return view('Index.index', [
            'shortcutButtonsStatistics' => $shortcutButtonsStatistics,
            // 'cycleFixCategories' => TextHelper::toJson($cycle_fix_categories),
            // 'cycleFixMissions' => TextHelper::toJson(array_values($cycle_fix_missions)),
            // 'cycleFixPlans' => TextHelper::toJson(array_values($cycle_fix_plans)),
            // 'cycleFixReals' => TextHelper::toJson(array_values($cycle_fix_reals)),
            // 'cycleFixYear' => $cycle_fix_year,
            // 'cycleFixMonth' => $cycle_fix_month,
            // 'cycleFixDateType' => $cycle_fix_date_type,
            // 'cycleFixDateList' => $cycle_fix_date_list,
            'takeStocks' => $takeStocks,
            'ripeStatistics' => TextHelper::toJson($ripe_statistics),
            'ripeYear' => $ripe_year,
            'ripeMonth' => $ripe_month,
            'ripeDateType' => $ripe_date_type,
            'ripeDateList' => $ripe_date_list,
            'ripeCategoriesAsJson' => TextHelper::toJson($ripe_categories),
            'sceneWorkshops' => $scene_workshops->pluck('name', 'unique_code'),
            'sceneWorkshopsAsJson' => $scene_workshops->pluck('name', 'unique_code')->toJson(),
        ]);
    }

    /**
     * 批量扫码绑定RFID测试页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getBatchBindingRFIDWithIdentityCode()
    {
        return view('Index.batchBindingRFIDWithIdentityCode');
    }

    /**
     * get测试
     */
    final public function getTest()
    {
        return view('Testing.index');
    }

    /**
     * @param Request $request
     * @return null
     */
    final public function postTest(Request $request)
    {
        dd($request->all());
    }

    /**
     * 测试A
     */
    final public function getTestSSE()
    {
    }

    /**
     * SQL server测试
     */
    final public function getTestSqlServer()
    {
        $a = DB::connection('huaxin')->table('检前非电气表')->first();
        dd($a);
    }

    /**
     * 监控大屏
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function monitor(Request $request)
    {
        try {
            $categories = DB::table('categories')->get(['name', 'id', 'unique_code'])->toArray();
            $maintains = DB::table('maintains as sc')
                ->selectRaw('sc.name as scene_workshop_name, sc.unique_code as scene_workshop_unique_code, s.unique_code as station_unique_code, s.name as station_name')
                ->leftJoin(DB::raw('maintains s'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->where('s.type', 'STATION')
                ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                ->get();
            $maintainStatistics = [];
            $sceneWorkshopPoints = [];

            foreach ($maintains as $maintain) {
                if (!array_key_exists($maintain->scene_workshop_unique_code, $maintainStatistics)) {
                    $sceneWorkshops[$maintain->scene_workshop_unique_code] = $maintain->scene_workshop_name;
                    $maintainStatistics[$maintain->scene_workshop_unique_code] = [
                        'unique_code' => $maintain->scene_workshop_unique_code,
                        'name' => $maintain->scene_workshop_name,
                        'stations' => [],
                    ];
                }
                if (!empty($maintain->station_unique_code)) {
                    $maintainStatistics[$maintain->scene_workshop_unique_code]['stations'][$maintain->station_unique_code] = [
                        'unique_code' => $maintain->station_unique_code,
                        'name' => $maintain->station_name
                    ];
                }
            }
            # 车站标点
            $stationPoints = [];
            $stations = DB::table('maintains')->where('type', 'STATION')->where('deleted_at', null)->where('lon', '<>', '')->where('lat', '<>', '')->get();
            foreach ($stations as $station) {
                if (!array_key_exists($station->unique_code, $stationPoints)) {
                    $stationPoints[$station->unique_code] = [
                        'lon' => $station->lon,
                        'lat' => $station->lat,
                        'name' => $station->name,
                        'contact' => $station->contact,
                        'contact_phone' => $station->contact_phone,
                        'contact_address' => $station->contact_address,
                        'scene_workshop_unique_code' => $station->parent_unique_code,
                    ];
                }
            }
            # 车间标点
            $workshops = DB::table('maintains')->where('deleted_at', null)->where('parent_unique_code', env('ORGANIZATION_CODE'))->where('lon', '<>', '')->where('lat', '<>', '')->get();
            foreach ($workshops as $workshop) {
                if (!array_key_exists($workshop->unique_code, $sceneWorkshopPoints)) {
                    $sceneWorkshopPoints[$workshop->unique_code] = [
                        'lon' => $workshop->lon,
                        'lat' => $workshop->lat,
                        'name' => $workshop->name,
                        'contact' => $workshop->contact,
                        'contact_phone' => $workshop->contact_phone,
                        'contact_address' => $workshop->contact_address,
                    ];
                }
            }

            $deviceDB = DB::table("entire_instances")
                ->where("deleted_at", null)
                ->where(
                    "category_unique_code",
                    request('categoryUniqueCode', 'S03')
                );
            $using = $deviceDB->whereIn("status", ["INSTALLING", "INSTALLED"])->count("id");
            $fixed = $deviceDB->where("status", "FIXED")->count("id");
            $returnFactory = $deviceDB->where("status", "RETURN_FACTORY")->count("id");
            $fixing = $deviceDB->whereIn("status", ["FIXING", "FACTORY_RETURN", "BUY_IN"])->count("id");
            $total = $deviceDB->where("status", "<>", "SCRAP")->count("id");

            $deviceDynamics_iframe = [
                'total' => $total,
                'status' => [
                    ["name" => "上道", "value" => $using],
                    ["name" => "维修", "value" => $fixing],
                    ["name" => "送检", "value" => $returnFactory],
                    ["name" => "成品", "value" => $fixed]
                ]
            ];
            # 车站连线
            $line_points = DB::table('line_points')->select('center_point', 'points', 'organization_code')->get();
            $all_line_points = collect([]);
            foreach ($line_points as $line_point) {
                $all_line_points->push(json_decode($line_point->points, true));
            }
            $current_center_point = $line_points->where('organization_code', env('ORGANIZATION_CODE'))->first()->center_point;
            // $gan_shen_xian = file_get_contents(storage_path('gan_shen_xian.json'));
            $high_light = file_get_contents(storage_path('zhang_ji_huai.json'));

            return view('Index.monitor', [
                'categories' => $categories,
                'categories_iframe' => TextHelper::toJson($categories),
                'sceneWorkshopPoints' => TextHelper::toJson($sceneWorkshopPoints),
                'all_line_points_as_json' => $all_line_points->toJson(),
                'current_center_point' => $current_center_point,
                'stationPoints' => TextHelper::toJson($stationPoints),
                'deviceDynamics_iframe' => TextHelper::toJson($deviceDynamics_iframe),
                'maintainStatistics' => json_encode($maintainStatistics),
                'high_light' => $high_light ?? '',
            ]);
        } catch (Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
            dd($e->getMessage(), $e->getFile(), $e->getLine());
            return Response::make($e->getMessage(), 500);
        }
    }

    /**
     * 监控大屏-左上-设备状态统计
     * @param Request $request
     * @return JsonResponseService
     */
    final public function monitorWithLeftTop(Request $request)
    {
        try {
            // queries
            $category_unique_code = $request->get('categoryId', '');
            $scene_workshop_unique_code = $request->get('sceneWorkshopUniqueCode', '');
            $station_unique_code = $request->get('stationUniqueCode', '');

            $statuses = (empty($scene_workshop_unique_code) && empty($station_unique_code))
                ? EntireInstance::$STATUSES
                : ['INSTALLED' => '上道使用', 'INSTALLING' => '现场备品', 'TRANSFER_IN' => '入所在途',];

            $statistics = DB::table('entire_instances as ei')
                ->selectRaw(implode(',', [
                    'count(ei.status) as aggregate',
                    'ei.status as status',
                ]))
                ->where('ei.deleted_at', null)
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                ->leftJoin(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->leftJoin(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->when(
                    $category_unique_code,
                    function ($query, $category_unique_code) {
                        $query->where('c.unique_code', $category_unique_code);
                    }
                )
                ->when(
                    $scene_workshop_unique_code,
                    function ($query, $scene_workshop_unique_code) {
                        $query->where('s.parent_unique_code', $scene_workshop_unique_code);
                    }
                )
                ->when(
                    $station_unique_code,
                    function ($query, $station_unique_code) {
                        $query->where('s.unique_code', $station_unique_code);
                    }
                )
                ->whereIn('ei.status', array_keys($statuses))
                ->groupBy(['ei.status'])
                ->pluck('aggregate', 'status');

            return JsonResponseFacade::dict([
                'statistics' => $statistics,
                'statuses' => $statuses,
            ]);

        } catch (\Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 监控大屏-左中-资产统计
     * @param Request $request
     * @return JsonResponseService
     */
    final public function monitorWithLeftMiddle(Request $request)
    {
        try {
            $scene_workshop_unique_code = $request->get('sceneWorkshopUniqueCode', '');
            $station_unique_code = $request->get('stationUniqueCode', '');

            $statuses = (empty($scene_workshop_unique_code) && empty($station_unique_code))
                ? EntireInstance::$STATUSES
                : ['INSTALLED' => '上道使用', 'INSTALLING' => '现场备品', 'TRANSFER_IN' => '入所在途',];

            $statistics = DB::table('entire_instances as ei')
                ->selectRaw(implode(',', [
                    'count(ei.category_unique_code) as aggregate',
                    'ei.category_name               as name',
                    'ei.category_unique_code        as unique_code',
                ]))
                ->where('ei.deleted_at', null)
                ->leftJoin(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->leftJoin(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->when(
                    $scene_workshop_unique_code,
                    function ($query, $scene_workshop_unique_code) {
                        $query->where('s.parent_unique_code', $scene_workshop_unique_code);
                    }
                )
                ->when(
                    $station_unique_code,
                    function ($query, $station_unique_code) {
                        $query->where('s.unique_code', $station_unique_code);
                    }
                )
                ->whereIn('ei.status', array_keys($statuses))
                ->groupBy(['ei.category_unique_code'])
                ->get();

            return JsonResponseFacade::dict(['statistics' => $statistics,]);

        } catch (Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 监控大屏-左下-仓库统计/现场备品统计
     * @param Request $request
     * @return JsonResponseService
     */
    final public function monitorWithLeftBottom(Request $request)
    {
        try {
            $scene_workshop_unique_code = $request->get('sceneWorkshopUniqueCode', '');
            $station_unique_code = $request->get('stationUniqueCode', '');

            if (empty($scene_workshop_unique_code) && empty($station_unique_code)) {
                // 统计所内备品
                $statistics = EntireInstanceSerializer::INS([])
                    ->GenerateQueryRelationShipS()
                    ->selectRaw(implode(',', [
                        'count(c.unique_code) as aggregate',
                        'c.name as name',
                        'c.unique_code as unique_code',
                    ]))
                    ->where('ei.status', 'FIXED')
                    ->groupBy(['c.name', 'c.unique_code',])
                    ->get();

                // 统计检修车间
                // $statistics = DB::table('entire_instances as ei')
                //     ->selectRaw(implode(',', [
                //         'count(c.unique_code) as aggregate',
                //         'c.name as name',
                //         'c.unique_code as unique_code',
                //     ]))
                //     ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                //     ->where('ei.status', 'FIXED')
                //     ->groupBy(['c.name', 'c.unique_code'])
                //     ->get();
                // $type = 'FIX_WORKSHOP';
            } else {
                // 统计现场车间或车站
                $statistics = DB::table('entire_instances as ei')
                    ->selectRaw(implode(',', [
                        'count(c.unique_code) as aggregate',
                        'c.name as name',
                        'c.unique_code as unique_code',
                    ]))
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                    ->leftJoin(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->leftJoin(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->when(
                        $scene_workshop_unique_code,
                        function ($query, $scene_workshop_unique_code) {
                            $query->where('s.parent_unique_code', $scene_workshop_unique_code);
                        }
                    )
                    ->when(
                        $station_unique_code,
                        function ($query, $station_unique_code) {
                            $query->where('s.unique_code', $station_unique_code);
                        }
                    )
                    ->groupBy(['c.name', 'c.unique_code'])
                    ->get();
                $type = 'SCENE_WORKSHOP';
            }

            return JsonResponseFacade::dict(['statistics' => $statistics, 'type' => $type]);
        } catch (\Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 监控大屏-右上-盘点统计/故障统计
     * @param Request $request
     * @return JsonResponseService
     */
    final public function monitorWithRightTop(Request $request)
    {
        try {
            $sceneWorkshopUniqueCode = $request->get('sceneWorkshopUniqueCode', '');
            $stationUniqueCode = $request->get('stationUniqueCode', '');
            $takeStockStatistic = [
                ' + ' => 0,
                ' - ' => 0,
                '=' => 0,
                'time' => 0,
                'takeStockTitle' => ''
            ];
            $takeStockUniqueCode = '';
            $breakdownWithStations = [];
            $breakdownWithCategories = [];

            if (empty($sceneWorkshopUniqueCode) && empty($stationUniqueCode)) {
                #盘点统计
                $takeStock = TakeStock::with([])->where('state', 'END')->orderBy('updated_at', 'desc')->first();
                if (!empty($takeStock)) {
                    $takeStockMaterial = DB::table('take_stock_instances')->selectRaw('count(id) as count , difference')
                        ->where('take_stock_unique_code', $takeStock->unique_code)
                        ->groupBy(['difference'])
                        ->pluck('count', 'difference')
                        ->toArray();
                    $takeStockStatistic = [
                        ' + ' => array_key_exists(' + ', $takeStockMaterial) ? $takeStockMaterial[' + '] : 0,
                        ' - ' => array_key_exists(' - ', $takeStockMaterial) ? $takeStockMaterial[' - '] : 0,
                        '=' => array_key_exists('=', $takeStockMaterial) ? $takeStockMaterial['='] : 0,
                        'time' => date('Y - m - d H:i:s', strtotime($takeStock->updated_at)),
                        'takeStockTitle' => $takeStock->name,
                    ];
                    $takeStockUniqueCode = $takeStock->unique_code;
                }
            }

            if (!empty($sceneWorkshopUniqueCode) && empty($stationUniqueCode)) {
                # 选择车间故障统计
                $workshop_name = DB::table('maintains')->where('unique_code', $sceneWorkshopUniqueCode)->value('name');

                $breakdownMaterialTmps = DB::table('breakdown_logs as bl')
                    ->selectRaw('bl.entire_instance_identity_code, s.unique_code as station_unique_code, s.name as station_name')
                    ->leftJoin(DB::raw('entire_instances m'), 'bl.entire_instance_identity_code', '=', 'm.identity_code')
                    ->join(DB::raw('maintains s'), 'm.maintain_station_name', '=', 's.name')
                    ->where('m.deleted_at', null)
                    ->where('s.type', '=', 'STATION')
                    ->where('s.is_show', 1)
                    ->where('bl.scene_workshop_name', $workshop_name)
                    ->groupBy(['bl.entire_instance_identity_code', 's.unique_code', 's.name']);
                $breakdownWithStations = DB::table(DB::raw("({$breakdownMaterialTmps->toSql()}) as breakdown_logs"))->mergeBindings($breakdownMaterialTmps)->selectRaw('count(breakdown_logs.entire_instance_identity_code) as count, breakdown_logs.station_unique_code,breakdown_logs.station_name')->groupBy(['breakdown_logs.station_unique_code', 'breakdown_logs.station_name'])->get()->toArray();
            }
            if (!empty($sceneWorkshopUniqueCode) && !empty($stationUniqueCode)) {
                # 选择车站故障统计
                $workshop_name = DB::table('maintains')->where('unique_code', $sceneWorkshopUniqueCode)->value('name');
                $station_name = DB::table('maintains')->where('unique_code', $stationUniqueCode)->value('name');
                $tmp = DB::table('breakdown_logs as bl')
                    ->selectRaw('bl.entire_instance_identity_code, c.name as category_name, c.id as category_id')
                    ->leftJoin(DB::raw('entire_instances m'), 'bl.entire_instance_identity_code', '=', 'm.identity_code')
                    ->leftJoin(DB::raw('entire_models em'), 'm.model_unique_code', 'em.unique_code')
                    ->leftJoin(DB::raw('categories c'), 'em.category_unique_code', 'c.unique_code')
                    ->where('m.deleted_at', null)
                    ->where('bl.scene_workshop_name', $workshop_name)
                    ->where('m.maintain_station_name', $station_name)
                    ->groupBy(['bl.entire_instance_identity_code', 'c.id', 'c.name']);
                $breakdownWithCategories = DB::table(DB::raw("({$tmp->toSql()}) as breakdown_logs"))->mergeBindings($tmp)->selectRaw('count(breakdown_logs.entire_instance_identity_code) as count, breakdown_logs.category_name,breakdown_logs.category_id')->groupBy(['breakdown_logs.category_name', 'breakdown_logs.category_id'])->get()->toArray();
            }

            return HttpResponseHelper::data([
                'takeStocks' => [
                    'takeStockStatistic' => $takeStockStatistic,
                    'takeStockUniqueCode' => $takeStockUniqueCode,
                ],
                'breakdownWithStations' => $breakdownWithStations,
                'breakdownWithCategories' => $breakdownWithCategories,
            ]);
        } catch (Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 监控大屏-右中-超期使用
     * @param Request $request
     * @return JsonResponseService
     */
    final public function monitorWithRightMiddle(Request $request)
    {
        try {
            $sceneWorkshopUniqueCode = $request->get('sceneWorkshopUniqueCode', '');
            $stationUniqueCode = $request->get('stationUniqueCode', '');
            $sceneWorkshopUniqueCode = DB::table('maintains')->where('unique_code', $sceneWorkshopUniqueCode)->value('name');
            $stationUniqueCode = DB::table('maintains')->where('unique_code', $stationUniqueCode)->value('name');
            $materialStatistics = DB::table('entire_instances as e')
                ->selectRaw('distinct count(e.id) as count , c.name as category_name , c.unique_code, c.id as category_id')
                // ->leftJoin(DB::raw('sub_models sm'), 'e.sub_model_unique_code', 'sm.unique_code')
                // ->leftJoin(DB::raw('entire_models em'), 'sm.entire_model_id', 'em.id')
                ->leftJoin(DB::raw('categories c'), 'e.category_unique_code', 'c.unique_code')
                ->where('e.deleted_at', null)
                ->when(
                    !empty($sceneWorkshopUniqueCode),
                    function ($query) use ($sceneWorkshopUniqueCode) {
                        return $query->where('e.maintain_workshop_name', $sceneWorkshopUniqueCode);
                    }
                )
                ->when(
                    !empty($stationUniqueCode),
                    function ($query) use ($stationUniqueCode) {
                        return $query->where('e.maintain_station_name', $stationUniqueCode);
                    }
                )
                ->groupBy(['c.name', 'c.id'])
                ->get()
                ->toArray();
            $overdueStatistics = DB::table('entire_instances as e')
                ->selectRaw('distinct count(e.id) as count , c.name as category_name , c.id as category_id')
                // ->leftJoin(DB::raw('sub_models sm'), 'e.sub_model_unique_code', 'sm.unique_code')
                // ->leftJoin(DB::raw('entire_models em'), 'sm.entire_model_id', 'em.id')
                ->leftJoin(DB::raw('categories c'), 'e.category_unique_code', 'c.unique_code')
                ->where('e.scarping_at', "<", date('Y-m-d H:i:s'))
                ->where('e.deleted_at', null)
                ->when(
                    !empty($sceneWorkshopUniqueCode),
                    function ($query) use ($sceneWorkshopUniqueCode) {
                        return $query->where('e.maintain_workshop_name', $sceneWorkshopUniqueCode);
                    }
                )
                ->when(
                    !empty($stationUniqueCode),
                    function ($query) use ($stationUniqueCode) {
                        return $query->where('e.maintain_station_name', $stationUniqueCode);
                    }
                )
                ->groupBy(['c.name', 'c.id'])
                ->pluck('count', 'category_name')
                ->toArray();

            return HttpResponseHelper::data([
                'materialStatistics' => $materialStatistics,
                'overdueStatistics' => $overdueStatistics
            ]);
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 监控大屏-右下-现场车间列表/车站列表
     * @param Request $request
     * @return JsonResponseService
     */
    final public function monitorWithRightBottom(Request $request)
    {
        try {
            $sceneWorkshopUniqueCode = $request->get('sceneWorkshopUniqueCode', '');
            $stationUniqueCode = $request->get('stationUniqueCode', '');
            $sceneWorkshopStatistics = [];
            $stationStatistics = [];
            if (empty($sceneWorkshopUniqueCode) && empty($stationUniqueCode)) {
                $sceneWorkshops = DB::table('maintains')
                    ->where('type', 'SCENE_WORKSHOP')
                    ->where('is_show', 1)
                    ->select('name', 'unique_code')
                    ->get()
                    ->toArray();
                $sceneWorkshopsByPluck = array_pluck($sceneWorkshops, 'unique_code', 'name');

                $statisticsQ = DB::table('entire_instances as ei')
                    ->selectRaw('count(ei.id) as count, ei.maintain_workshop_name')
                    ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->where('ei.maintain_workshop_name', '<>', '')
                    ->where('ei.deleted_at', null)
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                    ->groupBy(['ei.maintain_workshop_name'])
                    ->get()
                    ->pluck('count', 'maintain_workshop_name')
                    ->toArray();

                $statisticsQ2 = [];
                foreach ($statisticsQ as $k => $v) {
                    if (array_key_exists($k, $sceneWorkshopsByPluck))
                        $statisticsQ2[$sceneWorkshopsByPluck[$k]] = $v;
                }

                $statisticsS = DB::table('entire_instances as ei')
                    ->selectRaw('count(ei.id) as count, ei.maintain_workshop_name')
                    ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->where('ei.deleted_at', null)
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                    ->groupBy(['ei.maintain_workshop_name'])
                    ->pluck('count', 'maintain_workshop_name')
                    ->toArray();

                $statisticsS2 = [];
                foreach ($statisticsS as $k => $v) {
                    if (array_key_exists($k, $sceneWorkshopsByPluck))
                        $statisticsS2[$sceneWorkshopsByPluck[$k]] = $v;
                }

                $materials = [];
                foreach ($statisticsS2 as $k => $v) {
                    if (!array_key_exists($k, $materials)) $materials[$k] = 0;
                    $materials[$k] += $v;
                }
                foreach ($statisticsQ2 as $k => $v) {
                    if (!array_key_exists($k, $materials)) $materials[$k] = 0;
                    $materials[$k] += $v;
                }

                foreach ($sceneWorkshops as $sceneWorkshop) {
                    $sceneWorkshopStatistics[] = [
                        'name' => $sceneWorkshop->name,
                        'unique_code' => $sceneWorkshop->unique_code,
                        'count' => $materials[$sceneWorkshop->unique_code] ?? 0,
                    ];
                }
            }
            if (!empty($sceneWorkshopUniqueCode) && empty($stationUniqueCode)) {
                // $workshop_name = DB::table('maintains')
                //     ->where('unique_code', $sceneWorkshopUniqueCode)
                //     ->value('name');
                $stations = DB::table('maintains')->where('type', 'STATION')
                    ->where('parent_unique_code', $sceneWorkshopUniqueCode)
                    ->where('is_show', 1)
                    ->select('name', 'unique_code')
                    ->get()
                    ->toArray();
                $statisticsQ = DB::table('entire_instances as ei')
                    ->selectRaw('count(ei.id) as count, mt.unique_code as station_unique_code')
                    ->join(DB::raw('maintains mt'), 'ei.maintain_station_name', 'mt.name')
                    ->whereNull('ei.deleted_at')
                    ->where('mt.parent_unique_code', $sceneWorkshopUniqueCode)
                    // ->where('ei.maintain_workshop_name', $workshop_name)
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                    ->groupBy(['mt.unique_code',])
                    ->pluck('count', 'station_unique_code')
                    ->toArray();

                // $statisticsS = DB::table('entire_instances as ei')
                //     ->selectRaw('count(ei.id) as count, mt.unique_code as station_unique_code')
                //     ->join(DB::raw('maintains mt'), 'ei.maintain_station_name', 'mt.name')
                //     ->whereNull('ei.deleted_at')
                //     ->where('mt.parent_unique_code', $sceneWorkshopUniqueCode)
                //     ->where('ei.maintain_workshop_name', $workshop_name)
                //     ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                //     ->groupBy(['ei.maintain_station_name'])
                //     ->pluck('count', 'station_unique_code')
                //     ->toArray();

                $materials = [];
                // foreach ($statisticsS as $k => $v) {
                //     if (!array_key_exists($k, $materials)) $materials[$k] = 0;
                //     $materials[$k] += $v;
                // }
                foreach ($statisticsQ as $k => $v) {
                    if (!array_key_exists($k, $materials)) $materials[$k] = 0;
                    $materials[$k] += $v;
                }

                foreach ($stations as $station) {
                    $stationStatistics[] = [
                        'name' => $station->name,
                        'unique_code' => $station->unique_code,
                        'count' => $materials[$station->unique_code] ?? 0,
                    ];
                }
            }
            return HttpResponseHelper::data([
                'sceneWorkshops' => array_chunk($sceneWorkshopStatistics, 3),
                'stations' => array_chunk($stationStatistics, 3)
            ]);
        } catch (\Exception $e) {
            return HttpResponseHelper::error($e->getMessage(), [get_class($e), $e->getFile(), $e->getLine()]);
        }
    }

    /**
     * 获取报警通知
     */
    final public function getEntireInstanceAlarm()
    {
        try {
            $entire_instance_alarm_logs = EntireInstanceAlarmLog::with([])->orderByDesc('created_at')->where('status', 'WARNING')->get();

            return JsonResponseFacade::dict(['entire_instance_alarm_logs' => $entire_instance_alarm_logs]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

}
