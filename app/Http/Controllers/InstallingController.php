<?php

namespace App\Http\Controllers;

use App\Exceptions\FuncNotFoundException;
use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\EntireInstanceUseReport;
use App\Model\Install\InstallPosition;
use App\Model\Install\InstallShelf;
use App\Model\Maintain;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class InstallingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $scene_workshops = DB::table('maintains as sc')->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))->where('sc.type', 'SCENE_WORKSHOP')->get();
            $stations = DB::table('maintains as s')->where('s.type', 'STATION')->get()->groupBy('parent_unique_code');
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

            $entire_instances = collect([]);
            if (!empty($install_shelf)) {
                $entire_instances = EntireInstance::with([])
                    ->select(['model_unique_code', 'model_name', 'maintain_location_code'])
                    ->where('maintain_location_code', 'like', $install_shelf->unique_code . '%')
                    ->get()
                    ->groupBy('maintain_location_code');
            }

            return view('Installing.index', [
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'install_positions' => !empty($install_positions) ? $install_positions->groupBy(['install_tier_unique_code']) : [],
                'installed_positions' => $installed_positions,
                'install_shelf_as_json' => $install_shelf ? $install_shelf->toJson() : collect([])->toJson(),
                'entire_instances_as_json' => $entire_instances->toJson(),
            ]);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ????????????????????????
     * @param Request $request
     * @return mixed
     */
    final public function postScan(Request $request)
    {
        $entire_instance = EntireInstance::with([
            'SubModel',
            'PartModel',
            'EntireInstanceLock',
        ])
            ->where('identity_code', $request->get('identity_code'))
            ->firstOrFail();

        // // ??????????????????????????????
        // if ($entire_instance->EntireInstanceLock)
        //     return JsonResponseFacade::errorForbidden(@$entire_instance->EntireInstanceLock->remark ?: "???????????????{$entire_instance->identity_code}???????????????????????????");

        // ????????????????????????????????????
        if ($entire_instance->can_i_installing !== true)
            return JsonResponseFacade::errorForbidden($entire_instance->can_i_installing);

        return JsonResponseFacade::data([
            'entire_instance' => $entire_instance,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    final public function store(Request $request)
    {
        try {
            $entire_instance = EntireInstance::with(['EntireInstanceLock'])->where('identity_code', $request->get('identity_code'))->firstOrFail();
            // ?????????????????????????????? @todo ????????????
            // if ($entire_instance->EntireInstanceLock)
            //     return JsonResponseFacade::errorForbidden($entire_instance->EntireInstanceLock->remark ?: "???????????????{$entire_instance->identity_code}???????????????????????????");

            if (!$request->get('scene_workshop_unique_code') && !$request->get('station_unique_code')) return JsonResponseFacade::errorValidate('??????????????????/????????????');
            $scene_workshop = $station = null;
            if ($request->get('scene_workshop_unique_code')) {
                $scene_workshop = Maintain::with([])->where('unique_code', $request->get('scene_workshop_unique_code'))->where('type', 'SCENE_WORKSHOP')->first();
                if (!$scene_workshop) return JsonResponseFacade::errorForbidden('?????????????????????');
            }
            if ($request->get('station_unique_code')) {
                $station = Maintain::with(['Parent'])->where('unique_code', $request->get('station_unique_code'))->where('type', 'STATION')->first();
                if (!$station) return JsonResponseFacade::errorForbidden('???????????????');
                if (!$station->Parent) return JsonResponseFacade::errorForbidden('??????????????????????????????????????????????????????');
                $scene_workshop = $station->Parent;
            }

            $can_i_installing = $entire_instance->can_i_installing;
            if ($can_i_installing !== true) return JsonResponseFacade::errorForbidden($can_i_installing);

            // ??????????????????
            DB::beginTransaction();
            $entire_instance
                ->fill([
                    'maintain_workshop_name' => $scene_workshop->name,
                    'maintain_station_name' => $station->name,
                    'maintain_location_code' => $request->get('maintain_location_code', '') ?? '',
                    'installed_time' => now(),
                    'status' => 'INSTALLING',
                    'is_emergency' => $request->get('is_emergency', false) ?? false,
                ])
                ->saveOrFail();

            // ????????????
            EntireInstanceLog::with([])
                ->create([
                    'created_at' => now(),
                    'updated_at' => now(),
                    'name' => '??????????????????',
                    'description' => implode('???', [
                        '????????????' . session('account.nickname'),
                        '???????????????' . @$entire_instance->Station->Parent->name ?? '???',
                        '?????????' . @$entire_instance->Station->name ?? '???',
                        '?????????' .
                        ($entire_instance->maintain_location_code
                            ? (@$entire_instance->InstallPosition->real_name ?: $entire_instance->maintain_location_code)
                            : '')
                        . $entire_instance->crossroad_number
                        . $entire_instance->open_direction,
                    ]),
                    'entire_instance_identity_code' => $entire_instance->identity_code,
                    'type' => 4,
                    'url' => '',
                    'operator_id' => session('account.id'),
                    'station_unique_code' => @$entire_instance->Station->unique_code ?? '',
                ]);

            // ??????????????????
            EntireInstanceUseReport::with([])->create([
                'id' => EntireInstanceUseReport::generateId(),
                'entire_instance_identity_code' => $entire_instance->identity_code,
                'scene_workshop_unique_code' => @$entire_instance->Station->Parent->unique_code ?? '',
                'maintain_station_unique_code' => @$entire_instance->Station->unique_code ?? '',
                'maintain_location_code' => @$entire_instance->maintain_location_code ?: '',
                'processor_id' => session('account.id'),
                'crossroad_number' => '',
                'open_direction' => '',
                'type' => 'INSTALLING',
                'status' => 'DONE',
            ]);
            DB::commit();

            return JsonResponseFacade::created([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
