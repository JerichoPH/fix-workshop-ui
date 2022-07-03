<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\OverhaulEntireInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class V250OverhaulEntireInstanceController extends Controller
{
    /**
     * 检修统计设备列表
     * @param Request $request
     */
    final public function index(Request $request)
    {
        $yearMonth = $request->get('year_month');
        $accountId = $request->get('account_id');
        $type = $request->get('type');
        $overhaulEntireInstance = OverhaulEntireInstance::with([
            'EntireInstance',
            'EntireInstance.SubModel',
            'EntireInstance.PartModel',
            'Fixer',
            'Checker',
            'SpotChecker',
        ])
            ->where('fixer_id', $accountId)
            ->whereBetween('allocate_at', [$yearMonth.'-01'.' '. '00:00:00',$yearMonth.'-31'.' '. '00:00:00']);
        $completedEntireInstances = OverhaulEntireInstance::with([
            'EntireInstance',
            'EntireInstance.SubModel',
            'EntireInstance.PartModel',
            'Fixer',
            'Checker',
            'SpotChecker',
        ])
            ->where('fixer_id', $accountId)
            ->whereBetween('allocate_at', [$yearMonth.'-01'.' '. '00:00:00',$yearMonth.'-31'.' '. '00:00:00']);
        $overdueCompletionEntireInstances = OverhaulEntireInstance::with([
            'EntireInstance',
            'EntireInstance.SubModel',
            'EntireInstance.PartModel',
            'Fixer',
            'Checker',
            'SpotChecker',
        ])
            ->where('fixer_id', $accountId)
            ->whereBetween('allocate_at', [$yearMonth.'-01'.' '. '00:00:00',$yearMonth.'-31'.' '. '00:00:00']);
        $incompleteEntireInstances = OverhaulEntireInstance::with([
            'EntireInstance',
            'EntireInstance.SubModel',
            'EntireInstance.PartModel',
            'Fixer',
            'Checker',
            'SpotChecker',
        ])
            ->where('fixer_id', $accountId)
            ->whereBetween('allocate_at', [$yearMonth.'-01'.' '. '00:00:00',$yearMonth.'-31'.' '. '00:00:00']);

        $taskEntireInstances = $overhaulEntireInstance->paginate(100);
        $completedEntireInstances = $completedEntireInstances->where('status', '1')->paginate(100);
        $overdueCompletionEntireInstances = $overdueCompletionEntireInstances->where('status', '2')->paginate(100);
        $incompleteEntireInstances = $incompleteEntireInstances->where('status', '0')->paginate(100);

        return view('OverhaulEntireInstance.index', [
            'taskEntireInstances' => $taskEntireInstances,
            'completedEntireInstances' => $completedEntireInstances,
            'overdueCompletionEntireInstances' => $overdueCompletionEntireInstances,
            'incompleteEntireInstances' => $incompleteEntireInstances,
            'type' => $type
        ]);
    }

    /**
     * 检修完成
     * @return mixed
     */
    final public function completeOverhaul(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $selected_for_fix_misson = $request->get('selected_for_fix_misson');
                $deadLine = $request->get('deadLine');
                DB::table('v250_task_entire_instances as tei')
                    ->join('v250_task_orders as to', 'to.serial_number', 'tei.v250_task_order_sn')
                    ->where('to.status', 'PROCESSING')
                    ->whereIn('entire_instance_identity_code', $selected_for_fix_misson)
                    ->update(['tei.fixed_at' => $deadLine]);
                foreach ($selected_for_fix_misson as $entire_instance) {
                    $deadAt = DB::table('overhaul_entire_instances')->where('status', '0')->where('entire_instance_identity_code', $entire_instance)->value('deadline');
                    if (strtotime($deadAt) >= strtotime($deadLine)) {
                        DB::table('overhaul_entire_instances')->where('status', '0')->where('entire_instance_identity_code', $entire_instance)->update(['fixed_at' => $deadLine, 'status' => '1']);
                    }else {
                        DB::table('overhaul_entire_instances')->where('status', '0')->where('entire_instance_identity_code', $entire_instance)->update(['fixed_at' => $deadLine, 'status' => '2']);
                    }
//                    if (DB::table('entire_instances')->where('v250_task_order_sn', null)->where('identity_code', $entire_instance)->exists()) {
//                        DB::table('entire_instances')->where('identity_code', $entire_instance)->update([
//                            'updated_at' => date('Y-m-d H:i:s'),
//                            'is_overhaul' => '0'
//                        ]);
//                    }
                    DB::table('entire_instances')->where('identity_code', $entire_instance)->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status' => 'FIXED',
                        'is_overhaul' => '0'
                    ]);
                }
            });
            return JsonResponseFacade::created([], '检修完成');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 取消检修分配
     * @return mixed
     */
    final public function cancelOverhaul(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $selected_for_fix_misson = $request->get('selected_for_fix_misson');
                DB::table('v250_task_entire_instances')->whereIn('entire_instance_identity_code', $selected_for_fix_misson)->update(['fixer_id' => 0, 'checker_id' => 0, 'spot_checker_id' => 0, 'fixed_at' => null, 'checked_at' => null, 'spot_checked_at' => null]);

                DB::table('entire_instances')->whereIn('identity_code', $selected_for_fix_misson)->update([
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 'FIXING',
                    'is_overhaul' => '0'
                ]);
                DB::table('overhaul_entire_instances')->whereIn('entire_instance_identity_code', $selected_for_fix_misson)->delete();
            });
            return JsonResponseFacade::created([], '取消分配成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
