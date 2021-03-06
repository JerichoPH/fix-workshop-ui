<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\EntireInstanceUseReport;
use App\Model\Install\InstallPosition;
use App\Model\Install\InstallShelf;
use App\Model\Maintain;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class InstalledController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Factory|Application|View
     */
    final public function index()
    {
        try {
            $scene_workshops = DB::table('maintains as sc')
                ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->where("is_show", true)
                ->get();
            $stations = DB::table('maintains as s')
                ->where('s.type', 'STATION')
                ->where("s.parent_unique_code", "<>", "")
                ->where("s.is_show", true)
                ->get()
                ->groupBy('parent_unique_code');
            $install_positions = [];
            if (request('install_shelf_unique_code')) {
                $install_positions = InstallPosition::with(['WithInstallTier',])
                    ->where('install_tier_unique_code', 'like', request('install_shelf_unique_code') . '%')
                    ->get();
            }

            $installed_positions = [];
            if (request('station_unique_code') && !empty($install_positions)) {
                $station = DB::table('maintains as s')->where('s.deleted_at', null)->where('unique_code', request('station_unique_code'))->first();
                $installed_positions = EntireInstance::with(['InstallPosition',])
                    ->where('maintain_station_name', $station->name)
                    ->whereIn('maintain_location_code', $install_positions->pluck('unique_code'))
                    ->pluck('maintain_location_code')
                    ->toArray();
            }

            $install_shelf = InstallShelf::with([
                'WithInstallTiers',
                'WithInstallTiers.WithInstallPositions',
            ])
                ->where('unique_code', request('install_shelf_unique_code'))
                ->first();

            $installed_entire_instances = collect([]);
            if (!empty($install_shelf)) {
                $installed_entire_instances = EntireInstance::with([])
                    ->select(['model_unique_code', 'model_name', 'maintain_location_code', 'identity_code'])
                    ->where('maintain_location_code', 'like', $install_shelf->unique_code . '%')
                    ->get()
                    ->groupBy('maintain_location_code');
            }

            return view('Installed.index', [
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'install_positions' => !empty($install_positions) ? $install_positions->groupBy(['install_tier_unique_code']) : [],
                'installed_positions' => $installed_positions,
                'install_shelf_as_json' => $install_shelf ? $install_shelf->toJson() : collect([])->toJson(),
                'installed_entire_instances_as_json' => $installed_entire_instances->toJson(),
            ]);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    final public function store(Request $request)
    {
        try {
            $entire_instance = EntireInstance::with(["EntireInstanceLock"])->where("identity_code", $request->get("identity_code"))->firstOrFail();
            // ?????????????????????????????? @todo ????????????
            // if ($entire_instance->EntireInstanceLock)
            //     return JsonResponseFacade::errorForbidden($entire_instance->EntireInstanceLock->remark ?: "???????????????{$entire_instance->identity_code}???????????????????????????");

            // ????????????????????????????????????
            if ($entire_instance->can_i_installed !== true) {
                return JsonResponseFacade::errorForbidden($entire_instance->can_i_installed);
            }

            if (
                !request("maintain_location_code")
                && !request("crossroad_number")
                && !request("open_direction")
                && !request("maintain_section_name")
                && !request("maintain_send_or_receive")
                && !request("maintain_signal_post_main_or_indicator_code")
                && !request("maintain_signal_post_main_light_position_code")
                && !request("maintain_signal_post_indicator_light_position_code")
            ) {
                return JsonResponseFacade::errorForbidden("??????????????????");
            }
            $scene_workshop = null;
            if ($request->get("scene_workshop_unique_code")) {
                $scene_workshop = Maintain::with([])->where("unique_code", $request->get("scene_workshop_unique_code"))->where("type", "SCENE_WORKSHOP")->first();
                if (!$scene_workshop) return JsonResponseFacade::errorForbidden("?????????????????????");
            }
            $station = null;
            if ($request->get("station_unique_code")) {
                $station = Maintain::with(["Parent"])->where("unique_code", $request->get("station_unique_code"))->where("type", "STATION")->first();
                if (!$station) return JsonResponseFacade::errorForbidden("???????????????");
                if (!$station->Parent) return JsonResponseFacade::errorForbidden("??????????????????????????????????????????????????????");
                $scene_workshop = $station->Parent;
            }
            if (!$scene_workshop && !$station) return JsonResponseFacade::errorForbidden("????????????????????????");

            // ??????/??????
            DB::beginTransaction();
            switch (request("status")) {
                case "INSTALLED":
                    // ????????????
                    // ???????????????????????????????????????????????????
                    if (array_flip(EntireInstance::$STATUSES)[$entire_instance->status] == "INSTALLING") {
                        EntireInstanceLog::with([])
                            ->create([
                                "created_at" => now(),
                                "updated_at" => now(),
                                "name" => "????????????",
                                "description" => implode("???", [
                                    "????????????" . session("account.nickname") ?? "???",
                                    "???????????????" . @$scene_workshop->name ?: "???",
                                    "?????????" . @$station->name ?: "???",
                                    "?????????" . @$entire_instance->InstallPosition->real_name ?: (@$entire_instance->maintain_location_code ?: '')
                                ]),
                                "entire_instance_identity_code" => request("identity_code"),
                                "type" => 4,
                                "url" => '',
                                "operator_id" => session("account.id"),
                                "station_unique_code" => @$station->unique_code ?? '',
                            ]);
                    }

                    // ????????????????????????
                    if ($request->get("maintain_location_code")) {
                        $install_position = null;
                        if ($install_position) if ($install_position->EntireInstances->count() >= $install_position->volume) return JsonResponseFacade::errorForbidden("?????????????????????{$install_position->volume}?????????");
                    }

                    // ????????????/??????
                    $entire_instance->fill([
                        "maintain_workshop_name" => @$scene_workshop->name ?? "",
                        "maintain_station_name" => @$station->name ?? "",
                        "maintain_location_code" => request("maintain_location_code") ?: "",
                        "crossroad_number" => request("crossroad_number") ?: "",
                        "open_direction" => request("open_direction") ?: "",
                        "line_name" => request("line_name") ?: "",
                        "maintain_signal_post_main_or_indicator_code" => request("maintain_signal_post_main_or_indicator_code") ?: "",
                        "maintain_signal_post_main_light_position_code" => request("maintain_signal_post_main_light_position_code") ?: "",
                        "maintain_signal_post_indicator_light_position_code" => request("maintain_signal_post_indicator_light_position_code") ?: "",
                        "maintain_send_or_receive" => request("maintain_send_or_receive") ?: "",
                        "maintain_section_name" => request("maintain_section_name") ?: "",
                        "status" => "INSTALLED",
                        "installed_at" => now(),
                        "is_emergency" => false,
                    ])
                        ->saveOrFail();

                    // ????????????
                    EntireInstanceLog::with([])
                        ->create([
                            "created_at" => now(),
                            "updated_at" => now(),
                            "name" => "??????",
                            "description" => join("???", [
                                "????????????" . session("account.nickname") ?? "???",
                                $entire_instance->use_position_name ? "???????????????" . $entire_instance->use_position_name : "",
                            ]),
                            "entire_instance_identity_code" => request("identity_code"),
                            "type" => 4,
                            "url" => '',
                            "operator_id" => session("account.id"),
                            "station_unique_code" => @$station->unique_code ?? '',
                        ]);

                    // ??????????????????
                    EntireInstanceUseReport::with([])->create([
                        "id" => EntireInstanceUseReport::generateId(),
                        "entire_instance_identity_code" => $entire_instance->identity_code,
                        "scene_workshop_unique_code" => @$scene_workshop->unique_code ?? '',
                        "maintain_station_unique_code" => @$station->unique_code ?? '',
                        "maintain_location_code" => $entire_instance->maintain_location_code,
                        "processor_id" => session("account.id"), 
                        "crossroad_number" => $entire_instance->crossroad_number,
                        "open_direction" => $entire_instance->open_direction,
                        "maintain_section_name" => $entire_instance->maintain_section_name,
                        "maintain_send_or_receive" => $entire_instance->maintain_send_or_receive,
                        "maintain_signal_post_main_or_indicator_code" => $entire_instance->maintain_signal_post_main_or_indicator_code,
                        "maintain_signal_post_main_light_position_code" => $entire_instance->maintain_signal_post_main_light_position_code,
                        "maintain_signal_post_indicator_light_position_code" => $entire_instance->maintain_signal_post_indicator_light_position_code,
                        "type" => "INSTALLED",
                        "status" => "DONE",
                    ]);
                    break;
                case "INSTALLING":
                    // ??????????????????
                    $entire_instance
                        ->fill([
                            "maintain_workshop_name" => @$scene_workshop->name ?? '',
                            "maintain_station_name" => @$station->name ?? '',
                            "maintain_location_code" => $request->get("maintain_location_code", '') ?? '',
                            "installed_time" => now(),
                            "status" => "INSTALLING",
                            "is_emergency" => $request->get("is_emergency", false) ?? false,
                        ])
                        ->saveOrFail();

                    // ????????????
                    EntireInstanceLog::with([])
                        ->create([
                            "created_at" => now(),
                            "updated_at" => now(),
                            "name" => "??????????????????",
                            "description" => implode("???", [
                                "????????????" . session("account.nickname"),
                                "???????????????" . @$entire_instance->Station->Parent->name ?? "???",
                                "?????????" . @$entire_instance->Station->name ?? "???",
                                "?????????" .
                                ($entire_instance->maintain_location_code
                                    ? (@$entire_instance->InstallPosition->real_name ?: $entire_instance->maintain_location_code)
                                    : '')
                                . $entire_instance->crossroad_number
                                . $entire_instance->open_direction,
                            ]),
                            "entire_instance_identity_code" => $entire_instance->identity_code,
                            "type" => 4,
                            "url" => '',
                            "operator_id" => session("account.id"),
                            "station_unique_code" => @$entire_instance->Station->unique_code ?? '',
                        ]);

                    // ??????????????????
                    EntireInstanceUseReport::with([])->create([
                        "id" => EntireInstanceUseReport::generateId(),
                        "entire_instance_identity_code" => $entire_instance->identity_code,
                        "scene_workshop_unique_code" => @$entire_instance->Station->Parent->unique_code ?? '',
                        "maintain_station_unique_code" => @$entire_instance->Station->unique_code ?? '',
                        "maintain_location_code" => @$entire_instance->maintain_location_code ?: '',
                        "processor_id" => session("account.id"),
                        "crossroad_number" => '',
                        "open_direction" => '',
                        "type" => "INSTALLING",
                        "status" => "DONE",
                    ]);
                    break;
                default:
                    return JsonResponseFacade::errorValidate("??????????????????");
            }

            DB::commit();

            return JsonResponseFacade::created([], "????????????");
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("?????????????????????");
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ?????????????????????
     * @return Factory|Application|RedirectResponse|View
     */
    final public function getHistory()
    {
        try {
            list($origin_at, $finish_at) = explode('~', request('created_at') ?? join('~', [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d'),]));

            $accounts = Account::with([])->get();
            $scene_workshops = DB::table('maintains as sc')->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))->where('sc.type', 'SCENE_WORKSHOP')->get();
            $stations = DB::table('maintains as s')->where('s.type', 'STATION')->get()->groupBy('parent_unique_code');

            $entire_instance_use_reports = ModelBuilderFacade::init(
                request(),
                EntireInstanceUseReport::with([
                    'EntireInstance',
                    'EntireInstance.Category',
                    'SceneWorkshop',
                    'Station',
                    'InstallPosition',
                    'Processor',
                ])
                    ->orderByDesc('created_at'),
                [
                    'created_at',
                    'install_shelf_unique_code',
                    'maintain_location_code',
                    'maintain_location_code_use_indistinct',
                    'crossroad_number',
                    'crossroad_number_use_indistinct',
                    'install_room_unique_code',
                    'install_platoon_unique_code',
                ]
            )
                ->extension(function ($entire_instance_use_report) use ($origin_at, $finish_at) {
                    return $entire_instance_use_report
                        ->when(request('maintain_location_code'), function ($query) {
                            if (request('maintain_location_code_use_indistinct')) {
                                $query->where('maintain_location_code', 'like', '%' . request('maintain_location_code') . '%');
                            } else {
                                $query->where('maintain_location_code', request('maintain_location_code'));
                            }
                        })
                        ->when(request('crossroad_number'), function ($query) {
                            if (request('crossroad_number_use_indistinct')) {
                                $query->where('crossroad_number', 'like', '%' . request('crossroad_number') . '%');
                            } else {
                                $query->where('crossroad_number', request('crossroad_number'));
                            }
                        })
                        ->when(request('install_room_unique_code'), function ($query) {
                            $query->where('maintain_location_code', 'like', request('install_room_unique_code') . '%');
                        })
                        ->when(request('install_platoon_unique_code'), function ($query) {
                            $query->where('maintain_location_code', 'like', request('install_platoon_unique_code') . '%');
                        })
                        ->when(request('install_shelf_unique_code'), function ($query) {
                            $query->where('maintain_location_code', 'like', request('install_shelf_unique_code') . '%');
                        })
                        ->whereBetween(
                            'created_at', [
                                Carbon::parse($origin_at)->startOfDay()->format('Y-m-d H:i:s'),
                                Carbon::parse($finish_at)->endOfDay()->format('Y-m-d H:i:s')
                            ]
                        );
                })
                ->pagination();

            return view('Installed.history', [
                'entire_instance_use_reports' => $entire_instance_use_reports,
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'accounts_as_json' => $accounts->toJson(),
                'origin_at' => $origin_at,
                'finish_at' => $finish_at,
                'types' => EntireInstanceUseReport::$TYPES,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
