<?php

namespace App\Http\Controllers;

use App\Exceptions\MaintainNotFoundException;
use App\Facades\JsonResponseFacade;
use App\Facades\TextFacade;
use App\Facades\WarehouseReportFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Model\Maintain;
use App\Model\TempTaskSubOrderEntireInstance;
use Carbon\Carbon;
use Curl\Curl;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TempTaskSubOrderController extends Controller
{

    private $_curl = null;

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    final public function store(Request $request)
    {
        try {
            $station = Maintain::with(['Parent'])
                ->where('type', 'STATION')
                ->where('unique_code', $request->get('maintain_station_unique_code'))
                ->first();
            if (!$station) return back()->withInput()->with('danger', '没有找到车站');
            if (!$station->Parent) return back()->withInput()->with('danger', '没有找到现场车间');

            $params = array_merge($request->all(), [
                'scene_workshop_unique_code' => $station->parent_unique_code,
                'scene_workshop_name' => $station->Parent->name,
                'maintain_station_unique_code' => $station->unique_code,
                'maintain_station_name' => $station->name,
                'nonce' => TextFacade::rand()
            ]);
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->post(env('GROUP_URL') . '/tempTaskSubOrder', $params);
            if ($this->_curl->error) return back()->withInput()->with('danger', $this->_curl->response->msg);

            return redirect("tempTaskSubOrderModel/{$this->_curl->response->data->temp_task_sub_order->id}/edit")->with('success', '新建成功');
        } catch (\Throwable $e) {
            return back()->withInput()->with('danger', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show($id)
    {
        try {
            $params = ['nonce' => TextFacade::rand()];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
            if ($this->_curl->error) return redirect('/tempTask')->with('danger', $this->_curl->response->msg);
            ['temp_task_sub_order' => $tempTaskSubOrder, 'temp_task_types' => $tempTaskTypes] = (array)$this->_curl->response->data;

            $tempTask = $tempTaskSubOrder->temp_task;
            $tempTaskAccessories = $tempTask->accessories;

            $viewName = array_flip((array)$tempTaskTypes)[$tempTaskSubOrder->temp_task->type];

            # 统计完成数
            $statistics = DB::table('temp_task_sub_order_entire_instances as ttsoei')
                ->selectRaw('count(id) as aggregated, ttsoei.model_name')
                ->where('ttsoei.is_finished', true)
                ->groupBy('ttsoei.model_name')
                ->get()
                ->pluck('aggregate', 'model_name');

            return view("TempTaskSubOrder.show_{$viewName}", [
                'tempTaskSubOrder' => $tempTaskSubOrder,
                'statistics' => $statistics,
                'tempTaskAccessories' => $tempTaskAccessories,
            ]);
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/tempTask')->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function edit($id)
    {
        try {
            # 标记消息已读
            $params = ['nonce' => TextFacade::rand()];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/message/" . request('message_id'), $params);
            if ($this->_curl->error) return redirect('tempTask?page=' . request('page', 1))->withInput()->with('danger', $this->_curl->response->msg);

            # 获取临时生产子任务详情
            $params = ['nonce' => TextFacade::rand()];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
            if ($this->_curl->error) return redirect('/tempTask')->with('danger', $this->_curl->response->msg);
            ['temp_task_sub_order' => $tempTaskSubOrder, 'temp_task_types' => $tempTaskTypes, 'temp_task_sub_order_statuses' => $tempTaskSubOrderStatuses] = (array)$this->_curl->response->data;
            ['temp_task_sub_order_models' => $tempTaskSubOrderModels, 'principal' => $principal] = (array)$tempTaskSubOrder;
            $tempTask = $tempTaskSubOrder->temp_task;
            $tempTaskAccessories = $tempTask->accessories;

//            if (array_flip((array)$tempTaskSubOrderStatuses)[$tempTaskSubOrder->status] == 'UNDONE') {
//                # 如果子任务状态：未开始，则标记为执行中
//                $params = ['nonce' => TextFacade::rand()];
//                $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
//                $this->_curl->put(env('GROUP_URL') . "/tempTaskSubOrder/{$id}/processing", $params);
//            }

            # 获取临时生产子任务 设备
            $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])
                ->orderByDesc('id')
                ->where('temp_task_id', $tempTask->id)
                ->where('temp_task_sub_order_id', $tempTaskSubOrder->id)
                ->get();

            # 统计完成数
            $statistics = DB::table('temp_task_sub_order_entire_instances as ttsoei')
                ->selectRaw('count(id) as aggregate, ttsoei.model_name')
                ->where('ttsoei.is_finished', true)
                ->groupBy('ttsoei.model_name')
                ->get()
                ->pluck('aggregate', 'model_name');

            /**
             * 新站
             * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
             */
            $NEW_STATION = function () use (
                $tempTaskSubOrder,
                $tempTaskSubOrderModels,
                $tempTask,
                $tempTaskTypes,
                $tempTaskAccessories,
                $principal,
                $tempTaskSubOrderEntireInstances,
                $tempTaskSubOrderStatuses,
                $statistics
            ) {
                return view("TempTaskSubOrder.edit_NEW_STATION", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTaskSubOrderModels' => $tempTaskSubOrderModels,
                    'tempTask' => $tempTask,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskAccessories' => $tempTaskAccessories,
                    'principal' => $principal,
                    'tempTaskSubOrderEntireInstances' => $tempTaskSubOrderEntireInstances,
                    'tempTaskSubOrderStatuses' => (array)$tempTaskSubOrderStatuses,
                    'statistics' => $statistics,
                ]);
            };

            /**
             * 大修
             * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
             */
            $FULL_FIX = function () use (
                $tempTaskSubOrder,
                $tempTaskSubOrderModels,
                $tempTask,
                $tempTaskTypes,
                $tempTaskAccessories,
                $principal,
                $tempTaskSubOrderEntireInstances,
                $tempTaskSubOrderStatuses,
                $statistics
            ) {
                return view("TempTaskSubOrder.edit_FULL_FIX", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTaskSubOrderModels' => $tempTaskSubOrderModels,
                    'tempTask' => $tempTask,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskAccessories' => $tempTaskAccessories,
                    'principal' => $principal,
                    'tempTaskSubOrderEntireInstances' => $tempTaskSubOrderEntireInstances,
                    'tempTaskSubOrderStatuses' => (array)$tempTaskSubOrderStatuses,
                    'statistics' => $statistics,
                ]);
            };

            /**
             * 高频/状态修
             * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
             */
            $HIGH_FREQUENCY = function () use (
                $tempTaskSubOrder,
                $tempTaskSubOrderModels,
                $tempTask,
                $tempTaskTypes,
                $tempTaskAccessories,
                $principal,
                $tempTaskSubOrderEntireInstances,
                $tempTaskSubOrderStatuses,
                $statistics
            ) {
                # 获取种类型
                $statisticsRootDir = storage_path('app/basicInfo');
                if (!is_dir($statisticsRootDir)) return back()->withInput()->with('danger', '基础缓存数据不存在');
                if (!file_exists("{$statisticsRootDir}/models.json")) return back()->withInput()->with('danger', '基础缓存数据不存在：种类和型号');
                $models = json_decode(file_get_contents("{$statisticsRootDir}/models.json"), true);

                return view("TempTaskSubOrder.edit_HIGH_FREQUENCY", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTaskSubOrderModels' => $tempTaskSubOrderModels,
                    'tempTask' => $tempTask,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskAccessories' => $tempTaskAccessories,
                    'principal' => $principal,
                    'tempTaskSubOrderEntireInstances' => $tempTaskSubOrderEntireInstances,
                    'tempTaskSubOrderStatuses' => (array)$tempTaskSubOrderStatuses,
                    'statistics' => $statistics,
                    'modelsAsJson' => json_encode($models),
                ]);
            };

            /**
             * 技改
             */
            $TECHNOLOGY_REMOULD = function () use (
                $tempTaskSubOrder,
                $tempTaskSubOrderModels,
                $tempTask,
                $tempTaskTypes,
                $tempTaskAccessories,
                $principal,
                $tempTaskSubOrderEntireInstances,
                $tempTaskSubOrderStatuses,
                $statistics
            ) {
                # 获取种类型
                $statisticsRootDir = storage_path('app/basicInfo');
                if (!is_dir($statisticsRootDir)) return back()->withInput()->with('danger', '基础缓存数据不存在');
                if (!file_exists("{$statisticsRootDir}/models.json")) return back()->withInput()->with('danger', '基础缓存数据不存在：种类和型号');
                $models = json_decode(file_get_contents("{$statisticsRootDir}/models.json"), true);

                return view("TempTaskSubOrder.edit_TECHNOLOGY_REMOULD", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTaskSubOrderModels' => $tempTaskSubOrderModels,
                    'tempTask' => $tempTask,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskAccessories' => $tempTaskAccessories,
                    'principal' => $principal,
                    'tempTaskSubOrderEntireInstances' => $tempTaskSubOrderEntireInstances,
                    'tempTaskSubOrderStatuses' => (array)$tempTaskSubOrderStatuses,
                    'statistics' => $statistics,
                    'modelsAsJson' => json_encode($models),
                ]);
            };

            $func = array_flip((array)$tempTaskTypes)[$tempTaskSubOrder->temp_task->type];
            return $$func();
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/tempTask')->with('danger', '意外错误');
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function update(Request $request, $id)
    {
        try {
            $params = array_merge($request->except('_method', 'number'), ['nonce' => TextFacade::rand()]);
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->put(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
            if ($this->_curl->error) back()->withInput()->with('danger', $this->_curl->response->msg);

            return back()->with('success', '保存成功');
        } catch (\Throwable $e) {
            return back()->withInput()->with('danger', '意外错误');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($id)
    {
        try {
            $tempTaskSubOrderEntireInstanceCount = TempTaskSubOrderEntireInstance::with([])
                ->where('temp_task_sub_order_id', $id)
                ->count('id');

            if ($tempTaskSubOrderEntireInstanceCount > 0) {
                return JsonResponseFacade::errorForbidden('子任务已经分配设备，不能删除');
            } else {
                $params = ['nonce' => TextFacade::rand()];
                $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
                $this->_curl->delete(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);
                return JsonResponseFacade::deleted();
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('没有找到子任务');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 临时生产子任务 交付
     * @param Request $request
     * @param $id
     * @return array|\Illuminate\Http\JsonResponse
     */
    final public function putDelivery(Request $request, $id)
    {
        try {
            # 获取子任务详情
            $params = ['nonce' => TextFacade::rand()];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);
            ['temp_task_sub_order' => $tempTaskSubOrder, 'temp_task_types' => $tempTaskTypes] = (array)$this->_curl->response->data;
            $tempTask = $tempTaskSubOrder->temp_task;

            # 标记任务：交付
            $params = array_merge($request->all(), ['status' => 'DELIVERY', 'nonce' => TextFacade::rand()]);
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->put(env('GROUP_URL') . "/tempTaskSubOrder/{$id}/delivery", $params);
            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);

            $tempTaskSubOrderSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])->where('temp_task_sub_order_id', $id)->get();
            EntireInstanceLock::freeLocks($tempTaskSubOrderSubOrderEntireInstances->pluck('old_entire_instance_identity_code')->toArray(), [array_flip((array)$tempTaskTypes)[$tempTask->type]]);
            EntireInstanceLock::freeLocks($tempTaskSubOrderSubOrderEntireInstances->pluck('new_entire_instance_identity_code')->toArray(), [array_flip((array)$tempTaskTypes)[$tempTask->type]]);

            return JsonResponseFacade::updated('交付成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 临时生产子任务 出入所扫码 页面
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function getWarehouse(int $id)
    {
        try {
            /**
             * 新站出所
             */
            $NEW_STATION_OUT = function () use ($id) {
                $params = ['nonce' => TextFacade::rand()];
                $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
                $this->_curl->get(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
                [
                    'temp_task_sub_order' => $tempTaskSubOrder,
                    'temp_task_types' => $tempTaskTypes,
                    'temp_task_sub_order_statuses' => $tempTaskSubOrderStatuses,
                ] = (array)$this->_curl->response->data;

                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([
                    'NewEntireInstance',
                ])
                    ->where('is_finished', false)
                    ->where('out_warehouse_sn', '')
                    ->get();

                return view("TempTaskSubOrder.warehouse_NEW_STATION_OUT", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskSubOrderStatuses' => (array)$tempTaskSubOrderStatuses,
                    'tempTaskSubOrderEntireInstances' => $tempTaskSubOrderEntireInstances,
                ]);
            };

            /**
             * 大修入所
             */
            $FULL_FIX_IN = function () use ($id) {

            };

            /**
             * 大修出所
             */
            $FULL_FIX_OUT = function () use ($id) {

            };

            /**
             * 高频修入所
             */
            $HIGH_FREQUENCY_IN = function () use ($id) {
                $params = ['nonce' => TextFacade::rand()];
                $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
                $this->_curl->get(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
                [
                    'temp_task_sub_order' => $tempTaskSubOrder,
                    'temp_task_types' => $tempTaskTypes,
                    'temp_task_sub_order_statuses' => $tempTaskSubOrderStatuses,
                ] = (array)$this->_curl->response->data;

                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([
                    'OldEntireInstance',
                ])
                    ->where('is_finished', false)
                    ->where('in_warehouse_sn', '')
                    ->get();

                return view("TempTaskSubOrder.warehouse_HIGH_FREQUENCY_IN", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskSubOrderStatuses' => (array)$tempTaskSubOrderStatuses,
                    'tempTaskSubOrderEntireInstances' => $tempTaskSubOrderEntireInstances,
                ]);
            };

            /**
             * 高频修出所
             */
            $HIGH_FREQUENCY_OUT = function () use ($id) {
                $params = ['nonce' => TextFacade::rand()];
                $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
                $this->_curl->get(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
                [
                    'temp_task_sub_order' => $tempTaskSubOrder,
                    'temp_task_types' => $tempTaskTypes,
                    'temp_task_sub_order_statuses' => $tempTaskSubOrderStatuses,
                ] = (array)$this->_curl->response->data;
                $tempTask = $tempTaskSubOrder->temp_task;

                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([
                    'NewEntireInstance',
                ])
                    ->where('is_finished', false)
                    ->where('out_warehouse_sn', '')
                    ->get();

                return view("TempTaskSubOrder.warehouse_HIGH_FREQUENCY_OUT", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTask' => $tempTask,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskSubOrderStatuses' => (array)$tempTaskSubOrderStatuses,
                    'tempTaskSubOrderEntireInstances' => $tempTaskSubOrderEntireInstances,
                ]);
            };

            $type = strtoupper(request('type'));
            $direction = strtoupper(request('direction'));
            $func = "{$type}_{$direction}";
            return $$func();
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 临时生产子任务
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    final public function postWarehouse(Request $request, int $id)
    {
        try {
            /**
             * 新站出所
             * @return mixed
             */
            $NEW_STATION_OUT = function () use ($request, $id) {
                # 获取任务相关设备
                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])
                    ->where('temp_task_sub_order_id', $id)
                    ->where('is_finished', false)
                    ->where('out_warehouse_sn', '')
                    ->where('out_scan', true)
                    ->get();
                if ($tempTaskSubOrderEntireInstances->isEmpty()) return JsonResponseFacade::errorEmpty('没有需要操作的设备');

                $entireInstances = EntireInstance::with([])
                    ->whereIn('identity_code', $tempTaskSubOrderEntireInstances->pluck('new_entire_instance_identity_code')->toArray())
                    ->get();
                $tempTaskSubOrderEntireInstanceIdentityCodes = $tempTaskSubOrderEntireInstances->pluck('new_entire_instance_identity_code')->toArray();
                $entireInstanceIdentityCodes = $entireInstances->pluck('identity_code')->toArray();
                $diff = array_diff($tempTaskSubOrderEntireInstanceIdentityCodes, $entireInstanceIdentityCodes);
                if ($diff) return JsonResponseFacade::errorEmpty('以下设备没有找到：\r\n' . implode('\r\n', $diff));

                DB::beginTransaction();
                # 更新设备临时生产任务和子任务编号
                DB::table('entire_instances as ei')
                    ->where('ei.deleted_at', null)
                    ->whereIn('identity_code', $entireInstanceIdentityCodes)
                    ->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id')
                    ]);

                # 标准出所流程
                $warehouseReportSN = WarehouseReportFacade::batchOutWithEntireInstanceIdentityCodes(
                    $entireInstanceIdentityCodes,
                    $request->get('processor_id'),
                    Carbon::createFromFormat('Y-m-d H:i', "{$request->get('processed_date')} {$request->get('processed_time')}")->format('Y-m-d H:i:s'),
                    strtoupper($request->get('type')),
                    $request->get('connection_name') ?? '',
                    $request->get('connection_phone') ?? ''
                );

                # 修改任务单中设备的入所单号
                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])
                    ->whereIn('old_entire_instance_identity_code', $entireInstanceIdentityCodes)
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

            /**
             * 高频/状态修入所
             */
            $HIGH_FREQUENCY_IN = function () use ($request, $id) {
                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])
                    ->where('temp_task_sub_order_id', $id)
                    ->where('is_finished', false)
                    ->where('in_warehouse_sn', '')
                    ->where('in_scan', true)
                    ->get();
                if ($tempTaskSubOrderEntireInstances->isEmpty()) return JsonResponseFacade::errorEmpty('没有需要操作的设备');

                $entireInstances = EntireInstance::with([])
                    ->whereIn('identity_code', $tempTaskSubOrderEntireInstances->pluck('old_entire_instance_identity_code')->toArray())
                    ->get();
                $tempTaskSubOrderEntireInstanceIdentityCodes = $tempTaskSubOrderEntireInstances->pluck('old_entire_instance_identity_code')->toArray();
                $entireInstanceIdentityCodes = $entireInstances->pluck('identity_code')->toArray();
                $diff = array_diff($tempTaskSubOrderEntireInstanceIdentityCodes, $entireInstanceIdentityCodes);
                if ($diff) return JsonResponseFacade::errorEmpty('以下设备没有找到：\r\n' . implode('\r\n', $diff));

                DB::beginTransaction();
                # 更新设备临时生产任务和子任务编号
                DB::table('entire_instances as ei')
                    ->where('ei.deleted_at', null)
                    ->whereIn('identity_code', $entireInstanceIdentityCodes)
                    ->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id')
                    ]);

                # 标准入所流程
                $warehouseReportSN = WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                    $entireInstanceIdentityCodes,
                    $request->get('processor_id'),
                    Carbon::createFromFormat('Y-m-d H:i', "{$request->get('processed_date')} {$request->get('processed_time')}")->format('Y-m-d H:i:s'),
                    strtoupper($request->get('type')),
                    $request->get('connection_name') ?? '',
                    $request->get('connection_phone') ?? ''
                );

                # 修改任务单中设备的入所单号
                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])
                    ->whereIn('old_entire_instance_identity_code', $entireInstanceIdentityCodes)
                    ->orderBy('id')
                    ->chunk(30, function ($tempTaskSubOrderEntireInstances) use ($warehouseReportSN) {
                        foreach ($tempTaskSubOrderEntireInstances as $tempTaskSubOrderEntireInstance) {
                            $tempTaskSubOrderEntireInstance->fill([
                                'in_warehouse_sn' => $warehouseReportSN,
                                'is_finished' => boolval(!empty($tempTaskSubOrderEntireInstance->out_warehouse_sn) && !empty($tempTaskSubOrderEntireInstance->fix_workflow_sn)),
                            ])
                                ->saveOrFail();
                        }
                    });
                DB::commit();
                return JsonResponseFacade::created(['warehouse_report_serial_number' => $warehouseReportSN], '入所成功');
            };

            /**
             * 高频修出所
             * @return mixed
             */
            $HIGH_FREQUENCY_OUT = function () use ($request, $id) {
                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with(['OldEntireInstance', 'NewEntireInstance'])
                    ->where('temp_task_sub_order_id', $id)
                    ->where('is_finished', false)
                    ->where('out_warehouse_sn', '')
                    ->where('out_scan', true)
                    ->get();
                if ($tempTaskSubOrderEntireInstances->isEmpty()) return JsonResponseFacade::errorEmpty('没有需要操作的设备');

                $entireInstances = EntireInstance::with([])
                    ->whereIn('identity_code', $tempTaskSubOrderEntireInstances->pluck('new_entire_instance_identity_code')->toArray())
                    ->get();
                $tempTaskSubOrderEntireInstanceIdentityCodes = $tempTaskSubOrderEntireInstances->pluck('new_entire_instance_identity_code')->toArray();
                $entireInstanceIdentityCodes = $entireInstances->pluck('identity_code')->toArray();
                $diff = array_diff($tempTaskSubOrderEntireInstanceIdentityCodes, $entireInstanceIdentityCodes);
                if ($diff) return JsonResponseFacade::errorEmpty('以下设备没有找到：\r\n' . implode('\r\n', $diff));

                DB::beginTransaction();
                # 更新设备临时生产任务和子任务编号
                DB::table('entire_instances as ei')
                    ->where('ei.deleted_at', null)
                    ->whereIn('identity_code', $entireInstanceIdentityCodes)
                    ->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'temp_task_id' => $request->get('temp_task_id'),
                        'temp_task_sub_order_id' => $request->get('temp_task_sub_order_id')
                    ]);

                # 替换设备上道位置
                foreach ($tempTaskSubOrderEntireInstances as $tempTaskSubOrderEntireInstance) {
                    $tempTaskSubOrderEntireInstance->OldEntireInstance->fill(['status' => 'UNINSTALLED'])->saveOrFail();
                    $tempTaskSubOrderEntireInstance->NewEntireInstance
                        ->fill([
                            'maintain_workshop_name' => $request->get('scene_workshop_name'),
                            'maintain_station_name' => $request->get('maintain_station_name'),
                            'maintain_location_code' => $tempTaskSubOrderEntireInstance->maintain_location_code,
                            'crossroad_number' => $tempTaskSubOrderEntireInstance->crossroad_number,
                            'open_direction' => $tempTaskSubOrderEntireInstance->open_direction,
                        ])
                        ->saveOrFail();
                }

                # 标准所流程
                $warehouseReportSN = WarehouseReportFacade::batchOutWithEntireInstanceIdentityCodes(
                    $entireInstanceIdentityCodes,
                    $request->get('processor_id'),
                    Carbon::createFromFormat('Y-m-d H:i', "{$request->get('processed_date')} {$request->get('processed_time')}")->format('Y-m-d H:i:s'),
                    strtoupper($request->get('type')),
                    $request->get('connection_name') ?? '',
                    $request->get('connection_phone') ?? ''
                );

                # 修改任务单中设备的入所单号
                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([])
                    ->whereIn('new_entire_instance_identity_code', $entireInstanceIdentityCodes)
                    ->orderBy('id')
                    ->chunk(30, function ($tempTaskSubOrderEntireInstances) use ($warehouseReportSN) {
                        foreach ($tempTaskSubOrderEntireInstances as $tempTaskSubOrderEntireInstance) {
                            $tempTaskSubOrderEntireInstance->fill([
                                'out_warehouse_sn' => $warehouseReportSN,
                                'is_finished' => boolval(!empty($tempTaskSubOrderEntireInstance->in_warehouse_sn) && !empty($tempTaskSubOrderEntireInstance->fix_workflow_sn)),
                            ])
                                ->saveOrFail();
                        }
                    });
                DB::commit();
                return JsonResponseFacade::created(['warehouse_report_serial_number' => $warehouseReportSN], '出所成功');
            };

            $type = strtoupper($request->get('type'));
            $direction = strtoupper($request->get('direction'));
            $func = "{$type}_{$direction}";
            return $$func();
        } catch (MaintainNotFoundException $e) {
            return JsonResponseFacade::errorEmpty($e->getMessage());
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取帮助信息
     */
    final public function getHelp()
    {
        try {
            $NEW_STATION = function () {
                $message = "1. 导入新设备（带位置）
2. 打印二维码标签
3. 通过扫描二维码添加到高频/状态修任务中
4. 导入检修人和验收人
5. 添加出所单和出所
6. 交付";
                return JsonResponseFacade::data(['message' => $message]);
            };
            $FULL_FIX = function () {
                $message = "";
                return JsonResponseFacade::data(['message' => $message]);
            };
            $HIGH_FREQUENCY = function () {
                $message = "1. 通过右侧顶部搜索找到需要添加的设备
2. 通过弹窗选择具体需要入所的设备
3. 添加完毕后，选择绑定设备
4. 导入检修人和验收人
5. 添加出所单和出所
6. 添加入所单和入所
7. 交付";
                return JsonResponseFacade::data(['message' => $message]);
            };

            $func = strtoupper(request('type'));
            return $$func();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 绑定位置页面
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function getBindEntireInstance(int $id)
    {
        try {
            /**
             * 高频修
             */
            $HIGH_FREQUENCY = function () use ($id) {
                # 获取子任务详情
                $params = ['nonce' => TextFacade::rand()];
                $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
                $this->_curl->get(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
                if ($this->_curl->error) return back()->with('danger', $this->_curl->response->msg);
                ['temp_task_sub_order' => $tempTaskSubOrder, 'temp_task_types' => $tempTaskTypes, 'temp_task_sub_order_statuses' => $tempTaskSubOrderStatuses] = (array)$this->_curl->response->data;
                $tempTask = $tempTaskSubOrder->temp_task;

                $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with([
                    'OldEntireInstance',
                    'NewEntireInstance',
                ])
                    ->where('temp_task_sub_order_id', $id)
                    ->where('is_finished', false)
                    ->get();

                # 获取未绑定设备总数
                $unBindCount = TempTaskSubOrderEntireInstance::with([])
                    ->where('temp_task_sub_order_id', $id)
                    ->where('is_finished', false)
                    ->where('new_entire_instance_identity_code', '')
                    ->count();

                $usableEntireInstances = $this->_getUsableEntireInstances($tempTaskSubOrderEntireInstances->pluck('model_name')->toArray());
                $usableEntireInstanceSum = $usableEntireInstances->sum(function ($value) {
                    return $value->count();
                });

                $isAllBound = !boolval(TempTaskSubOrderEntireInstance::with([])->where('is_finished', false)->count() > 0);

                return view('TempTaskSubOrder.bind_HIGH_FREQUENCY', [
                    'tempTaskSubOrderEntireInstances' => $tempTaskSubOrderEntireInstances,
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTask' => $tempTask,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskSuborderStatuses' => (array)$tempTaskSubOrderStatuses,
                    'unBindCount' => $unBindCount,
                    'usableEntireInstances' => $usableEntireInstances,
                    'usableEntireInstanceSum' => $usableEntireInstanceSum,
                    'isAllBound' => $isAllBound,
                ]);
            };

            $func = strtoupper(request('type'));
            return $$func();
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 根据出所单号，获取该入所单
     * @param array $modelNames
     * @return EntireInstance[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|mixed[]
     */
    final private function _getUsableEntireInstances(array $modelNames)
    {
        $mustWarehouseLocation = false;  # 必须有仓库位置编号

        # 获取可用的新设备
        return EntireInstance::with(['FixWorkflows'])
            ->withCount('FixWorkflows')
            ->where('status', 'FIXED')
            ->when($mustWarehouseLocation, function ($query) {
                return $query->where('location_unique_code', '<>', '');
            })
            ->whereNotIn('identity_code', DB::table('entire_instance_locks')->pluck('entire_instance_identity_code')->toArray())
            ->whereIn('model_name', $modelNames)
            ->orderByDesc('made_at')
            ->orderBy('fix_workflows_count')
            ->get()
            ->groupBy('model_name');
    }

    /**
     * 绑定设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postBindEntireInstance(Request $request)
    {
        try {
            DB::beginTransaction();
            $oldEntireInstance = EntireInstance::with([])->where('identity_code', $request->get('oldIdentityCode'))->first();
            if (!$oldEntireInstance) return JsonResponseFacade::errorEmpty("待下道设备：{$request->get('oldIdentityCode')}不存在");
            $newEntireInstance = EntireInstance::with([])->where('identity_code', $request->get('newIdentityCode'))->first();
            if (!$newEntireInstance) return JsonResponseFacade::errorEmpty("所内备品：{$request->get('newIdentityCode')}不存在");

            $this->_bindEntireInstance(
                $oldEntireInstance,
                $newEntireInstance,
                strtoupper($request->get('type')),
                $request->get('temp_task_id'),
                $request->get('temp_task_sub_order_id'),
                $request->get('temp_task_serial_number'),
                $request->get('temp_task_title'),
                $request->get('temp_task_sub_order_work_area_id'),
                $request->get('temp_task_sub_order_maintain_station_name')
            );
            DB::commit();

            return JsonResponseFacade::created([], '绑定成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 绑定设备
     * @param EntireInstance $oldEntireInstance
     * @param EntireInstance $newEntireInstance
     * @param string $lockName
     * @param int $tempTaskId
     * @param int $tempTaskSubOrderId
     * @param string $tempTaskSerialNumber
     * @param string $tempTaskTitle
     * @param int $tempTaskSubOrderWorkAreaId
     * @param string $tempTaskSubOrderMaintainStationName
     * @return bool
     * @throws \Throwable
     */
    final public function _bindEntireInstance(
        EntireInstance $oldEntireInstance,
        EntireInstance $newEntireInstance,
        string $lockName,
        int $tempTaskId,
        int $tempTaskSubOrderId,
        string $tempTaskSerialNumber,
        string $tempTaskTitle,
        int $tempTaskSubOrderWorkAreaId,
        string $tempTaskSubOrderMaintainStationName
    )
    {
        DB::beginTransaction();
        # 设置绑定关系
        $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with([])->where('old_entire_instance_identity_code', $oldEntireInstance->identity_code)->firstOrFail();
        if ($tempTaskSubOrderEntireInstance->new_entire_instance_identity_code) {
            # 如果已经绑定了成品设备，先给成品设备解绑
            EntireInstanceLock::freeLock($tempTaskSubOrderEntireInstance->new_entire_instance_identity_code, [$lockName]);
            DB::table('entire_instances as ei')->where('deleted_at', null)->where('ei.identity_code', $tempTaskSubOrderEntireInstance->new_entire_instance_identity_code)->update(['updated_at' => now(), 'temp_task_id' => 0, 'temp_task_sub_order_id' => 0]);
        }
        # 绑定成品设备
        $tempTaskSubOrderEntireInstance->fill(['new_entire_instance_identity_code' => $newEntireInstance->identity_code])->saveOrFail();

        # 修改成品设备临时生产任务标识
        $newEntireInstance->fill(['temp_task_id' => $tempTaskId, 'temp_task_sub_order_id' => $tempTaskSubOrderId])->saveOrFail();

        # 设备加锁
        $lock = EntireInstanceLock::setOnlyLock(
            $newEntireInstance->identity_code,
            [strtoupper($lockName)],
            "设备器材：{$newEntireInstance->identity_code}在临时生产任务中被使用；详情：[{$tempTaskSerialNumber}]{$tempTaskTitle}"
            . Account::$WORK_AREAS[$tempTaskSubOrderWorkAreaId]
            . "({$tempTaskSubOrderMaintainStationName})"
        );
        DB::commit();

        return $lock;
    }

    /**
     * 解绑设备
     * @param Request $request
     */
    final public function deleteBindEntireInstance(Request $request)
    {
        try {
            DB::beginTransaction();
            # 获取任务设备
            $tempTaskSubOrderEntireInstance = TempTaskSubOrderEntireInstance::with(['OldEntireInstance', 'NewEntireInstance'])
                ->where('temp_task_sub_order_id', $request->get('temp_task_sub_order_id'))
                ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                ->firstOrFail();
            EntireInstanceLock::freeLock(
                $tempTaskSubOrderEntireInstance->new_entire_instance_identity_code,
                [strtoupper($request->get('type'))],
                function () use ($tempTaskSubOrderEntireInstance) {
                    $tempTaskSubOrderEntireInstance->NewEntireInstance->fill(['temp_task_id' => 0, 'temp_task_sub_order_id' => 0])->saveOrFail();
                    $tempTaskSubOrderEntireInstance->fill(['new_entire_instance_identity_code' => ''])->saveOrFail();
                });
            DB::commit();

            return JsonResponseFacade::deleted('解绑成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 全部解绑
     * @param Request $request
     */
    final public function deleteBindEntireInstances(Request $request)
    {
        try {
            DB::beginTransaction();
            # 获取已经上锁的设备
            $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with(['OldEntireInstance', 'NewEntireInstance'])
                ->where('temp_task_id', $request->get('temp_task_id'))
                ->where('temp_task_sub_order_id', $request->get('temp_task_sub_order_id'))
                ->get();

            # 设备解锁
            EntireInstanceLock::freeLocks(
                array_merge($tempTaskSubOrderEntireInstances->pluck('old_entire_instance_identity_code')->toArray(),
                    $tempTaskSubOrderEntireInstances->pluck('new_entire_instance_identity_code')->toArray()),
                [strtoupper($request->get('type'))],
                function () use ($tempTaskSubOrderEntireInstances) {
                    $tempTaskSubOrderEntireInstances->each(function ($tempTaskSubOrderEntireInstance) {
                        $tempTaskSubOrderEntireInstance->NewEntireInstance->fill(['temp_task_id' => 0, 'temp_task_sub_order_id' => 0])->saveOrfail();
                        $tempTaskSubOrderEntireInstance->fill(['new_entire_instance_identity_code' => ''])->saveOrFail();
                    });
                }
            );
            DB::commit();
            return JsonResponseFacade::deleted('解绑成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 自动绑定
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postAutoBindEntireInstance(Request $request)
    {
        try {
            $oldEntireInstance = EntireInstance::with([])->where('identity_code', $request->get('oldIdentityCode'))->first();
            if (!$oldEntireInstance) return JsonResponseFacade::errorEmpty("待下道设备器材：{$request->get('oldIdentityCode')}不存在");

            $usableEntireInstances = $this->_getUsableEntireInstances([$oldEntireInstance->model_name]);

            $lock = $this->_bindEntireInstance(
                $oldEntireInstance,
                $usableEntireInstances->first()->first(),
                strtoupper($request->get('type')),
                $request->get('temp_task_id'),
                $request->get('temp_task_sub_order_id'),
                $request->get('temp_task_serial_number'),
                $request->get('temp_task_title'),
                $request->get('temp_task_sub_order_work_area_id'),
                $request->get('temp_task_sub_order_maintain_station_name')
            );

            return JsonResponseFacade::created(['lock' => $lock], '绑定成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 自动绑定（全部）
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postAutoBindEntireInstances(Request $request)
    {
        try {
            DB::beginTransaction();
            # 获取该子任务下所有设备未绑定设备
            $tempTaskSubOrderEntireInstances = TempTaskSubOrderEntireInstance::with(['OldEntireInstance'])
                ->where('new_entire_instance_identity_code', '')
                ->where('is_finished', false)
                ->where('temp_task_id', $request->get('temp_task_id'))
                ->where('temp_task_sub_order_id', $request->get('temp_task_sub_order_id'))
                ->get();
            $modelNames = $tempTaskSubOrderEntireInstances->pluck('model_name')->unique()->toArray();

            $usableEntireInstances = $this->_getUsableEntireInstances($modelNames);
            $diffModelNames = array_diff($modelNames, $usableEntireInstances->keys()->toArray());
            if ($diffModelNames) return JsonResponseFacade::errorEmpty("以下型号没有找到成品：" . implode("\r\n", $diffModelNames));

            $tempTaskSubOrderEntireInstances->groupBy('model_name')
                ->each(function ($entireInstances, $modelName) use ($request, $usableEntireInstances) {
                    foreach ($entireInstances as $entireInstance) {
                        $this->_bindEntireInstance(
                            $entireInstance->OldEntireInstance,
                            $usableEntireInstances->get($modelName)->shift(),
                            strtoupper($request->get('type')),
                            $request->get('temp_task_id'),
                            $request->get('temp_task_sub_order_id'),
                            $request->get('temp_task_serial_number'),
                            $request->get('temp_task_title'),
                            $request->get('temp_task_sub_order_work_area_id'),
                            $request->get('temp_task_sub_order_maintain_station_name')
                        );
                    }
                });
            DB::commit();

            return JsonResponseFacade::created([], '绑定成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
