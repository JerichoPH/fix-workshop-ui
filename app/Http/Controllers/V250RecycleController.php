<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Model\EntireInstanceLog;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\FixWorkflowRecord;
use App\Model\Maintain;
use App\Model\OverhaulEntireInstance;
use App\Model\PartInstance;
use App\Model\V250TaskEntireInstance;
use App\Model\V250TaskOrder;
use App\Model\WorkArea;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class V250RecycleController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return mixed
     */
    final public function index()
    {
        $functions = [
            # 回收任务
            'RECYCLE' => function (): View {
                $V250ChangeModels = V250TaskOrder::with([
                    'SceneWorkshop',
                    'MaintainStation',
                    'V250TaskEntireInstances',
                    'V250TaskEntireInstances.Fixer',
                    'V250TaskEntireInstances.Checker',
                    'V250TaskEntireInstances.SpotChecker',
                    'Principal',
                    'WorkAreaByUniqueCode'
                ])
                    ->orderByDesc('id')
                    ->where('work_area_unique_code', session('account.work_area_unique_code'))
                    ->where('type', 'RECYCLE')
                    ->paginate(env('PAGE_SIZE', 15));
                return view('V250Recycle.index', [
                    'taskOrders' => $V250ChangeModels,
                ]);
            },
        ];

        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return back()->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function create()
    {
        $functions = [
            # 换型任务
            'RECYCLE' => function (): View {
                $stations = Maintain::with([])->where('type', 'STATION')->where('parent_unique_code', 'like', env('ORGANIZATION_CODE') . '%')->get();
                $workAreas = WorkArea::with([])->get();
                return view('V250Recycle.create', [
                    'stations' => $stations,
                    'workAreas' => $workAreas,
                ]);
            },
        ];
        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return redirect('/V250Recycle?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return redirect('/V250Recycle?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/V250Recycle?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return mixed
     */
    final public function store(Request $request)
    {
        $functions = [
            # 新站任务
            'RECYCLE' => function () use ($request) {
                # 验证是否重复
                $V250ChangeModelRepeat = V250TaskOrder::with([])
                    ->where('maintain_station_unique_code', $request->get('maintain_station_unique_code'))
                    ->where('work_area_unique_code', $request->get('work_area_unique_code'))
                    ->where('type', $request->get('type'))
                    ->first();
                if ($V250ChangeModelRepeat) return JsonResponseFacade::errorForbidden('任务重复');

                # 验证是否存在车站
                if (!$request->get('maintain_station_unique_code')) return JsonResponseFacade::errorEmpty('请选择车站');
                $maintain = Maintain::with(['Parent'])->where('unique_code', $request->get('maintain_station_unique_code'))->first();
                if (!$maintain) return JsonResponseFacade::errorEmpty('车站不存在');
                if (!$maintain->Parent) return JsonResponseFacade::errorEmpty('该车站没有找到对应的现场车间');

                # 验证是否有截止日期
                if (!$request->get('expiring_at')) return JsonResponseFacade::errorEmpty('截止日期不能为空');
                try {
                    $expiringAt = Carbon::parse($request->get('expiring_at'))->format('Y-m-d');
                } catch (\Throwable $e) {
                    return response()->json(['msg' => '截止日期格式错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
                }

                # 验证是否填写工区
                if (!$request->get('work_area_unique_code')) return JsonResponseFacade::errorEmpty('请选择工区');

                # 创建任务
                $V250ChangeModel = V250TaskOrder::with([])->create([
                    'scene_workshop_unique_code' => $maintain->Parent->unique_code,
                    'maintain_station_unique_code' => $maintain->unique_code,
                    'serial_number' => $newV250ChangeModelSN = V250TaskOrder::getNewSN(strtoupper($request->get('type'))),
                    'expiring_at' => $expiringAt,
                    'principal_id' => session('account.id'),
                    'work_area_unique_code' => $request->get('work_area_unique_code'),
                    'status' => 'PROCESSING',
                    'type' => strtoupper($request->get('type')),
                ]);

                return JsonResponseFacade::created(['task_order' => $V250ChangeModel]);
            },
        ];

        try {
            $func = strtoupper($request->get('type'));
            if (!array_key_exists($func, $functions)) return JsonResponseFacade::errorForbidden('任务类型参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('数据不存在');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Display the specified resource.
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     * @param $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function edit($sn)
    {
        $functions = [
            'RECYCLE' => function () use ($sn): View {
                $v250_task_order = V250TaskOrder::with([
                    'SceneWorkshop',
                    'MaintainStation',
                    'Principal',
                    'WorkAreaByUniqueCode'
                ])
                    ->where('serial_number', $sn)
                    ->firstOrFail();

                $v250_task_entire_instances = V250TaskEntireInstance::with([
                    'EntireInstance',
                    'EntireInstance.SubModel',
                    'EntireInstance.PartModel',
                    'Fixer',
                    'Checker',
                    'SpotChecker',
                ])
                    ->where('v250_task_order_sn', $sn)
                    ->paginate(200);
                // dd($v250_task_entire_instances);

                return view('V250Recycle.edit', [
                    'taskOrder' => $v250_task_order,
                    'sn' => $sn,
                    'taskEntireInstances' => $v250_task_entire_instances,
                ]);
            },
        ];

        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return redirect('/V250Recycle?page=' . request('page', 1))->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {

            return redirect('/V250Recycle?page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/V250Recycle?page=' . request('page', 1))->with('danger', '意外错误');
        }
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
     * @param string $sn
     * @return \Illuminate\Http\JsonResponse
     */
    final public function destroy(string $sn)
    {
        try {
            $v250_task_order = V250TaskOrder::with(['V250TaskEntireInstances'])->where('serial_number', $sn)->firstOrFail();

            if ($v250_task_order->V250TaskEntireInstances->isNotEmpty()) {
                EntireInstanceLock::freeLocks(
                    $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray(),
                    ['RECYCLE'],
                    function () use ($v250_task_order) {
                        $v250_task_entire_instances = V250TaskEntireInstance::with([])->where('v250_task_order_sn', $v250_task_order->serial_number)->where('is_utilize_used', false)->get();  # 新设备
                        $utilize_used_v250_task_entire_instances = V250TaskOrder::with([])->where('v250_task_order_sn', $v250_task_order->serial_number)->where('is_utilize_used', true)->get();  # 利旧设备

                        V250TaskEntireInstance::with([])->whereIn('id', $v250_task_entire_instances->pluck('id')->toArray())->delete();  # 显出新设备
                        EntireInstanceLog::with([])->whereIn('entire_instance_identity_code', $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除设备日志（新设备）
                        EntireInstance::with([])->whereIn('identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除设备（新设备）
                        EntireInstance::with([])->whereIn('identity_code', $utilize_used_v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->update(['v250_task_order_sn' => '']);  # 去掉任务编号（利旧设备）
                        PartInstance::with([])->whereIn('entire_instance_identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除设备部件（新设备）

                        OverhaulEntireInstance::with([])->whereIn('entire_instance_identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除 检修分配
                        OverhaulEntireInstance::with([])->whereIn('entire_instance_identity_code', $utilize_used_v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->update(['v250_task_order_sn' => '']);  # 检修分配 去掉任务编号（利旧设备）

                        DB::table('v250_workshop_in_entire_instances')->where('v250_task_orders_serial_number', $v250_task_order->serial_number)->delete();
                        DB::table('v250_workshop_out_entire_instances')->where('v250_task_orders_serial_number', $v250_task_order->serial_number)->delete();
                        DB::table('v250_workshop_stay_out')->where('v250_task_orders_serial_number', $v250_task_order->serial_number)->delete();
                        $fix_workflows = FixWorkflow::with([])->whereIn('entire_instance_identity_code', $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray())->get();
                        $fix_workflow_processes = FixWorkflowProcess::with([])->whereIn('fix_workflow_serial_number', $fix_workflows->pluck('serial_number')->toArray())->get();
                        FixWorkflowRecord::with([])->whereIn('fix_workflow_process_serial_number', $fix_workflow_processes->pluck('serial_number')->toArray())->forceDelete();
                        FixWorkflowProcess::with([])->where('id', $fix_workflow_processes->pluck('id')->toArray())->forceDelete();
                        Fixworkflow::with([])->where('id', $fix_workflows->pluck('id')->toArray())->forceDelete();
                    }
                );
            }

            $v250_task_order->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('新站任务不存在');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 交付
     * @param Request $request
     * @param string $sn
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postDelivery(Request $request, string $sn)
    {
        try {
            $functions = [
                'RECYCLE' => function () use ($request, $sn) {
                    $v250_task_order = V250TaskOrder::with(['V250TaskEntireInstances' => function ($V250TaskEntireInstances) {
                        $V250TaskEntireInstances->where('is_out', false);
                    }])
                        ->where('serial_number', $sn)
                        ->firstOrFail();
                    if ($v250_task_order->V250TaskEntireInstances->isNotEmpty()) return JsonResponseFacade::errorForbidden('任务中所有设备均出所后才能交付任务');

                    $v250_task_order
                        ->fill([
                            'finished_at' => date('Y-m-d H:i:s'),
                            'status' => 'DONE',
                            'delivery_message' => $request->get('delivery_message'),
                        ])
                        ->saveOrFail();

                    $un_out_entire_instance_count = V250TaskEntireInstance::with([])->where('v250_task_order_sn', $sn)->where('is_out', false)->count();
                    if ($un_out_entire_instance_count > 0) return JsonResponseFacade::errorForbidden("当前任务存在未完成出所的设备器材：{$un_out_entire_instance_count}台。不能交付");

                    # 解锁设备
                    $identity_codes = $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray();
                    EntireInstanceLock::freeLocks($identity_codes, ['RECYCLE'], function () use ($identity_codes) {
                        EntireInstance::with([])->whereIn('identity_code', $identity_codes)->update(['v250_task_order_sn' => '', 'is_overhaul' => 0]);
                    });

                    return JsonResponseFacade::updated('交付成功');
                },
            ];

            $func = strtoupper($request->get('type'));
            if (!array_key_exists($func, $functions)) return JsonResponseFacade::errorForbidden('任务类型不正确');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
