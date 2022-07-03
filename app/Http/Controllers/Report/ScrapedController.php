<?php

namespace App\Http\Controllers\Report;

use App\Facades\CommonFacade;
use App\Facades\KindsFacade;
use App\Facades\OrganizationFacade;
use App\Facades\QueryBuilderFacade;
use App\Facades\QueryConditionFacade;
use App\Facades\ModelBuilderFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Serializers\EntireInstanceSerializer;
use Exception;
use Jericho\Excel\ExcelWriteHelper;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Excel\ExcelReadHelper;

class ScrapedController extends Controller
{
    /**
     * 超期使用
     * @return Factory|RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    final public function scraped()
    {
        try {
            $db_device_statistics_S = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name',]);
            $db_device_statistics_Q = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name',]);
            $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

            $db_scraped_statistics_S = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                ]))
                ->where('scarping_at', '<', now())
                ->groupBy(['c.unique_code', 'c.name',]);
            $db_scraped_statistics_Q = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                ]))
                ->where('scarping_at', '<', now())
                ->groupBy(['c.unique_code', 'c.name',]);
            $scraped_statistics = QueryBuilderFacade::unionAll($db_scraped_statistics_S, $db_scraped_statistics_Q)->get();

            return view('Report.Scraped.scraped', [
                'device_statistics_as_json' => $device_statistics,
                'scraped_statistics_as_json' => $scraped_statistics,
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 超期使用（指定种类）
     * @param string $category_unique_code
     * @return Factory|\Illuminate\Foundation\Application|View
     */
    final public function scrapedWithCategory(string $category_unique_code)
    {
        try {
            $db_device_statistics_S = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code        as category_unique_code',
                    'c.name               as category_name',
                    'em.unique_code       as entire_model_unique_code',
                    'em.name              as entire_model_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name',]);
            $db_device_statistics_Q = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code        as category_unique_code',
                    'c.name               as category_name',
                    'em.unique_code       as entire_model_unique_code',
                    'em.name              as entire_model_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name',]);
            $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

            $db_scraped_statistics_S = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code        as category_unique_code',
                    'c.name               as category_name',
                    'em.unique_code       as entire_model_unique_code',
                    'em.name              as entire_model_name',
                ]))
                ->where('scarping_at', '<', now())
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name',]);
            $db_scraped_statistics_Q = EntireInstanceSerializer::INS()
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code        as category_unique_code',
                    'c.name               as category_name',
                    'em.unique_code       as entire_model_unique_code',
                    'em.name              as entire_model_name',
                ]))
                ->where('scarping_at', '<', now())
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name',]);
            $scraped_statistics = QueryBuilderFacade::unionAll($db_scraped_statistics_S, $db_scraped_statistics_Q)->get();

            return view('Report.Scraped.scrapedWithCategory', [
                'device_statistics_as_json' => $device_statistics,
                'scraped_statistics_as_json' => $scraped_statistics,
            ]);
        } catch (Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 超期使用（指定类型）
     * @param string $entireModelUniqueCode
     * @return Factory|View
     */
    final public function scrapedWithEntireModel(string $entireModelUniqueCode)
    {
        $db_device_statistics_S = EntireInstanceSerializer::INS()
            ->GenerateQueryRelationShipS()
            ->selectRaw(implode(',', [
                'count(c.unique_code) as aggregate',
                'c.unique_code        as category_unique_code',
                'c.name               as category_name',
                'em.unique_code       as entire_model_unique_code',
                'em.name              as entire_model_name',
                'ei.model_unique_code as model_unique_code',
                'ei.model_name        as model_name',
            ]))
            ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $db_device_statistics_Q = EntireInstanceSerializer::INS()
            ->GenerateQueryRelationShipQ()
            ->selectRaw(implode(',', [
                'count(c.unique_code) as aggregate',
                'c.unique_code        as category_unique_code',
                'c.name               as category_name',
                'em.unique_code       as entire_model_unique_code',
                'em.name              as entire_model_name',
                'ei.model_unique_code as model_unique_code',
                'ei.model_name        as model_name',
            ]))
            ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

        $db_scraped_statistics_S = EntireInstanceSerializer::ins()
            ->GenerateQueryRelationShipS()
            ->selectRaw(implode(',', [
                'count(c.unique_code) as aggregate',
                'c.unique_code        as category_unique_code',
                'c.name               as category_name',
                'em.unique_code       as entire_model_unique_code',
                'em.name              as entire_model_name',
                'ei.model_unique_code as model_unique_code',
                'ei.model_name        as model_name',
            ]))
            ->where('scarping_at', '<', now())
            ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $db_scraped_statistics_Q = EntireInstanceSerializer::INS()
            ->GenerateQueryRelationShipQ()
            ->selectRaw(implode(',', [
                'count(c.unique_code) as aggregate',
                'c.unique_code        as category_unique_code',
                'c.name               as category_name',
                'em.unique_code       as entire_model_unique_code',
                'em.name              as entire_model_name',
                'ei.model_unique_code as model_unique_code',
                'ei.model_name        as model_name',
            ]))
            ->where('scarping_at', '<', now())
            ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name',]);
        $scraped_statistics = QueryBuilderFacade::unionAll($db_scraped_statistics_S, $db_scraped_statistics_Q)->get();

        return view('Report.Scraped.scrapedWithEntireModel', [
            'device_statistics_as_json' => $device_statistics,
            'scraped_statistics_as_json' => $scraped_statistics,
        ]);
    }

    /**
     * 超期使用（指定型号）
     * @param string $modelUniqueCode
     * @return Factory|RedirectResponse|View
     */
    final public function scrapedWithSubModel(string $modelUniqueCode)
    {
        try {
            $now = Carbon::now()->format('Y-m-d');
            $root_dir = storage_path('app/scraped');
            if (!is_dir($root_dir)) return back()->with('danger', '数据不存在');
            list($dateMadeAtOrigin, $dateMadeAtFinish) = explode('~', request('date_made_at', "{$now}~{$now}"));
            list($dateCreatedAtOrigin, $dateCreatedAtFinish) = explode('~', request('date_created_at', "{$now}~{$now}"));
            list($dateScarpingAtOrigin, $dateScarpingAtFinish) = explode('~', request('date_scarping_at', "{$now}~{$now}"));

            $statuses = collect(EntireInstance::$STATUSES);
            $factories = @\App\Model\Factory::with([])->get() ?: collect([]);
            $categories = KindsFacade::getCategories([],function($db){
                return $db->where("is_show",true);
            });
            $entire_models = KindsFacade::getEntireModelsByCategory();
            $models = KindsFacade::getModelsByEntireModel();
            $scene_workshops = OrganizationFacade::getSceneWorkshops([], function ($db) {
                return $db->where("sc.is_show", true);
            });
            $stations = OrganizationFacade::getStationsBySceneWorkshop([], function ($db) {
                return $db->where("s.is_show", true);
            });

            $partSql = DB::table('entire_instances as ei')
                ->select([
                    'ei.identity_code',
                    'ei.serial_number',
                    'ei.category_name',
                    'ei.factory_name',
                    'ei.status',
                    'ei.scarping_at',
                    'ei.maintain_station_name',
                    'ei.maintain_location_code',
                    'ei.open_direction',
                    'ei.to_direction',
                    'ei.crossroad_number',
                    'ei.line_name',
                    'ei.said_rod',
                    'ei.model_unique_code',
                    'ei.model_name',
                ])
                ->leftJoin(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                ->leftJoin(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                ->leftJoin(DB::raw('entire_models em'), 'pm.entire_model_unique_code', '=', 'em.unique_code')
                ->leftJoin(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->leftJoin(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                ->leftJoin(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->leftJoin(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->where('ei.scarping_at', '<', now())
                ->whereNull('ei.deleted_at')
                ->where('ei.status', '<>', 'SCRAP')
                ->whereNull('pm.deleted_at')
                ->whereNull('pc.deleted_at')
                ->where('pc.is_main', true)
                ->whereNull('em.deleted_at')
                ->where('em.is_sub_model', false)
                ->whereNull('c.deleted_at')
                ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                ->whereNull('sc.deleted_at')
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->whereNull('s.deleted_at')
                ->where('s.type', 'STATION')
                ->when(
                    request('status'),
                    function ($query, $status) {
                        return $query->where("ei.status", $status);
                    }
                )
                ->when(
                    request('factory_unique_code'),
                    function ($query, $factory_unique_code) {
                        return $query->where('f.unique_code', $factory_unique_code);
                    }
                )
                ->when(
                    request('scene_workshop_unique_code'),
                    function ($query, $scene_workshop_unique_code) {
                        $query->where('sc.unique_code', $scene_workshop_unique_code)
                            ->where('s.parent_unique_code', $scene_workshop_unique_code);
                    }
                )
                ->when(
                    request('station_unique_code'),
                    function ($query, $station_unique_code) {
                        $query->where('s.unique_code', $station_unique_code);
                    }
                )
                ->when(request("use_made_at") == "1", function ($q) {
                    return $q->whereBetween("ei.made_at", explode("~", request("date_made_at")));
                })
                ->when(request("use_created_at") == "1", function ($q) {
                    return $q->whereBetween("ei.created_at", explode("~", request("date_created_at")));
                })
                ->when(request("use_next_fixing_day") == "1", function ($q) {
                    return $q->whereBetween("ei.next_fixing_day", explode("~", request("date_next_fixing_day")));
                })
                ->when($modelUniqueCode, function ($query, $model_unique_code) {
                    $query->where('ei.model_unique_code', $model_unique_code);
                })
                ->orderBy('ei.scarping_at');

            $entireSql = DB::table('entire_instances as ei')
                ->select([
                    'ei.identity_code',
                    'ei.serial_number',
                    'ei.category_name',
                    'ei.factory_name',
                    'ei.status',
                    'ei.scarping_at',
                    'ei.maintain_station_name',
                    'ei.maintain_location_code',
                    'ei.open_direction',
                    'ei.to_direction',
                    'ei.crossroad_number',
                    'ei.line_name',
                    'ei.said_rod',
                    'ei.model_unique_code',
                    'ei.model_name',
                ])
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.entire_model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->join(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->where('ei.scarping_at', '<', now())
                ->whereNull('ei.deleted_at')
                ->where('ei.status', '<>', 'SCRAP')
                ->whereNull('sm.deleted_at')
                ->where('sm.is_sub_model', true)
                ->whereNull('em.deleted_at')
                ->where('em.is_sub_model', false)
                ->whereNull('c.deleted_at')
                ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                ->whereNull('sc.deleted_at')
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->whereNull('s.deleted_at')
                ->where('s.type', 'STATION')
                ->when(
                    request('status'),
                    function ($query, $status) {
                        $query->where('ei.status', $status);
                    }
                )
                ->when(
                    request('factory_unique_code'),
                    function ($query, $factory_unique_code) {
                        $query->where('f.unique_code', $factory_unique_code);
                    }
                )
                ->when(
                    request('scene_workshop_unique_code'),
                    function ($query, $scene_workshop_unique_code) {
                        $query->where('sc.unique_code', $scene_workshop_unique_code)
                            ->where('s.parent_unique_code', $scene_workshop_unique_code);
                    }
                )
                ->when(
                    request('station_unique_code'),
                    function ($query, $station_unique_code) {
                        $query->where('s.unique_code', $station_unique_code);
                    }
                )
                ->when(
                    $modelUniqueCode,
                    function ($query, $model_unique_code) {
                        $query->where('ei.model_unique_code', $model_unique_code);
                    }
                )
                ->when(request("use_made_at") == "1", function ($q) {
                    return $q->whereBetween("ei.made_at", explode("~", request("date_made_at")));
                })
                ->when(request("use_created_at") == "1", function ($q) {
                    return $q->whereBetween("ei.created_at", explode("~", request("date_created_at")));
                })
                ->when(request("use_next_fixing_day") == "1", function ($q) {
                    return $q->whereBetween("ei.next_fixing_day", explode("~", request("date_next_fixing_day")));
                })
                ->orderBy('ei.scarping_at');

            if (request('download') == '1') {
                # 下载Excel
            } else {
                $entireInstances = QueryBuilderFacade::unionAll($partSql, $entireSql)->paginate();
            }

            return view('Report.Scraped.scrapedWithSub', [
                'entireInstances' => $entireInstances,
                'dateMadeAtOrigin' => $dateMadeAtOrigin,
                'dateMadeAtFinish' => $dateMadeAtFinish,
                'dateCreatedAtOrigin' => $dateCreatedAtOrigin,
                'dateCreatedAtFinish' => $dateCreatedAtFinish,
                'dateScarpingAtOrigin' => $dateScarpingAtOrigin,
                'dateScarpingAtFinish' => $dateScarpingAtFinish,
                'statuses_as_json' => $statuses->toJson(),
                'factories_as_json' => $factories->toJson(),
                'categories_as_json' => $categories->toJson(),
                'entire_models_as_json' => $entire_models->toJson(),
                'models_as_json' => $models->toJson(),
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'statuses' => EntireInstance::$STATUSES,
            ]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '暂无数据');
        }
    }
}
