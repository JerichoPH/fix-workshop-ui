<?php

namespace App\Http\Controllers;

use App\Exceptions\MaintainNotFoundException;
use App\Facades\JsonResponseFacade;
use App\Facades\TextFacade;
use App\Facades\WarehouseReportFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Model\TempTaskSubOrderEntireInstance;
use Carbon\Carbon;
use Curl\Curl;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TempTaskSubOrderEntireInstanceController extends Controller
{
    private $_curl;

    public function __construct()
    {
        $this->_curl = new Curl();
        $this->_curl->setHeader('Access-Key', env('GROUP_ACCESS_KEY'));
    }

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
        try {
            $NEW_STATION = function()use($request){
                # 获取子任务详情
                $params = ['nonce' => TextFacade::rand()];
                $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
                $this->_curl->get(env('GROUP_URL') . "/tempTaskSubOrder/{$request->get('temp_task_sub_order_id')}", $params);
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);
                ['temp_task_sub_order' => $tempTaskSubOrder, 'temp_task_types' => $tempTaskTypes, 'temp_task_sub_order_statuses' => $tempTaskSubOrderStatuses] = (array)$this->_curl->response->data;
                $tempTask = $tempTaskSubOrder->temp_task;
                $tempTaskType = array_flip((array)$tempTaskTypes)[$tempTask->type];

                DB::beginTransaction();
                # 获取设备信息
                $entireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('entire_instance_identity_code'))->first();
                if (!$entireInstance) return JsonResponseFacade::errorEmpty("设备器材：{$request->get('entire_instance_identity_code')}不存在");
                if (!$entireInstance->Station) return JsonResponseFacade::errorEmpty("设备器材：{$entireInstance->identity_code}，没有所属车站");
                if (!$entireInstance->Station->Parent) return JsonResponseFacade::errorEmpty("设备器材：{$entireInstance->identity_code}，没有所属现场车间");
                if (!$entireInstance->maintain_location_code && !$entireInstance->crossroad_number) return JsonResponseFacade::errorEmpty("设备器材：{$entireInstance->identity_code}，没有上道位置");

                $tempTaskOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])
                    ->where('old_entire_instance_identity_code', $request->get('entire_instance_identity_code'))
                    ->where('temp_task_id', $request->get('temp_task_id'))
                    ->where('temp_task_sub_order_id', $request->get('temp_task_sub_order_id'))
                    ->first();
                if ($tempTaskOrderEntireInstance) return JsonResponseFacade::errorForbidden('重复添加设备器材');

                $tempTaskOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->create([
                    'old_entire_instance_identity_code' => $entireInstance->identity_code,
                    'new_entire_instance_identity_code' => $entireInstance->identity_code,
                    'model_unique_code' => $entireInstance->model_unique_code,
                    'model_name' => $entireInstance->model_name,
                    'temp_task_id' => $request->get('temp_task_id'),
                    'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id'),
                    'temp_task_type' => $tempTaskType,
                    'maintain_location_code' => $entireInstance->maintain_location_code ?? '',
                    'crossroad_number' => $entireInstance->crossroad_number ?? '',
                    'open_direction' => $entireInstance->open_direction ?? '',
                ]);

                # 修改设备器材临时生产任务编号信息
                $entireInstance
                    ->fill([
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id'),
                    ])
                    ->saveOrFail();

                # 设备加锁
                EntireInstanceLock::setOnlyLock(
                    $entireInstance->identity_code,
                    [strtoupper($request->get('type'))],
                    "设备器材：{$entireInstance->identity_code}，在临时生产任务中被使用。详情：[{$tempTask->serial_number}]{$tempTask->title}"
                    . Account::$WORK_AREAS[$tempTaskSubOrder->work_area_id]
                    . "({$tempTaskSubOrder->maintain_station_name})");
                DB::commit();

                return JsonResponseFacade::created(['temp_task_sub_order_entire_instance' => $tempTaskOrderEntireInstance]);
            };

            $func = strtoupper($request->get('type'));
            return $$func();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('设备不存在');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
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
     * 删除多个设备
     * @param Request $request
     * @return mixed
     */
    final public function deleteEntireInstances(Request $request)
    {
        try {
            $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])
                ->whereIn('old_entire_instance_identity_code', $request->get('identityCodes'))
                ->get();
            $identityCodes = $tempTaskSubOrderEntireInstances->pluck('new_entire_instance_identity_code', 'old_entire_instance_identity_code')->toArray();
            $identityCodes = array_filter(array_merge(array_keys($identityCodes), array_values($identityCodes)), function ($datum) {
                return !(empty($datum) || is_null($datum));
            });
            # 去掉临时生产任务标记
            $entireInstances = EntireInstance::with([])
                ->whereIn('identity_code', $identityCodes)
                ->update(['updated_at' => date('Y-m-d H:i:s'), 'temp_task_id' => 0, 'temp_task_sub_order_id' => 0]);

            # 解锁
            EntireInstanceLock::freeLocks($identityCodes, [strtoupper($request->get('type'))]);

            # 删除临时生产子任务设备
            TempTaskSubOrderEntireInstance::with([])
                ->whereIn('old_entire_instance_identity_code', $request->get('identityCodes'))
                ->delete();
            return JsonResponseFacade::deleted();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 临时生产子任务 设备 出入所扫码
     * @param Request $request
     * @param string $identityCode
     * @return mixed
     */
    final public function postScanForWarehouse(Request $request, string $identityCode)
    {
        try {
            /**
             * 新站出所扫码
             * @return mixed
             */
            $NEW_STATION_OUT = function () use ($request, $identityCode) {
                $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->where('new_entire_instance_identity_code', $identityCode)->firstOrFail();
                $tempTaskSubOrderEntireInstance->fill(['out_scan' => true])->saveOrFail();

                return JsonResponseFacade::updated(['temp_task_sub_order_entire_instance' => $tempTaskSubOrderEntireInstance], '扫码成功');
            };

            /**
             * 大修入所扫码
             * @return mixed
             */
            $FULL_FIX_IN = function () use ($request, $identityCode) {
                $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->where('old_entire_instance_identity_code', $identityCode)->firstOrFail();
                $tempTaskSubOrderEntireInstance->fill(['in_scan' => true])->saveOrFail();

                return JsonResponseFacade::updated(['temp_task_sub_order_entire_instance' => $tempTaskSubOrderEntireInstance], '扫码成功');
            };

            /**
             * 大修出所扫码
             * @return mixed
             */
            $FULL_FIX_OUT = function () use ($request, $identityCode) {
                $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->where('new_entire_instance_identity_code', $identityCode)->firstOrFail();
                $tempTaskSubOrderEntireInstance->fill(['out_scan' => true])->saveOrFail();

                return JsonResponseFacade::updated(['temp_task_sub_order_entire_instance' => $tempTaskSubOrderEntireInstance], '扫码成功');
            };

            /**
             * 高频/状态修入所扫码
             * @return mixed
             */
            $HIGH_FREQUENCY_IN = function () use ($request, $identityCode) {
                $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->where('old_entire_instance_identity_code', $identityCode)->firstOrFail();
                $tempTaskSubOrderEntireInstance->fill(['in_scan' => true])->saveOrFail();

                return JsonResponseFacade::updated(['temp_task_sub_order_entire_instance' => $tempTaskSubOrderEntireInstance], '扫码成功');
            };

            /**
             * 高频/状态修出所扫码
             * @return mixed
             */
            $HIGH_FREQUENCY_OUT = function () use ($request, $identityCode) {
                $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->where('new_entire_instance_identity_code', $identityCode)->firstOrFail();
                $tempTaskSubOrderEntireInstance->fill(['out_scan' => true])->saveOrFail();

                return JsonResponseFacade::updated(['temp_task_sub_order_entire_instance' => $tempTaskSubOrderEntireInstance], '扫码成功');
            };

            /**
             * 技改入所扫码
             * @return mixed
             */
            $TECHNOLOGY_REMOULD_IN = function () use ($request, $identityCode) {
                $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->where('old_entire_instance_identity_code', $identityCode)->firstOrFail();
                $tempTaskSubOrderEntireInstance->fill(['in_scan' => true])->saveOrFail();

                return JsonResponseFacade::updated(['temp_task_sub_order_entire_instance' => $tempTaskSubOrderEntireInstance], '扫码成功');
            };

            /**
             * 技改出所扫码
             * @return mixed
             */
            $TECHNOLOGY_REMOULD_OUT = function () use ($request, $identityCode) {
                $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->where('new_entire_instance_identity_code', $identityCode)->firstOrFail();
                $tempTaskSubOrderEntireInstance->fill(['out_scan' => true])->saveOrFail();

                return JsonResponseFacade::updated(['temp_task_sub_order_entire_instance' => $tempTaskSubOrderEntireInstance], '扫码成功');
            };

            $type = strtoupper($request->get('type'));
            $direction = strtoupper($request->get('direction'));
            $func = "{$type}_{$direction}";
            return $$func();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 临时生产子任务 设备 删除出入所扫码
     * @param Request $request
     * @param int $tempTaskSubOrderEntireInstanceId
     */
    final public function deleteScanForWarehouse(Request $request, int $tempTaskSubOrderEntireInstanceId)
    {
        try {
            $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->where('id', $tempTaskSubOrderEntireInstanceId)->firstOrFail();
            $tempTaskSubOrderEntireInstance->fill([strtoupper($request->get('direction')) . '_scan' => false])->saveOrFail();
            return JsonResponseFacade::deleted(['temp_task_sub_order_entire_instance' => $tempTaskSubOrderEntireInstance], '删除成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 出入所
     * @param Request $request
     * @return mixed
     */
    final public function postWarehouse(Request $request)
    {
        try {
            if (!$request->get('type')) return JsonResponseFacade::errorForbidden('缺少出入库参数');

            $entireInstances = EntireInstance::with([])->whereIn('identity_code', $request->get('identity_codes'))->get();
            if ($entireInstances->isEmpty()) return JsonResponseFacade::errorEmpty('没有找到操作的速度');
            $diff = array_diff($request->get('identity_codes'), $entireInstances->pluck('identity_code')->toArray());
            if ($diff) return JsonResponseFacade::errorEmpty('以下设备没有找到：\r\n' . implode('\r\n', $diff));

            # 入所
            $IN = function () use ($request, $entireInstances) {
                DB::beginTransaction();
                # 更新设备临时生产任务和子任务编号
                DB::table('entire_instances as ei')
                    ->where('ei.deleted_at', null)
                    ->whereIn('identity_code', $request->get('identity_codes'))
                    ->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id')
                    ]);

                # 标准入所流程
                $warehouseReportSN = WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                    $entireInstances->pluck('identity_code')->toArray(),
                    $request->get('processor_id'),
                    Carbon::createFromFormat('Y-m-d H:i', "{$request->get('processed_date')} {$request->get('processed_time')}")->format('Y-m-d H:i:s'),
                    'NEW_STATION',
                    $request->get('connection_name') ?? '',
                    $request->get('connection_phone') ?? ''
                );

                # 修改任务单中设备的入所单号
                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])
                    ->whereIn('old_entire_instance_identity_code', $request->get('identity_codes'))
                    ->orderBy('id')
                    ->chunk(30, function ($tempTaskSubOrderEntireInstances) use ($warehouseReportSN) {
                        foreach ($tempTaskSubOrderEntireInstances as $tempTaskSubOrderEntireInstance) {
                            $tempTaskSubOrderEntireInstance->fill([
                                'out_warehouse_sn' => $warehouseReportSN,
                                'is_finished' => (
                                    !empty($tempTaskSubOrderEntireInstance->out_warehouse_sn)
                                    && !empty($tempTaskSubOrderEntireInstance->fix_workshop_sn)
                                )
                            ])
                                ->saveOrFail();
                        }
                    });
                DB::commit();
                return JsonResponseFacade::created(['warehouse_report_serial_number' => $warehouseReportSN], '入所成功');
            };

            # 出所
            $OUT = function () use ($request, $entireInstances) {
                DB::beginTransaction();
                # 更新设备临时生产任务和子任务编号
                DB::table('entire_instances as ei')
                    ->where('ei.deleted_at', null)
                    ->whereIn('identity_code', $request->get('identity_codes'))
                    ->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id')
                    ]);

                # 标准出所流程
                $warehouseReportSN = WarehouseReportFacade::batchOutWithEntireInstanceIdentityCodes(
                    $entireInstances->pluck('identity_code')->toArray(),
                    $request->get('processor_id'),
                    Carbon::createFromFormat('Y-m-d H:i', "{$request->get('processed_date')} {$request->get('processed_time')}")->format('Y-m-d H:i:s'),
                    'NEW_STATION',
                    $request->get('connection_name') ?? '',
                    $request->get('connection_phone') ?? ''
                );

                # 修改任务单中设备的入所单号
                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])
                    ->whereIn('old_entire_instance_identity_code', $request->get('identity_codes'))
                    ->orderBy('id')
                    ->chunk(30, function ($tempTaskSubOrderEntireInstances) use ($warehouseReportSN) {
                        foreach ($tempTaskSubOrderEntireInstances as $tempTaskSubOrderEntireInstance) {
                            $tempTaskSubOrderEntireInstance->fill([
                                'out_warehouse_sn' => $warehouseReportSN,
                                'is_finished' => !empty($tempTaskSubOrderEntireInstance->fix_workflow_sn),
                            ])
                                ->saveOrFail();
                        }
                    });
                DB::commit();
                return JsonResponseFacade::created(['warehouse_report_serial_number' => $warehouseReportSN], '出所成功');
            };

            $func = strtoupper($request->get('type'));
            return $$func();
        } catch (MaintainNotFoundException $e) {
            return JsonResponseFacade::errorEmpty($e->getMessage());
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取设备列表
     */
    final public function getEntireInstances()
    {
        try {
            /**
             * 搜索高频修设备列表
             * @return mixed
             */
            $HIGH_FREQUENCY_OLD = function () {
                $entireInstances = EntireInstance::with(['Station',])
                    ->whereHas('Station', function ($Station) {
                        $Station->where('unique_code', request('maintain_station_unique_code'));
                    })
                    ->where('model_unique_code', request('model_unique_code'))
                    ->when(request('status'), function ($query) {
                        is_array(request('status')) ?
                            $query->whereIn('status', request('status')) :
                            $query->where('status', reqeust()->get('status'));
                    })
                    ->where('temp_task_id', request('temp_task_id'))
                    ->where('temp_task_sub_order_id', request('temp_task_sub_order_id'))
                    ->whereNotIn('identity_code', DB::table('entire_instance_locks')->pluck('entire_instance_identity_code')->toArray())
                    ->when(request('use_made_at') == 1, function ($query) {
                        list($originAt, $finishAt) = explode('~', request('made_at'));
                        $query->whereBetween('made_at', [$originAt, $finishAt]);
                    })
                    ->when(request('use_scarping_at') == 1, function ($query) {
                        list($originAt, $finishAt) = explode('~', request('scarping_at'));
                        $query->whereBetween('scarping_at', [$originAt, $finishAt]);
                    })
                    ->get();

                return JsonResponseFacade::data(['entire_instances' => $entireInstances]);
            };

            /**
             * 技改
             */
            $TECHNOLOGY_REMOULD_OLD = function(){
                $entireInstances = EntireInstance::with(['Station',])
                    ->whereHas('Station', function ($Station) {
                        $Station->where('unique_code', request('maintain_station_unique_code'));
                    })
                    ->where('model_unique_code', request('model_unique_code'))
                    ->when(request('status'), function ($query) {
                        is_array(request('status')) ?
                            $query->whereIn('status', request('status')) :
                            $query->where('status', reqeust()->get('status'));
                    })
                    ->where('temp_task_id', request('temp_task_id'))
                    ->where('temp_task_sub_order_id', request('temp_task_sub_order_id'))
                    ->whereNotIn('identity_code', DB::table('entire_instance_locks')->pluck('entire_instance_identity_code')->toArray())
                    ->when(request('use_made_at') == 1, function ($query) {
                        list($originAt, $finishAt) = explode('~', request('made_at'));
                        $query->whereBetween('made_at', [$originAt, $finishAt]);
                    })
                    ->when(request('use_scarping_at') == 1, function ($query) {
                        list($originAt, $finishAt) = explode('~', request('scarping_at'));
                        $query->whereBetween('scarping_at', [$originAt, $finishAt]);
                    })
                    ->get();

                return JsonResponseFacade::data(['entire_instances' => $entireInstances]);
            };

            $func = strtoupper(request('type'));
            return $$func();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 加入设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postEntireInstances(Request $request)
    {
        try {
            /**
             * 高频/状态修
             * @return mixed
             */
            $HIGH_FREQUENCY_OLD = function () use ($request) {
                $params = ['nonce' => TextFacade::rand()];
                $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
                $this->_curl->get(env('GROUP_URL') . "/tempTask/{$request->get('temp_task_id')}", $params);
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);
                ['temp_task' => $tempTask, 'temp_task_statuses' => $tempTaskStatuses, 'temp_task_types' => $tempTaskTypes, 'temp_task_modes' => $tempTaskModes,] = (array)$this->_curl->response->data;
                $tempTaskType = array_flip((array)$tempTaskTypes)[$tempTask->type];

                DB::beginTransaction();
                $entireInstances = EntireInstance::with(['Station','Station.Parent'])->whereIn('identity_code', $request->get('entireInstanceIdentityCode_Search'))->get();
                $diff = array_diff($request->get('entireInstanceIdentityCode_Search'), $entireInstances->pluck('identity_code')->toArray());
                if ($diff) return JsonResponseFacade::errorEmpty('以下设备没有找到：' . implode("\r\n", $diff));

                $remarks = [];
                $entireInstances->each(function ($entireInstance) use ($request, &$remarks, $tempTaskType) {
                    TempTaskSubOrderEntireInstance::with([])->create([
                        'old_entire_instance_identity_code' => $entireInstance->identity_code,
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id'),
                        'model_unique_code' => $entireInstance->model_unique_code,
                        'model_name' => $entireInstance->model_name,
                        'temp_task_type' => $tempTaskType,
                        'maintain_location_code' => $entireInstance->maintain_location_code ?? '',
                        'crossroad_number' => $entireInstance->crossroad_number ?? '',
                        'open_direction' => $entireInstance->open_direction ?? '',
                    ]);
                    $entireInstance->fill([
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id')
                    ])
                        ->saveOrFail();
                    $remarks[] = [
                        "设备器材：{$entireInstance->identity_code}，在临时生产任务中被占用；详情："
                        . "[{$request->get('temp_task_serial_number')}]{$request->get('temp_task_title')}"
                        . Account::$WORK_AREAS[$request->get('temp_task_sub_order_work_area_id')]
                        . "({$request->get('temp_task_sub_order_maintain_station_name')})"
                    ];
                });
                # 设备加锁
                EntireInstanceLock::setOnlyLocks($entireInstances->pluck('identity_code')->toArray(), [strtoupper($request->get('lock_name'))], $remarks);
                DB::commit();
                return JsonResponseFacade::created([], '添加成功');
            };

            /**
             * 站改
             */
            $TECHNOLOGY_REMOULD_OLD = function()use($request){
                $params = ['nonce' => TextFacade::rand()];
                $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
                $this->_curl->get(env('GROUP_URL') . "/tempTask/{$request->get('temp_task_id')}", $params);
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);
                ['temp_task' => $tempTask, 'temp_task_statuses' => $tempTaskStatuses, 'temp_task_types' => $tempTaskTypes, 'temp_task_modes' => $tempTaskModes,] = (array)$this->_curl->response->data;
                $tempTaskType = array_flip((array)$tempTaskTypes)[$tempTask->type];

                DB::beginTransaction();
                $entireInstances = EntireInstance::with(['Station','Station.Parent'])->whereIn('identity_code', $request->get('entireInstanceIdentityCode_Search'))->get();
                $diff = array_diff($request->get('entireInstanceIdentityCode_Search'), $entireInstances->pluck('identity_code')->toArray());
                if ($diff) return JsonResponseFacade::errorEmpty('以下设备没有找到：' . implode("\r\n", $diff));

                $remarks = [];
                $entireInstances->each(function ($entireInstance) use ($request, &$remarks, $tempTaskType) {
                    TempTaskSubOrderEntireInstance::with([])->create([
                        'old_entire_instance_identity_code' => $entireInstance->identity_code,
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id'),
                        'model_unique_code' => $entireInstance->model_unique_code,
                        'model_name' => $entireInstance->model_name,
                        'temp_task_type' => $tempTaskType,
                        'maintain_location_code' => $entireInstance->maintain_location_code ?? '',
                        'crossroad_number' => $entireInstance->crossroad_number ?? '',
                        'open_direction' => $entireInstance->open_direction ?? '',
                    ]);
                    $entireInstance->fill([
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id')
                    ])
                        ->saveOrFail();
                    $remarks[] = [
                        "设备器材：{$entireInstance->identity_code}，在临时生产任务中被占用；详情："
                        . "[{$request->get('temp_task_serial_number')}]{$request->get('temp_task_title')}"
                        . Account::$WORK_AREAS[$request->get('temp_task_sub_order_work_area_id')]
                        . "({$request->get('temp_task_sub_order_maintain_station_name')})"
                    ];
                });
                # 设备加锁
                EntireInstanceLock::setOnlyLocks($entireInstances->pluck('identity_code')->toArray(), [strtoupper($request->get('lock_name'))], $remarks);
                DB::commit();
                return JsonResponseFacade::created([], '添加成功');
            };

            $func = strtoupper($request->get('type'));
            return $$func();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
