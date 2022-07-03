<?php

namespace App\Http\Controllers\V2\Entire;

use App\Facades\BreakdownLogFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\Maintain;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstanceController extends Controller
{
    /**
     * 详情
     * @param string $identity_code
     * @return mixed
     */
    final public function show(string $identity_code)
    {
        try {
            $entire_instance = EntireInstance::with([
                "Category",
                "SubModel",
                "SubModel.Parent",
                "BreakdownLogs" => function ($BreakdownLogs) {
                    $BreakdownLogs->orderByDesc("id");
                },
                "BreakdownLogs.BreakdownTypes"
            ])
                ->where("identity_code", $identity_code)
                ->firstOrFail();

            // 器材日志和故障日志
            // $entire_instance_logs_with_month = [];
            // $breakdown_logs_with_month = [];
            $entire_instance_logs_with_month = @EntireInstanceLogFacade::GetLogsWithMonthByEntireInstanceIdentityCode($entire_instance->identity_code) ?: [];
            $breakdown_logs_with_month = @BreakdownLogFacade::GetLogsWithMonthByEntireInstance($entire_instance) ?: [];

            // $entire_instance_logs = DB::table('entire_instance_logs')
            //     ->whereNull('deleted_at')
            //     ->where('entire_instance_identity_code', $entire_instance->identity_code)
            //     ->orderByDesc('id')
            //     ->get();
            // $entire_instance_logs_with_month = [];
            // $breakdown_logs_with_month = [];
            // try {
            //     foreach ($entire_instance_logs as $entire_instance_log) {
            //         $__ = Carbon::createFromFormat('Y-m-d H:i:s', $entire_instance_log->created_at);
            //         $month = $__->copy()->format('Y-m');
            //         $entire_instance_logs_with_month[$month][] = [
            //             "date" => $__->copy()->format("Y-m-d"),
            //             "name" => $entire_instance_log->name,
            //             "description" => $entire_instance_log->description,
            //         ];
            //     }
            //     foreach ($entire_instance->BreakdownLogs as $breakdown_log) {
            //         $__ = Carbon::createFromFormat('Y-m-d H:i:s', $breakdown_log->created_at);
            //         $month = $__->copy()->format('Y-m');
            //         $breakdown_logs_with_month[$month][] = [
            //             "date" => $__->copy()->format("Y-m-d"),
            //             "name" => $breakdown_log->type,
            //             "explain" => ($breakdown_log->BreakdownTypes ? "<ol><li>" . $breakdown_log->BreakdownTypes->pluck("name")->implode("</li><li>") . "</li></ol>" : "") . "<br>$breakdown_log->explain",
            //             "workshop_name" => $breakdown_log->scene_workshop_name,
            //             "station_name" => $breakdown_log->maintain_station_name,
            //             "breakdown_types" => @$breakdown_log->BreakdownTypes ? $breakdown_log->BreakdownTypes->pluck("name") : [],
            //         ];
            //     }
            // } catch (Exception $e) {
            // }
            // krsort($entire_instance_logs_with_month);
            // krsort($breakdown_logs_with_month);

            // 送修
            $send_repairs = [];
            $entire_instance->WithSendRepairInstances->each(function ($datum) use (&$send_repairs) {
                $send_repairs [] = [
                    'date' => $datum->created_at->format("Y-m-d"),
                    'repair_report_url' => $datum->repair_report_url,
                    'remark' => $datum->repair_remark,
                ];
            });

            // 备品
            $standby_statistics = ["station" => [
                "title" => "当前车站",
                "unique_code" => "",
                "name" => "",
                "count" => 0,
                "distance" => 0,
            ], "nearStation" => [
                "title" => "临近车站",
                "unique_code" => "",
                "name" => "",
                "count" => 0,
                "distance" => 0,
            ], "workshop" => [
                "title" => "当前车间",
                "unique_code" => "",
                "name" => "",
                "count" => 0,
                "distance" => 0,
            ], "fixWorkshop" => [
                "title" => env("JWT_ISS"),
                "unique_code" => "",
                "name" => "",
                "count" => 0,
                "distance" => 0,
            ]];
            if ($entire_instance->prototype("status") == "INSTALLED" && $entire_instance->Station) {
                $station = $entire_instance->Station;
                $fix_workshop = Maintain::with([])->where("unique_code", env("ORGANIZATION_LOCATION_CODE"))->first();

                $getStatisticsDB = function (string $status = null) use ($entire_instance): array {
                    $db_Q = DB::table('entire_instances as ei')
                        ->selectRaw(implode(',', [
                            'count(ei.model_unique_code) as aggregate',
                            'ei.model_unique_code as unique_code',
                            'ei.model_name as name',
                        ]))
                        ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                        ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                        ->where('is_part', false)
                        ->whereNull('ei.deleted_at')
                        ->where('ei.status', '<>', 'SCRAP')
                        ->where('ei.status', $status)
                        ->where('ei.model_unique_code', $entire_instance->model_unique_code)
                        ->whereNull('sm.deleted_at')
                        ->where('sm.is_sub_model', true)
                        ->whereNull('em.deleted_at')
                        ->where('em.is_sub_model', false)
                        ->whereNull('c.deleted_at')
                        ->groupBy(['ei.model_unique_code', 'ei.model_name',]);

                    $db_S = DB::table('entire_instances as ei')
                        ->selectRaw(implode(',', [
                            'count(ei.model_unique_code) as aggregate',
                            'ei.model_unique_code as unique_code',
                            'ei.model_name as name',
                        ]))
                        ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                        ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                        ->where('is_part', false)
                        ->where('ei.deleted_at', null)
                        ->where('ei.status', '<>', 'SCRAP')
                        ->where('ei.status', $status)
                        ->where('ei.model_unique_code', $entire_instance->model_unique_code)
                        ->whereNull('pm.deleted_at')
                        ->where('pc.is_main', true)
                        ->whereNull('em.deleted_at')
                        ->where('em.is_sub_model', false)
                        ->whereNull('c.deleted_at')
                        ->groupBy(['ei.model_unique_code', 'ei.model_name',]);
                    return [$db_Q, $db_S];
                };

                $functions = [
                    'station' => function () use ($station, $getStatisticsDB) {
                        list($db_Q, $db_S) = $getStatisticsDB('INSTALLING');
                        $db_Q->where('s.unique_code', $station->unique_code);
                        $db_S->where('s.unique_code', $station->unique_code);
                        return ModelBuilderFacade::unionAll($db_Q, $db_S)->get();

                    },
                    'near_station' => function () use ($station, $getStatisticsDB) {
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

                        $near_station_statistics = [];
                        foreach ($distance_with_stations as $distance_with_station) {
                            list($db_Q, $db_S) = $getStatisticsDB('INSTALLING');
                            $db_Q->where('s.unique_code', $distance_with_station->to_unique_code);
                            $db_S->where('s.unique_code', $distance_with_station->to_unique_code);
                            $near_station_statistics[$distance_with_station->to_unique_code] = [
                                'count' => ModelBuilderFacade::unionAll($db_Q, $db_S)->get()->count(),
                                'unique_code' => $distance_with_station->to_unique_code,
                                'name' => $distance_with_station->name,
                                'distance' => $distance_with_station->distance,
                            ];
                        }

                        return $near_station_statistics;
                    },
                    'scene_workshop' => function () use ($station, $getStatisticsDB) {
                        list($db_Q, $db_S) = $getStatisticsDB('INSTALLING');
                        $db_Q->where('s.parent_unique_code', $station->parent_unique_code);
                        $db_S->where('s.parent_unique_code', $station->parent_unique_code);
                        return ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
                    },
                    'fix_workshop' => function () use ($station, $getStatisticsDB) {
                        list($db_Q, $db_S) = $getStatisticsDB('FIXED');
                        return ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
                    }
                ];

                $distances = [
                    'scene_workshop' => DB::table('distance')->where('from_unique_code', $station->unique_code)->where('to_unique_code', $station->parent_unique_code)->first(['distance'])->distance ?? 0,
                    'fix_workshop' => DB::table('distance')->where('from_unique_code', $station->unique_code)->where('to_unique_code', env('CURRENT_WORKSHOP_UNIQUE_CODE'))->first(['distance'])->distance ?? 0
                ];

                $standby_statistics['station'] = [
                    "title" => "当前车站",
                    "unique_code" => $station->unique_code,
                    "name" => $station->name,
                    "count" => count($functions['station']()),
                    "distance" => 0,
                ];
                $standby_statistics['nearStation'] = $functions['near_station']();
                $standby_statistics['workshop'] = [
                    "title" => "当前车间",
                    "unique_code" => $station->Parent->unique_code,
                    "name" => $station->Parent->name,
                    "count" => count($functions['scene_workshop']()),
                    "distance" => $distances["scene_workshop"],
                ];
                $standby_statistics['fixWorkshop'] = [
                    "title" => @$fix_workshop->name ?: "",
                    "unique_code" => env("ORGANIZATION_LOCATION_CODE"),
                    "name" => @$fix_workshop->name ?: "",
                    "count" => count($functions['fix_workshop']()),
                    "distance" => $distances["fix_workshop"],
                ];
                // $standby_statistics['near_station_category_count'] = 0;
                // foreach ($standby_statistics['nearStation'] as $near_station) {
                //     $standby_statistics['near_station_category_count'] += count($near_station['statistics']) > 0 ? count($near_station['statistics']) : 1;
                // }
            }

            $return = [
                "info" => [
                    "unique_code" => $entire_instance->identity_code,
                    "unique_code8" => Str::substr($entire_instance->identity_code, 8),
                    "category_name" => @$entire_instance->full_kind->category_name ?: "",
                    "entire_model_name" => @$entire_instance->full_kind->entire_model_name ?: "",
                    "sub_model_name" => @$entire_instance->full_kind->sub_model_name ?: "",
                    "equipment_category_name" => "",
                    "equipment_entire_model_name" => "",
                    "equipment_sub_model_name" => "",
                    "state" => @$entire_instance->status ?: "",
                    "location_name" => @$entire_instance->storehouse_location["name"] ?: "",
                    "location_image" => "",
                    "install_location_name" => @$entire_instance->use_position_name ?: "",
                    "workshop_name" => @$entire_instance->maintain_workshop_name ?: "",
                    "station_name" => @$entire_instance->maintain_station_name ?: "",
                    "factory_name" => @$entire_instance->factory_name ?: "",
                    "factory_number" => @$entire_instance->factory_device_code ?: "",
                    "ex_factory_at" => @$entire_instance->made_at ? Carbon::parse($entire_instance->made_at)->format("Y-m-d") : null,
                    "last_installed_at" => @$entire_instance->last_installed_at ?: null,
                    "take_stock_at" => @$entire_instance->last_take_stock_at ?: null,
                    "year_fixed_at" => @$entire_instance->fixed_at ?: null,
                    "due_date_at" => @$entire_instance->scarping_at ? Carbon::parse($entire_instance->scarping_at)->format("Y-m-d") : null,
                    "version_number" => "",
                    "service_life" => 0,
                ],
                "material_logs" => @$entire_instance_logs_with_month ?: ["" => [],],
                "breakdown_logs" => @$breakdown_logs_with_month ?: ["" => [],],
                "send_repairs" => $send_repairs,
                "standbies" => $standby_statistics,
            ];

            return JsonResponseFacade::dict($return);
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
