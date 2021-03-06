<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\DownloadFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\KindsFacade;
use App\Facades\OrganizationFacade;
use App\Facades\QueryBuilderFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\Factory;
use App\Serializers\EntireInstanceSerializer;
use Illuminate\Contracts\View\Factory as IlluminateFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class QueryController extends Controller
{
    /**
     * @return IlluminateFactory|Application|View
     * @throws Throwable
     */
    final public function index()
    {
        $statuses = collect(EntireInstance::$STATUSES);
        $factories = @Factory::with([])->get() ?: collect([]);
        $categories = KindsFacade::getCategories([], function ($db) {
            return $db->where("is_show", true);
        });
        $category_unique_codes = $categories->keys()->toJson();
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
        $size = request("size", 100) ?? 100;

        $account = Account::with([])->where("id", session("account.id"))->first();
        $account->fill(["page_size" => $size,])->saveOrFail();

        $query_keys = [
            "identity_code",
            "factory_device_code",
            "factory_unique_code",
            "serial_number",
            "status",
            "category_unique_code",
            "entire_model_unique_code",
            "sub_model_unique_code",
            "scene_workshop_name",
            "station_name",
            "maintain_location_code",
            "crossroad_number",
            "created_at",
            "made_at",
            "out_at",
            "installed_at",
            "scarping_at",
            "next_fixing_day",
            "fixed_at",
            "behavior_type",
            "work_area",
            "account_id",
            "warehousein_at",
            "source_type_code",
            "source_name",
        ];  // ?????????????????????
        $fields = [
            "ei.updated_at",
            "ei.identity_code",
            "ei.factory_name",
            "ei.factory_device_code",
            "ei.serial_number",
            "ei.status",
            "ei.category_name",
            "ei.last_installed_time",
            "ei.maintain_workshop_name",
            "ei.maintain_station_name",
            "ei.maintain_location_code",
            "ei.line_unique_code",
            "ei.line_name",
            "ei.open_direction",
            "ei.to_direction",
            "ei.traction",
            "ei.crossroad_number",
            "ei.said_rod",
            "ei.warehouse_name",
            "ei.location_unique_code",
            "ei.work_area",
            "ei.next_fixing_day",
            "ei.next_fixing_time",
            "ei.scarping_at",
            "ei.model_name",
            "ei.warehousein_at",
            "ei.in_warehouse_time",
            "ei.note",
            "ei.fixed_at",
            "ei.last_fix_workflow_at as fw_updated_at",
            "ei.bind_device_code as bind_device_code",  // ??????????????????
            "ei.bind_crossroad_number as bind_crossroad_number",  // ?????????????????????
            "ei.bind_device_type_name as bind_device_type_name",  // ??????????????????
            "positions.name as position_name",  // ???
            "tiers.name          as tier_name",  // ???
            "shelves.name         as shelf_name",  // ???
            "platoons.name       as platoon_name",  // ???
            "areas.name          as area_name",  // ???
            "storehouses.name     as storehous_name",  // ???
            "ei.last_fix_workflow_at",  // ??????????????????
            "ei.source_type", // ????????????
            "ei.source_name", // ????????????
            "l.name as l_name",  // ????????????
            "ei.last_out_at",  // ??????????????????
            "ei.made_at",  // ????????????
            "ei.maintain_section_name",  // ????????????
            "ei.maintain_send_or_receive", // ???/??????
            "ei.maintain_signal_post_main_or_indicator_code",  // ???????????????????????????
            "ei.maintain_signal_post_main_light_position_code",  // ?????????????????????
            "ei.maintain_signal_post_indicator_light_position_code",  // ???????????????
        ];  // ????????????

        $entire_instances = [];  // ??????????????????????????????
        $statistics_template = collect([]);  // ????????????????????????
        $breakdownCounts = [];

        if (!empty(array_intersect(request()->keys(), $query_keys))) {
            $fields_S =
                request('model_unique_code')
                    ? [
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'ei.model_unique_code as sub_model_unique_code',
                    'ei.model_name as sub_model_name',
                    'em.fix_cycle_value as entire_model_fix_cycle_value',
                ]
                    : [
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    "'' as sub_model_unique_code",
                    "'' as sub_model_name",
                    'em.fix_cycle_value as entire_model_fix_cycle_value',
                ];
            $db_S = EntireInstanceSerializer::INS()->GenerateQueryRelationShipS()
                ->selectRaw('distinct ' . implode(',', array_merge($fields, $fields_S)));

            $db_Q = EntireInstanceSerializer::INS()->GenerateQueryRelationShipQ()
                ->selectRaw('distinct ' . implode(',', array_merge($fields, [
                        'em.unique_code as entire_model_unique_code',
                        'em.name as entire_model_name',
                        'sm.unique_code as sub_model_unique_code',
                        'sm.name as sub_model_name',
                        'sm.fix_cycle_value as sub_model_fix_cycle_value',
                    ])));
            $db = QueryBuilderFacade::unionAll($db_S, $db_Q)
                ->orderBy("maintain_workshop_name")
                ->orderBy("maintain_station_name")
                ->orderBy("maintain_location_code")
                ->orderBy("crossroad_number")
                ->orderBy("open_direction")
                ->orderBy("maintain_section_name")
                ->orderBy("maintain_send_or_receive");

            if (request('d') == 1) {
                return DownloadFacade::EntireInstancesByRequestToExcel();
                // DownloadFacade::EntireInstancesByRequestToCsv();
            } else {
                // ??????
                $entire_instances = $db->paginate(request("size", $account->page_size) ?? 100);

                $maintain_station_names = $entire_instances->pluck('maintain_station_name')->unique()->toArray();
                // ??????????????????
                if (!empty($maintain_station_names)) {
                    $breakdownCounts = DB::table('breakdown_logs')
                        ->selectRaw(implode(',', [
                            'count(*) as count',
                            'concat(maintain_station_name, maintain_location_code, crossroad_number) as install_location',
                        ]))
                        ->whereIn('maintain_station_name', $maintain_station_names)
                        ->groupBy(['maintain_station_name', 'maintain_location_code', 'crossroad_number'])
                        ->where('maintain_station_name', '<>', '')
                        ->whereNotNull('maintain_station_name')
                        ->pluck('count', 'install_location')
                        ->toArray();
                }

                // ????????????????????????
                $statistics_template = $this->_generateQueryStatisticsTemplate(request());
            }
        }

        // ???????????????????????????
        for ($i = 0; $i <= 12; $i++) {
            $dates[] = date('Y-m', strtotime("+$i months"));
        }

        return view('Query.index', [
            'account' => $account,
            'statuses_as_json' => $statuses->toJson(),
            'factories_as_json' => $factories->toJson(),
            'categories_as_json' => $categories->toJson(),
            'category_unique_codes_as_json' => $categories->keys()->toJson(),
            'entire_models_as_json' => $entire_models->toJson(),
            'models_as_json' => $models->toJson(),
            'scene_workshops_as_json' => $scene_workshops->toJson(),
            'stations_as_json' => $stations->toJson(),
            'lines_as_json' => $lines->toJson(),
            'statuses' => EntireInstance::$STATUSES,
            'source_types_as_json' => json_encode(EntireInstance::$SOURCE_TYPES),
            'entireInstances' => $entire_instances,
            'categoryUniqueCodes' => $category_unique_codes,
            'dates' => $dates,
            'breakdownCounts' => $breakdownCounts,
            'statistics_template_count' => $statistics_template->count(),
            'statistics_template_as_json' => $statistics_template->toJson(),
        ]);
    }

    /**
     * @param Request $request
     * @return Collection
     */
    private function _generateQueryStatisticsTemplate(Request $request): Collection
    {
        $i = 0;
        $merges = [];
        $cols[] = [
            'type' => 'numbers',
        ];

        $generateTitle = function ($field, $title) {
            return [
                'field' => $field,
                'title' => $title,
                'sort' => true,
            ];
        };

        if ($request->get('qs_is_use_workshop') == 1) {
            $i++;
            $merges[] = $i;
            $cols[] = $generateTitle('workshop_name', '??????');
        }

        if ($request->get('qs_is_use_station') == 1) {
            $i++;
            $merges[] = $i;
            $cols[] = $generateTitle('station_name', '??????');
        }

        if ($request->get('qs_is_use_category') == 1) {
            $i++;
            $merges[] = $i;
            $cols[] = $generateTitle('category_name', '??????');
        }

        if ($request->get('qs_is_use_entire_model') == 1) {
            $i++;
            $merges[] = $i;
            $cols[] = $generateTitle('entire_model_name', '??????');
        }

        if ($request->get('qs_is_use_model') == 1) {
            $i++;
            $merges[] = $i;
            $cols[] = $generateTitle('model_name', '??????');
        }

        if ($request->get('qs_is_use_status') == 1) {
            $i++;
            $merges[] = $i;
            $cols[] = $generateTitle('status_name', '??????');
        }

        if ($request->get("qs_is_use_factory") == 1) {
            $i++;
            $merges[] = $i;
            $cols[] = $generateTitle("factory_name", "??????");
        }

        if (empty($merges)) {
            return collect([]);
        } else {
            $cols[] = [
                'field' => 'count',
                'title' => '??????',
                'sort' => true
            ];
            return collect([
                'merges' => $merges,
                'cols' => $cols,
            ]);
        }
    }

    /**
     * ????????????
     * @return array
     */
    final public function getStatistics()
    {
        $fields = ['ei.identity_code'];
        $fields2 = ["count(tmp_union_all.identity_code) as count"];
        $groups = [];
        $orders = [];

        if (request('qs_is_use_workshop') == 1) {
            $fields[] = 'sc.name as workshop_name';
            $fields2[] = 'tmp_union_all.workshop_name';
            $groups[] = 'tmp_union_all.workshop_name';
            $orders[] = 'tmp_union_all.workshop_name desc';
        }

        if (request('qs_is_use_station') == 1) {
            $fields[] = 's.name as station_name';
            $fields2[] = 'tmp_union_all.station_name';
            $groups[] = 'tmp_union_all.station_name';
            $orders[] = 'tmp_union_all.station_name';
        }

        if (request('qs_is_use_category') == 1) {
            $fields[] = 'c.name as category_name';
            $fields2[] = 'tmp_union_all.category_name';
            $groups[] = 'tmp_union_all.category_name';
            $orders[] = 'tmp_union_all.category_name';
        }

        if (request('qs_is_use_entire_model') == 1) {
            $fields[] = 'em.name as entire_model_name';
            $fields2[] = 'tmp_union_all.entire_model_name';
            $groups[] = 'tmp_union_all.entire_model_name';
            $orders[] = 'tmp_union_all.entire_model_name';
        }

        if (request('qs_is_use_model') == 1) {
            $fields[] = 'ei.model_name as model_name';
            $fields2[] = 'tmp_union_all.model_name';
            $groups[] = 'tmp_union_all.model_name';
            $orders[] = 'tmp_union_all.model_name';
        }

        if (request('qs_is_use_status') == 1) {
            $fields[] = "case when ei.status = 'INSTALLED' then '????????????' when ei.status = 'INSTALLING' then '????????????' when ei.status = 'TRANSFER_OUT' then '????????????' when ei.status = 'TRANSFER_IN' then '????????????' when ei.status = 'SCRAP' then '??????' when ei.status = 'FIXED' then '????????????' when ei.status = 'FIXING' then '??????' when ei.status = 'SEND_REPAIR' then '?????????' else '' end as status_name";
            $fields2[] = 'tmp_union_all.status_name';
            $groups[] = 'tmp_union_all.status_name';
            $orders[] = 'tmp_union_all.status_name';
        }

        if (request("qs_is_use_factory") == 1) {
            $fields[] = "ei.factory_name";
            $fields2[] = "tmp_union_all.factory_name";
            $groups[] = "tmp_union_all.factory_name";
            $orders[] = "tmp_union_all.factory_name";
        }

        $sql_S = EntireInstanceSerializer::INS()
            ->GenerateQueryRelationShipS()
            ->selectRaw(implode(',', $fields));
        $sql_Q = EntireInstanceSerializer::INS()
            ->GenerateQueryRelationShipQ()
            ->selectRaw(implode(',', $fields));

        $statistics = QueryBuilderFacade::unionAll($sql_S, $sql_Q)
            ->selectRaw(implode(',', $fields2))
            ->groupBy($groups)
            ->orderByRaw(implode(',', $orders))
            ->get();

        return [
            'code' => 0,
            'msg' => '',
            'count' => count($statistics),
            'data' => $statistics
        ];
    }

    /**
     * ????????????????????????
     * @param string $categoryUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function entireModels(string $categoryUniqueCode)
    {
        return response()->json(
            DB::table('entire_models')
                ->where('deleted_at', null)
                ->where('category_unique_code', $categoryUniqueCode)
                ->where('is_sub_model', false)
                ->pluck('name', 'unique_code')
        );
    }

    /**
     * ????????????????????????
     * @param string $entireModelUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function subModels(string $entireModelUniqueCode)
    {
        return response()->json(
            array_merge(
                DB::table('entire_models')
                    ->where('deleted_at', null)
                    ->where('parent_unique_code', $entireModelUniqueCode)
                    ->where('is_sub_model', true)
                    ->pluck('name', 'unique_code')
                    ->toArray(),
                DB::table('part_models')
                    ->where('deleted_at', null)
                    ->where('entire_model_unique_code', $entireModelUniqueCode)
                    ->pluck('name', 'unique_code')
                    ->toArray()
            )
        );
    }

    /**
     * ????????????????????????
     * @return \Illuminate\Http\JsonResponse
     */
    final public function stations()
    {
        return response()->json(
            DB::table('maintains')
                ->where('deleted_at', null)
                ->where('type', '=', 'STATION')
                ->when(
                    request('sceneWorkshopUniqueCode'),
                    function ($query) {
                        return $query->where('parent_unique_code', request('sceneWorkshopUniqueCode'));
                    }
                )
                ->when(
                    request('lineUniqueCode'),
                    function ($query) {
                        $line = DB::table('lines')->where('unique_code', request('lineUniqueCode'))->first();
                        return $line ? $query->whereIn('id', DB::table('lines_maintains')->where('lines_id', $line->id)->pluck('maintains_id')->toArray()) : null;
                    }
                )
                ->pluck('name', 'unique_code')
        );
    }

    /**
     * ????????????????????????????????????
     * @param int $workArea
     * @return \Illuminate\Http\JsonResponse
     */
    final public function accounts(int $workArea)
    {
        $organizationCode = env('ORGANIZATION_CODE');

        return response()->json(
            DB::table('accounts')
                ->where('deleted_at', null)
                ->where('workshop_code', $organizationCode)
                ->where('work_area', '<>', null)
                ->when(
                    $workArea > 0,
                    function ($query) use ($workArea) {
                        return $query->where('work_area', $workArea);
                    },
                    function ($query) {
                        return $query->whereIn('work_area', [1, 2, 3]);
                    }
                )
                ->pluck('nickname', 'id')
        );
    }

    /**
     * ????????????
     * @param Request $request
     */
    final public function putBatchUpdate(Request $request)
    {
        try {
            $update_datum = [];
            if ($request->get('is_update_factory') == 1) {
                $factory_unique_code = $request->get('factory_unique_code');
                $factory_name = '';
                if ($factory_unique_code) {
                    $factory = Factory::with([])->where('unique_code', $factory_unique_code)->first();
                    if (!$factory) return JsonResponseFacade::errorValidate('??????????????????????????????');
                    $factory_name = $factory->name;
                }
                $update_datum['factory_name'] = $factory_name;
            }
            if ($request->get('is_update_source_type') == 1) {
                $update_datum['source_type'] = $request->get('source_type', '') ?? '';
            }
            if ($request->get('is_update_source_name') == 1) {
                $update_datum['source_name'] = $request->get('source_name', '') ?? '';
            }
            if ($request->get('is_update_note') == 1) {
                $update_datum['note'] = $request->get('note', '') ?? '';
            }
            if ($request->get('is_update_maintain_location_code') == 1) {
                $update_datum['maintain_location_code'] = $request->get('maintain_location_code', '') ?? '';
            }
            if ($request->get('is_update_crossroad_number') == 1) {
                $update_datum['crossroad_number'] = $request->get('crossroad_number', '') ?? '';
            }
            if ($request->get('is_update_open_direction') == 1) {
                $update_datum['open_direction'] = $request->get('open_direction', '') ?? '';
            }

            if ($update_datum) {
                DB::table('entire_instances')
                    ->whereIn('identity_code', explode(',', $request->get('identity_codes')))
                    ->update($update_datum);
                return JsonResponseFacade::updated();
            } else {
                return JsonResponseFacade::errorEmpty('????????????????????????');
            }
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ??????????????????????????????????????????
     */
    final public function getKindsNamesByIdentityCodes()
    {
        try {
            $ret = null;
            EntireInstance::with([])
                ->whereIn('identity_code', request('identity_codes'))
                ->groupBy(['entire_model_unique_code'])
                ->each(function ($entire_instance) use (&$ret) {
                    $ret[] = [
                        'category' => @$entire_instance->Category,
                        'entire_model' => @$entire_instance->EntireModel->Parent ?: @$entire_instance->EntireModel,
                        'sub_model' => @$entire_instance->EntireModel->Parent ? @$entire_instance->EntireModel : null,
                    ];
                });

            return $ret;
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
