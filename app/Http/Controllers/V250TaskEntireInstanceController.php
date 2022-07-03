<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Model\EntireInstanceLog;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\FixWorkflowRecord;
use App\Model\OverhaulEntireInstance;
use App\Model\PartInstance;
use App\Model\V250TaskEntireInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class V250TaskEntireInstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        //
    }

    /**
     * 检修分配判断
     * @param Request $request
     * @param $sn
     * @return array|int[]
     */
    final public function judgeService(Request $request, $sn)
    {
        try {
            $selected_for_fix_mission = $request->get('selected_for_fix_misson');
            foreach ($selected_for_fix_mission as $entire_instance_identity_code) {
                if (DB::table('v250_task_entire_instances')->where('v250_task_order_sn', $sn)->where('entire_instance_identity_code', $entire_instance_identity_code)->where('fixer_id', '!=', 0)->exists()) {
                    return ['code' => 0, 'msg' => $entire_instance_identity_code . '已分配检修任务'];
                }
            }
            return ['code' => 1, 'msg' => '选择成功'];
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 检修分配
     * @param Request $request
     * @param $sn
     * @return mixed
     */
    final public function storeService(Request $request, $sn)
    {
        try {
            $selected_for_fix_misson = $request->get('selected_for_fix_misson');
            $accountId = $request->get('selAccountId');
            DB::table('v250_task_entire_instances')->where('v250_task_order_sn', $sn)->whereIn('entire_instance_identity_code', $selected_for_fix_misson)->update(['fixer_id' => $accountId]);
            return JsonResponseFacade::created([], '分配成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加待出所单->设备状态判断
     * @param Request $request
     * @param $sn
     * @return array|int[]
     */
    final public function judgeWorkshopOut(Request $request, $sn)
    {
        try {
            $selected_for_workshop_out = $request->get('selected_for_workshop_out');
            foreach ($selected_for_workshop_out as $entire_instance_identity_code) {
                if (DB::table('entire_instances')->where('identity_code', $entire_instance_identity_code)->whereNotIn('status', ['FIXED','BUY_IN'])->exists()) {
                    return ['code' => 0, 'msg' => $entire_instance_identity_code . '状态必须为成品或新购'];
                }
            }
            $serial_number = DB::table('v250_workshop_stay_out')->orderByDesc('id')->value('serial_number');
            if ($serial_number) {
                $serial_number_8 = str_pad(substr($serial_number, -8) + 1, 8, 0, STR_PAD_LEFT);
                $serial_number = env('ORGANIZATION_CODE') . $serial_number_8;
                $GLOBALS['uq'] = $serial_number;
            } else {
                $serial_number = env('ORGANIZATION_CODE') . '00000001';
                $GLOBALS['uq'] = $serial_number;
            }
            DB::transaction(function () use ($sn, $selected_for_workshop_out, $serial_number) {
                DB::table('v250_workshop_stay_out')->insert([
                    'created_at' => date('Y-m-d H:i:s'),
                    'v250_task_orders_serial_number' => $sn,
                    'serial_number' => $serial_number,
                    'expiring_at' => DB::table('v250_task_orders')->where('serial_number', $sn)->value('expiring_at'),
                    'status' => 'PROCESSING'
                ]);
                foreach ($selected_for_workshop_out as $entire_instance_identity_code) {
                    DB::table('v250_workshop_out_entire_instances as wi')
                        ->join('v250_workshop_stay_out as wo', 'wo.serial_number', 'wi.v250_workshop_stay_out_serial_number')
                        ->where('wo.status', '!=', 'DONE')
                        ->where('wi.entire_instance_identity_code', $entire_instance_identity_code)
                        ->delete();
                    $entireInstanceSerial_number = DB::table('entire_instances')->where('identity_code', $entire_instance_identity_code)->value('serial_number');
                    DB::table('v250_workshop_out_entire_instances')->insert([
                        'created_at' => date('Y-m-d H:i:s'),
                        'v250_task_orders_serial_number' => $sn,
                        'v250_workshop_stay_out_serial_number' => $serial_number,
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'entire_instance_serial_number' => $entireInstanceSerial_number,
                    ]);
                }
                // 删除空待出所单
//                $v250_workshop_stay_out_serial_numbers = DB::table('v250_workshop_stay_out as wo')
//                    ->Join('v250_workshop_out_entire_instances as wi', 'wo.serial_number', 'wi.v250_workshop_stay_out_serial_number')
//                    ->where('wo.status', '!=', 'DONE')
//                    ->distinct()
//                    ->get(['v250_workshop_stay_out_serial_number']);
//                foreach ($v250_workshop_stay_out_serial_numbers as $v250_workshop_stay_out_serial_numbe) {
////                    DB::table('v250_workshop_stay_out')->where('serial_number', $v250_workshop_stay_out_serial_numbe->v250_workshop_stay_out_serial_number)->delete();
//                }
            });
            return ['code' => 1, 'msg' => '添加成功', 'sn' => $serial_number];
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
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
    }

    /**
     * 删除设备
     * @param Request $request
     * @param string $sn
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteItems(Request $request, string $sn)
    {
        try {
            EntireInstanceLock::freeLocks(
                $request->get('identityCodes'),
                ['NEW_STATION'],
                function () use ($request, $sn) {
                    $v250_task_entire_instances = V250TaskEntireInstance::with([])->where('v250_task_order_sn', $sn)->whereIn('entire_instance_identity_code', $request->get('identityCodes'))->where('is_utilise_used', false)->get();  # 新设备
                    $utilise_used_v250_task_entire_instances = V250TaskEntireInstance::with([])->where('v250_task_order_sn', $sn)->whereIn('entire_instance_identity_code', $request->get('identityCodes'))->where('is_utilise_used', true)->get();  # 利旧设备

                    V250TaskEntireInstance::with([])->whereIn('id', $v250_task_entire_instances->pluck('id')->toArray())->delete();  # 删掉新设备
                    EntireInstance::with([])->whereIn('identity_code',$utilise_used_v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->update(['v250_task_order_sn'=>'']);  # 去掉任务编号（利旧设备）

                    EntireInstanceLog::with([])->whereIn('entire_instance_identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除日志（新设备）
                    EntireInstance::with([])->whereIn('identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除设备（新设备）
                    PartInstance::with([])->whereIn('entire_instance_identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除部件（新设备）
                    OverhaulEntireInstance::with([])->whereIn('entire_instance_identity_code',$v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除 检修分配（新设备）
                    OverhaulEntireInstance::with([])->whereIn('entire_instance_identity_code',$utilise_used_v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->update(['v250_task_order_sn'=>'']);  # 删除任务标记 检修分配（利旧设备）

                    DB::table('v250_workshop_in_entire_instances')->whereIn('entire_instance_identity_code', $request->get('identityCodes'))->delete();
                    DB::table('v250_workshop_out_entire_instances')->whereIn('entire_instance_identity_code', $request->get('identityCodes'))->delete();
                    $fix_workflows = FixWorkflow::with([])->whereIn('entire_instance_identity_code', $request->get('identityCodes'))->get();
                    $fix_workflow_processes = FixWorkflowProcess::with([])->whereIn('fix_workflow_serial_number', $fix_workflows->pluck('serial_number')->toArray())->get();
                    FixWorkflowRecord::with([])->whereIn('fix_workflow_process_serial_number', $fix_workflow_processes->pluck('serial_number')->toArray())->forceDelete();
                    FixWorkflowProcess::with([])->where('id', $fix_workflow_processes->pluck('id')->toArray())->forceDelete();
                    Fixworkflow::with([])->where('id', $fix_workflows->pluck('id')->toArray())->forceDelete();
                });

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
