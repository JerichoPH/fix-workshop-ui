<?php

namespace App\Console\Commands;

use App\Facades\KindsFacade;
use App\Facades\QueryBuilderFacade;
use App\Facades\StatisticsFacade;
use App\Facades\WarehouseReportFacade;
use App\Model\EntireInstance;
use App\Serializers\BreakdownSerializer;
use App\Serializers\EntireInstanceSerializer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Jericho\FileSystem;

class StatisticsIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistics:index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '1小时主页缓存';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $time = now()->timestamp;
        $this->info('1小时首页缓存生成：' . now()->toDateTimeString());
        /**
         * 动态统计
         * @param $category_unique_code
         * @return array
         */
        $deviceDynamicAsStatus = function (string $category_unique_code = ''): array {
            $filename = ('deviceDynamicAsStatus' . ($category_unique_code ? ".{$category_unique_code}" : ''));
            return StatisticsFacade::getOrCreate(
                'index',
                $filename,
                function () {
                    $statuses = collect(EntireInstance::$STATUSES);
                    $db_S = EntireInstanceSerializer::INS()->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'count(ei.status) as aggregate',
                            'ei.status as status',
                        ]))
                        ->groupBy(['ei.status',]);

                    $db_Q = EntireInstanceSerializer::INS()->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'count(ei.status) as aggregate',
                            'ei.status as status',
                        ]))
                        ->groupBy(['ei.status',]);
                    $statistics = QueryBuilderFacade::unionAll($db_S, $db_Q)->get();

                    $categories = KindsFacade::getCategories();

                    return [
                        'statistics' => $statistics,
                        'statuses' => $statuses,
                        'categories' => $categories,
                    ];
                }
            );
        };
        $categories = KindsFacade::getCategories();
        $deviceDynamicAsStatus();
        $this->comment('生成缓存：动态统计');
        $categories->each(function ($category) use ($deviceDynamicAsStatus) {
            $deviceDynamicAsStatus($category->unique_code);
            $this->comment("生成缓存：动态统计 {$category->unique_code} {$category->name}");
        });

        /**
         * 出入所统计
         * @return array
         */
        $warehouseReport = function () {
            return WarehouseReportFacade::generateStatisticsFor7Days();
        };
        $warehouseReport();
        $this->comment('生成缓存：出入所统计');

        /**
         * 资产管理
         * @return array
         */
        $property = function (): array {
            return StatisticsFacade::getOrCreate('index', 'property', function () {
                $db_S = EntireInstanceSerializer::ins([EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,])->GenerateQueryRelationShipS()
                    ->selectRaw(implode(',', [
                        'count(f.unique_code) as aggregate',
                        'c.unique_code as category_unique_code',
                        'c.name as category_name',
                        'f.unique_code as factory_unique_code',
                        'f.name as factory_name',
                    ]))
                    ->groupBy(['c.unique_code', 'c.name', 'f.unique_code', 'f.name',]);
                $db_Q = EntireInstanceSerializer::ins([EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,])->GenerateQueryRelationShipQ()
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
            });
        };
        $property();
        $this->comment('生成缓存：资产管理');

        /**
         * 质量报告
         * @return array
         * @throws \Exception
         */
        $quality = function (): array {
            return StatisticsFacade::getOrCreate('index', 'quality', function () {
                $file = FileSystem::init(__FILE__);
                // $quality_years = $file->setPath(storage_path($this->_qualityDir))->join('yearList.json')->fromJson();
                // $quality_months = $file->setPath(storage_path($this->_qualityDir))->join('dateList.json')->fromJson();
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

                $db_device_statistics_S = EntireInstanceSerializer::ins()
                    ->GenerateQueryRelationShipS()
                    ->selectRaw(implode(',', [
                        'count(f.unique_code) as aggregate',
                        'f.unique_code as factory_unique_code',
                        'f.name as factory_name',
                    ]))
                    ->groupBy(['c.unique_code', 'c.name',]);

                $db_device_statistics_Q = EntireInstanceSerializer::ins()
                    ->GenerateQueryRelationShipQ()
                    ->selectRaw(implode(',', [
                        'count(f.unique_code) as aggregate',
                        'f.unique_code as factory_unique_code',
                        'f.name as factory_name',
                    ]))
                    ->groupBy(['c.unique_code', 'c.name',]);
                $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

                $db_breakdown_statistics_S = BreakdownSerializer::INIT()
                    ->generateStandardRelationshipS()
                    ->selectRaw(implode(',', [
                        'count(f.unique_code) as aggregate',
                        'c.unique_code as category_unique_code',
                        'c.name as category_name',
                        'f.unique_code as factory_unique_code',
                        'f.name as factory_name',
                    ]))
                    ->whereBetween('rbboei.created_at', [$origin_at, $finish_at])
                    ->groupBy(['c.unique_code', 'c.name', 'f.unique_code', 'f.name',]);
                $db_breakdown_statistics_Q = BreakdownSerializer::INIT()
                    ->generateStandardRelationshipQ()
                    ->selectRaw(implode(',', [
                        'count(f.unique_code) as aggregate',
                        'c.unique_code as category_unique_code',
                        'c.name as category_name',
                        'f.unique_code as factory_unique_code',
                        'f.name as factory_name',
                    ]))
                    ->whereBetween('rbboei.created_at', [$origin_at, $finish_at])
                    ->groupBy(['c.unique_code', 'c.name', 'f.unique_code', 'f.name',]);
                $breakdown_statistics = QueryBuilderFacade::unionAll($db_breakdown_statistics_S, $db_breakdown_statistics_Q)->get();

                return [
                    'device_statistics' => $device_statistics,
                    'breakdown_statistics' => $breakdown_statistics,
                    'quality_date' => request('date', date('Y-m')),
                    // 'quality_years' => $quality_years,
                    // 'quality_months' => $quality_months,
                ];
            });
        };
        $quality();
        $this->comment('生成缓存：质量报告');

        /**
         * 超期使用
         * @return array
         */
        $scraped = function (): array {
            return StatisticsFacade::getOrCreate('index', 'scraped', function () {
                $db_device_statistics_S = EntireInstanceSerializer::ins()
                    ->GenerateQueryRelationShipS()
                    ->selectRaw(implode(',', [
                        'count(c.unique_code) as aggregate',
                        'c.unique_code as category_unique_code',
                        'c.name as category_name',
                    ]))
                    ->groupBy(['c.unique_code', 'c.name',]);
                $db_device_statistics_Q = EntireInstanceSerializer::ins()
                    ->GenerateQueryRelationShipQ()
                    ->selectRaw(implode(',', [
                        'count(c.unique_code) as aggregate',
                        'c.unique_code as category_unique_code',
                        'c.name as category_name',
                    ]))
                    ->groupBy(['c.unique_code', 'c.name',]);
                $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

                $db_scraped_statistics_S = EntireInstanceSerializer::ins()
                    ->GenerateQueryRelationShipS()
                    ->selectRaw(implode(',', [
                        'count(c.unique_code) as aggregate',
                        'c.unique_code as category_unique_code',
                        'c.name as category_name',
                    ]))
                    ->where('scarping_at', '<', now())
                    ->groupBy(['c.unique_code', 'c.name',]);
                $db_scraped_statistics_Q = EntireInstanceSerializer::ins()
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
            });
        };
        $scraped();
        $this->comment('生成缓存：超期使用');

        /**
         * 检修车间台账统计
         */
        $fixWorkshopMaintain = function () {
            return StatisticsFacade::getOrCreate('index', 'fixWorkshopMaintain', function () {
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
            });

        };
        $fixWorkshopMaintain();
        $this->comment('生成缓存：检修车间台账');

        /**
         * 台账
         * @return array
         */
        $maintain = function () {
            return StatisticsFacade::getOrCreate('index', 'maintain', function () {
                return [
                    'statistics' => DB::table('entire_instances as ei')
                        ->selectRaw(implode(',', [
                            'count(ei.status) as aggregate',
                            'ei.status as status',
                            'sc.unique_code as scene_workshop_unique_code',
                            'sc.name as scene_workshop_name',
                        ]))
                        ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                        ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                        ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                        ->groupBy(['ei.status', 'sc.unique_code', 'sc.name',])
                        ->get(),
                ];
            });
        };
        $maintain();
        $this->comment('生成缓存：台账');

        /**
         * 备品统计
         * @return array
         */
        $standby = function () {
            return StatisticsFacade::getOrCreate('index', 'standby', function () {
                $statistics_by_standby_S = EntireInstanceSerializer::INS()
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
                $statistics_by_standby_Q = EntireInstanceSerializer::INS()
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
                    $type = 'SCENE_WORKSHOP';
                    $statistics_by_standby = QueryBuilderFacade::unionAll($statistics_by_standby_S->where('ei.status', 'INSTALLING'), $statistics_by_standby_Q->where('ei.status', 'INSTALLING'))->get();
                } else {
                    $type = 'FIX_WORKSHOP';
                    $statistics_by_standby = QueryBuilderFacade::unionAll($statistics_by_standby_S->where('ei.status', 'FIXED'), $statistics_by_standby_Q->where('ei.status', 'FIXED'))->get();
                }
                return [
                    'statistics' => $statistics_by_standby,
                    'type' => $type,
                ];
            });
        };
        $standby();
        $this->comment('生成缓存：备品统计');

        $this->info('1小时首页缓存生成完毕：' . now()->toDateTimeString() . ' 用时：' . (now()->timestamp - $time) . '秒');
    }
}
