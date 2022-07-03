<?php

namespace App\Http\Controllers\Warehouse;

use App\Facades\AccountFacade;
use App\Facades\CodeFacade;
use App\Http\Controllers\Controller;
use App\Services\AccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function index()
    {
        $accountId = session('account.id');

        $sql = "select count(em.name) as count,
       em.name,
       em2.name as parent_name
from entire_instances ei
         join accounts a on a.work_area = ei.work_area
         join entireModels em on em.unique_code = ei.entire_model_unique_code
         left join entireModels em2 on em2.unique_code = em.parent_unique_code
where ei.deleted_at is null
  and ei.warehouse_name = '成品区'
  and a.id = {$accountId}
  and em.deleted_at is null
  and em2.deleted_at is null";
        if (request('entireModelName')) $sql .= " and em.name='" . request('entireModelName') . "'";
        $sql .= " group by em.name, em2.name";

        # 制作空数据
        list($entireModelCount, $subModelCount, $comboModelCount) = AccountFacade::getWorkArea()->getModels();

        foreach (DB::select($sql) as $row) {
            $entireModelCount[$row->parent_name] += $row->count;
            $subModelCount[$row->name] += $row->count;
            $comboModelCount[$row->parent_name][$row->name] += $row->count;
        }
        $subModelNames = collect($subModelCount)->keys()->toArray();

        return view('Warehouse.Storage.index', [
            'entireModelCount' => $entireModelCount,
            'subModelCount' => $subModelCount,
            'subModelNames' => TextHelper::toJson($subModelNames),
            'comboModelCount' => $comboModelCount,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create()
    {
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
            $now = Carbon::now()->format('Y-m-d');
            $identityCodes = collect($request->all())->keys()->toArray();

            foreach (DB::table('warehouse_storage_batch_reports')->whereIn('identity_code', $identityCodes)->get() as $item) {
                DB::table('entire_instances')->where('identity_code', $item->identity_code)->update([
                    'warehouse_name' => '成品区',
                    'location_unique_code' => $request->all()[$item->identity_code],
                    'maintain_station_name' => $item->maintain_station_name,
                    'maintain_location_code' => $item->maintain_location_code,
                    'to_direction' => $item->to_direction,
                    'crossroad_number' => $item->crossroad_number,
                    'traction' => $item->traction,
                    'open_direction' => $item->open_direction,
                    'said_rod' => $item->said_rod,
                    'line_name' => $item->line_name,
                    'in_warehouse_time' => $now,
                ]);
                DB::table('warehouse_storage_batch_reports')->where('identity_code', $item->identity_code)->delete();
            }

            return response()->make('入库成功');
        } catch (\Exception $exception) {
            return response()->make($exception->getMessage() . ':' . $exception->getFile() . ':' . $exception->getLine());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $subModelName
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function show($subModelName)
    {
        $isSub = DB::table('entire_models')->where('deleted_at', null)->where('is_sub_model', true)->where('name', $subModelName)->first(['id']);
        $isPart = DB::table('part_models')->where('deleted_at', null)->where('name', $subModelName)->first(['id']);

        if ($isSub != null && $isPart == null) {
            $entireInstances = DB::table('entire_instances')
                ->select([
                    'entire_instances.identity_code',
                    'entire_instances.updated_at',
                    'entire_instances.warehouse_name',
                    'entire_instances.location_unique_code',
                    'entire_instances.maintain_station_name',
                    'entire_instances.maintain_location_code',
                    'entire_instances.crossroad_number',
                    'entire_instances.open_direction',
                    'entire_instances.line_name',
                    'entire_instances.said_rod',
                    'entire_instances.to_direction',
                    'entire_instances.traction',
                ])
                ->join('entireModels', 'entireModels.unique_code', '=', 'entire_instances.entire_model_unique_code')
                ->where('entire_instances.deleted_at', null)
                ->where('entireModels.deleted_at', null)
                ->where('entireModels.name', $subModelName)
                ->where('entire_instances.status', 'FIXED')
                ->where('entire_instances.work_area', AccountFacade::getWorkArea()->workArea)
                ->where('entire_instances.warehouse_name', '成品区')
                ->orderByDesc('entire_instances.updated_at')
                ->paginate();
        } else {
            $entireInstances = DB::table('entire_instances')
                ->select([
                    'entire_instances.identity_code',
                    'entire_instances.updated_at',
                    'entire_instances.warehouse_name',
                    'entire_instances.location_unique_code',
                    'entire_instances.maintain_station_name',
                    'entire_instances.maintain_location_code',
                    'entire_instances.crossroad_number',
                    'entire_instances.open_direction',
                    'entire_instances.line_name',
                    'entire_instances.said_rod',
                    'entire_instances.to_direction',
                    'entire_instances.traction',
                ])
                ->join('part_instances', 'part_instances.entire_instance_identity_code', '=', 'entire_instances.identity_code')
                ->join('part_models', 'part_models.unique_code', '=', 'part_instances.part_model_unique_code')
                ->where('entire_instances.deleted_at', null)
                ->where('part_instances.deleted_at', null)
                ->where('part_models.name', $subModelName)
                ->where('entire_instances.status', 'FIXED')
                ->where('entire_instances.work_area', AccountFacade::getWorkArea()->workArea)
                ->where('entire_instances.warehouse_name', '成品区')
                ->orderByDesc('entire_instances.updated_at')
                ->paginate();
        }

        return view('Warehouse.Storage.show', ['entireInstances' => $entireInstances]);
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
    final public function destroy($id = null)
    {
        if ($id == null) {
            # 清空
            DB::table('warehouse_storage_batch_reports')->truncate();
            return response()->make('清空成功');
        } else {
            # 删除单独
            DB::table('warehouse_storage_batch_reports')->where('id', $id)->delete();
            return response()->make('删除成功');
        }
    }

    final public function getScanInBatch()
    {
        $entireInstances = DB::table('warehouse_storage_batch_reports')->orderByDesc('id')->paginate();
        return view('Warehouse.Storage.scanInBatch', ['entireInstances' => $entireInstances]);
    }

    final public function postScanInBatch(Request $request)
    {
        try {
            $now = Carbon::now()->format('Y-m-d');

            $identityCodes = $request->get('identity_codes', null);
            $rfidCodes = $request->get('rfid_codes', null);

            $warehouseStorageWithIdentityCodes = [];
            $warehouseStorageWithRfidCodes = [];
            if ($identityCodes) {
                # 数据去重
                $i = collect($identityCodes)->pluck('identity_code')->toArray();
                $i = array_unique(array_merge(DB::table('warehouse_storage_batch_reports')->whereIn('identity_code', $i)->pluck('identity_code')->toArray(), $i));
                $entireInstances = DB::table('entire_instances')
                    ->select([
                        'entire_instances.identity_code',
                        'entire_instances.factory_device_code',
                        'entire_instances.serial_number',
                        'entire_instances.rfid_code',
                        'entire_instances.category_unique_code',
                        'entire_instances.category_name',
                        'entire_instances.entire_model_unique_code',
                        'entireModels.name as entire_model_name',
                    ])
                    ->join('entireModels', 'entireModels.unique_code', '=', 'entire_instances.entire_model_unique_code')
                    ->where('entire_instances.deleted_at', null)
                    ->whereIn('entire_instances.identity_code', $i)
                    ->where('entireModels.deleted_at', null)
                    ->get();

                foreach ($entireInstances as $entireInstance) {
                    $warehouseStorageWithIdentityCodes[$entireInstance->identity_code] = (array) $entireInstance;
                    $warehouseStorageWithIdentityCodes[$entireInstance->identity_code]['created_at'] = $now;
                    $warehouseStorageWithIdentityCodes[$entireInstance->identity_code]['updated_at'] = $now;
                }

                foreach ($identityCodes as $identityCode) {
                    $warehouseStorageWithIdentityCodes[$identityCode['identity_code']]['maintain_station_name'] = $identityCode['maintain_station_name'];
                    $warehouseStorageWithIdentityCodes[$identityCode['identity_code']]['maintain_location_code'] = $identityCode['maintain_location_code'];
                    $warehouseStorageWithIdentityCodes[$identityCode['identity_code']]['to_direction'] = $identityCode['to_direction'];
                    $warehouseStorageWithIdentityCodes[$identityCode['identity_code']]['crossroad_number'] = $identityCode['crossroad_number'];
                    $warehouseStorageWithIdentityCodes[$identityCode['identity_code']]['traction'] = $identityCode['traction'];
                    $warehouseStorageWithIdentityCodes[$identityCode['identity_code']]['open_direction'] = $identityCode['open_direction'];
                    $warehouseStorageWithIdentityCodes[$identityCode['identity_code']]['said_rod'] = $identityCode['said_rod'];
                    $warehouseStorageWithIdentityCodes[$identityCode['identity_code']]['line_name'] = $identityCode['line_name'];
                }
            }

            if ($rfidCodes) {
                $r = collect($rfidCodes)->pluck('rfid_code')->toArray();
                $r = array_unique(array_merge(DB::table('warehouse_storage_batch_reports')->whereIn('rfid_code', $r)->pluck('rfid_code')->toArray(), $r));
                $entireInstances = DB::table('entire_instances')
                    ->select([
                        'entire_instances.identity_code',
                        'entire_instances.factory_device_code',
                        'entire_instances.serial_number',
                        'entire_instances.rfid_code',
                        'entire_instances.category_unique_code',
                        'entire_instances.category_name',
                        'entire_instances.entire_model_unique_code',
                        'entireModels.name as entire_model_name',
                    ])
                    ->join('entireModels', 'entireModels.unique_code', '=', 'entire_instances.entire_model_unique_code')
                    ->where('entire_instances.deleted_at', null)
                    ->whereIn('entire_instances.rfid_code', $r)
                    ->where('entireModels.deleted_at', null)
                    ->get();

                foreach ($entireInstances as $entireInstance) {
                    $warehouseStorageWithRfidCodes[$entireInstance->rfid_code] = (array) $entireInstance;
                    $warehouseStorageWithRfidCodes[$entireInstance->rfid_code]['created_at'] = $now;
                    $warehouseStorageWithRfidCodes[$entireInstance->rfid_code]['updated_at'] = $now;
                }

                foreach ($rfidCodes as $rfidCode) {
                    $warehouseStorageWithRfidCodes[$rfidCode['rfid_code']]['maintain_station_name'] = $rfidCode['maintain_station_name'];
                    $warehouseStorageWithRfidCodes[$rfidCode['rfid_code']]['maintain_location_code'] = $rfidCode['maintain_location_code'];
                    $warehouseStorageWithRfidCodes[$rfidCode['rfid_code']]['to_direction'] = $rfidCode['to_direction'];
                    $warehouseStorageWithRfidCodes[$rfidCode['rfid_code']]['crossroad_number'] = $rfidCode['crossroad_number'];
                    $warehouseStorageWithRfidCodes[$rfidCode['rfid_code']]['traction'] = $rfidCode['traction'];
                    $warehouseStorageWithRfidCodes[$rfidCode['rfid_code']]['open_direction'] = $rfidCode['open_direction'];
                    $warehouseStorageWithRfidCodes[$rfidCode['rfid_code']]['said_rod'] = $rfidCode['said_rod'];
                    $warehouseStorageWithRfidCodes[$rfidCode['rfid_code']]['line_name'] = $rfidCode['line_name'];
                }
            }

            if (!empty($warehouseStorageWithIdentityCodes)) DB::table('warehouse_storage_batch_reports')->insert($warehouseStorageWithIdentityCodes);
            if (!empty($warehouseStorageWithRfidCodes)) DB::table('warehouse_storage_batch_reports')->insert($warehouseStorageWithRfidCodes);

            return response()->make('导入成功', 201);
        } catch (\Exception $exception) {
            return response()->make($exception->getMessage() . ':' . $exception->getFile() . ':' . $exception->getLine());
        }
    }

    final public function getStock()
    {
        return view('');
    }

    final public function postStock()
    {
        return 'postStock';
    }
}
