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

class V250ChangeModelController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return mixed
     */
    final public function index()
    {
        $functions = [
            # 换型任务
            'CHANGE_MODEL' => function (): View {
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
                    ->where('type', 'CHANGE_MODEL')
                    ->paginate(env('PAGE_SIZE', 15));
                return view('V250ChangeModel.index', [
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
            'CHANGE_MODEL' => function (): View {
                $stations = Maintain::with([])->where('type', 'STATION')->where('parent_unique_code', 'like', env('ORGANIZATION_CODE') . '%')->get();
                $workAreas = WorkArea::with([])->get();
                return view('V250ChangeModel.create', [
                    'stations' => $stations,
                    'workAreas' => $workAreas,
                ]);
            },
        ];
        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return redirect('/V250ChangeModel?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return redirect('/V250ChangeModel?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/V250ChangeModel?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * 换型任务 列表页
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function changeModelList(Request $request)
    {
        try {
            $stationName = $request->get('stationName');
            $categoryUniqueCode = $request->get('categoryUniqueCode');
            $entireModelUniqueCode = $request->get('entireModelUniqueCode');
            $subModelUniqueCode = $request->get('subModelUniqueCode');
            $factoryName = $request->get('factoryName');
            $entireInstanceUniqueCode = $request->get('entireInstanceUniqueCode');
            $sn = $request->get('sn');

            $categories = DB::table('categories')->where('deleted_at', null)->pluck('name', 'unique_code');
            $factories = DB::table('factories')->where('deleted_at', null)->pluck('name');

            $dbQ = DB::table('entire_instances as ei')
                ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'ei.last_out_at', 'ei.maintain_station_name', 'ei.maintain_location_code'])
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.entire_model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->when($categoryUniqueCode, function ($query, $categoryUniqueCode) {
                    $query->where('c.unique_code', $categoryUniqueCode);
                })
                ->when($entireModelUniqueCode, function ($query, $entireModelUniqueCode) {
                    $query->where('em.unique_code', $entireModelUniqueCode);
                })
                ->when($subModelUniqueCode, function ($query, $subModelUniqueCode) {
                    $query->where('sm.unique_code', $subModelUniqueCode);
                })
                ->when($factoryName, function ($query, $factoryName) {
                    $query->where('ei.factory_name', $factoryName);
                })
                ->when($entireInstanceUniqueCode, function ($query, $entireInstanceUniqueCode) {
                    $query->where('ei.identity_code', $entireInstanceUniqueCode);
                })
                ->where('ei.maintain_station_name', $stationName)
                ->where('ei.v250_task_order_sn', '')
                ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
                ->where('ei.deleted_at', null);

            $dbS = DB::table('entire_instances as ei')
                ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'ei.last_out_at', 'ei.maintain_station_name', 'ei.maintain_location_code'])
                ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->when($categoryUniqueCode, function ($query, $categoryUniqueCode) {
                    $query->where('c.unique_code', $categoryUniqueCode);
                })
                ->when($entireModelUniqueCode, function ($query, $entireModelUniqueCode) {
                    $query->where('em.unique_code', $entireModelUniqueCode);
                })
                ->when($subModelUniqueCode, function ($query, $subModelUniqueCode) {
                    $query->where('pm.unique_code', $subModelUniqueCode);
                })
                ->when($factoryName, function ($query, $factoryName) {
                    $query->where('ei.factory_name', $factoryName);
                })
                ->when($entireInstanceUniqueCode, function ($query, $entireInstanceUniqueCode) {
                    $query->where('ei.identity_code', $entireInstanceUniqueCode);
                })
                ->where('ei.maintain_station_name', $stationName)
                ->where('ei.v250_task_order_sn', '')
                ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
                ->where('ei.deleted_at', null);

            $db = $dbS->unionAll($dbQ);
            $workshopEntireInstances = DB::table(DB::raw("({$db->toSql()}) as a"))->mergeBindings($db)->paginate(100);

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
                ->where('ei.entire_model_unique_code', $request->get('subModel1'))
                ->where('ei.v250_task_order_sn', '')
                ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
                ->where('ei.deleted_at', null);

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
                ->where('ei.entire_model_unique_code', $request->get('subModel1'))
                ->where('ei.v250_task_order_sn', '')
                ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
                ->where('ei.deleted_at', null);

            $db1 = $dbS1->unionAll($dbQ1);
            $workshopEntireInstances1 = DB::table(DB::raw("({$db1->toSql()}) as a"))->mergeBindings($db1)->get();

            return view('V250ChangeModel.changeModel', [
                'categories' => $categories,
                'factories' => $factories,
                'workshopEntireInstances' => $workshopEntireInstances,
                'workshopEntireInstances1' => $workshopEntireInstances1,
                'sn' => $sn
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect('/V250ChangeModel?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/V250ChangeModel?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * 换型操作
     * @param Request $request
     * @return \Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    final public function changeModel(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $oldIdentityCodes = $request->get('oldIdentityCodes');
                $newIdentityCodes = $request->get('newIdentityCodes');
                $sn = $request->get('sn');
                foreach ($newIdentityCodes as $newIdentityCode) {
                    DB::table('entire_instances')->where('identity_code', $newIdentityCode)->update(['v250_task_order_sn' => $sn, 'updated_at' => date('Y-m-d H:i:s')]);
                    DB::table('entire_instances')->where('identity_code', $oldIdentityCodes)->update(['v250_task_order_sn' => $sn, 'updated_at' => date('Y-m-d H:i:s')]);
                    DB::table('v250_task_entire_instances')->insert([
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'v250_task_order_sn' => $sn,
                        'entire_instance_identity_code' => $newIdentityCode,
                        'is_scene_back' => 0,
                        'is_out' => 0,
                        'out_at' => null
                    ]);
                }
                foreach ($oldIdentityCodes as $oldIdentityCode) {
                    $oldRemark[$oldIdentityCode] = '设备器材：' . $oldIdentityCode . '，' . '在换型任务中被使用。详情：工区：' . session('account.work_area');
                }
                foreach ($newIdentityCodes as $oldIdentityCode) {
                    $newRemark[$oldIdentityCode] = '设备器材：' . $oldIdentityCode . '，' . '在换型任务中被使用。详情：工区：' . session('account.work_area');
                }
                # 设备器材加锁
                EntireInstanceLock::setOnlyLocks($oldIdentityCodes, ['CHANGE_MODEL'], $oldRemark);
                EntireInstanceLock::setOnlyLocks($newIdentityCodes, ['CHANGE_MODEL'], $newRemark);
            });
            return JsonResponseFacade::created([], '操作成功');
        } catch (ModelNotFoundException $e) {
//            return redirect('/V250ChangeModel?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '数据不存在');
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
//            return redirect('/V250ChangeModel?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '意外错误');
            return JsonResponseFacade::errorException($e);
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
            # 换型任务
            'CHANGE_MODEL' => function () use ($request) {
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
            'CHANGE_MODEL' => function () use ($sn): View {
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

                return view('V250ChangeModel.edit', [
                    'taskOrder' => $v250_task_order,
                    'sn' => $sn,
                    'taskEntireInstances' => $v250_task_entire_instances,
                ]);
            },
        ];

        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return redirect('/V250ChangeModel?page=' . request('page', 1))->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {

            return redirect('/V250ChangeModel?page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/V250ChangeModel?page=' . request('page', 1))->with('danger', '意外错误');
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
                    ['CHANGE_MODEL'],
                    function () use ($v250_task_order) {
                        $v250_task_entire_instances = V250TaskEntireInstance::with([])->where('v250_task_order_sn', $v250_task_order->serial_number)->where('is_utilize_used', false)->get();  # 新设备器材
                        $utilize_used_v250_task_entire_instances = V250TaskOrder::with([])->where('v250_task_order_sn', $v250_task_order->serial_number)->where('is_utilize_used', true)->get();  # 利旧设备器材

                        V250TaskEntireInstance::with([])->whereIn('id', $v250_task_entire_instances->pluck('id')->toArray())->delete();  # 显出新设备器材
                        EntireInstanceLog::with([])->whereIn('entire_instance_identity_code', $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除设备器材日志（新设备器材）
                        EntireInstance::with([])->whereIn('identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除设备器材（新设备器材）
                        EntireInstance::with([])->whereIn('identity_code', $utilize_used_v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->update(['v250_task_order_sn' => '']);  # 去掉任务编号（利旧设备器材）
                        PartInstance::with([])->whereIn('entire_instance_identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除设备部件（新设备器材）

                        OverhaulEntireInstance::with([])->whereIn('entire_instance_identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  # 删除 检修分配
                        OverhaulEntireInstance::with([])->whereIn('entire_instance_identity_code', $utilize_used_v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->update(['v250_task_order_sn' => '']);  # 检修分配 去掉任务编号（利旧设备器材）

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
            return JsonResponseFacade::errorEmpty('换型任务不存在');
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
                'CHANGE_MODEL' => function () use ($request, $sn) {
                    $v250_task_order = V250TaskOrder::with(['V250TaskEntireInstances' => function ($V250TaskEntireInstances) {
                        $V250TaskEntireInstances->where('is_out', false);
                    }])
                        ->where('serial_number', $sn)
                        ->firstOrFail();
                    if ($v250_task_order->V250TaskEntireInstances->isNotEmpty()) return JsonResponseFacade::errorForbidden('任务中所有设备器材均出所后才能交付任务');

                    $v250_task_order
                        ->fill([
                            'finished_at' => date('Y-m-d H:i:s'),
                            'status' => 'DONE',
                            'delivery_message' => $request->get('delivery_message'),
                        ])
                        ->saveOrFail();

                    $un_out_entire_instance_count = V250TaskEntireInstance::with([])->where('v250_task_order_sn', $sn)->where('is_out', false)->count();
                    if ($un_out_entire_instance_count > 0) return JsonResponseFacade::errorForbidden("当前任务存在未完成出所的设备器材：{$un_out_entire_instance_count}台。不能交付");

                    # 解锁设备器材
                    $identity_codes = $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray();
                    EntireInstanceLock::freeLocks($identity_codes, ['CHANGE_MODEL'], function () use ($identity_codes) {
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
