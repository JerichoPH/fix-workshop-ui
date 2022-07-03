<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\QueryBuilderFacade;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceAlarmLog;
use App\Model\EntireInstanceLog;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\Install\InstallShelf;
use App\Model\Maintain;
use App\Model\PartModel;
use App\Model\StationElectricImage;
use App\Serializers\BreakdownSerializer;
use App\Serializers\EntireInstanceSerializer;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\Model\Log;
use Throwable;

class StationWikiController extends Controller
{
    final public function show(string $station_unique_code)
    {
        try {
            // station basic info
            $station = Maintain::with([
                'Parent',
                'Lines',
            ])
                ->where('type', 'STATION')
                ->where('unique_code', $station_unique_code)
                ->firstOrFail();


            $fix_workshop = Maintain::with([])->where('unique_code', env('CURRENT_WORKSHOP_UNIQUE_CODE'))->first();
            if (!$fix_workshop) return '<h1>错误：没有找到检修车间' . env('JWT_ISS') . '</h1>';

            if (!empty($station->lon) && !empty($station->lat)) {
                $station_location = [
                    'unique_code' => $station->unique_code,
                    'name' => $station->name,
                    'parent_unique_code' => $station->Parent->unique_code ?? '',
                    'lon' => $station->lon,
                    'lat' => $station->lat,
                    'contact' => $station->contact,
                    'contact_phone' => $station->contact_phone,
                ];
            } else {
                $station_location = [
                    'unique_code' => $fix_workshop->unique_code,
                    'name' => $fix_workshop->name,
                    'parent_unique_code' => $fix_workshop->parent_unique_code,
                    'lon' => $fix_workshop->lon,
                    'lat' => $fix_workshop->lat,
                    'contact' => $fix_workshop->contact,
                    'contact_phone' => $fix_workshop->contact_phone,
                ];
            }

            // get kinds factories states condition for search
            $categories = Category::with([])->get();
            $entire_models = EntireModel::with([])->where('is_sub_model', false)->whereNotNull('category_unique_code')->get()->groupBy(['category_unique_code']);
            $sub_models = EntireModel::with([])->where('is_sub_model', true)->whereNotNull('parent_unique_code')->get()->groupBy(['parent_unique_code']);
            $part_models = PartModel::with([])->whereNotNull('entire_model_unique_code')->get()->groupBy(['entire_model_unique_code']);
            $models = [];
            $sub_models->each(function ($sub_model, $entire_model_unique_code) use (&$models) {
                $models[$entire_model_unique_code] = $sub_model;
            });
            $part_models->each(function ($part_model, $entire_model_unique_code) use (&$models) {
                $models[$entire_model_unique_code] = $part_model;
            });

            $factories = Factory::with([])->get();
            $statuses = collect(['INSTALLED' => '上道使用', 'INSTALLING' => '现场备品', 'TRANSFER_IN' => '入所在途',]);

            // equipment entire instances in current station
            // $equipments = EntireInstance::with([
            //     'Station',
            //     'Station.Parent',
            //     'Category',
            //     'EntireModel',
            //     'SubModel',
            //     'PartModel',
            // ])
            //     ->where('category_unique_code', 'like', 'S%')
            //     ->whereHas('Station', function ($Station) use ($station) {
            //         $Station->where('unique_code', $station->unique_code);
            //     })
            //     ->get();

            // electric images in current station
            $station_electric_images = StationElectricImage::with([])->where('maintain_station_unique_code', $station->unique_code)->orderBy('id')->get();

            // get entire instance list
            $entire_instances = $this->__getEntireInstances($statuses, $station->unique_code);

            // shelf electric image list in current station
            $install_shelves = InstallShelf::with([
                'WithInstallPlatoon',
                'WithInstallPlatoon.WithInstallRoom',
                'WithInstallTiers' => function ($WithInstallTiers) {
                    $WithInstallTiers->orderByDesc('id');
                },
                'WithInstallTiers.WithInstallPositions',
            ])
                ->whereHas(
                    'WithInstallPlatoon.WithInstallRoom',
                    function (Builder $WithInstallRoom)
                    use ($station_unique_code) {
                        $WithInstallRoom->where('station_unique_code', $station_unique_code);
                    }
                )
                ->limit(4)
                ->get();

            return view('StationWiki.show', [
                'categories_as_json' => $categories->toJson(),
                'entire_models_as_json' => $entire_models->toJson(),
                'models_as_json' => collect($models)->toJson(),
                'factories_as_json' => $factories->toJson(),
                'statuses_as_json' => $statuses->toJson(),
                'station' => $station,
                // 'equipments' => $equipments,
                'station_electric_images_as_json' => $station_electric_images->toJson(),
                'install_shelves' => $install_shelves,
                'entire_instances' => $entire_instances,
                'station_location_as_json' => collect($station_location)->toJson(),
            ]);
        } catch (ModelNotFoundException $e) {
            return '<h1>错误：车站不存在</h1>';
        } catch (Throwable $e) {
            // return '<h1>意外错误</h1>';
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * maintain statistics
     * @param string $station_unique_code '
     */
    final public function getMaintainStatistics(string $station_unique_code)
    {
        try {
            $station = Maintain::with([])->where('unique_code', $station_unique_code)->firstOrFail();

            $statuses = [
                'INSTALLING' => '现场备品',
                'INSTALLED' => '上道使用',
                'BREAKDOWN' => '故障',
                'PASS_DUE' => '超期',
            ];

            // 台账统计
            $statistics_by_maintain_S = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(ei.status)            as aggregate',
                    'ei.status                   as status',
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_maintain_Q = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(ei.status)            as aggregate',
                    'ei.status                   as status',
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_maintain = QueryBuilderFacade::unionAll($statistics_by_maintain_S, $statistics_by_maintain_Q)->get()->toArray();

            // 故障统计
            $statistics_by_breakdown_S = BreakdownSerializer::INIT([
                BreakdownSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->generateStandardRelationshipS()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "'BREAKDOWN'                 as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_breakdown_Q = BreakdownSerializer::INIT([
                BreakdownSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->generateStandardRelationshipQ()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "'BREAKDOWN'                 as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_breakdown = QueryBuilderFacade::unionAll($statistics_by_breakdown_Q, $statistics_by_breakdown_S)->get()->toArray();

            // 超期统计
            $statistics_by_pass_due_S = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "'PASS_DUE'                  as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('ei.scarping_at', '<', now())
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_pass_due_Q = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "'PASS_DUE'                  as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('ei.scarping_at', '<', now())
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_pass_due = QueryBuilderFacade::unionAll($statistics_by_pass_due_Q, $statistics_by_pass_due_S)->get()->toArray();

            $statistics = array_merge($statistics_by_maintain, $statistics_by_breakdown, $statistics_by_pass_due);

            return JsonResponseFacade::data([
                'statistics' => $statistics,
                'statuses' => $statuses,
                'statuses_as_flip' => array_flip($statuses),
            ]);
        } catch (ModelNotFoundException $e) {
            if (request()->ajax()) {
                return JsonResponseFacade::errorEmpty();
            } else {
                return '<h1>错误：车站不存在</h1>';
            }

        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     *
     * @param string $station_unique_code
     * @return array
     */
    final public function getMaintainStatisticsByCategoryUniqueCode(string $station_unique_code)
    {
        if (!request('category_unique_code')) return JsonResponseFacade::errorEmpty('种类参数错误');
        $category_unique_code = request('category_unique_code');

        $statuses = [
            'INSTALLING' => '现场备品',
            'INSTALLED' => '上道使用',
            'BREAKDOWN' => '故障',
            'PAST_DUE' => '超期',
        ];

        // 台账统计
        $statistics_by_maintain_S = EntireInstanceSerializer::INS([
            EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
        ])
            ->GenerateQueryRelationShipS()
            ->selectRaw(implode(',', [
                'count(ei.status)            as aggregate',
                'ei.status                   as status',
                'c.unique_code               as category_unique_code',
                'c.name                      as category_name',
                'em.unique_code              as entire_model_unique_code',
                'em.name                     as entire_model_name',
                'ei.model_unique_code        as model_unique_code',
                'ei.model_name               as model_name',
            ]))
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
            ->where('s.unique_code', $station_unique_code)
            ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $statistics_by_maintain_Q = EntireInstanceSerializer::INS([
            EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
        ])
            ->GenerateQueryRelationShipQ()
            ->selectRaw(implode(',', [
                'count(ei.status)            as aggregate',
                'ei.status                   as status',
                'c.unique_code               as category_unique_code',
                'c.name                      as category_name',
                'em.unique_code              as entire_model_unique_code',
                'em.name                     as entire_model_name',
                'ei.model_unique_code        as model_unique_code',
                'ei.model_name               as model_name',
            ]))
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
            ->where('s.unique_code', $station_unique_code)
            ->where('c.unique_code', $category_unique_code)
            ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $statistics_by_maintain = QueryBuilderFacade::unionAll($statistics_by_maintain_S, $statistics_by_maintain_Q)->get()->toArray();

        // 故障统计
        $statistics_by_breakdown_S = BreakdownSerializer::INIT([
            BreakdownSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
        ])
            ->generateStandardRelationshipS()
            ->selectRaw(implode(',', [
                'count(ei.model_unique_code) as aggregate',
                "'BREAKDOWN'                 as status",
                'c.unique_code               as category_unique_code',
                'c.name                      as category_name',
                'em.unique_code              as entire_model_unique_code',
                'em.name                     as entire_model_name',
                'ei.model_unique_code        as model_unique_code',
                'ei.model_name               as model_name',
            ]))
            ->where('s.unique_code', $station_unique_code)
            ->where('c.unique_code', $category_unique_code)
            ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $statistics_by_breakdown_Q = BreakdownSerializer::INIT([
            BreakdownSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
        ])
            ->generateStandardRelationshipQ()
            ->selectRaw(implode(',', [
                'count(ei.model_unique_code) as aggregate',
                "'BREAKDOWN'                 as status",
                'c.unique_code               as category_unique_code',
                'c.name                      as category_name',
                'em.unique_code              as entire_model_unique_code',
                'em.name                     as entire_model_name',
                'ei.model_unique_code        as model_unique_code',
                'ei.model_name               as model_name',
            ]))
            ->where('s.unique_code', $station_unique_code)
            ->where('c.unique_code', $category_unique_code)
            ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $statistics_by_breakdown = QueryBuilderFacade::unionAll($statistics_by_breakdown_Q, $statistics_by_breakdown_S)->get()->toArray();

        // 超期统计
        $statistics_by_pass_due_S = EntireInstanceSerializer::INS()
            ->GenerateQueryRelationShipS()
            ->selectRaw(implode(',', [
                'count(ei.model_unique_code) as aggregate',
                "'PASS_DUE'                  as status",
                'c.unique_code               as category_unique_code',
                'c.name                      as category_name',
                'em.unique_code              as entire_model_unique_code',
                'em.name                     as entire_model_name',
                'ei.model_unique_code        as model_unique_code',
                'ei.model_name               as model_name',
            ]))
            ->where('ei.scarping_at', '<', now())
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
            ->where('s.unique_code', $station_unique_code)
            ->where('c.unique_code', $category_unique_code)
            ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $statistics_by_pass_due_Q = EntireInstanceSerializer::INS()
            ->GenerateQueryRelationShipQ()
            ->selectRaw(implode(',', [
                'count(ei.model_unique_code) as aggregate',
                "'PASS_DUE'                  as status",
                'c.unique_code               as category_unique_code',
                'c.name                      as category_name',
                'em.unique_code              as entire_model_unique_code',
                'em.name                     as entire_model_name',
                'ei.model_unique_code        as model_unique_code',
                'ei.model_name               as model_name',
            ]))
            ->where('ei.scarping_at', '<', now())
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
            ->where('s.unique_code', $station_unique_code)
            ->where('c.unique_code', $category_unique_code)
            ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $statistics_by_pass_due = QueryBuilderFacade::unionAll($statistics_by_pass_due_Q, $statistics_by_pass_due_S)->get()->toArray();

        $statistics = array_merge($statistics_by_maintain, $statistics_by_breakdown, $statistics_by_pass_due);

        return JsonResponseFacade::data([
            'statistics' => $statistics,
            'statuses' => $statuses,
            'statuses_as_flip' => array_flip($statuses),
        ]);
    }

    /**
     * get logs: install, breakdown, alarm
     * @param string $station_unique_code
     * @param string|null $station_name
     * @param bool $is_download
     * @param string $excel_type
     * @return mixed
     */
    final public function getLogs(string $station_unique_code, string $excel_type = '')
    {
        try {
            $times = [
                'today' => [now()->startOfDay(), now()->endOfDay()],
                'week' => [now()->startOfWeek(), now()->endOfWeek()],
                'month' => [now()->startOfMonth(), now()->endOfMonth()],
            ];
            $statistics = [
                'install_log' => ['today' => 0, 'week' => 0, 'month' => 0],
                'breakdown_log' => ['today' => 0, 'week' => 0, 'month' => 0],
                'material_alarm_log' => ['today' => 0, 'week' => 0, 'month' => 0],
            ];
            $station = Maintain::with([])->where('unique_code', $station_unique_code)->first();

            // install, installing, transfer in logs
            $install_logs = EntireInstanceLog::with(['Operator', 'Station'])
                ->where('station_unique_code', $station_unique_code)
                ->orderByDesc('created_at')
                ->where('type', 4)
                ->get();
            $statistics['install_log']['today'] = DB::table('entire_instance_logs')->where('type', 4)->whereBetween('created_at', [$times['today']])->count();
            $statistics['install_log']['week'] = DB::table('entire_instance_logs')->where('type', 4)->whereBetween('created_at', [$times['week']])->count();
            $statistics['install_log']['month'] = DB::table('entire_instance_logs')->where('type', 4)->whereBetween('created_at', [$times['week']])->count();

            // breakdown logs
            $breakdown_logs = EntireInstanceLog::with(['Processor'])
                ->where('station_unique_code', $station_unique_code)
                ->orderByDesc('created_at')
                ->where('type', 5)
                ->get();
            $statistics['breakdown_log']['today'] = DB::table('entire_instance_logs')->where('type', 5)->whereBetween('created_at', [$times['today']])->count();
            $statistics['breakdown_log']['week'] = DB::table('entire_instance_logs')->where('type', 5)->whereBetween('created_at', [$times['week']])->count();
            $statistics['breakdown_log']['month'] = DB::table('entire_instance_logs')->where('type', 5)->whereBetween('created_at', [$times['week']])->count();

            // entire instance alarm logs
            $entire_instance_alarm_logs = EntireInstanceAlarmLog::with([])
                ->where('station_unique_code', $station_unique_code)
                ->orderByDesc('created_at')
                ->get();
            $statistics['entire_instance_alarm_log']['today'] = DB::table('entire_instance_alarm_logs')->whereBetween('created_at', [$times['today']])->count();
            $statistics['entire_instance_alarm_log']['week'] = DB::table('entire_instance_alarm_logs')->whereBetween('created_at', [$times['week']])->count();
            $statistics['entire_instance_alarm_log']['month'] = DB::table('entire_instance_alarm_logs')->whereBetween('created_at', [$times['week']])->count();

            if (request()->ajax()) {
                return JsonResponseFacade::data([
                    'install_logs' => $install_logs,
                    'breakdown_logs' => $breakdown_logs,
                    'material_alarm_logs' => $entire_instance_alarm_logs,
                    'statistics' => $statistics,
                ]);
            } else {
                $excel_types = [
                    'install' => '上下道日志',
                    'breakdown' => '故障日志',
                    'entire_instance_alarm' => '报警日志',
                ];

                $current_row = 3;
                ExcelWriteHelper::download(function ($excel)
                use (
                    $excel_type,
                    $excel_types,
                    $station,
                    $install_logs,
                    $breakdown_logs,
                    $entire_instance_alarm_logs,
                    $statistics,
                    &$current_row
                ) {
                    $sheet = $excel->getActiveSheet();
                    $sheet->setTitle("{$station->name}站{$excel_types[$excel_type]}");

                    switch ($excel_type) {
                        case 'install':
                            // statistics
                            $sheet->setCellValueExplicit("A1", "今日：{$statistics['install_log']['today']}");
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(20);
                            $sheet->setCellValueExplicit("B1", "本周：{$statistics['install_log']['week']}");
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(20);
                            $sheet->setCellValueExplicit("C1", "本月：{$statistics['install_log']['month']}");
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(20);

                            // first row
                            $first_row_data = [
                                ['context' => '时间', 'color' => 'black', 'width' => 20],  // A
                                ['context' => '操作人', 'color' => 'black', 'width' => 20],  // B
                                ['context' => '名称', 'color' => 'black', 'width' => 20],  // C
                                ['context' => '内容', 'color' => 'black', 'width' => 50],  // D
                            ];
                            // fill first row
                            foreach ($first_row_data as $col => $first_row_datum) {
                                $col_for_excel = ExcelWriteHelper::int2Excel($col);
                                ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                                $sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                                $sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                                $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                            }

                            foreach ($install_logs as $install_log) {
                                $sheet->setCellValueExplicit("A{$current_row}", $install_log->created_at);
                                $sheet->setCellValueExplicit("B{$current_row}", $install_log->WithAccount->nickname);
                                $sheet->setCellValueExplicit("C{$current_row}", $install_log->name);
                                $sheet->setCellValueExplicit("D{$current_row}", $install_log->description);
                            }

                            return $excel;
                        case 'breakdown':
                            // statistics
                            $sheet->setCellValueExplicit("A1", "今日：{$statistics['breakdown_log']['today']}");
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(20);
                            $sheet->setCellValueExplicit("B1", "本周：{$statistics['breakdown_log']['week']}");
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(20);
                            $sheet->setCellValueExplicit("C1", "本月：{$statistics['breakdown_log']['month']}");
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(20);

                            // first row
                            $first_row_data = [
                                ['context' => '时间', 'color' => 'black', 'width' => 20],  // A
                                ['context' => '操作人', 'color' => 'black', 'width' => 30],  // B
                                ['context' => '名称', 'color' => 'black', 'width' => 20],  // C
                                ['context' => '内容', 'color' => 'black', 'width' => 20],  // D
                            ];
                            // fill first row
                            foreach ($first_row_data as $col => $first_row_datum) {
                                $col_for_excel = ExcelWriteHelper::int2Excel($col);
                                ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                                $sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                                $sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                                $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                            }

                            foreach ($breakdown_logs as $breakdown_log) {
                                $sheet->setCellValueExplicit("A{$current_row}", $breakdown_log->created_at);
                                $sheet->setCellValueExplicit("B{$current_row}", $breakdown_log->WithAccount->nickname);
                                $sheet->setCellValueExplicit("C{$current_row}", $breakdown_log->name);
                                $sheet->setCellValueExplicit("D{$current_row}", $breakdown_log->description);
                            }

                            return $excel;
                        case 'entire_instance_alarm':
                            // statistics
                            $sheet->setCellValueExplicit("A1", "今日：{$statistics['entire_instance_alarm_log']['today']}");
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(20);
                            $sheet->setCellValueExplicit("B1", "本周：{$statistics['entire_instance_alarm_log']['week']}");
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(20);
                            $sheet->setCellValueExplicit("C1", "本月：{$statistics['entire_instance_alarm_log']['month']}");
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(20);

                            // first row
                            $first_row_data = [
                                ['context' => '时间', 'color' => 'black', 'width' => 20],  // A
                                ['context' => '级别', 'color' => 'black', 'width' => 30],  // B
                                ['context' => '内容', 'color' => 'black', 'width' => 20],  // C
                                ['context' => '原因', 'color' => 'black', 'width' => 20],  // D
                            ];
                            // fill first row
                            foreach ($first_row_data as $col => $first_row_datum) {
                                $col_for_excel = ExcelWriteHelper::int2Excel($col);
                                ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                                $sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                                $sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                                $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                            }

                            foreach ($entire_instance_alarm_logs as $material_alarm_log) {
                                $sheet->setCellValueExplicit("A{$current_row}", $material_alarm_log->alarm_at);
                                $sheet->setCellValueExplicit("B{$current_row}", $material_alarm_log->alarm_level);
                                $sheet->setCellValueExplicit("C{$current_row}", $material_alarm_log->alarm_content);
                                $sheet->setCellValueExplicit("D{$current_row}", $material_alarm_log->alarm_cause);
                            }

                            return $excel;
                        default:
                            return $excel;
                    }
                }, "{$station->name}站{$excel_types[$excel_type]}");
                return null;
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * get standby statistics
     * @param string $station_unique_code
     * @param string|null $type
     * @return array
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public function getStandbyStatistics(string $station_unique_code, string $type = null)
    {
        $station = Maintain::with(['Parent', 'EntireInstances',])
            ->where('unique_code', $station_unique_code)
            ->first();
        if (!$station) return JsonResponseFacade::errorEmpty('车站不存在');
        if (@$station->EntireInstances->isEmpty()) return JsonResponseFacade::errorEmpty('车站没有设备器材');

        $models_by_station_S = EntireInstanceSerializer::INS([
            EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
        ])
            ->GenerateQueryRelationShipS()
            ->select([
                'c.name                as category_name',
                'c.unique_code         as category_unique_code',
                'em.name               as entire_model_name',
                'em.unique_code        as entire_model_unique_code',
                'ei.model_name         as sub_model_name',
                'ei.model_unique_code  as sub_model_unique_code',
            ])
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING'])
            ->when(request('category_unique_code'), function ($query, $category_unique_code) {
                $query->where('c.unique_code', $category_unique_code);
            })
            ->where('s.unique_code', $station->unique_code)
            ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name']);
        $models_by_station_Q = EntireInstanceSerializer::INS([
            EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
        ])
            ->GenerateQueryRelationShipQ()
            ->select([
                'c.name                as category_name',
                'c.unique_code         as category_unique_code',
                'em.name               as entire_model_name',
                'em.unique_code        as entire_model_unique_code',
                'ei.model_name         as sub_model_name',
                'ei.model_unique_code  as sub_model_unique_code',
            ])
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING'])
            ->when(request('category_unique_code'), function ($query, $category_unique_code) {
                $query->where('c.unique_code', $category_unique_code);
            })
            ->where('s.unique_code', $station->unique_code)
            ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name']);
        $models_by_station = ModelBuilderFacade::unionAll($models_by_station_Q, $models_by_station_S)->get();

        $select_raw = request('category_unique_code')
            ? implode(',', [
                'em.name as name',
                'em.unique_code as unique_code',
                'count(em.unique_code) as aggregate',
            ])
            : implode(',', [
                'c.name as name',
                'c.unique_code as unique_code',
                'count(c.unique_code) as aggregate',
            ]);
        $group_by = request('category_unique_code') ? ['em.unique_code', 'em.name'] : ['c.unique_code', 'c.name'];

        if ($type == 'download') {
            $select_raw = implode(',', [
                'count(ei.model_unique_code) as aggregate',
                'c.name                      as category_name',
                'c.unique_code               as category_unique_code',
                'em.name                     as entire_model_name',
                'em.unique_code              as entire_model_unique_code',
                'ei.model_name               as sub_model_name',
                'ei.model_unique_code        as sub_model_unique_code',
            ]);
            $group_by = ['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',];
        }

        $getStatisticsDB = function (string $status = null) use ($select_raw, $group_by): array {
            $db_S = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipS()
                ->where('ei.status', $status);
            $db_Q = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipQ()
                ->where('ei.status', $status);

            $db_Q->selectRaw($select_raw)->groupBy($group_by);
            $db_S->selectRaw($select_raw)->groupBy($group_by);
            return [$db_Q, $db_S];
        };

        $functions = [
            'station' => function () use ($station, $select_raw, $getStatisticsDB) {
                list($db_Q, $db_S) = $getStatisticsDB('INSTALLING');
                $db_Q->where('s.unique_code', $station->unique_code);
                $db_S->where('s.unique_code', $station->unique_code);
                return ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
            },
            'near_station' => function () use ($station, $models_by_station, $getStatisticsDB, $select_raw) {
                $distance_with_stations = DB::table('distance as d')
                    ->selectRaw(implode(',', [
                        'd.from_unique_code',
                        'd.to_unique_code',
                        'd.distance',
                        's.name',
                        'ws.unique_code as workshop_unique_code',
                        's.contact',
                        's.contact_phone',
                        's.lon',
                        's.lat',
                    ]))
                    ->selectRaw("d.from_unique_code,d.to_unique_code, d.distance, s.name, ws.unique_code as workshop_unique_code, s.contact, s.contact_phone, s.lon, s.lat")
                    ->join(DB::raw('maintains s'), 'd.to_unique_code', '=', 's.unique_code')
                    ->join(DB::raw('maintains ws'), 'ws.unique_code', '=', 's.parent_unique_code')
                    ->where('d.from_unique_code', $station->unique_code)
                    ->where('d.to_unique_code', '<>', $station->unique_code)
                    ->where('d.from_type', 'STATION')
                    ->where('d.to_type', 'STATION')
                    ->orderBy(DB::raw('d.distance+0'))
                    ->limit(2)
                    ->get()
                    ->toArray();

                // $near_station_unique_codes = array_pluck($distance_with_stations, 'name', 'to_unique_code');

                $near_station_statistics = [];
                foreach ($distance_with_stations as $distance_with_station) {
                    list($db_Q, $db_S) = $getStatisticsDB('INSTALLING');
                    $db_Q->where('s.unique_code', $distance_with_station->to_unique_code)->whereIn('ei.model_unique_code', $models_by_station->pluck('sub_model_unique_code')->toArray());
                    $db_S->where('s.unique_code', $distance_with_station->to_unique_code)->whereIn('ei.model_unique_code', $models_by_station->pluck('sub_model_unique_code')->toArray());
                    $near_station_statistics[$distance_with_station->to_unique_code] = [
                        'statistics' => ModelBuilderFacade::unionAll($db_Q, $db_S)->get(),
                        'unique_code' => $distance_with_station->to_unique_code,
                        'name' => $distance_with_station->name,
                        'distance' => $distance_with_station->distance,
                    ];
                }

                return $near_station_statistics;
            },
            'scene_workshop' => function () use ($station, $models_by_station, $getStatisticsDB, $select_raw) {
                list($db_Q, $db_S) = $getStatisticsDB('INSTALLING');
                $db_Q->where('s.parent_unique_code', $station->parent_unique_code)->whereIn('ei.model_unique_code', $models_by_station->pluck('sub_model_unique_code')->toArray());
                $db_S->where('s.parent_unique_code', $station->parent_unique_code)->whereIn('ei.model_unique_code', $models_by_station->pluck('sub_model_unique_code')->toArray());
                return ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
            },
            'fix_workshop' => function () use ($station, $models_by_station, $getStatisticsDB, $select_raw) {
                list($db_Q, $db_S) = $getStatisticsDB('FIXED');
                $db_Q->whereIn('ei.model_unique_code', $models_by_station->pluck('sub_model_unique_code'));
                $db_S->whereIn('ei.model_unique_code', $models_by_station->pluck('sub_model_unique_code'));
                return ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
            }
        ];

        $distances = [
            'scene_workshop' => DB::table('distance')->where('from_unique_code', $station->unique_code)->where('to_unique_code', $station->parent_unique_code)->first(['distance'])->distance ?? 0,
            'fix_workshop' => DB::table('distance')->where('from_unique_code', $station->unique_code)->where('to_unique_code', env('CURRENT_WORKSHOP_UNIQUE_CODE'))->first(['distance'])->distance ?? 0
        ];

        $statistics['station'] = $functions['station']();
        $statistics['near_station'] = $functions['near_station']();
        $statistics['scene_workshop'] = $functions['scene_workshop']();
        $statistics['fix_workshop'] = $functions['fix_workshop']();
        $statistics['near_station_category_count'] = 0;
        foreach ($statistics['near_station'] as $near_station) {
            $statistics['near_station_category_count'] += count($near_station['statistics']) > 0 ? count($near_station['statistics']) : 1;
        }

        if (request()->ajax()) {
            return JsonResponseFacade::data([
                'statistics' => $statistics,
                'distances' => $distances,
                'models_by_station' => $models_by_station,
            ]);
        } else {
            ExcelWriteHelper::download(function ($excel) use ($statistics, $station) {
                $excel->getActiveSheet()->setTitle('当前车站');
                // current station
                $functions = [
                    'station' => function () use (&$excel, $statistics, $station) {
                        $current_row = 2;
                        $excel->getActiveSheet()->setTitle("当前车站：{$station->name}");
                        $sheet = $excel->getActiveSheet();

                        // first row
                        $first_row_data = [
                            ['context' => '种类', 'color' => 'black', 'width' => 30],  // A
                            ['context' => '类型', 'color' => 'black', 'width' => 30],  // B
                            ['context' => '型号', 'color' => 'black', 'width' => 30],  // C
                            ['context' => '备品数量', 'color' => 'black', 'width' => 20],  // D
                        ];
                        // fill first row
                        foreach ($first_row_data as $col => $first_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                            $sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        foreach ($statistics['station'] as $item) {
                            $sheet->setCellValueExplicit("A{$current_row}", $item->category_name);
                            $sheet->setCellValueExplicit("B{$current_row}", $item->entire_model_name);
                            $sheet->setCellValueExplicit("C{$current_row}", $item->model_name);
                            $sheet->setCellValueExplicit("D{$current_row}", $item->aggregate);
                            $current_row++;
                        }
                    },
                    'near_station' => function () use (&$excel, $statistics) {
                        $current_sheet_index = 1;
                        foreach ($statistics['near_station'] as $near_station_statistics) {
                            $current_row = 2;
                            ['name' => $name, 'statistics' => $station_statistics,] = $near_station_statistics;
                            $excel->createSheet();
                            $excel->setActiveSheetIndex($current_sheet_index);
                            $excel->getActiveSheet()->setTitle("临近车站：{$name}");
                            $current_sheet_index++;
                            $sheet = $excel->getActiveSheet();

                            // first row
                            $first_row_data = [
                                ['context' => '种类', 'color' => 'black', 'width' => 30],  // A
                                ['context' => '类型', 'color' => 'black', 'width' => 30],  // B
                                ['context' => '型号', 'color' => 'black', 'width' => 30],  // C
                                ['context' => '备品数量', 'color' => 'black', 'width' => 20],  // D
                            ];
                            // fill first row
                            foreach ($first_row_data as $col => $first_row_datum) {
                                $col_for_excel = ExcelWriteHelper::int2Excel($col);
                                ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                                $sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                                $sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                                $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                            }

                            foreach ($station_statistics as $item) {
                                $sheet->setCellValueExplicit("A{$current_row}", $item->category_name);
                                $sheet->setCellValueExplicit("B{$current_row}", $item->entire_model_name);
                                $sheet->setCellValueExplicit("C{$current_row}", $item->model_name);
                                $sheet->setCellValueExplicit("D{$current_row}", $item->aggregate);
                                $current_row++;
                            }
                        }

                    },
                    'scene_workshop' => function () use (&$excel, $statistics, $station) {
                        $current_row = 2;
                        $excel->createSheet();
                        $excel->setActiveSheetIndex(3);
                        $excel->getActiveSheet()->setTitle("所属车间：{$station->Parent->name}");
                        $sheet = $excel->getActiveSheet();

                        // first row
                        $first_row_data = [
                            ['context' => '种类', 'color' => 'black', 'width' => 30],  // A
                            ['context' => '类型', 'color' => 'black', 'width' => 30],  // B
                            ['context' => '型号', 'color' => 'black', 'width' => 30],  // C
                            ['context' => '备品数量', 'color' => 'black', 'width' => 20],  // D
                        ];
                        // fill first row
                        foreach ($first_row_data as $col => $first_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                            $sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        foreach ($statistics['scene_workshop'] as $item) {
                            $sheet->setCellValueExplicit("A{$current_row}", $item->category_name);
                            $sheet->setCellValueExplicit("B{$current_row}", $item->entire_model_name);
                            $sheet->setCellValueExplicit("C{$current_row}", $item->model_name);
                            $sheet->setCellValueExplicit("D{$current_row}", $item->aggregate);
                            $current_row++;
                        }
                    },
                    'fix_workshop' => function () use (&$excel, $statistics) {
                        $current_row = 2;
                        $excel->createSheet();
                        $excel->setActiveSheetIndex(4);
                        $excel->getActiveSheet()->setTitle("检修车间：" . env('JWT_ISS'));
                        $sheet = $excel->getActiveSheet();

                        // first row
                        $first_row_data = [
                            ['context' => '种类', 'color' => 'black', 'width' => 30],  // A
                            ['context' => '类型', 'color' => 'black', 'width' => 30],  // B
                            ['context' => '型号', 'color' => 'black', 'width' => 30],  // C
                            ['context' => '备品数量', 'color' => 'black', 'width' => 20],  // D
                        ];
                        // fill first row
                        foreach ($first_row_data as $col => $first_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                            $sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        foreach ($statistics['fix_workshop'] as $item) {
                            $sheet->setCellValueExplicit("A{$current_row}", $item->category_name);
                            $sheet->setCellValueExplicit("B{$current_row}", $item->entire_model_name);
                            $sheet->setCellValueExplicit("C{$current_row}", $item->model_name);
                            $sheet->setCellValueExplicit("D{$current_row}", $item->aggregate);
                            $current_row++;
                        }
                    },
                ];

                $functions['station']();
                $functions['near_station']();
                $functions['scene_workshop']();
                $functions['fix_workshop']();

                $excel->setActiveSheetIndex(0);
                return $excel;
            }, "{$station->name}备品统计");

            return $statistics;
        }

    }

    /**
     * get scraped statistics
     * @param string $station_unique_code
     * @return mixed
     */
    final public function getScrapedStatistics(string $station_unique_code)
    {
        try {
            $station = Maintain::with([])->where('unique_code', $station_unique_code)->first();

            // 超期统计
            $statistics_by_pass_due_S = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "'PASS_DUE'                  as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('ei.scarping_at', '<', now())
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_pass_due_Q = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "'PASS_DUE'                  as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('ei.scarping_at', '<', now())
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_pass_due = QueryBuilderFacade::unionAll($statistics_by_pass_due_Q, $statistics_by_pass_due_S)->get()->toArray();

            $statistics_by_total_S = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "ei.status                   as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_total_Q = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "ei.status                   as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('s.unique_code', $station_unique_code)
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_total = QueryBuilderFacade::unionAll($statistics_by_total_S, $statistics_by_total_Q)->get()->toArray();

            if (request()->ajax()) {
                return JsonResponseFacade::dict([
                    'statistics_by_pass_due' => $statistics_by_pass_due,
                    'statistics_by_total' => $statistics_by_total,
                ]);
            } else {
                $statistics = [];
                foreach ($statistics_by_total as $statistic_by_total) {
                    ['category_name' => $category_name, 'entire_model_name' => $entire_model_name, 'model_name' => $model_name, 'aggregate' => $total] = (array)$statistic_by_total;
                    if (!array_key_exists($statistic_by_total->model_unique_code, $statistics)) $statistics[$statistic_by_total->model_unique_code] = ['category_name' => $category_name, 'entire_model_name' => $entire_model_name, 'model_name' => $model_name, 'total' => 0, 'scraped' => 0, 'probability' => ''];
                    $statistics[$statistic_by_total->model_unique_code]['total'] = $total;
                }
                foreach ($statistics_by_pass_due as $statistic_by_pass_due) {
                    ['category_name' => $category_name, 'entire_model_name' => $entire_model_name, 'model_name' => $model_name, 'aggregate' => $scraped] = (array)$statistic_by_pass_due;
                    if (!array_key_exists($statistic_by_pass_due->model_unique_code, $statistics)) $statistics[$statistic_by_pass_due->model_unique_code] = ['category_name' => $category_name, 'entire_model_name' => $entire_model_name, 'model_name' => $model_name, 'total' => 0, 'scraped' => 0, 'probability' => ''];
                    $statistics[$statistic_by_pass_due->model_unique_code]['scraped'] = $scraped;
                }
                $statistics = array_map(function ($statistic) {
                    ['total' => $total, 'scraped' => $scraped] = $statistic;
                    $statistic['probability'] = (($scraped > 0) && ($total > 0)) ? number_format((($scraped / $total) * 100), 2) . '%' : '0.00%';
                    return $statistic;
                }, $statistics);
                ksort($statistics);

                ExcelWriteHelper::download(
                    function ($excel) use ($statistics, $station) {
                        $current_row = 2;
                        $sheet = $excel->getActiveSheet();
                        $sheet->setTitle("{$station->name}超期统计");

                        // first row
                        $first_row_data = [
                            ['context' => '种类', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '类型', 'color' => 'black', 'width' => 30],  // B
                            ['context' => '型号', 'color' => 'black', 'width' => 30],  // C
                            ['context' => '设备器材总数', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '超期使用', 'color' => 'black', 'width' => 20],  // E
                            ['context' => '超期率', 'color' => 'black', 'width' => 20],  // F
                        ];
                        // fill first row
                        foreach ($first_row_data as $col => $first_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                            $sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        foreach ($statistics as $statistic) {
                            $sheet->setCellValueExplicit("A{$current_row}", $statistic['category_name']);
                            $sheet->setCellValueExplicit("B{$current_row}", $statistic['entire_model_name']);
                            $sheet->setCellValueExplicit("C{$current_row}", $statistic['model_name']);
                            $sheet->setCellValueExplicit("D{$current_row}", $statistic['total']);
                            $sheet->setCellValueExplicit("E{$current_row}", $statistic['scraped']);
                            $sheet->setCellValueExplicit("F{$current_row}", $statistic['probability']);
                            $current_row++;
                        }

                        return $excel;
                    },
                    "{$station->name}超期统计"
                );

                return null;
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * get scraped statistics by category unique code
     * @param string $station_unique_code
     * @return mixed
     */
    final public function getScrapedStatisticsByCategoryUniqueCode(string $station_unique_code)
    {
        try {
            $station = Maintain::with([])->where('unique_code', $station_unique_code)->first();

            // 超期统计
            $statistics_by_pass_due_S = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "'PASS_DUE'                  as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('ei.scarping_at', '<', now())
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                ->where('s.unique_code', $station_unique_code)
                ->when(
                    request('category_unique_code'),
                    function ($query, $category_unique_code) {
                        $query->where('c.unique_code', $category_unique_code);
                    }
                )
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_pass_due_Q = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "'PASS_DUE'                  as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('ei.scarping_at', '<', now())
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                ->where('s.unique_code', $station_unique_code)
                ->when(
                    request('category_unique_code'),
                    function ($query, $category_unique_code) {
                        $query->where('c.unique_code', $category_unique_code);
                    }
                )
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_pass_due = QueryBuilderFacade::unionAll($statistics_by_pass_due_Q, $statistics_by_pass_due_S)->get();

            $statistics_by_total_S = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "ei.status                   as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('s.unique_code', $station_unique_code)
                ->when(
                    request('category_unique_code'),
                    function ($query, $category_unique_code) {
                        $query->where('c.unique_code', $category_unique_code);
                    }
                )
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_total_Q = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    "ei.status                   as status",
                    'c.unique_code               as category_unique_code',
                    'c.name                      as category_name',
                    'em.unique_code              as entire_model_unique_code',
                    'em.name                     as entire_model_name',
                    'ei.model_unique_code        as model_unique_code',
                    'ei.model_name               as model_name',
                ]))
                ->where('s.unique_code', $station_unique_code)
                ->when(
                    request('category_unique_code'),
                    function ($query, $category_unique_code) {
                        $query->where('c.unique_code', $category_unique_code);
                    }
                )
                ->groupBy(['ei.status', 'c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
            $statistics_by_total = QueryBuilderFacade::unionAll($statistics_by_total_S, $statistics_by_total_Q)->get();

            return JsonResponseFacade::dict([
                'statistics_by_pass_due' => $statistics_by_pass_due,
                'statistics_by_total' => $statistics_by_total,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * get entire instances
     * @param Collection $statuses
     * @param string $station_unique_code
     * @return mixed
     */
    final private function __getEntireInstances(Collection $statuses, string $station_unique_code)
    {
        return ModelBuilderFacade::init(
            request(),
            EntireInstance::with([
                'Station',
                'Station.Parent',
                'Category',
                'EntireModel',
                'SubModel',
                'SubModel.Parent',
                'PartModel',
                'PartModel.Parent',
                'InstallPosition',
                'Factory',
            ]),
            [
                'station_unique_code',
                'bottom',
                'download',
                'factory_unique_code',
                'entire_model_unique_code',
                'time',
                'is_iframe',
            ]
        )
            ->extension(function ($builder) use ($statuses, $station_unique_code) {
                $builder
                    ->whereHas('Station', function ($Station) use ($station_unique_code) {
                        $Station->where('unique_code', $station_unique_code);
                    })
                    ->whereIn('status', $statuses->keys()->toArray())
                    ->when(
                        request('status'),
                        function ($query, $status) {
                            $query->where('status', $status);
                        }
                    )
                    ->when(
                        request('factory_unique_code'),
                        function ($query, $factory_unique_code) {
                            $query->whereHas('Factory', function ($Factory) use ($factory_unique_code) {
                                $Factory->where('unique_code', $factory_unique_code);
                            });
                        }
                    )
                    ->when(
                        request('entire_model_unique_code'),
                        function ($query, $entire_model_unique_code) {
                            $query->where('model_unique_code', 'like', $entire_model_unique_code . '%');
                        }
                    );
            })
            ->pagination();
    }

    /**
     * get warning alarm
     * @param string $station_unique_code
     * @return mixed
     */
    final public function getEntireInstanceAlarmLogs(string $station_unique_code)
    {
        try {
            $station = Maintain::with([])->where('unique_code', $station_unique_code)->firstOrFail();

            $entire_instance_alarm_logs = EntireInstanceAlarmLog::with([
                'EntireInstance',
                'EntireInstance.Category',
                'EntireInstance.SubModel',
                'EntireInstance.SubModel.Parent',
                'EntireInstance.PartModel',
                'EntireInstance.PartModel.Parent',
            ])
                ->where('station_unique_code', $station->unique_code)
                ->orderByDesc('created_at')
                ->where('status', 'WARNING')
                ->get();

            return JsonResponseFacade::dict(['entire_instance_alarm_logs' => $entire_instance_alarm_logs]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

}
