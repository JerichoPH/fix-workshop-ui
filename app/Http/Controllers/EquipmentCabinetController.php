<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\CombinationLocationRow;
use App\Model\EntireInstance;
use App\Model\EquipmentCabinet;
use App\Model\Maintain;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use \Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\View\View;
use \Throwable;

class EquipmentCabinetController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Factory|Application|RedirectResponse|View
     */
    final public function index()
    {
        try {
            $equipment_cabinets = ModelBuilderFacade::init(
                request(),
                EquipmentCabinet::with([
                    'EntireInstance',
                    'Station',
                ]),
                ['equipment_cabinet_unique_code',]
            );
            if (request()->ajax()) return JsonResponseFacade::data(['equipment_cabinets' => $equipment_cabinets->all()]);

            $current_equipment_cabinet_unique_code = request('equipment_cabinet_unique_code', $equipment_cabinets->all()->first() ? $equipment_cabinets->all()->first()->unique_code : '');
            $combination_locations = CombinationLocationRow::with([])->where('equipment_cabinet_unique_code', $current_equipment_cabinet_unique_code)->orderBy('row')->orderBy('column')->get();

            return view('EquipmentCabinet.index', [
                'equipment_cabinets' => $equipment_cabinets->pagination(),
                'equipment_cabinet_room_types_as_json' => json_encode(EquipmentCabinet::$ROOM_TYPES, 256),
                'combination_locations' => $combination_locations,
                'current_equipment_cabinet_unique_code' => $current_equipment_cabinet_unique_code,
            ]);
        } catch (Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    final public function create()
    {
        return view('EquipmentCabinet.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */
    final public function store(Request $request)
    {
        try {
            if (!$request->get('name')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('名称不能为空');
                return back()->withInput()->with('danger', '名称不能为空');
            }
            if (!$request->get('room_type')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('房间类型不能为空');
                return back()->withInput()->with('danger', '房间类型不能为空');
            }
            if (!$request->get('maintain_station_unique_code')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('所属车站不能为空');
                return back()->withInput()->with('danger', '所属车站不能为空');
            }
            $row = intval($request->get('row', 0) ?? 0);
            if (!$row) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('排必须是正整数');
                return back()->withInput()->with('danger', '排必须是正整数');
            }

            if (EquipmentCabinet::with([])->where('name', $request->get('name'))->where('maintain_station_unique_code', $request->get('maintain_station_unique_code'))->first()) {
                if (!$request->ajax()) return JsonResponseFacade::errorForbidden('名称被占用');
                return back()->withInput()->with('danger', '名称被占用');
            }

            $station = Maintain::with([])->where('unique_code', $request->get('maintain_station_unique_code'))->first();
            if (!$station) return JsonResponseFacade::errorForbidden('所选车站不存在');

            $title = $row . '排' . $request->get('name') . '柜';
            $unique_code = EquipmentCabinet::generateUniqueCode($request->get('room_type'), $request->get('maintain_station_unique_code'), $row);

            $equipment_cabinet = EquipmentCabinet::with([])->create(
                array_merge(
                    ['title' => $title, 'unique_code' => $unique_code,],
                    $request->all()
                )
            );

            return JsonResponseFacade::created(['equipment_cabinet' => $equipment_cabinet]);
        } catch (Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '异常错误');
        }
    }

    /**
     * Display the specified resource.
     * @param int $id
     * @return RedirectResponse
     */
    final public function show(int $id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([
                'EntireInstance',
                'Station',
                'CombinationLocations',
            ])->where('id', $id)->firstOrFail();

            return JsonResponseFacade::data(['equipment_cabinet' => $equipment_cabinet]);
        } catch (\Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            Commonfacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }


    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Factory|Application|RedirectResponse|View
     */
    final public function edit(int $id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([
                'EntireInstance',
                'Station',
                'CombinationLocations',
            ])
                ->where('id', $id)
                ->firstOrFail();

            if (request()->ajax()) return JsonResponseFacade::data(['equipment_cabinet' => $equipment_cabinet]);
            return view('EquipmentCabinet.edit', [
                'equipment_cabinet' => $equipment_cabinet,
            ]);
        } catch (\Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            Commonfacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     * @throws Throwable
     */
    final public function update(Request $request, $id)
    {
        try {
            if (!$request->get('name')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('名称不能为空');
                return back()->withInput()->with('danger', '名称不能为空');
            }
            if (!$request->get('room_type')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('房间类型不能为空');
                return back()->withInput()->with('danger', '房间类型不能为空');
            }
            if (!$request->get('maintain_station_unique_code')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('所属车站不能为空');
                return back()->withInput()->with('danger', '所属车站不能为空');
            }
            $row = intval($request->get('row', 0) ?? 0);
            if (!$row) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('排必须是正整数');
                return back()->withInput()->with('danger', '排必须是正整数');
            }

            if (EquipmentCabinet::with([])->where('id', '<>', $id)->where('name', $request->get('name'))->where('maintain_station_unique_code', $request->get('maintain_station_unique_code'))->first()) {
                if (!$request->ajax()) return JsonResponseFacade::errorForbidden('名称被占用');
                return back()->withInput()->with('danger', '名称被占用');
            }

            $title = $row . '排' . $request->get('name') . '柜';

            $equipment_cabinet = EquipmentCabinet::with([
                'EntireInstance',
                'Station',
                'CombinationLocations',
            ])
                ->where('id', $id)
                ->firstOrFail();
            $equipment_cabinet->fill(
                array_merge(
                    ['title' => $title,],
                    $request->all()
                )
            )->saveOrFail();

            return JsonResponseFacade::updated(['equipment_cabinet' => $equipment_cabinet]);
        } catch (\Exception $e) {
            if ($request->ajax()) return JsonResponseFacade::errorException($e);
            Commonfacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return RedirectResponse
     */
    final public function destroy(int $id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([])->where('id', $id)->firstOrFail();
            if (!$equipment_cabinet->unique_code) return JsonResponseFacade::errorForbidden('机柜位置代码参数错误');
            $entire_instances_S = EntireInstance::with([])->where('equipment_cabinet_unique_code', $equipment_cabinet->unique_code)->get();
            if ($entire_instances_S->isNotEmpty()) return JsonResponseFacade::errorForbidden('拒绝删除，该机柜下绑定了以下设备：<br>' . implode("<br>", $entire_instances_S->pluck('identity_code')->toArray()));
            $combination_locations = CombinationLocation::with([])->where('equipment_cabinet_unique_code', $equipment_cabinet->unique_code)->get();
            if ($combination_locations->isNotEmpty()) return JsonResponseFacade::errorForbidden('该机柜下存在位置，请先删除位置');
            $equipment_cabinet->forceDelete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            if (request()->ajax()) return JsonResponseFacade::errorEmpty();
            return back()->with('danger', '数据不存在');
        } catch (Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 获取绑定设备列表
     * @param int $equipment_cabinet_id
     * @return mixed
     */
    final public function getBindEntireInstance(int $equipment_cabinet_id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([])->where('id', $equipment_cabinet_id)->firstOrFail();

            $entire_instances = EntireInstance::with([])
                ->where('category_unique_code', 'like', 'S%')
                ->where('maintain_station_name', $equipment_cabinet->Station->name)
                ->get();

            return JsonResponseFacade::data([
                'entire_instances' => $entire_instances,
                'current_entire_instance_identity_code' => $equipment_cabinet->entire_instance_identity_code,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 绑定设备到机柜
     * @param Request $request
     * @param int $equipment_cabinet_id
     * @return mixed
     */
    final public function postBindEntireInstance(Request $request, int $equipment_cabinet_id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([])->where('id', $equipment_cabinet_id)->firstOrFail();
            $equipment_cabinet->fill(['entire_instance_identity_code' => $request->get('entireInstanceIdentityCode')])->saveOrFail();
            return JsonResponseFacade::created(['equipment_cabinet' => $equipment_cabinet], '绑定成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 解绑机柜设备
     * @param int $equipment_cabinet_id
     */
    final public function deleteBindEntireInstance(int $equipment_cabinet_id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([])->where('id', $equipment_cabinet_id)->firstOrFail();
            $equipment_cabinet->fill(['entire_instance_identity_code' => ''])->saveOrFail();
            return JsonResponseFacade::deleted([], '解绑成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
