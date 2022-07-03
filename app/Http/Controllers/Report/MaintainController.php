<?php

namespace App\Http\Controllers\Report;

use App\Facades\CommonFacade;
use App\Facades\KindsFacade;
use App\Facades\OrganizationFacade;
use App\Facades\QueryBuilderFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\Install\InstallRoom;
use App\Model\Maintain;
use App\Serializers\EntireInstanceSerializer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\TextHelper;

class MaintainController extends Controller
{
    final public function index()
    {
        $scene_workshops = OrganizationFacade::getSceneWorkshops();

        return view('Report.Maintain.index', [
            'sceneWorkshops' => $scene_workshops,
            'sceneWorkshopsAsJson' => $scene_workshops->toJson(),
        ]);
    }

    /**
     * 现场车间下车站统计
     * @param string $scene_workshop_unique_code
     * @return Factory|RedirectResponse|View
     */
    final public function getStationsWithSceneWorkshop(string $scene_workshop_unique_code)
    {
        $scene_workshop = Maintain::with([])->where('unique_code', $scene_workshop_unique_code)->first();
        $stations = OrganizationFacade::getStationsBySceneWorkshop([], function ($db) use ($scene_workshop_unique_code) {
            return $db->where("s.is_show", true)->where("s.parent_unique_code", $scene_workshop_unique_code);
        })->get($scene_workshop_unique_code);

        $statistics = DB::table('entire_instances as ei')
            ->selectRaw(implode(',', [
                'count(ei.status) as aggregate',
                'ei.status as status',
                's.unique_code as station_unique_code',
                's.name as station_name',
            ]))
            ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
            ->where('s.parent_unique_code', $scene_workshop_unique_code)
            ->where("s.is_show", true)
            ->groupBy(['ei.status', 's.unique_code', 's.name',])
            ->get();

        $db_statistic_S = EntireInstanceSerializer::INS([
            EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
            EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            EntireInstanceSerializer::$IS_PART => false,
        ])
            ->GenerateQueryRelationShipS()
            ->selectRaw(implode(',', [
                'count(ei.status) as aggregate',
                'ei.status as status',
                's.unique_code as station_unique_code',
                's.name as station_name',
            ]))
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
            ->where('s.parent_unique_code', $scene_workshop_unique_code)
            ->where("s.is_show", true)
            ->groupBy(['ei.status', 's.unique_code', 's.name',]);
        $db_statistic_Q = EntireInstanceSerializer::INS([
            EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
            EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            EntireInstanceSerializer::$IS_PART => false,
        ])
            ->GenerateQueryRelationShipQ()
            ->selectRaw(implode(',', [
                'count(ei.status) as aggregate',
                'ei.status as status',
                's.unique_code as station_unique_code',
                's.name as station_name',
            ]))
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
            ->where('s.parent_unique_code', $scene_workshop_unique_code)
            ->where("s.is_show", true)
            ->groupBy(['ei.status', 's.unique_code', 's.name',]);
        $statistics = QueryBuilderFacade::unionAll($db_statistic_S, $db_statistic_Q)->get();

        return view('Report.Maintain.stationsWithSceneWorkshop', [
            'scene_workshop' => $scene_workshop,
            'stations' => $stations,
            'statistics_as_json' => $statistics->toJson(),
        ]);
    }

    /**
     * 获取台账设备列表
     */
    final public function getMaintainEntireInstances()
    {
        try {
            $station = Maintain::with(['Parent'])->where('unique_code', request('station_unique_code'))->first();
            if (!$station) return back()->with('danger', '没有找到车站');
            if (!$station->Parent) return back()->with('danger', '车站数据错误，没有找到所属现场车间');


            $getFirstCategoryUniqueCode = function () {
                $db_S = EntireInstanceSerializer::INS([
                    EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                    EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                    EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
                ])
                    ->GenerateQueryRelationShipS()
                    ->selectRaw(implode(',', ['c.unique_code as category_unique_code',]))
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                    ->orderBy('c.unique_code')
                    ->groupBy(['c.unique_code']);
                $db_Q = EntireInstanceSerializer::INS([
                    EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                    EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                    EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
                ])
                    ->GenerateQueryRelationShipQ()
                    ->selectRaw(implode(',', ['c.unique_code as category_unique_code',]))
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                    ->orderBy('c.unique_code')
                    ->groupBy(['c.unique_code']);
                QueryBuilderFacade::unionAll($db_S, $db_Q)->first();
            };
            $current_category_unique_code = request('category_unique_code', $getFirstCategoryUniqueCode());

            $statuses = collect(EntireInstance::$STATUSES);
            $factories = @\App\Model\Factory::with([])->get() ?: collect([]);
            $categories = KindsFacade::getCategories([], function ($db) {
                return $db->where("is_show", true);
            });
            $entire_models = KindsFacade::getEntireModelsByCategory();
            $models = KindsFacade::getModelsByEntireModel();
            $scene_workshops = OrganizationFacade::getSceneWorkshops([], function ($db) {
                return $db->where("sc.is_show", true);
            });
            $lines = OrganizationFacade::getLines([], function ($db) {
                return $db->where("is_show", true);
            });
            $stations = OrganizationFacade::getStationsBySceneWorkshop([], function ($db) {
                return $db->where("s.is_show", true);
            });
            $install_rooms = InstallRoom::with([])->where('station_unique_code', $station->unique_code)->get();

            $db_statistics_S = EntireInstanceSerializer::ins([
                EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
            ])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(ei.status) as aggregate',
                    'ei.status as status',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'ei.model_unique_code as model_unique_code',
                    'ei.model_name as model_name',
                ]))
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name', 'ei.status',])
                ->when(
                    $current_category_unique_code,
                    function ($query, $current_category_unique_code) {
                        $query->where('c.unique_code', $current_category_unique_code);
                    }
                );
            $db_statistics_Q = EntireInstanceSerializer::ins([
                EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(ei.status) as aggregate',
                    'ei.status        as status',
                    'c.unique_code    as category_unique_code',
                    'c.name           as category_name',
                    'em.unique_code   as entire_model_unique_code',
                    'em.name          as entire_model_name',
                    'sm.unique_code   as model_unique_code',
                    'sm.name          as model_name',
                ]))
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sm.unique_code', 'sm.name', 'ei.status',])
                ->when(
                    $current_category_unique_code,
                    function ($query, $current_category_unique_code) {
                        $query->where('c.unique_code', $current_category_unique_code);
                    }
                );
            $statistics = QueryBuilderFacade::unionAll($db_statistics_S, $db_statistics_Q)->get();

            $db_S = EntireInstanceSerializer::ins([
                EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
            ])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'ei.identity_code          as identity_code',
                    'ei.identity_code          as serial_number',
                    'ei.status                 as eis',
                    'ei.maintain_location_code as maintain_location_code',
                    'ei.crossroad_number       as crossroad_number',
                    'ei.next_fixing_time       as next_fixing_time',
                    'ei.model_unique_code      as smu',
                    'ei.model_name             as smn',
                    'em.unique_code            as emu',
                    'em.name                   as emn',
                    'c.unique_code             as cu',
                    'c.name                    as cn',
                    'f.unique_code             as fu',
                    'f.name                    as fn',
                    's.unique_code             as su',
                    's.name                    as sn',
                    'sc.unique_code            as scu',
                    'sc.name                   as scn',
                ]))
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                ->when(
                    $current_category_unique_code,
                    function ($query, $current_category_unique_code) {
                        $query->where('c.unique_code', $current_category_unique_code);
                    }
                );
            $db_Q = EntireInstanceSerializer::ins([
                EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'ei.identity_code          as identity_code',
                    'ei.identity_code          as serial_number',
                    'ei.status                 as eis',
                    'ei.maintain_location_code as maintain_location_code',
                    'ei.crossroad_number       as crossroad_number',
                    'ei.next_fixing_time       as next_fixing_time',
                    'sm.unique_code            as smu',
                    'sm.name                   as smn',
                    'em.unique_code            as emu',
                    'em.name                   as emn',
                    'c.unique_code             as cu',
                    'c.name                    as cn',
                    'f.unique_code             as fu',
                    'f.name                    as fn',
                    's.unique_code             as su',
                    's.name                    as sn',
                    'sc.unique_code            as scu',
                    'sc.name                   as scn',
                ]))
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN',])
                ->when(
                    $current_category_unique_code,
                    function ($query, $current_category_unique_code) {
                        $query->where('c.unique_code', $current_category_unique_code);
                    }
                );
            $entire_instances = QueryBuilderFacade::unionAll($db_Q, $db_S)->paginate(10);

            return view('Report.Maintain.maintainEntireInstances', [
                'statuses_as_json' => $statuses->toJson(),
                'factories_as_json' => $factories->toJson(),
                'categories_as_json' => $categories->toJson(),
                'entire_models_as_json' => $entire_models->toJson(),
                'models_as_json' => $models->toJson(),
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'lines_as_json' => $lines->toJson(),
                'statuses' => EntireInstance::$STATUSES,
                'install_rooms_as_json' => $install_rooms->toJson(),
                'entire_instances' => $entire_instances,
                'statistics_as_json' => $statistics->toJson(),
                'current_category_unique_code' => $current_category_unique_code,
            ]);
        } catch (\Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取现场车间具体设备列表
     * @param string $sceneWorkshopUniqueCode
     * @return Factory|RedirectResponse|View
     */
    final public function sceneWorkshopEntireInstances(string $sceneWorkshopUniqueCode)
    {
        try {
            $fileDir = storage_path("app/台账");
            if (!is_dir($fileDir)) return back()->with('danger', '数据不存在');
            $sceneWorkshops = TextHelper::parseJson(file_get_contents("{$fileDir}/现场车间.json"));
            $stationsWithSceneWorkshop = TextHelper::parseJson(file_get_contents("{$fileDir}/车站-现场车间.json"));
            $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
            $currentCategory = $categories[request('categoryUniqueCode')];
            $entireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/类型-种类.json"))[$currentCategory];
            $currentEntireModel = $entireModels[request('entireModelUniqueCode')];
            $subModels = TextHelper::parseJson(file_get_contents("{$fileDir}/型号和子类-类型.json"))[$currentEntireModel];
            $currentSubModel = $subModels[request('subModelUniqueCode')];
            $entireInstanceStatuses = EntireInstance::$STATUSES;
            $currentStatus = array_flip($entireInstanceStatuses)[request('status')];

            $sceneWorkshopName = $sceneWorkshops[$sceneWorkshopUniqueCode];
            $stations = $stationsWithSceneWorkshop[$sceneWorkshopName];

            $getDB = function () use ($stations, $currentStatus): array {
                $stationNames = '';
                foreach ($stations as $station) $stationNames .= "'{$station}',";
                $stationNames = rtrim($stationNames, ',');

                $categoryUniqueCode = request('categoryUniqueCode') ? "and ei.category_unique_code = '" . request('categoryUniqueCode') . "'" : '';
                $entireModelUniqueCode = request('entireModelUniqueCode') ? "and em.unique_code = '" . request('entireModelUniqueCode') . "'" : '';
                $subModelUniqueCode = request('subModelUniqueCode') ? "and sm.unique_code = '" . request('subModelUniqueCode') . "'" : '';
                $status = request('status') ? "and ei.status = '{$currentStatus}'" : '';

                $sqlSm = "
select ei.identity_code,
       ei.category_name,
       em.name as entire_model_name,
       sm.name as sub_model_name,
       ei.status,
       ei.maintain_station_name,
       ei.maintain_location_code,
       ei.crossroad_number,
       ei.open_direction,
       ei.to_direction,
       ei.line_name,
       ei.said_rod,
       ei.next_fixing_day
from entire_instances ei
         inner join entire_models sm on sm.unique_code = ei.entire_model_unique_code
         left join entire_models em on em.unique_code = sm.parent_unique_code
where ei.deleted_at is null
  and ei.maintain_station_name in ({$stationNames})
  {$categoryUniqueCode}
  {$entireModelUniqueCode}
  {$subModelUniqueCode}
  {$status}";

                $subModelUniqueCode = request('subModelUniqueCode') ? "and pm.unique_code = '" . request('subModelUniqueCode') . "'" : '';

                $sqlPm = "
select ei.identity_code,
       ei.category_name,
       em.name as entire_model_name,
       pm.name as sub_model_name,
       ei.status,
       ei.maintain_station_name,
       ei.maintain_location_code,
       ei.crossroad_number,
       ei.open_direction,
       ei.to_direction,
       ei.line_name,
       ei.said_rod,
       ei.next_fixing_day
from entire_instances ei
inner join part_instances pi on pi.entire_instance_identity_code = ei.identity_code
inner join part_models pm on pm.unique_code = pi.part_model_unique_code
inner join entire_models em on em.unique_code = pm.entire_model_unique_code
where ei.deleted_at is null
  and ei.maintain_station_name in ({$stationNames})
  {$categoryUniqueCode}
  {$entireModelUniqueCode}
  {$subModelUniqueCode}
  {$status}";

                return array_merge(DB::select($sqlSm), DB::select($sqlPm));
            };

            return view('Report.Maintain.sceneWorkshopEntireInstances', [
                'entireInstances' => $getDB(),
                'sceneWorkshops' => $sceneWorkshops,
                'currentSceneWorkshop' => $sceneWorkshopUniqueCode,
                'statuses' => EntireInstance::$STATUSES,
                'currentStatus' => $currentStatus,
                'categories' => $categories,
                'currentCategory' => $currentCategory,
                'entire_models' => $entireModels,
                'currentEntireModel' => $currentEntireModel,
                'subModels' => $subModels,
                'currentSubModel' => $currentSubModel,
            ]);
        } catch (\Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 根据种类获取类型列表（现场车间-设备列表）
     * @param string $categoryUniqueCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    final public function entireModelsWithSceneWorkshopEntireInstances(string $categoryUniqueCode)
    {
        try {
            $fileDir = storage_path('app/台账');
            if (!is_dir($fileDir)) return response()->make('数据不存在', 404);

            $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
            $currentCategory = $categories[$categoryUniqueCode];
            $entireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/类型-种类.json"))[$currentCategory];
            return response()->json($entireModels);
        } catch (\Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 根据类型获取型号和子类列表（现场车间-设备列表）
     * @param string $entireModelUniqueCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    final public function subModelsWithSceneWorkshopEntireInstances(string $entireModelUniqueCode)
    {
        try {
            $fileDir = storage_path('app/台账');
            if (!is_dir($fileDir)) return response()->make('数据不存在', 404);

            $categoryUniqueCode = substr($entireModelUniqueCode, 0, 3);
            $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
            $currentCategory = $categories[$categoryUniqueCode];
            $entireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/类型-种类.json"))[$currentCategory];
            $currentEntireModel = $entireModels[$entireModelUniqueCode];
            $subModels = TextHelper::parseJson(file_get_contents("{$fileDir}/型号和子类-类型.json"))[$currentEntireModel];
            return response()->json($subModels);
        } catch (\Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }
}
