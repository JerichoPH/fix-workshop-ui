<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use Curl\Curl;
use Illuminate\Http\Request;
use Jericho\TextHelper;

class TempTaskSubOrderModelController extends Controller
{
    private $_curl;
    private $_workAreas = [];

    public function __construct()
    {
        $this->_curl = new Curl();
        $this->_curl->setHeader('Access-Key', env('GROUP_ACCESS_KEY'));
        $this->_workAreas = array_flip(Account::$WORK_AREAS);
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
        try {
            $params = ['nonce' => TextHelper::rand()];
            $this->_curl->setHeader('Sign', TextHelper::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/tempTask/" . request('temp_task_id'), $params);
            ['temp_task' => $tempTask, 'temp_task_types' => $tempTaskTypes, 'temp_task_statuses' => $tempTaskStatuses] = (array)$this->_curl->response->data;
            $tempTaskAccessories = $tempTask->accessories;
            $viewName = array_flip((array)$tempTaskTypes)[$tempTask->type];

            # 创建新临时生产子任务
            $params = [
                'temp_task_id' => $tempTask->id,
                'principal_id' => session('account.id'),
                'work_area_id' => request('work_area_id'),
                'nonce' => TextHelper::rand(),
            ];
            $this->_curl->setHeader('Sign', TextHelper::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->post(env('GROUP_URL') . '/tempTaskSubOrder', $params);
            if ($this->_curl->error) return back()->with('danger', $this->_curl->response->msg);
            ['temp_task_sub_order' => $tempTaskSubOrder] = (array)$this->_curl->response->data;
            $tempTaskSubOrderModels = $tempTaskSubOrder->temp_task_sub_order_models;

            # 工区负责人
            $workAreaPrincipal = Account::with([])->where('work_area', request('work_area_id'))->where('temp_task_position', 'WorkshopWorkArea')->first();

            # 现场车间
            $statisticsRootDir = storage_path('app/basicInfo');
            if (!file_exists("{$statisticsRootDir}/stations.json")) return back()->with('danger', '现场车间、车站缓存不存在');
            $sceneWorkshops = file_get_contents("{$statisticsRootDir}/stations.json");

            # 种类型
            if (!file_exists("{$statisticsRootDir}/models.json")) return back()->with('danger', '种类型缓存不存在');
            $models = file_get_contents("{$statisticsRootDir}/models.json");

            return view("tempTaskSubOrderModel.create_{$viewName}", [
                'tempTask' => $tempTask,
                'tempTaskAccessories' => $tempTaskAccessories,
                'tempTaskTypes' => (array)$tempTaskTypes,
                'tempTaskStatuses' => (array)$tempTaskStatuses,
                'tempTaskSubOrder' => $tempTaskSubOrder,
                'tempTaskSubOrderModels' => $tempTaskSubOrderModels,
                'workAreaPrincipal' => (array)$workAreaPrincipal,
                'sceneWorkshopsAsJson' => $sceneWorkshops,
                'modelsAsJson' => $models,
            ]);
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function store(Request $request)
    {
        try {
            $params = array_merge($request->all(), ['nonce' => TextHelper::rand()]);
            $this->_curl->setHeader('Sign', TextHelper::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->post(env('GROUP_URL') . "/tempTaskSubOrderModel", $params);
            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);

            return JsonResponseFacade::created(['temp_task_sub_order_model' => $this->_curl->response->data->temp_task_sub_order_model]);
        } catch (\Exception $e) {
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
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function edit($id)
    {
        try {
            $params = ['nonce' => TextHelper::rand()];
            $this->_curl->setHeader('Sign', TextHelper::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/tempTaskSubOrder/{$id}", $params);
            if ($this->_curl->error) return back()->with('danger', $this->_curl->response->msg);
            ['temp_task_sub_order' => $tempTaskSubOrder, 'temp_task_types' => $tempTaskTypes] = (array)$this->_curl->response->data;
            $tempTask = $tempTaskSubOrder->temp_task;
            $tempTaskAccessories = $tempTask->accessories;
            $tempTaskSubOrderModels = $tempTaskSubOrder->temp_task_sub_order_models;

            # 现场车间
            $statisticsRootDir = storage_path('app/basicInfo');
            if (!file_exists("{$statisticsRootDir}/stations.json")) return back()->with('danger', '现场车间、车站缓存不存在');
            $sceneWorkshops = file_get_contents("{$statisticsRootDir}/stations.json");

            # 种类型
            if (!file_exists("{$statisticsRootDir}/models.json")) return back()->with('danger', '种类型缓存不存在');
            $models = file_get_contents("{$statisticsRootDir}/models.json");

            /**
             * 新站
             * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
             */
            $NEW_STATION = function () use (
                $tempTaskSubOrder,
                $tempTask,
                $tempTaskAccessories,
                $tempTaskSubOrderModels,
                $models,
                $tempTaskTypes,
                $sceneWorkshops
            ) {
                return view("TempTaskSubOrderModel.edit_NEW_STATION", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTask' => $tempTask,
                    'tempTaskAccessories' => $tempTaskAccessories,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskSubOrderModels' => $tempTaskSubOrderModels,
                    'sceneWorkshopsAsJson' => $sceneWorkshops,
                    'workAreas' => Account::$WORK_AREAS,
                    'modelsAsJson' => $models,
                ]);
            };

            /**
             * 大修
             * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
             */
            $FULL_FIX = function () use (
                $tempTaskSubOrder,
                $tempTask,
                $tempTaskAccessories,
                $tempTaskSubOrderModels,
                $models,
                $tempTaskTypes,
                $sceneWorkshops
            ) {
                # 获取全站设备（超期）
                $entireInstancesForScraped = EntireInstance::with([])
                    ->where('maintain_station_name', $tempTaskSubOrder->maintain_station_name)
                    ->where('scarping_at', '<', now())
                    ->get();

                # 获取全站设备（未超期）
                $entireInstancesForNormal = EntireInstance::with([])
                    ->where('maintain_station_name', $tempTaskSubOrder->maintain_station_name)
                    ->where(function ($query) {
                        return $query->where('scarping_at', '>', now())
                            ->orWhere('scarping_at', null);
                    })
                    ->get();

                $models = EntireInstance::with([])
                    ->select(['model_unique_code', 'model_name'])
                    ->where('maintain_station_name', $tempTaskSubOrder->maintain_station_name)
                    ->get()
                    ->pluck('model_name', 'model_unique_code');

                return view("TempTaskSubOrderModel.edit_FULL_FIX", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTask' => $tempTask,
                    'tempTaskAccessories' => $tempTaskAccessories,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskSubOrderModels' => $tempTaskSubOrderModels,
                    'sceneWorkshopsAsJson' => $sceneWorkshops,
                    'modelsAsJson' => $models,
                    'workAreas' => Account::$WORK_AREAS,
                    'entireInstancesForScraped' => $entireInstancesForScraped,
                    'entireInstancesForNormal' => $entireInstancesForNormal,
                    'models' => $models,
                ]);
            };

            /**
             * 高频/状态修
             */
            $HIGH_FREQUENCY = function () use (
                $tempTaskSubOrder,
                $tempTask,
                $tempTaskAccessories,
                $tempTaskSubOrderModels,
                $models,
                $tempTaskTypes,
                $sceneWorkshops
            ) {
                return view("TempTaskSubOrderModel.edit_HIGH_FREQUENCY", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTask' => $tempTask,
                    'tempTaskAccessories' => $tempTaskAccessories,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskSubOrderModels' => $tempTaskSubOrderModels,
                    'sceneWorkshopsAsJson' => $sceneWorkshops,
                    'modelsAsJson' => $models,
                    'workAreas' => Account::$WORK_AREAS,
                ]);
            };

            /**
             * 技改
             */
            $TECHNOLOGY_REMOULD = function () use (
                $tempTaskSubOrder,
                $tempTask,
                $tempTaskAccessories,
                $tempTaskSubOrderModels,
                $models,
                $tempTaskTypes,
                $sceneWorkshops
            ) {
                return view("TempTaskSubOrderModel.edit_TECHNOLOGY_REMOULD", [
                    'tempTaskSubOrder' => $tempTaskSubOrder,
                    'tempTask' => $tempTask,
                    'tempTaskAccessories' => $tempTaskAccessories,
                    'tempTaskTypes' => (array)$tempTaskTypes,
                    'tempTaskSubOrderModels' => $tempTaskSubOrderModels,
                    'sceneWorkshopsAsJson' => $sceneWorkshops,
                    'modelsAsJson' => $models,
                    'workAreas' => Account::$WORK_AREAS,
                ]);
            };

            $func = strtoupper(array_flip((array)$tempTaskTypes)[$tempTask->type]);
            return $$func();
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function update(Request $request, $id)
    {
        try {
            $params = array_merge($request->all(), ['nonce' => TextHelper::rand()]);
            $this->_curl->setHeader('Sign', TextHelper::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->put(env('GROUP_URL') . "/tempTaskSubOrderModel/{$id}", $params);
            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);

            return JsonResponseFacade::updated(['temp_task_sub_order_model' => $this->_curl->response->data->temp_task_sub_order_model]);
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function destroy($id)
    {
        try {
            $params = ['nonce' => TextHelper::rand()];
            $this->_curl->setHeader('Sign', TextHelper::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->delete(env('GROUP_URL') . "/tempTaskSubOrderModel/{$id}", $params);
            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);

            return JsonResponseFacade::deleted();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
