<?php

namespace App\Http\Controllers\V1\Warehouse;

use App\Facades\CodeFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\PartInstance;
use App\Model\Warehouse;
use Dingo\Api\Routing\Helpers;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;

class PostController extends Controller
{
    use Helpers;

    /**
     * 入库
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function in(Request $request)
    {
        try {
            $request = TextHelper::parseJson(@file_get_contents('php://input'));
            $entireExists = [];
            $partExists = [];
            $noExists = [];
            $location_unique_code = $request['warehouse_location'];
            $position = DB::table('positions')->where('unique_code', $location_unique_code)->exists();
            if (empty($position)) return HttpResponseHelper::errorEmpty('位置编码不存在');

            foreach ($request['data'] as $uniqueCode) {
                $len = strlen($uniqueCode);
                if (substr($uniqueCode, 0, 4) == '130E' || substr($uniqueCode, 0, 4) == '130F') {
                    # rfid_epc
                    $instance = EntireInstance::with([])->where('identity_code', CodeFacade::hexToIdentityCode($uniqueCode))->first();
                    if (empty($instance)) {
                        $noExists[] = $uniqueCode;
                    } else {
                        $entireExists[] = $instance->identity_code;
                    }
                } elseif (substr($uniqueCode, 0, 2) == 'E' && $len == 24) {
                    # rfid_code
                    $instance = EntireInstance::with([])->where('rfid_code', $uniqueCode)->first();
                    if (empty($instance)) {
                        $noExists[] = $uniqueCode;
                    } else {
                        $entireExists[] = $instance->identity_code;
                    }
                } elseif ($len < 14) {
                    # 部件
                    $is_part = true;
                    $instance = PartInstance::with([])->where('identity_code', $uniqueCode)->first();
                    if (empty($instance)) {
                        $noExists[] = $uniqueCode;
                    } else {
                        $partExists[] = $instance->identity_code;
                    }
                } else {
                    # 唯一编号
                    $instance = EntireInstance::with([])->where('identity_code', $uniqueCode)->first();
                    if (empty($instance)) {
                        $noExists[] = $uniqueCode;
                    } else {
                        $entireExists[] = $instance->identity_code;
                    }
                }
            }
            if (empty($entireExists) && empty($partExists)) return HttpResponseHelper::errorEmpty('没有有效的设备器材编号');

            DB::transaction(function () use ($entireExists, $partExists, $location_unique_code) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('IN');
                $warehouse->fill([
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'IN',
                    'account_id' => session('account.id'),
                    'state' => 'END'
                ]);
                $warehouse->save();
                $warehouseId = $warehouse->id;

                $current_time = date('Y-m-d H:i:s');
                if (!empty($entireExists)) {
                    # 整件入库
                    DB::table('entire_instances')
                        ->whereIn('identity_code', $entireExists)
                        ->update([
                            'status' => 'FIXED',
                            'location_unique_code' => $location_unique_code,
                            'is_bind_location' => 1,
                            'in_warehouse_time' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'maintain_station_name' => '',
                            'maintain_workshop_name' => '',
                            'maintain_location_code' => '',
                            'crossroad_number' => '',
                        ]);
                    $warehouse_materials_insert = [];
                    foreach ($entireExists as $entireExist) {
                        $warehouse_materials_insert[] = [
                            'created_at' => $current_time,
                            'updated_at' => $current_time,
                            'material_unique_code' => $entireExist,
                            'warehouse_unique_code' => $warehouseUniqueCode,
                            'material_type' => 'ENTIRE'
                        ];
                    }
                    if (!empty($warehouse_materials_insert)) DB::table('warehouse_materials')->insert($warehouse_materials_insert);
                    # 整件日志
                    $entireInstanceLogs = [];
                    foreach ($entireExists as $unique_code) {
                        $entireInstanceLogs[] = [
                            'created_at' => $current_time,
                            'updated_at' => $current_time,
                            'name' => '入库',
                            'entire_instance_identity_code' => $unique_code,
                            'type' => 4,
                            'url' => "/storehouse/index/{$warehouseId}",
                            'operator_id' => session('account.id'),
                            'station_unique_code' => '',
                        ];
                    }
                    if (!empty($entireInstanceLogs)) EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);
                    DB::table('tmp_materials')->where('material_type', 'ENTIRE')->where('account_id', session('account.id'))->where('state', 'IN')->whereIn('material_unique_code', $entireExists)->delete();
                }

                if (!empty($partExists)) {
                    # 部件入库
                    DB::table('part_instances')
                        ->whereIn('identity_code', $partExists)
                        ->update([
                            'status' => 'FIXED',
                            'location_unique_code' => $location_unique_code,
                            'is_bind_location' => 1,
                            'in_warehouse_time' => date('Y-m-d H:i:s'),
                        ]);
                    $warehouse_materials_insert = [];
                    foreach ($partExists as $partExist) {
                        $warehouse_materials_insert[] = [
                            'created_at' => $current_time,
                            'updated_at' => $current_time,
                            'material_unique_code' => $partExist,
                            'warehouse_unique_code' => $warehouseUniqueCode,
                            'material_type' => 'PART'
                        ];
                    }
                    if (!empty($warehouse_materials_insert)) DB::table('warehouse_materials')->insert($warehouse_materials_insert);
                    DB::table('tmp_materials')->where('material_type', 'PART')->where('account_id', session('account.id'))->where('state', 'IN')->whereIn('material_unique_code', $partExists)->delete();
                }
            });

            $msg = '';
            if ($entireExists) $msg = "\r\n成功入库整件：" . count($entireExists) . "件";
            if ($partExists) $msg .= "\r\n成功入库部件：" . count($partExists) . "件";
            if ($noExists) $msg .= "\r\n数据不存在：" . count($noExists) . "件";

            return HttpResponseHelper::created(ltrim($msg, "\r\n"));
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 盘点
     * @return \Illuminate\Http\JsonResponse
     */
    final public function stock()
    {
        $request = TextHelper::parseJson(file_get_contents('php://input'));
//        $workArea = array_flip(Account::$WORK_AREA)[session('account.work_area')];

        $getBuilder = function (): Builder {
            return DB::table('entire_instances')
                ->select([
                    'entire_instances.identity_code',
                    'entire_instances.rfid_code',
                    'entire_instances.warehouse_name',
                    'entire_instances.location_unique_code',
                ])
                ->where('deleted_at', null)
//                ->where('work_area', $workArea)
                ->where('warehouse_name', '成品区');
        };

        $notExists = $getBuilder()->whereNotIn('rfid_code', $request)->get();
        $total = $getBuilder()->get();

//        return DingResponseFacade::data(['total' => $total, 'not_exists' => $notExists]);
        return HttpResponseHelper::data(['total' => $total, 'not_exists' => $notExists]);
    }
}
