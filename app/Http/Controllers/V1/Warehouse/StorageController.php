<?php

namespace App\Http\Controllers\V1\Warehouse;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    final public function postScanInBatch()
    {
        try {
            $now = Carbon::now()->format('Y-m-d');

            $request = TextHelper::parseJson(file_get_contents('php://input'));
            if ($request == null) return response()->make('数据为空', 404);

            $identityCodes = array_key_exists('identity_codes', $request) ? $request['identity_codes'] : null;
            $rfidCodes = array_key_exists('rfid_codes', $request) ? $request['rfid_codes'] : null;

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
                    $warehouseStorageWithIdentityCodes[$entireInstance->identity_code] = (array)$entireInstance;
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
                    $warehouseStorageWithRfidCodes[$entireInstance->rfid_code] = (array)$entireInstance;
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

            if (count($warehouseStorageWithIdentityCodes) + count($warehouseStorageWithRfidCodes) == 0) return response()->make('数据为空', 403);

                return response()->make('导入成功', 201);
        } catch (\Exception $exception) {
            return response()->make($exception->getMessage() . ':' . $exception->getFile() . ':' . $exception->getLine());
        }
    }
}
