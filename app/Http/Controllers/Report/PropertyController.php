<?php

namespace App\Http\Controllers\Report;

use App\Facades\KindsFacade;
use App\Facades\OrganizationFacade;
use App\Facades\QueryBuilderFacade;
use App\Facades\QueryConditionFacade;
use App\Facades\ModelBuilderFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Serializers\EntireInstanceSerializer;
use App\Services\OrganizationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Model\Log;

class PropertyController extends Controller
{
    /**
     * 资产管理
     */
    final public function property()
    {
        $currentYear = date('Y');
        /**
         * 资产管理 ✅
         * @return array
         */
        $property = function () use ($currentYear): array {
            $fileDir = storage_path('app/property');
            if (!is_dir($fileDir)) return [];

            return json_decode(file_get_contents("{$fileDir}/{$currentYear}/devicesAsKind.json"), true);
        };

        return view('Report.Property.property', [
            'propertyDevicesAsKindAsJson' => json_encode($property())
        ]);
    }

    /**
     * 根据种类获取设备列表（资产管理）
     * @param string $category_unique_code
     * @return Factory|RedirectResponse|View
     */
    final public function propertyCategory(string $category_unique_code)
    {
        try {
            $db_S = EntireInstanceSerializer::ins([EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(f.unique_code) as aggregate',
                    'ei.model_unique_code as model_unique_code',
                    'ei.model_name as model_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->where('c.unique_code', $category_unique_code)
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name', 'f.unique_code', 'f.name',]);
            $db_Q = EntireInstanceSerializer::ins([EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(f.unique_code) as aggregate',
                    'sm.unique_code as model_unique_code',
                    'sm.name as model_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->where('c.unique_code', $category_unique_code)
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sm.unique_code', 'sm.name', 'f.unique_code', 'f.name',]);
            $statistics = QueryBuilderFacade::unionAll($db_S, $db_Q)->get();

            return view('Report.Property.propertyCategory', [
                'statistics_as_json' => $statistics->toJson(),
            ]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 根据型号名称获取设备列表（资产管理）
     * @return Factory|RedirectResponse|View
     */
    final public function propertySubModel()
    {
        try {
            $now = Carbon::now()->format('Y-m-d');
            list($dateMadeAtOrigin, $dateMadeAtFinish) = explode('~', request('date_made_at', '{$now} 00:00:00~{$now} 23:59:59'));
            list($dateCreatedAtOrigin, $dateCreatedAtFinish) = explode('~', request('date_created_at', '{$now} 00:00:00~{$now} 23:59:59'));
            list($dateNextFixingDayOrigin, $dateNextFixingDayFinish) = explode('~', request('date_next_fixing_day', '{$now} 00:00:00~{$now} 23:59:59'));

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

            $db_Q = EntireInstanceSerializer::ins([EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'ei.identity_code',
                    'ei.factory_name',
                    'ei.maintain_location_code',
                    'ei.crossroad_number',
                    'ei.open_direction',
                    'ei.said_rod',
                    'ei.installed_at',
                    'ei.last_fix_workflow_at',
                    'ei.next_fixing_time',
                    'ei.scarping_at',
                    'ei.model_name',
                    'ei.model_unique_code',
                    'em.unique_code as entire_model_unique_code',
                    'em.category_unique_code as category_unique_code',
                    'ei.status',
                    'ei.fix_cycle_value as ei_fix_cycle_value',
                    'em.fix_cycle_value as em_fix_cycle_value',
                ]))
                ->orderByDesc('ei.identity_code');
            $db_S = EntireInstanceSerializer::ins([EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'ei.identity_code',
                    'ei.factory_name',
                    'ei.maintain_location_code',
                    'ei.crossroad_number',
                    'ei.open_direction',
                    'ei.said_rod',
                    'ei.installed_at',
                    'ei.last_fix_workflow_at',
                    'ei.next_fixing_time',
                    'ei.scarping_at',
                    'ei.model_name',
                    'ei.model_unique_code',
                    'em.unique_code as entire_model_unique_code',
                    'em.category_unique_code as category_unique_code',
                    'ei.status',
                    'ei.fix_cycle_value as ei_fix_cycle_value',
                    'em.fix_cycle_value as em_fix_cycle_value',
                ]))
                ->orderByDesc('ei.identity_code');
            $entire_instances = QueryBuilderFacade::unionAll($db_Q, $db_S)->paginate(30);

            return view('Entire.Instance.index2', [
                'statuses_as_json' => $statuses->toJson(),
                'factories_as_json' => $factories->toJson(),
                'categories_as_json' => $categories->toJson(),
                'entire_models_as_json' => $entire_models->toJson(),
                'models_as_json' => $models->toJson(),
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'statuses' => EntireInstance::$STATUSES,
                'entire_instances' => $entire_instances,
                'dateMadeAtOrigin' => $dateMadeAtOrigin,
                'dateMadeAtFinish' => $dateMadeAtFinish,
                'dateCreatedAtOrigin' => $dateCreatedAtOrigin,
                'dateCreatedAtFinish' => $dateCreatedAtFinish,
                'dateNextFixingDayOrigin' => $dateNextFixingDayOrigin,
                'dateNextFixingDayFinish' => $dateNextFixingDayFinish,
            ]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '暂无数据');
        }
    }
}
