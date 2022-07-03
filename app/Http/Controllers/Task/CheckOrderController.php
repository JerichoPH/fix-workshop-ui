<?php

namespace App\Http\Controllers\Task;

use App\Facades\JsonResponseFacade;
use App\Facades\TextFacade;
use App\Model\Account;
use App\Model\CheckPlan;
use App\Model\CheckProject;
use App\Model\Maintain;
use App\Model\TaskStationCheckEntireInstance;
use App\Model\TaskStationCheckOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;
use function Hprose\Future\map;

class CheckOrderController extends Controller
{
    private $_current_time = '';

    public function __construct()
    {
        $this->_current_time = date('Y-m-d H:i:s');
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $taskStationCheckOrders = TaskStationCheckOrder::with(['PrincipalIdLevel3', 'PrincipalIdLevel5'])->orderByDesc('updated_at')->paginate();
            return view('Task.CheckOrder.index', [
                'taskStationCheckOrders' => $taskStationCheckOrders
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create(Request $request)
    {
        try {
            $checkPlanSerialNumber = $request->get('check_plan_serial_number', '');
            $checkPlan = CheckPlan::with(['WithStation', 'WithStation.Parent'])->where('serial_number', $checkPlanSerialNumber)->firstOrFail();
            $workshop_unique_code = $checkPlan->WithStation->parent_unique_code;
            $principalIdLevel3 = DB::table('accounts')->where('deleted_at', null)->where('workshop_unique_code', $workshop_unique_code)->where('rank', 'SceneWorkshopPrincipal')->select('id', 'nickname')->first();
            $principalIdLevel5s = DB::table('accounts')->where('deleted_at', null)->where('workshop_unique_code', $workshop_unique_code)->where('rank', 'SceneWorkAreaCrew')->where('work_area_unique_code', '<>', '')->select('id', 'nickname')->pluck('nickname', 'id')->toArray();
            return view('Task.CheckOrder.create', [
                'checkPlan' => $checkPlan,
                'principalIdLevel3' => $principalIdLevel3,
                'principalIdLevel5s' => $principalIdLevel5s,
                'checkPlanSerialNumber' => $checkPlanSerialNumber,
                'minExpiringAt' => Carbon::createFromTimestamp(strtotime($checkPlan->expiring_at))->startOfMonth()->toDateTimeString(),
                'maxExpiringAt' => $checkPlan->expiring_at
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '检修计划数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    final public function store(Request $request)
    {
        try {
            $checkPlanSerialNumber = $request->get('check_plan_serial_number', '');
            $principal_id_level_3 = $request->get('principal_id_level_3', '');
            $checkPlan = CheckPlan::with(['WithStation'])->where('serial_number', $checkPlanSerialNumber)->firstOrFail();
            $station_unique_code = $checkPlan->station_unique_code;
            $workshop_unique_code = $checkPlan->WithStation->parent_unique_code;
            $principal_id_level_5 = $request->get('principal_id_level_5', '');
            $expiring_at = $request->get('expiring_at', '');
            if (empty($expiring_at)) return JsonResponseFacade::errorValidate('截止时间为空');
            // 获取一级负责人（科长）
            $principal1 = Account::with([])->where('rank', 'SectionChief')->first();
            if (empty($principal1)) return JsonResponseFacade::errorEmpty('一级负责人（科长）不存在');
            // 获取二级负责人（主管工程师）
            $principal2 = Account::with([])->where('workshop_unique_code', $workshop_unique_code)->where('rank', 'EngineerMaster')->first();
            if (empty($principal2)) return JsonResponseFacade::errorEmpty('二级负责人（主管工程师）不存在');
            $principal5 = Account::with(['WorkAreaByUniqueCode'])->where('id', $principal_id_level_5)->first();
            if (empty($principal5)) return JsonResponseFacade::errorEmpty('现场工区职工不存在');
            $principal4 = Account::with([])->where('rank', 'SceneWorkAreaPrincipal')->where('work_area_unique_code', $principal5->work_area_unique_code)->first();
            if (empty($principal4)) return JsonResponseFacade::errorEmpty("{$principal5->WorkAreaByUniqueCode->name}没有设置工长");

            // 创建现场检修任务
            $task_station_check_order = TaskStationCheckOrder::with([])->create([
                'check_plan_serial_number' => $checkPlanSerialNumber,
                'serial_number' => TaskStationCheckOrder::generateSerialNumber($workshop_unique_code),
                'work_area_unique_code' => $principal5->work_area_unique_code,
                'scene_workshop_unique_code' => $workshop_unique_code,
                'maintain_station_unique_code' => $station_unique_code,
                'principal_id_level_1' => $principal1->id,  // 1级负责人（科长）
                'principal_id_level_2' => $principal2->id,  // 2级负责人（主管工程师）
                'principal_id_level_3' => $principal_id_level_3,  // 3级负责人（现场车间主任）
                'principal_id_level_4' => $principal4->id,  // 4级负责人（现场工区工长）
                'principal_id_level_5' => $principal5->id,  // 5级负责人（现场工区职工）
                'expiring_at' => $expiring_at,  // 截止日期
                'title' => "{$checkPlan->WithStation->Parent->name} {$principal5->WorkAreaByUniqueCode->name} {$principal5->nickname} " . date('Y-m-d', strtotime($expiring_at)),
                'unit' => $checkPlan->unit ?? '',
                'number' => 0,
            ]);

            return JsonResponseFacade::created([
                'task_station_check_order_serial_number' => $task_station_check_order->serial_number
            ]);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 设备列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function instanceWithIndex(Request $request)
    {
        try {
            $task_station_check_order_serial_number = $request->get('task_station_check_order_serial_number', '');
            $crossroad_number = $request->get('crossroad_number', '');
            $taskStationCheckOrder = TaskStationCheckOrder::with(['WithCheckPlan', 'PrincipalIdLevel5','MaintainStation','SceneWorkshop'])->where('serial_number', $task_station_check_order_serial_number)->firstOrFail();
            $station = DB::table('maintains')->where('deleted_at', null)->where('type', 'STATION')->where('unique_code', $taskStationCheckOrder->maintain_station_unique_code)->first();
            $entireInstances = DB::table('check_plan_entire_instances as cpei')
                ->select('ei.maintain_workshop_name', 'ei.maintain_station_name', 'ei.crossroad_number', 'ei.status', 'ei.category_unique_code', 'ei.entire_model_unique_code', 'ei.model_unique_code', 'ei.identity_code', 'c.name as category_name', 'em.name as entire_model_name', 'pm.name as sub_model_name')
                ->join(DB::raw('entire_instances ei'), 'cpei.entire_instance_identity_code', '=', 'ei.identity_code')
                ->leftJoin(DB::raw('categories c'), 'ei.category_unique_code', '=', 'c.unique_code')
                ->leftJoin(DB::raw('entire_models em'), 'ei.entire_model_unique_code', '=', 'em.unique_code')
                ->leftJoin(DB::raw('part_models pm'), 'ei.model_unique_code', '=', 'pm.unique_code')
                ->where('ei.deleted_at', null)
                ->where('c.deleted_at', null)
                ->where('em.deleted_at', null)
                ->where('pm.deleted_at', null)
                ->where('em.is_sub_model', false)
                ->where('ei.maintain_station_name', $station->name)
                ->where('cpei.check_plan_serial_number', $taskStationCheckOrder->check_plan_serial_number)
                ->where(function ($query) use ($task_station_check_order_serial_number) {
                    $query->where('cpei.task_station_check_order_serial_number', '')
                        ->orWhere('cpei.task_station_check_order_serial_number', '=', $task_station_check_order_serial_number);
                })
                ->when(
                    !empty($crossroad_number),
                    function ($query) use ($crossroad_number) {
                        return $query->where('crossroad_number', 'like', "%{$crossroad_number}%");
                    }
                )
                ->paginate();
            $entireInstanceIdentityCodes = DB::table('task_station_check_entire_instances')->where('task_station_check_order_sn', $task_station_check_order_serial_number)->select('entire_instance_identity_code')->pluck('entire_instance_identity_code')->toArray();
            return view('Task.CheckOrder.instance', [
                'taskStationCheckOrder' => $taskStationCheckOrder,
                'entireInstances' => $entireInstances,
                'task_station_check_order_serial_number' => $task_station_check_order_serial_number,
                'entireInstanceIdentityCodes' => $entireInstanceIdentityCodes,
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '任务数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 任务-添加设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function instanceWithStore(Request $request): JsonResponse
    {
        try {
            $entire_instance_identity_code = $request->get('entire_instance_identity_code', '');
            $task_station_check_order_serial_number = $request->get('task_station_check_order_serial_number', '');
            $taskStationCheckOrder = TaskStationCheckOrder::with([])->where('serial_number', $task_station_check_order_serial_number)->firstOrFail();
            $checkPlan = CheckPlan::with([])->where('serial_number', $taskStationCheckOrder->check_plan_serial_number)->first();
            if (empty($checkPlan)) return JsonResponseFacade::errorEmpty('检修计划不存在');
            DB::transaction(function () use ($checkPlan, $task_station_check_order_serial_number, $entire_instance_identity_code, $taskStationCheckOrder) {
                TaskStationCheckEntireInstance::with([])->create([
                    'task_station_check_order_sn' => $task_station_check_order_serial_number,
                    'entire_instance_identity_code' => $entire_instance_identity_code,
                ]);
                $taskStationCheckOrder->fill([
                    'number' => DB::table('task_station_check_entire_instances')->where('task_station_check_order_sn', $task_station_check_order_serial_number)->count('id'),
                ])->save();
                DB::table('check_plan_entire_instances')->where('check_plan_serial_number', $checkPlan->serial_number)->where('entire_instance_identity_code', $entire_instance_identity_code)->update([
                    'updated_at' => $this->_current_time,
                    'is_use' => 1,
                    'task_station_check_order_serial_number' => $task_station_check_order_serial_number,
                ]);
                $count = DB::table('check_plan_entire_instances')->where('check_plan_serial_number', $checkPlan->serial_number)->where('is_use', 0)->count('id');
                $checkPlan->fill([
                    'status' => $count > 0 ? 2 : 3,
                ])->save();
            });

            return JsonResponseFacade::created();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 任务-删除设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function instanceWithDestroy(Request $request): JsonResponse
    {
        try {
            $entire_instance_identity_code = $request->get('entire_instance_identity_code', '');
            $task_station_check_order_serial_number = $request->get('task_station_check_order_serial_number', '');
            $taskStationCheckOrder = TaskStationCheckOrder::with([])->where('serial_number', $task_station_check_order_serial_number)->firstOrFail();
            $checkPlan = CheckPlan::with([])->where('serial_number', $taskStationCheckOrder->check_plan_serial_number)->first();
            if (empty($checkPlan)) return JsonResponseFacade::errorEmpty('检修计划不存在');
            DB::transaction(function () use ($checkPlan, $task_station_check_order_serial_number, $entire_instance_identity_code, $taskStationCheckOrder) {
                DB::table('task_station_check_entire_instances')->where('task_station_check_order_sn', $task_station_check_order_serial_number)->where('entire_instance_identity_code', $entire_instance_identity_code)->delete();
                $taskStationCheckOrder->fill([
                    'number' => DB::table('task_station_check_entire_instances')->where('task_station_check_order_sn', $task_station_check_order_serial_number)->count('id'),
                ])->save();
                DB::table('check_plan_entire_instances')->where('check_plan_serial_number', $checkPlan->serial_number)->where('entire_instance_identity_code', $entire_instance_identity_code)->update([
                    'updated_at' => $this->_current_time,
                    'is_use' => 0,
                    'task_station_check_order_serial_number' => '',
                ]);
                $count = DB::table('check_plan_entire_instances')->where('check_plan_serial_number', $checkPlan->serial_number)->where('is_use', 0)->count('id');
                $checkPlan->fill([
                    'status' => $count > 0 ? 2 : 3,
                ])->save();
            });

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 检修计划完成统计
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function statisticForProject(Request $request)
    {
        try {
            $expiring_at = $request->get('expiring_at', date('Y-m'));
            $project_type = $request->get('project_type', '');
            $maintain_unique_code = $request->get('maintain_unique_code', '');
            $maintain = Maintain::with(['Parent'])->where('unique_code', $maintain_unique_code)->first();
            $origin_at = Carbon::createFromTimestamp(strtotime($expiring_at))->startOfMonth()->toDateTimeString();
            $finish_at = Carbon::createFromTimestamp(strtotime($expiring_at))->endOfMonth()->toDateTimeString();
            // 任务统计
            $mission_statistics = DB::table('task_station_check_entire_instances as tscei')
                ->selectRaw("count(tscei.entire_instance_identity_code) as aggregate, cpro.name as project_name, cpro.id as project_id, cpro.type as project_type, 'mission' as type")
                ->join(DB::raw('task_station_check_orders tsco'), 'tsco.serial_number', '=', 'tscei.task_station_check_order_sn')
                ->join(DB::raw('check_plans cp'), 'cp.serial_number', '=', 'tsco.check_plan_serial_number')
                ->join(DB::raw('check_projects cpro'), 'cpro.id', '=', 'cp.check_project_id')
                ->whereBetween('tsco.expiring_at', [$origin_at, $finish_at])
                ->where(function ($query) {
                    $query
                        ->where('tsco.principal_id_level_1', session('account.id'))
                        ->orWhere('tsco.principal_id_level_2', session('account.id'))
                        ->orWhere('tsco.principal_id_level_3', session('account.id'))
                        ->orWhere('tsco.principal_id_level_4', session('account.id'))
                        ->orWhere('tsco.principal_id_level_5', session('account.id'));
                })
                ->when(
                    !empty($project_type),
                    function ($query) use ($project_type) {
                        $query->where('cpro.type', $project_type);
                    }
                )
                ->when(
                    empty($maintain),
                    function ($query) {
                        // 统计所有，按照车间分组
                        $query
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 'tsco.scene_workshop_unique_code')
                            ->addSelect(['sc.name', 'sc.unique_code']);
                    }
                )
                ->when(
                    !empty($maintain) && $maintain->type == '现场车间',
                    function ($query) use ($maintain) {
                        // 按照车间统计，按照车站分组
                        $query
                            ->where('tsco.scene_workshop_unique_code', $maintain->unique_code)
                            ->join(DB::raw('maintains s'), 's.unique_code', '=', 'tsco.maintain_station_unique_code')
                            ->addSelect(['s.name', 's.unique_code']);
                    }
                )
                ->when(
                    !empty($maintain) && $maintain->type == '车站',
                    function ($query) use ($maintain) {
                        // 按照工区统计，按照5级负责人分组
                        $query
                            ->join(DB::raw('accounts as a'), 'a.id', '=', 'tsco.principal_id_level_5')
                            ->where('tsco.maintain_station_unique_code', $maintain->unique_code)
                            ->addSelect(['a.nickname as name', 'a.id as unique_code']);
                    }
                )
                ->groupBy(['project_name', 'project_id', 'name', 'unique_code', 'project_type', 'type'])
                ->get()->toArray();
            // 完成统计
            $finish_statistics = DB::table('task_station_check_entire_instances as tscei')
                ->selectRaw("count(tscei.entire_instance_identity_code) as aggregate, cpro.name as project_name, cpro.id as project_id, cpro.type as project_type, 'finish' as type")
                ->join(DB::raw('task_station_check_orders as tsco'), 'tsco.serial_number', '=', 'tscei.task_station_check_order_sn')
                ->join(DB::raw('check_plans cp'), 'cp.serial_number', '=', 'tsco.check_plan_serial_number')
                ->join(DB::raw('check_projects cpro'), 'cpro.id', '=', 'cp.check_project_id')
                ->where('tscei.processor_id', '<>', 0)
                ->where('tscei.processed_at', '<>', null)
                ->whereBetween('tsco.expiring_at', [$origin_at, $finish_at])
                ->where(function ($query) {
                    $query
                        ->where('tsco.principal_id_level_1', session('account.id'))
                        ->orWhere('tsco.principal_id_level_2', session('account.id'))
                        ->orWhere('tsco.principal_id_level_3', session('account.id'))
                        ->orWhere('tsco.principal_id_level_4', session('account.id'))
                        ->orWhere('tsco.principal_id_level_5', session('account.id'));
                })
                ->when(
                    !empty($project_type),
                    function ($query) use ($project_type) {
                        $query->where('cpro.type', $project_type);
                    }
                )
                ->when(
                    empty($maintain),
                    function ($query) {
                        // 统计所有，按照车间分组
                        $query
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 'tsco.scene_workshop_unique_code')
                            ->addSelect(['sc.name', 'sc.unique_code']);
                    }
                )
                ->when(
                    !empty($maintain) && $maintain->type == '现场车间',
                    function ($query) use ($maintain) {
                        // 按照车间统计，按照工区分组
                        $query
                            ->where('tsco.scene_workshop_unique_code', $maintain->unique_code)
                            ->join(DB::raw('maintains s'), 's.unique_code', '=', 'tsco.maintain_station_unique_code')
                            ->addSelect(['s.name', 's.unique_code']);
                    }
                )
                ->when(
                    !empty($maintain) && $maintain->type == '车站',
                    function ($query) use ($maintain) {
                        // 按照工区统计，按照5级负责人分组
                        $query
                            ->join(DB::raw('accounts as a'), 'a.id', '=', 'tsco.principal_id_level_5')
                            ->where('tsco.maintain_station_unique_code', $maintain->unique_code)
                            ->addSelect(['a.nickname as name', 'a.id as unique_code']);
                    }
                )
                ->groupBy(['project_name', 'project_id', 'name', 'unique_code', 'project_type', 'type'])
                ->get()->toArray();
            $statistics = array_merge($mission_statistics, $finish_statistics);
            $title = '';
            if (empty($maintain)) {
                $title = '车间';
            } else {
                if ($maintain->type == '现场车间') $title = '车站';
                if ($maintain->type == '车站') $title = '人员';
            }
            return view('Task.CheckOrder.statisticForProject', [
                'expiring_at' => $expiring_at,
                'maintain' => $maintain,
                'statistics' => json_encode($statistics, 256),
                'title' => $title,
                'current_maintain_unique_code' => $maintain_unique_code,
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 检修计划完成统计-设备列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function statisticForInstance(Request $request)
    {
        try {
            $station_unique_code = $request->get('station_unique_code', '');
            $principal_id_level_5 = $request->get('principal_id_level_5', '');
            $project_id = $request->get('project_id', '');
            $maintain = Maintain::with(['Parent'])->where('unique_code', $station_unique_code)->firstOrFail();
            $account = DB::table('accounts')->where('id', $principal_id_level_5)->select('nickname')->first();
            $checkProject = CheckProject::with([])->where('id', $project_id)->first();
            if (empty($account)) return back()->with('danger', '用户不存在');
            $entireInstances = DB::table('task_station_check_entire_instances as tscei')
                ->select(['ei.identity_code', 'ei.crossroad_number', 'c.name as category_name', 'em.name as entire_model_name', 'pm.name as sub_model_name','tscei.processor_id','tscei.processed_at'])
                ->join(DB::raw('task_station_check_orders tsco'), 'tscei.task_station_check_order_sn', '=', 'tsco.serial_number')
                ->join(DB::raw('check_plans check_plan'), 'tsco.check_plan_serial_number', '=', 'check_plan.serial_number')
                ->join(DB::raw('check_projects check_project'), 'check_plan.check_project_id', '=', 'check_project.id')
                ->join(DB::raw('entire_instances ei'), 'tscei.entire_instance_identity_code', '=', 'ei.identity_code')
                ->leftJoin(DB::raw('categories c'), 'ei.category_unique_code', '=', 'c.unique_code')
                ->leftJoin(DB::raw('entire_models em'), 'ei.entire_model_unique_code', '=', 'em.unique_code')
                ->leftJoin(DB::raw('part_models pm'), 'ei.model_unique_code', '=', 'pm.unique_code')
                ->where('em.is_sub_model', false)
                ->where('ei.deleted_at', null)
                ->where('c.deleted_at', null)
                ->where('em.deleted_at', null)
                ->where('pm.deleted_at', null)
                ->where('check_project.id', $project_id)
                ->where('tsco.maintain_station_unique_code', $station_unique_code)
                ->where('tsco.principal_id_level_5', $principal_id_level_5)
                ->get();

            return view('Task.CheckOrder.statisticForInstance', [
                'entireInstances' => $entireInstances,
                'maintain' => $maintain,
                'account' => $account,
                'checkProject' => $checkProject,
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '车站不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

}
