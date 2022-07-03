<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\OrganizationFacade;
use App\Model\Material;
use App\Model\MaterialStorehouseOrder;
use App\Model\MaterialStorehouseOrderItem;
use App\Model\MaterialType;
use App\Model\Position;
use App\Validations\Web\MaterialAppendValidation;
use App\Validations\Web\MaterialScanValidation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class MaterialStorehouseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Factory|Application|View
     */
    final public function index()
    {
        if (request()->ajax()) {
            $material_storehouse_orders = (new MaterialStorehouseOrder)->ReadMany()->get();

            return JsonResponseFacade::dict(['material_storehouse_orders' => $material_storehouse_orders,]);
        } else {
            $workshops = OrganizationFacade::getWorkshops();
            $stations = OrganizationFacade::getStationsBySceneWorkshop();
            $work_areas = OrganizationFacade::getWorkAreaByWorkshop();

            return view('MaterialStorehouseOrder.index', [
                'workshops' => $workshops,
                'stations' => $stations,
                'work_areas' => $work_areas,
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($id)
    {
        //
    }

    /**
     * 入库页面
     * @return Factory|Application|View
     */
    final public function getIn()
    {
        $storehouses = OrganizationFacade::getStorehouses();
        $areas = OrganizationFacade::getAreasByStorehouse();

        return view('MaterialStorehouseOrder.in', [
            'storehouses' => $storehouses,
            'areas' => $areas,
        ]);
    }

    /**
     * 入库
     * @param Request $request
     * @return JsonResponse
     */
    final public function postIn(Request $request): JsonResponse
    {
        DB::beginTransaction();
        if (!$request->get('position_unique_code')) return JsonResponseFacade::errorValidate('请选择仓库-位');
        if (!Position::with([])->where('unique_code', $request->get('position_unique_code'))->exists())
            return JsonResponseFacade::errorValidate('仓库位置不存在');

        $materials = Material::with([])
            ->whereNotNull('tmp_scan')
            ->where('tmp_scan_processor_id', session('account.id'))
            ->where('operation_direction', 'IN')
            ->get();
        if ($materials->isEmpty()) return JsonResponseFacade::errorValidate('没有要入库的材料');

        $material_storehouse_order = MaterialStorehouseOrder::with([])
            ->create([
                'serial_number' => Str::uuid(),
                'operator_id' => session('account.id'),
                'direction' => 'IN',
            ]);

        $success_count = 0;
        $materials->each(function ($material) use (&$success_count, $request, $material_storehouse_order) {
            $material->fill([
                'tmp_scan' => null,
                'tmp_scan_processor_id' => 0,
                'operation_direction' => '',
                'workshop_unique_code' => '',
                'station_unique_code' => '',
                'work_area_unique_code' => '',
                'position_unique_code' => $request->get('position_unique_code'),
                'status' => 'STORED_IN',
            ])
                ->saveOrFail();

            MaterialStorehouseOrderItem::with([])
                ->create([
                    'material_storehouse_order_serial_number' => $material_storehouse_order->serial_number,
                    'material_identity_code' => $material->identity_code,
                    'workshop_unique_code' => '',
                    'station_unique_code' => '',
                    'work_area_unique_code' => '',
                    'position_unique_code' => $request->get('position_unique_code'),
                ]);
            $success_count++;
        });

        DB::commit();

        return JsonResponseFacade::OK("成功入库：{$success_count}");
    }

    /**
     * 出库页面
     * @return Factory|Application|View
     */
    final public function getOut()
    {
        $storehouses = OrganizationFacade::getStorehouses();
        $areas = OrganizationFacade::getAreasByStorehouse();
        $workshops = OrganizationFacade::getWorkshops();
        $stations = OrganizationFacade::getStationsBySceneWorkshop();
        $work_areas = OrganizationFacade::getWorkAreaByWorkshop();

        return view('MaterialStorehouseOrder.out', [
            'storehouses' => $storehouses,
            'areas' => $areas,
            'workshops' => $workshops,
            'stations' => $stations,
            'work_areas' => $work_areas,
        ]);
    }

    /**
     * 出库
     * @param Request $request
     * @return JsonResponse
     */
    final public function postOut(Request $request): JsonResponse
    {
        $workshop_unique_code = $request->get('workshop_unique_code', '') ?: '';
        $station_unique_code = $request->get('station_unique_code', '') ?: '';
        $work_area_unique_code = $request->get('work_area_unique_code', '') ?: '';
        if (empty($workshop_unique_code) && empty($station_unique_code) && empty($work_area_unique_code))
            return JsonResponseFacade::errorValidate('车间、车站、工区必选其中一项');

        $materials = Material::with([])
            ->whereNotNull('tmp_scan')
            ->where('tmp_scan_processor_id', session('account.id'))
            ->where('operation_direction', 'OUT')
            ->get();
        if ($materials->isEmpty()) return JsonResponseFacade::errorValidate('没有要出库的材料');

        $material_storehouse_order = MaterialStorehouseOrder::with([])
            ->create([
                'serial_number' => Str::uuid(),
                'operator_id' => session('account.id'),
                'direction' => 'OUT',
            ]);

        $success_count = 0;
        $materials->each(function ($material) use (
            &$success_count,
            $request,
            $material_storehouse_order,
            $workshop_unique_code,
            $station_unique_code,
            $work_area_unique_code
        ) {
            $material->fill([
                'tmp_scan' => null,
                'tmp_scan_processor_id' => 0,
                'operation_direction' => '',
                'workshop_unique_code' => $workshop_unique_code,
                'station_unique_code' => $station_unique_code,
                'work_area_unique_code' => $work_area_unique_code,
                'position_unique_code' => '',
                'status' => 'STORED_OUT',
            ])
                ->saveOrFail();

            MaterialStorehouseOrderItem::with([])
                ->create([
                    'material_storehouse_order_serial_number' => $material_storehouse_order->serial_number,
                    'material_identity_code' => $material->identity_code,
                    'workshop_unique_code' => $workshop_unique_code,
                    'station_unique_code' => $station_unique_code,
                    'work_area_unique_code' => $work_area_unique_code,
                    'position_unique_code' => '',
                ]);
            $success_count++;
        });

        return JsonResponseFacade::OK("成功出库：{$success_count}");
    }

    /**
     * 扫码添加器材
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    final public function postScan(Request $request): JsonResponse
    {
        $validation = (new MaterialScanValidation($request));
        $v = $validation->check()->after(function ($validator) use ($request) {
            if (
            Material::with([])
                ->where('identity_code', $request->get('identity_code'))
                ->where('tmp_scan', true)
                ->exists()
            )
                $validator->errors()->add('identity_code', '已经被扫码');
        });
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

        $validated = $validation->validated();

        $material = Material::with([])->where('identity_code', $validated->get('identity_code'))->firstOrFail();
        $material
            ->fill([
                'tmp_scan' => now(),
                'tmp_scan_processor_id' => session('account.id'),
                'operation_direction' => $validated->get('operation_direction'),
            ])
            ->saveOrFail();

        return JsonResponseFacade::OK('添加成功');
    }

    /**
     * 添加扫码
     * @param Request $request
     * @return JsonResponse
     */
    final public function postAppend(Request $request): JsonResponse
    {
        $validation = (new MaterialAppendValidation($request));
        $v = $validation->check();
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

        $validated = $validation->validated();

        $material_type = (new MaterialType)->ReadOneByIdentityCode($validated->get('material_type_identity_code'))->first();
        if (!$material_type) return JsonResponseFacade::errorEmpty("材料类型不存在：{$validated->get('material_type_identity_code')}");

        $materials = Material::with([])
            ->where('material_type_identity_code', $validated->get('material_type_identity_code'))
            ->whereNull('tmp_scan')
            ->where('status', 'TAGGED')
            ->orderBy('id')
            ->limit($validated->get('number'))
            ->get();
        if ($materials->count() < $validated->get('number'))
            return JsonResponseFacade::errorForbidden("{$material_type->name}不满足：{$validated->get('number')}{$material_type->unit}");

        $materials->each(function ($material) use ($validated) {
            $material
                ->fill([
                    'tmp_scan' => now(),
                    'tmp_scan_processor_id' => session('account.id'),
                    'operation_direction' => $validated->get('operation_direction'),
                ])
                ->saveOrFail();
        });

        return JsonResponseFacade::dict(['materials' => $materials,]);
    }

    /**
     * 删除出入库扫码
     * @param Request $request
     * @param string $identity_code
     * @return JsonResponse
     */
    final public function deleteScan(Request $request, string $identity_code): JsonResponse
    {
        Material::with([])->where('identity_code', $identity_code)->update(['tmp_scan' => false, 'tmp_scan_processor_id' => 0, 'operation_direction' => '',]);

        return JsonResponseFacade::deleted();
    }
}
