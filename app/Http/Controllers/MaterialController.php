<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\OrganizationFacade;
use App\Model\Material;
use App\Model\Position;
use App\Validations\Web\MaterialTaggingValidation;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Factory|Application|JsonResponse|View
     */
    final public function index()
    {
        if (request()->ajax()) {
            $materials = (new Material)
                ->ReadMany([
                    'storehouse_unique_code',
                    'area_unique_code',
                    'platoon_unique_code',
                    'shelf_unique_code',
                    'tier_unique_code',
                    'position_unique_code',
                ])
                ->with([
                    'Type',
                    'Workshop',
                    'Station',
                    'WorkArea',
                    'Position',
                    'Position.Tier',
                    'Position.Tier.Shelf',
                    'Position.Tier.Shelf.Platoon',
                    'Position.Tier.Shelf.Platoon.Area',
                    'Position.Tier.Shelf.Platoon.Area.Storehouse',
                ])
                ->when(
                    request('storehouse_unique_code'),
                    function ($query, $storehouse_unique_code) {
                        $query->where('position_unique_code', 'like', "{$storehouse_unique_code}%");
                    }
                )
                ->when(
                    request('area_unique_code'),
                    function ($query, $area_unique_code) {
                        $query->where('position_unique_code', 'like', "{$area_unique_code}%");
                    }
                )
                ->when(
                    request('platoon_unique_code'),
                    function ($query, $platoon_unique_code) {
                        $query->where('position_unique_code', 'like', "{$platoon_unique_code}%");
                    }
                )
                ->when(
                    request('shelf_unique_code'),
                    function ($query, $shelf_unique_code) {
                        $query->where('position_unique_code', 'like', "{$shelf_unique_code}%");
                    }
                )
                ->when(
                    request('tier_unique_code'),
                    function ($query, $tier_unique_code) {
                        $query->where('position_unique_code', 'like', "{$tier_unique_code}%");
                    }
                )
                ->when(
                    request('position_unique_code'),
                    function ($query, $position_unique_code) {
                        $query->where('position_unique_code', $position_unique_code);
                    }
                )
                ->get();

            $position_unique_codes = $materials->pluck('position_unique_code');
            $position_names_by_unique_code = Position::with([])
                ->whereIn('unique_code', $position_unique_codes)
                ->get()
                ->map(function ($position) {
                    return ['unique_code' => $position->unique_code, 'real_name' => $position->real_name,];
                })
                ->pluck('real_name', 'unique_code');
            $materials->map(function ($datum) use ($position_names_by_unique_code) {
                $datum->position_real_name = @$position_names_by_unique_code[$datum->position_unique_code] ?: '';
                return $datum;
            });

            return JsonResponseFacade::dict(['materials' => $materials,]);
        } else {
            $workshops = OrganizationFacade::getWorkshops();
            $stations = OrganizationFacade::getStationsBySceneWorkshop();
            $work_areas = OrganizationFacade::getWorkAreaByWorkshop();
            $statuses = collect(Material::$STATUSES);
            $storehouses = OrganizationFacade::getStorehouses();
            $areas = OrganizationFacade::getAreasByStorehouse();

            return view('Material.index', [
                'statuses' => $statuses,
                'workshops' => $workshops,
                'stations' => $stations,
                'work_areas' => $work_areas,
                'storehouses' => $storehouses,
                'areas' => $areas,
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    final public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $identity_code
     * @return JsonResponse
     */
    final public function destroy(string $identity_code): JsonResponse
    {
        $material = (new Material)->ReadOneByIdentityCode($identity_code);

        $material->delete();

        return JsonResponseFacade::deleted();
    }

    /**
     * 赋码
     * @param Request $request
     * @return JsonResponse
     */
    final public function postTagging(Request $request): JsonResponse
    {
        $validation = new MaterialTaggingValidation($request);
        $v = $validation->check();
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first() . $request->get('asset_code'));

        $validated = $validation->validated();

        $new_identity_codes = Material::generateIdentityCodes($validated->get('asset_code'), $validated->get('number'));

        $materials = [];
        if ($new_identity_codes->isNotEmpty()) {
            $new_identity_codes->each(function ($new_identity_code) use ($validated, &$materials) {
                $materials[] = Material::with([])->create($validated->merge(['identity_code' => $new_identity_code,])->except('number')->toArray());
            });
        }

        return JsonResponseFacade::OK('赋码成功');
    }

    /**
     * 批量修改
     * @param Request $request
     * @return JsonResponse
     */
    final public function putBatch(Request $request)
    {
        $update_datum = [];
        if ($request->get('change_asset_code') == 1) {
            $update_datum['asset_code'] = $request->get('asset_code', '') ?? '';
        }

        if ($request->get('change_fixed_asset_code') == 1) {
            $update_datum['fixed_asset_code'] = $request->get('fixed_asset_code', '') ?? '';
        }

        if ($request->get('change_source_type') == 1) {
            $update_datum['source_type'] = $request->get('source_type', '') ?? '';
        }

        if ($request->get('change_source_name') == 1) {
            $update_datum['source_name'] = $request->get('source_name', '') ?? '';
        }

        if ($request->get('change_workshop_unique_code') == 1) {
            $update_datum['workshop_unique_code'] = $request->get('workshop_unique_code', '') ?? '';
        }

        if ($request->get('change_station_unique_code') == 1) {
            $update_datum['station_unique_code'] = $request->get('station_unique_code', '') ?? '';
        }

        if ($request->get('change_work_area_unique_code') == 1) {
            $update_datum['work_area_unique_code'] = $request->get('work_area_unique_code', '') ?? '';
        }

        Material::with([])->whereIn('identity_code', explode(',', $request->get('identity_codes')))->update($update_datum);

        return JsonResponseFacade::updated();
    }
}
