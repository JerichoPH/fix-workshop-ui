<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\QueryBuilderFacade;
use App\Model\WarehouseReportDisplayBoard;
use App\Model\WorkArea;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

class WarehouseReportDisplayBoardController extends Controller
{
    final public function show(string $work_area_unique_code)
    {
        try {
            $work_area = WorkArea::with([])->where('unique_code', $work_area_unique_code)->firstOrFail();

            $cycle_fix_plan_statistics = DB::table('entire_instances as ei')
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code)  as aggregate',
                    'ei.model_unique_code         as model_unique_code',
                    'ei.model_name                as model_name',
                    'c.unique_code                as category_unique_code',
                    'c.name                       as category_name',
                    's.unique_code                as station_unique_code',
                    's.name                       as station_name',
                ]))
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->whereNull('ei.deleted_at')
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                ->whereNull('c.deleted_at')
                ->where('work_area_unique_code', $work_area_unique_code)
                ->whereNotNull('next_fixing_day')
                ->whereBetween('next_fixing_day', [now()->addMonth()->startOfMonth(), now()->addMonth()->endOfMonth(),])
                ->groupBy(['ei.model_unique_code', 'ei.model_name', 'c.unique_code', 'c.name', 's.unique_code', 's.name',])
                ->get();

            return view('WarehouseReportDisplayBoard.show', [
                'work_area' => $work_area,
                'cycle_fix_plan_statistics' => $cycle_fix_plan_statistics,
                'cycle_fix_plan_statistics_count' => $cycle_fix_plan_statistics->count(),
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 周期修统计数据
     * @param string $work_area_unique_code
     */
    final public function getCycleFixStatistics(string $work_area_unique_code)
    {
        try {
            $work_area = WorkArea::with([])->where('unique_code', $work_area_unique_code)->first();
            $times = [now()->startOfYear(), now()->endOfYear(),];

            $generate_mission_statistics = function () use ($times, $work_area_unique_code) {
                $mission_db_S = DB::table('repair_base_plan_out_cycle_fix_entire_instances as rbpocfei')
                    ->selectRaw(implode(',', [
                        'count(pm.unique_code)        as aggregate',
                        'pm.unique_code               as model_unique_code',
                        'pm.name                      as model_name',
                        'em.unique_code               as entire_model_unique_code',
                        'em.name                      as entire_model_name',
                        'c.unique_code                as category_unique_code',
                        'c.name                       as category_name',
                        's.unique_code                as station_unique_code',
                        's.name                       as station_name',
                    ]))
                    ->join(DB::raw('repair_base_plan_out_cycle_fix_bills rbpocfb'), 'rbpocfb.id', '=', 'rbpocfei.bill_id')
                    ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'rbpocfei.old')
                    ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->whereNull('ei.deleted_at')
                    ->whereNull('pm.deleted_at')
                    ->whereNull('pc.deleted_at')
                    ->where('pc.is_main', true)
                    ->whereNull('em.deleted_at')
                    ->where('em.is_sub_model', false)
                    ->whereNull('c.deleted_at')
                    ->where('ei.work_area_unique_code', $work_area_unique_code)
                    ->whereBetween('rbpocfb.created_at', $times)
                    ->groupBy(['pm.unique_code', 'pm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 's.unique_code', 's.name',]);

                $mission_db_Q = DB::table('repair_base_plan_out_cycle_fix_entire_instances as rbpocfei')
                    ->selectRaw(implode(',', [
                        'count(sm.unique_code)        as aggregate',
                        'sm.unique_code               as model_unique_code',
                        'sm.name                      as model_name',
                        'em.unique_code               as entire_model_unique_code',
                        'em.name                      as entire_model_name',
                        'c.unique_code                as category_unique_code',
                        'c.name                       as category_name',
                        's.unique_code                as station_unique_code',
                        's.name                       as station_name',
                    ]))
                    ->join(DB::raw('repair_base_plan_out_cycle_fix_bills rbpocfb'), 'rbpocfb.id', '=', 'rbpocfei.bill_id')
                    ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'rbpocfei.old')
                    ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->whereNull('ei.deleted_at')
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                    ->whereNull('sm.deleted_at')
                    ->where('sm.is_sub_model', true)
                    ->whereNull('em.deleted_at')
                    ->where('em.is_sub_model', false)
                    ->whereNull('c.deleted_at')
                    ->where('ei.work_area_unique_code', $work_area_unique_code)
                    ->whereBetween('rbpocfb.created_at', $times)
                    ->groupBy(['sm.unique_code', 'sm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 's.unique_code', 's.name',]);

                return QueryBuilderFacade::unionAll($mission_db_Q, $mission_db_S)->get();
            };
            $mission_statistics = $generate_mission_statistics();

            $generate_fixed_statistics = function () use ($times, $work_area_unique_code) {
                $fixed_db_S = DB::table('repair_base_plan_out_cycle_fix_entire_instances as rbpocfei')
                    ->selectRaw(implode(',', [
                        'count(pm.unique_code)        as aggregate',
                        'pm.unique_code               as model_unique_code',
                        'pm.name                      as model_name',
                        'em.unique_code               as entire_model_unique_code',
                        'em.name                      as entire_model_name',
                        'c.unique_code                as category_unique_code',
                        'c.name                       as category_name',
                        's.unique_code                as station_unique_code',
                        's.name                       as station_name',
                    ]))
                    ->join(DB::raw('repair_base_plan_out_cycle_fix_bills rbpocfb'), 'rbpocfb.id', '=', 'rbpocfei.bill_id')
                    ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'rbpocfei.new')
                    ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->whereNull('ei.deleted_at')
                    ->whereNull('pm.deleted_at')
                    ->whereNull('pc.deleted_at')
                    ->where('pc.is_main', true)
                    ->whereNull('em.deleted_at')
                    ->where('em.is_sub_model', false)
                    ->whereNull('c.deleted_at')
                    ->where('ei.work_area_unique_code', $work_area_unique_code)
                    ->whereBetween('rbpocfb.created_at', $times)
                    ->where('rbpocfei.out_warehouse_sn', '<>', '')
                    ->groupBy(['pm.unique_code', 'pm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 's.unique_code', 's.name',]);

                $fixed_db_Q = DB::table('repair_base_plan_out_cycle_fix_entire_instances as rbpocfei')
                    ->selectRaw(implode(',', [
                        'count(sm.unique_code)        as aggregate',
                        'sm.unique_code               as model_unique_code',
                        'sm.name                      as model_name',
                        'em.unique_code               as entire_model_unique_code',
                        'em.name                      as entire_model_name',
                        'c.unique_code                as category_unique_code',
                        'c.name                       as category_name',
                        's.unique_code                as station_unique_code',
                        's.name                       as station_name',
                    ]))
                    ->join(DB::raw('repair_base_plan_out_cycle_fix_bills rbpocfb'), 'rbpocfb.id', '=', 'rbpocfei.bill_id')
                    ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'rbpocfei.new')
                    ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->whereNull('ei.deleted_at')
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                    ->whereNull('sm.deleted_at')
                    ->where('sm.is_sub_model', true)
                    ->whereNull('em.deleted_at')
                    ->where('em.is_sub_model', false)
                    ->whereNull('c.deleted_at')
                    ->where('ei.work_area_unique_code', $work_area_unique_code)
                    ->whereBetween('rbpocfb.created_at', $times)
                    ->where('rbpocfei.out_warehouse_sn', '<>', '')
                    ->groupBy(['sm.unique_code', 'sm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 's.unique_code', 's.name',]);

                return QueryBuilderFacade::unionAll($fixed_db_Q, $fixed_db_S)->get();
            };
            $fixed_statistics = $generate_fixed_statistics();

            return JsonResponseFacade::dict([
                'mission_statistics' => $mission_statistics,
                'fixed_statistics' => $fixed_statistics,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 周期修统计展示
     * @param string $work_area_unique_code
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function showCycleFix(string $work_area_unique_code)
    {
        try {
            $work_area = WorkArea::with([])->where('unique_code', $work_area_unique_code)->first();
            $times = [now()->startOfYear(), now()->endOfYear(),];

            $generate_plan_statistics = function () use ($times, $work_area_unique_code) {
                $plan_db_S = DB::table('entire_instances as ei')
                    ->selectRaw(implode(',', [
                        'count(pm.unique_code)        as aggregate',
                        'pm.unique_code               as model_unique_code',
                        'pm.name                      as model_name',
                        'em.unique_code               as entire_model_unique_code',
                        'em.name                      as entire_model_name',
                        'c.unique_code                as category_unique_code',
                        'c.name                       as category_name',
                        's.unique_code                as station_unique_code',
                        's.name                       as station_name',
                    ]))
                    ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->whereNull('ei.deleted_at')
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                    ->whereNull('pm.deleted_at')
                    ->whereNull('pc.deleted_at')
                    ->where('pc.is_main', true)
                    ->whereNull('em.deleted_at')
                    ->where('em.is_sub_model', false)
                    ->whereNull('c.deleted_at')
                    ->where('ei.work_area_unique_code', $work_area_unique_code)
                    ->whereNotNull('ei.next_fixing_day')
                    ->whereBetween('ei.next_fixing_day', $times)
                    ->groupBy(['pm.unique_code', 'pm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 's.unique_code', 's.name',]);
                $plan_db_Q = DB::table('entire_instances as ei')
                    ->selectRaw(implode(',', [
                        'count(sm.unique_code)        as aggregate',
                        'sm.unique_code               as model_unique_code',
                        'sm.name                      as model_name',
                        'em.unique_code               as entire_model_unique_code',
                        'em.name                      as entire_model_name',
                        'c.unique_code                as category_unique_code',
                        'c.name                       as category_name',
                        's.unique_code                as station_unique_code',
                        's.name                       as station_name',
                    ]))
                    ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->whereNull('ei.deleted_at')
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING',])
                    ->whereNull('sm.deleted_at')
                    ->where('sm.is_sub_model', true)
                    ->whereNull('em.deleted_at')
                    ->where('em.is_sub_model', false)
                    ->whereNull('c.deleted_at')
                    ->where('ei.work_area_unique_code', $work_area_unique_code)
                    ->whereNotNull('ei.next_fixing_day')
                    ->whereBetween('ei.next_fixing_day', $times)
                    ->groupBy(['sm.unique_code', 'sm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 's.unique_code', 's.name',]);
                return QueryBuilderFacade::unionAll($plan_db_S, $plan_db_Q)->get();
            };
            $plan_statistics = $generate_plan_statistics();

            return view('WarehouseReportDisplayBoard.showCycleFix', [
                'work_area' => $work_area,
                'plan_statistics_as_json' => $plan_statistics->toJson(),
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * get warehouse in statistics
     * @param string $work_area_unique_code
     * @param string $time_type
     * @return mixed
     */
    final public function getWarehouseStatistics(string $work_area_unique_code, string $time_type = '')
    {
        // $filename = storage_path('warehouseReportDisplayBoard.statisticTime');
        // if (!is_file($filename)) return JsonResponseFacade::errorEmpty('日志文件不存在');

        $statistics = [
            'in' => ['today' => [], 'week' => [], 'month' => []],
            'out' => ['today' => [], 'week' => [], 'month' => []],
        ];

        $statistics_time = [
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
        ];

        $statistics_db = function (array $times, string $direction = '') use ($work_area_unique_code) {
            $db_Q = DB::table('warehouse_report_entire_instances as wrei')
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'sm.unique_code as model_unique_code',
                    'sm.name as model_name',
                    's.unique_code as station_unique_code',
                    's.name as station_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                ]))
                ->join(DB::raw('maintains s'), 's.name', '=', 'wrei.maintain_station_name')
                ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'wrei.entire_instance_identity_code')
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->join(DB::raw('warehouse_reports wr'), 'wr.serial_number', '=', 'wrei.warehouse_report_serial_number')
                ->whereNull('wrei.deleted_at')
                ->whereNull('s.deleted_at')
                ->where('s.type', 'STATION')
                ->whereNull('sc.deleted_at')
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->whereNull('ei.deleted_at')
                ->whereNull('sm.deleted_at')
                ->where('sm.is_sub_model', true)
                ->whereNull('em.deleted_at')
                ->where('em.is_sub_model', false)
                ->whereNull('c.deleted_at')
                ->whereNull('wr.deleted_at')
                ->where('ei.work_area_unique_code', $work_area_unique_code)
                ->where('wr.direction', $direction)
                ->whereBetween('wr.created_at', $times)
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sm.unique_code', 'sm.name', 's.unique_code', 's.name', 'sc.unique_code', 'sc.name',]);

            $db_S = DB::table('warehouse_report_entire_instances as wrei')
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'pm.unique_code as model_unique_code',
                    'pm.name as model_name',
                    's.unique_code as station_unique_code',
                    's.name as station_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                ]))
                ->join(DB::raw('maintains s'), 's.name', '=', 'wrei.maintain_station_name')
                ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'wrei.entire_instance_identity_code')
                ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->join(DB::raw('warehouse_reports wr'), 'wr.serial_number', '=', 'wrei.warehouse_report_serial_number')
                ->whereNull('wrei.deleted_at')
                ->whereNull('s.deleted_at')
                ->where('s.type', 'STATION')
                ->whereNull('sc.deleted_at')
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->whereNull('ei.deleted_at')
                ->whereNull('pm.deleted_at')
                ->whereNull('pc.deleted_at')
                ->where('pc.is_main', true)
                ->whereNull('em.deleted_at')
                ->where('em.is_sub_model', false)
                ->whereNull('c.deleted_at')
                ->whereNull('wr.deleted_at')
                ->where('ei.work_area_unique_code', $work_area_unique_code)
                ->where('wr.direction', $direction)
                ->whereBetween('wr.created_at', $times)
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'pm.unique_code', 'pm.name', 's.unique_code', 's.name', 'sc.unique_code', 'sc.name',]);

            return [$db_Q, $db_S];
        };

        // generate warehouse in/out statistic
        // today
        list($db_Q, $db_S) = $statistics_db($statistics_time['today'], 'IN');
        $statistics['in']['today'] = ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
        list($db_Q, $db_S) = $statistics_db($statistics_time['today'], 'OUT');
        $statistics['out']['today'] = ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
        // week
        list($db_Q, $db_S) = $statistics_db($statistics_time['week'], 'IN');
        $statistics['in']['week'] = ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
        list($db_Q, $db_S) = $statistics_db($statistics_time['week'], 'OUT');
        $statistics['out']['week'] = ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
        // month
        list($db_Q, $db_S) = $statistics_db($statistics_time['month'], 'IN');
        $statistics['in']['month'] = ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
        list($db_Q, $db_S) = $statistics_db($statistics_time['month'], 'OUT');
        $statistics['out']['month'] = ModelBuilderFacade::unionAll($db_Q, $db_S)->get();


        if ($time_type) {
            return JsonResponseFacade::dict([
                'statistics' => [
                    'in' => $statistics['in'][$time_type],
                    'out' => $statistics['out'][$time_type],
                ],
            ]);
        } else {
            return JsonResponseFacade::dict(['statistics' => $statistics,]);
        }
    }

    /**
     * 出所统计页面
     * @param string $work_area_unique_code
     * @param string $time_type
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function showWarehouseReport(string $work_area_unique_code, string $time_type = 'today')
    {
        try {
            $time_type_names = [
                'today' => [
                    'name' => '今日',
                    'time' => now()->format('Y-m-d'),
                ],
                'week' => [
                    'name' => '本周',
                    'time' => [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d'),],
                ],
                'month' => [
                    'name' => '本月',
                    'time' => [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d'),],
                ],
            ];

            $work_area = WorkArea::with([])->where('unique_code', $work_area_unique_code)->firstOrFail();
            return view('WarehouseReportDisplayBoard.showWarehouseReport', [
                'work_area' => $work_area,
                'time_type_name' => $time_type_names[$time_type],
                'time_type' => $time_type,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
