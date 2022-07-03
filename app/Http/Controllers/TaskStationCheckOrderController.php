<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\Account;
use App\Model\Maintain;
use Illuminate\Http\Request;
use App\Model\TaskStationCheckOrder;
use App\Model\WorkArea;

class TaskStationCheckOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $task_station_check_orders = TaskStationCheckOrder::with([])->orderByDesc('id')->paginate();
            return view('TaskStationCheckOrder.index', [
                'task_station_check_orders' => $task_station_check_orders
            ]);
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create()
    {
        try {
            // 获取工区列表
            $work_areas = WorkArea::with([])->where('workshop_unique_code', env('ORGANIZATION_CODE'))->get();

            # 获取车站列表
            $stations = Maintain::with(['Parent'])
                ->where('type', 'STATION')
                ->whereHas('Parent', function ($Parent) {
                    $Parent->where('parent_unique_code', env('ORGANIZATION_CODE'));
                })
                ->get();

            # 获取1级人员列表
            $accounts_level_1 = Account::with([])->where('task_station_check_account_level_id', 1)->get();
            # 获取1级人员列表
            $accounts_level_2 = Account::with([])->where('task_station_check_account_level_id', 2)->get();
            # 获取1级人员列表
            $accounts_level_3 = Account::with([])->where('task_station_check_account_level_id', 3)->get();
            # 获取1级人员列表
            $accounts_level_4 = Account::with([])->where('task_station_check_account_level_id', 4)->get();

            return view('TaskStationCheckOrder.create', [
                'work_areas' => $work_areas,
                'stations' => $stations,
                'accounts_level_1' => $accounts_level_1,
                'accounts_level_2' => $accounts_level_2,
                'accounts_level_3' => $accounts_level_3,
                'accounts_level_4' => $accounts_level_4,
            ]);
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
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
            if (!$request->get('maintain_station_unique_code')) return JsonResponseFacade::errorForbidden('车站没有选择');
            if (!$request->get('beginning_at')) return JsonResponseFacade::errorForbidden('开始时间不能为空');
            if (!$request->get('expiring_at')) return JsonResponseFacade::errorForbidden('截至时间不能为空');
            if (!$request->get('principal_id_level_1')) return JsonResponseFacade::errorForbidden('1级负责人没有选择');
            if (!$request->get('principal_id_level_2')) return JsonResponseFacade::errorForbidden('2级负责人没有选择');
            if (!$request->get('principal_id_level_3')) return JsonResponseFacade::errorForbidden('3级负责人没有选择');
            if (!$request->get('principal_id_level_4')) return JsonResponseFacade::errorForbidden('4级负责人没有选择');

            $maintain = Maintain::with(['Parent'])
                ->whereHas('Parent', function ($Parent) {
                    $Parent->where('type', 'SCENE_WORKSHOP')->where('parent_unique_code', env('ORGANIZATION_CODE'));
                })
                ->where('unique_code', $request->get('maintain_station_unique_code'))
                ->where('type', 'STATION')
                ->first();

            if (!$maintain) return JsonResponseFacade::errorForbidden("车站没有找到");
            if (!$maintain->Parent) return JsonResponseFacade::errorForbidden("车站没有所属现场车间");



            return JsonResponseFacade::dump($request->all());
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
        //
    }
}
