<?php

namespace App\Http\Controllers\Task;

use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CheckPlanStoreRequest;
use App\Model\CheckPlan;
use App\Model\CheckProject;
use App\Model\EntireInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\ValidateHelper;

class CheckPlanController extends Controller
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
            $checkPlans = CheckPlan::with(['WithStation', 'WithAccount', 'WithCheckProject'])->orderByDesc('serial_number')->paginate();
            return view('Task.CheckPlan.index', [
                'checkPlans' => $checkPlans
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create()
    {
        try {
            $sceneWorkshops = DB::table('maintains')->where('deleted_at', null)->where('type', 'SCENE_WORKSHOP')->where('parent_unique_code', env('ORGANIZATION_CODE'))->select('name', 'unique_code')->pluck('name', 'unique_code')->toArray();
            return view('Task.CheckPlan.create', [
                'types' => CheckProject::$TYPE,
                'sceneWorkshops' => $sceneWorkshops,
            ]);
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
            $v = ValidateHelper::firstErrorByRequest($request, new CheckPlanStoreRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $checkPlan = new CheckPlan();
            $req = array_filter($request->all(), function ($v) {
                return !is_null($v);
            });
            $expiring_at = Carbon::createFromTimestamp(strtotime($req['expiring_at']))->endOfMonth()->toDateTimeString();
            $isValidate = DB::table('check_plans')->where('station_unique_code', $req['station_unique_code'])->where('check_project_id', $req['check_project_id'])->where('account_id', session('account.id'))->where('expiring_at', $expiring_at)->first();
            if (!empty($isValidate)) return JsonResponseFacade::errorValidate('该计划已经存在，不能重复创建。重复条件：车站/项目/用户/截止日期');
            $checkPlan->fill(array_merge($req, [
                'account_id' => session('account.id'),
                'serial_number' => $checkPlan::generateSerialNumber($request->get('workshop_unique_code')),
                'status' => 0,
                'expiring_at' => $expiring_at
            ]))->saveOrFail();

            return JsonResponseFacade::created();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 设备道岔列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function instanceWithIndex(Request $request)
    {
        try {
            $serial_number = $request->get('serial_number', '');
            $checkPlan = CheckPlan::with(['WithStation', 'WithStation.Parent'])->where('serial_number', $serial_number)->firstOrFail();
            $crossroad_number = $request->get('crossroad_number', '');
            $maintain_station_name = $checkPlan->WithStation->name ?? '';
            $entireInstances = DB::table('entire_instances as ei')
                ->selectRaw("ei.maintain_workshop_name,ei.maintain_station_name,ei.crossroad_number,count('id') as count")
                ->where('ei.deleted_at', null)
                ->where('ei.crossroad_number', '<>', null)
                ->where('ei.crossroad_number', '<>', '')
                ->when(
                    !empty($maintain_station_name),
                    function ($query) use ($maintain_station_name) {
                        return $query->where('ei.maintain_station_name', $maintain_station_name);
                    }
                )
                ->when(
                    !empty($crossroad_number),
                    function ($query) use ($crossroad_number) {
                        return $query->where('ei.crossroad_number', 'like', "%{$crossroad_number}%");
                    }
                )->groupBy('ei.crossroad_number')->get()->chunk(2)->toArray();
            $checkPlanEntireInstanceCrossroadNumbers = DB::table('check_plan_entire_instances as cpei')
                ->select('ei.crossroad_number')
                ->join(DB::raw('entire_instances ei'), 'cpei.entire_instance_identity_code', '=', 'ei.identity_code')
                ->where('ei.deleted_at', null)
                ->where('ei.crossroad_number', '<>', null)
                ->where('ei.crossroad_number', '<>', '')
                ->where('cpei.check_plan_serial_number', $serial_number)
                ->groupBy('ei.crossroad_number')
                ->pluck('ei.crossroad_number')->toArray();

            return view('Task.CheckPlan.instance', [
                'checkPlan' => $checkPlan,
                'entireInstances' => $entireInstances,
                'serial_number' => $serial_number,
                'checkPlanEntireInstanceCrossroadNumbers' => $checkPlanEntireInstanceCrossroadNumbers,
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 设备列表新建
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function instanceWithStore(Request $request): JsonResponse
    {
        try {
            $serial_number = $request->get('serial_number', '');
            $maintain_station_name = $request->get('maintain_station_name', '');
            $crossroad_number = $request->get('crossroad_number', '');
            if (empty($maintain_station_name)) return JsonResponseFacade::errorValidate('车站不存在');
            if (empty($crossroad_number)) return JsonResponseFacade::errorValidate('道岔不存在');
            $checkPlan = CheckPlan::with([])->where('serial_number', $serial_number)->firstOrFail();
            $entireInstances = DB::table('entire_instances')->where('deleted_at', null)->where('maintain_station_name', $maintain_station_name)->where('crossroad_number', $crossroad_number)->select('identity_code')->get();
            $inserts = [];
            foreach ($entireInstances as $entireInstance) {
                $inserts[] = [
                    'created_at' => $this->_current_time,
                    'updated_at' => $this->_current_time,
                    'check_plan_serial_number' => $serial_number,
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                ];
            }
            if (empty($inserts)) return JsonResponseFacade::errorEmpty('设备数据为空请刷新重试');
            DB::table('check_plan_entire_instances')->insert($inserts);

            $checkPlan->fill([
                'number' => DB::table('check_plan_entire_instances')->where('check_plan_serial_number', $serial_number)->count('id'),
            ])->saveOrFail();
            return JsonResponseFacade::created();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 设备列表删除
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function instanceWithDestroy(Request $request): JsonResponse
    {
        try {
            $serial_number = $request->get('serial_number', '');
            $maintain_station_name = $request->get('maintain_station_name', '');
            $crossroad_number = $request->get('crossroad_number', '');
            if (empty($maintain_station_name)) return JsonResponseFacade::errorValidate('车站不存在');
            if (empty($crossroad_number)) return JsonResponseFacade::errorValidate('道岔不存在');
            $entireInstances = DB::table('entire_instances')->where('deleted_at', null)->where('maintain_station_name', $maintain_station_name)->where('crossroad_number', $crossroad_number)->select('identity_code')->get();
            foreach ($entireInstances as $entireInstance) {
                DB::table('check_plan_entire_instances')->where('check_plan_serial_number', $serial_number)->where('entire_instance_identity_code', $entireInstance->identity_code)->delete();
            }
            $checkPlan = CheckPlan::with([])->where('serial_number', $serial_number)->firstOrFail();
            $checkPlan->fill([
                'number' => DB::table('check_plan_entire_instances')->where('check_plan_serial_number', $serial_number)->count('id'),
            ])->saveOrFail();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }


}
