<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Facades\KindsFacade;
use App\Facades\QueryBuilderFacade;
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
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class V250UnCycleFixController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return mixed
     */
    final public function index()
    {
        $functions = [
            # 状态修任务
            'UNCYCLE_FIX' => function (): View {
                $V250UnCycleFix = V250TaskOrder::with([
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
                    ->where('type', 'UNCYCLE_FIX')
                    ->paginate(env('PAGE_SIZE', 15));
                return view('V250UnCycleFix.index', [
                    'taskOrders' => $V250UnCycleFix,
                ]);
            },
        ];

        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return back()->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return RedirectResponse
     */
    final public function create()
    {
        $functions = [
            # 状态修任务
            'UNCYCLE_FIX' => function (): View {
                $stations = Maintain::with([])->where('type', 'STATION')->where('parent_unique_code', 'like', env('ORGANIZATION_CODE') . '%')->get();
                $workAreas = WorkArea::with([])->get();
                return view('V250UnCycleFix.create', [
                    'stations' => $stations,
                    'workAreas' => $workAreas,
                ]);
            },
        ];
        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return redirect('/V250UnCycleFix?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return redirect('/V250UnCycleFix?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/V250UnCycleFix?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * 状态修列表页
     * @param Request $request
     * @return Factory|Application|RedirectResponse|Redirector|View
     */
    final public function unCycleFixList(Request $request)
    {
        try {
            $oldModelNameJson = $request->get('oldModelNameJson');
            $stationName = $request->get('stationName');
            $categoryUniqueCode = $request->get('categoryUniqueCode');
            $entireModelUniqueCode = $request->get('entireModelUniqueCode');
            $subModelUniqueCode = $request->get('subModelUniqueCode');
            $factoryName = $request->get('factoryName');
            $entireInstanceIdentityCode = $request->get('entireInstanceUniqueCode');
            $sn = $request->get('sn');

            $categories = DB::table('categories')->whereNull('deleted_at')->pluck('name', 'unique_code');
            $factories = DB::table('factories')->whereNull('deleted_at')->pluck('name');

            $dbQ = DB::table('entire_instances as ei')
                ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'ei.last_out_at', 'ei.maintain_station_name', 'ei.maintain_location_code'])
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->whereNull('sm.deleted_at')
                ->where('sm.is_sub_model', true)
                ->whereNull('em.deleted_at')
                ->where('em.is_sub_model', false)
                ->whereNull('c.deleted_at')
                ->where('ei.maintain_station_name', $stationName)
                ->when(
                    $categoryUniqueCode,
                    function ($query, $categoryUniqueCode) {
                        $query->where('c.unique_code', $categoryUniqueCode);
                    }
                )
                ->when(
                    $entireModelUniqueCode,
                    function ($query, $entireModelUniqueCode) {
                        $query->where('em.unique_code', $entireModelUniqueCode);
                    }
                )
                ->when(
                    $subModelUniqueCode,
                    function ($query, $subModelUniqueCode) {
                        $query->where('c.unique_code', $subModelUniqueCode);
                    }
                )
                ->when(
                    $factoryName,
                    function ($query, $factoryName) {
                        $query->where('ei.factory_name', $factoryName);
                    }
                )
                ->when(
                    $entireInstanceIdentityCode,
                    function ($query, $entireInstanceIdentityCode) {
                        $query->where('ei.identity_code', $entireInstanceIdentityCode);
                    }
                )
                ->where('ei.v250_task_order_sn', '')
                ->where('ei.work_area_unique_code', session('account.work_area_unique_code'));

            $dbS = DB::table('entire_instances as ei')
                ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'ei.last_out_at', 'ei.maintain_station_name', 'ei.maintain_location_code'])
                ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->whereNull('pm.deleted_at')
                ->whereNull('pc.deleted_at')
                ->where('pc.is_main', true)
                ->whereNull('em.deleted_at')
                ->where('em.is_sub_model', false)
                ->whereNull('c.deleted_at')
                ->where('ei.maintain_station_name', $stationName)
                ->when(
                    $categoryUniqueCode,
                    function ($query, $categoryUniqueCode) {
                        $query->where('c.unique_code', $categoryUniqueCode);
                    }
                )
                ->when(
                    $entireModelUniqueCode,
                    function ($query, $entireModelUniqueCode) {
                        $query->where('em.unique_code', $entireModelUniqueCode);
                    }
                )
                ->when(
                    $subModelUniqueCode,
                    function ($query, $subModelUniqueCode) {
                        $query->where('c.unique_code', $subModelUniqueCode);
                    }
                )
                ->when(
                    $factoryName,
                    function ($query, $factoryName) {
                        $query->where('ei.factory_name', $factoryName);
                    }
                )
                ->when(
                    $entireInstanceIdentityCode,
                    function ($query, $entireInstanceIdentityCode) {
                        $query->where('ei.identity_code', $entireInstanceIdentityCode);
                    }
                )
                ->where('ei.v250_task_order_sn', '')
                ->where('ei.work_area_unique_code', session('account.work_area_unique_code'));

            $entire_instances = QueryBuilderFacade::unionAll($dbQ, $dbS)->paginate(100);

            $modelName = [];
            if ($oldModelNameJson) {
                foreach (json_decode(@$oldModelNameJson) as $key => $value) {
                    array_push($modelName, $key);
                }
            }
            $dbQ1 = DB::table('entire_instances as ei')
                ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.entire_model_unique_code', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'ei.last_out_at', 'ei.maintain_station_name', 'ei.maintain_location_code', 'position.name as position_name', 'tier.name as tier_name', 'shelf.name as shelf_name', 'platoon.name as platoon_name', 'area.name as area_name', 'storehous.name as storehous_name'])
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.entire_model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                ->whereIn('ei.status', ['FIXING', 'FIXED', 'BUY_IN'])
                ->whereIn('ei.model_name', $modelName)
                ->where('ei.v250_task_order_sn', '')
                ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
                ->whereNull('ei.deleted_at');

            $dbS1 = DB::table('entire_instances as ei')
                ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.entire_model_unique_code', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'ei.last_out_at', 'ei.maintain_station_name', 'ei.maintain_location_code', 'position.name as position_name', 'tier.name as tier_name', 'shelf.name as shelf_name', 'platoon.name as platoon_name', 'area.name as area_name', 'storehous.name as storehous_name'])
                ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                ->whereIn('ei.status', ['FIXING', 'FIXED', 'BUY_IN'])
                ->whereIn('ei.model_name', $modelName)
                ->where('ei.v250_task_order_sn', '')
                ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
                ->whereNull('ei.deleted_at');

            $workshopEntireInstances1 = QueryBuilderFacade::unionAll($dbQ1, $dbS1)->get();

            return view('V250UnCycleFix.unCycleFix', [
                'categories' => $categories,
                'factories' => $factories,
                'workshopEntireInstances' => $entire_instances,
                'workshopEntireInstances1' => $workshopEntireInstances1,
                'sn' => $sn
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect('/V250UnCycleFix?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/V250UnCycleFix?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '意外错误');
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
            # 状态修任务
            'UNCYCLE_FIX' => function () use ($request) {
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
                } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Display the specified resource.
     * @param $id
     * @return RedirectResponse
     */
    final public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     * @param $sn
     * @return RedirectResponse
     */
    final public function edit($sn)
    {
        $functions = [
            'UNCYCLE_FIX' => function () use ($sn): View {
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

                return view('V250UnCycleFix.edit', [
                    'taskOrder' => $v250_task_order,
                    'sn' => $sn,
                    'taskEntireInstances' => $v250_task_entire_instances,
                ]);
            },
        ];

        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return redirect('/V250UnCycleFix?page=' . request('page', 1))->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return redirect('/V250UnCycleFix?page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return redirect('/V250UnCycleFix?page=' . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    final public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param string $sn
     * @return JsonResponse
     */
    final public function destroy(string $sn)
    {
        try {
            $v250_task_order = V250TaskOrder::with(['V250TaskEntireInstances'])->where('serial_number', $sn)->firstOrFail();

            if ($v250_task_order->V250TaskEntireInstances->isNotEmpty()) {
                EntireInstanceLock::freeLocks(
                    $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray(),
                    ['UNCYCLE_FIX'],
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
            return JsonResponseFacade::errorEmpty('状态修任务不存在');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 交付
     * @param Request $request
     * @param string $sn
     * @return JsonResponse
     */
    final public function postDelivery(Request $request, string $sn)
    {
        try {
            $functions = [
                'UNCYCLE_FIX' => function () use ($request, $sn) {
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
                    EntireInstanceLock::freeLocks($identity_codes, ['UNCYCLE_FIX'], function () use ($identity_codes) {
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
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
