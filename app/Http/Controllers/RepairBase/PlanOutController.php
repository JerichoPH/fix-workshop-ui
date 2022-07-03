<?php

namespace App\Http\Controllers\RepairBase;

use App\Facades\AccountFacade;
use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Model\RepairBasePlanOutCycleFixBill;
use App\Model\RepairBasePlanOutCycleFixEntireInstance;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\FileSystem;
use Jericho\HttpResponseHelper;
use Throwable;

class PlanOutController extends Controller
{
    private $_organizationCode = null;
    private $_organizationName = null;
    private $_current_time = null;
    private $_lock_name = '';

    public function __construct()
    {
        $this->_organizationCode = env('ORGANIZATION_CODE');
        $this->_organizationName = env('ORGANIZATION_NAME');
        $this->_current_time = Carbon::now()->format('Y-m-d H:i:s');
        $this->_lock_name = 'CYCLEFIX';
    }

    /**
     * 周期修任务列表
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|View
     */
    final public function cycleFix()
    {
        try {
            return view('RepairBase.PlanOut.cycleFixIndex');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 周期修年度任务表 - 月份
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function cycleFixWithMonth(Request $request)
    {
        try {
            if (!session('account.work_area_unique_code')) return back()->with('danger', '当前用户没有工区');

            $year = $request->get('year', date('Y'));
            $month = str_pad(strval($request->get('month', date('m'))), 2, '0', STR_PAD_LEFT);
            $workAreas = Account::$WORK_AREAS;
            $session_current_work_area = $request->get('workAreaId', array_flip($workAreas)[session('account.work_area')]);
            unset($workAreas[0]);
            $workAreaId = intval(substr(session('account.work_area_unique_code'), 5));
            $file = FileSystem::init(__FILE__);
            $fileDir = storage_path("app/cycleFix");
            $yearLists = $file->setPath($fileDir)->join('yearList.json')->fromJson();

            // 检索可用设备
            $usableEntireInstanceCounts = AccountFacade::workAreaWithDb(
                DB::table('entire_instances as ei')
                    ->selectRaw('count(ei.identity_code) as count, ei.model_unique_code')
                    ->leftJoin(DB::raw('entire_instance_locks eil'), 'ei.identity_code', '=', 'eil.entire_instance_identity_code')
                    ->where('ei.deleted_at', null)
                    ->where('ei.status', 'FIXED')
                    ->where('eil.entire_instance_identity_code', null)
                    ->groupBy(['ei.model_unique_code']),
                $workAreas[$workAreaId]
            )
                ->pluck('count', 'ei.model_unique_code')
                ->toArray();

            // 所有任务
            $bills = DB::table('repair_base_plan_out_cycle_fix_bills')
                ->orderByDesc('created_at')
                ->where('year', $year)
                ->where('month', $month)
                ->where('work_area_id', $workAreaId)
                ->get();

            // 获取任务列表
            $plans = $file->setPath("{$fileDir}/{$year}/{$year}-{$month}")->join('planDeviceAsStation.json')->fromJson();
            $planWithStations = [];
            $subModels = [];
            $planTotalWithStations = [];
            foreach ($plans as $stationUniqueCode => $plan) {
                if (array_key_exists($workAreaId, $plan['work_areas'])) {
                    if (!array_key_exists($stationUniqueCode, $planTotalWithStations)) $planTotalWithStations[$stationUniqueCode] = 0;
                    $planCountWithWorkArea = $plan['work_areas'][$workAreaId]['statistics']['plan_device_count'] ?? 0;
                    $planTotalWithStations[$stationUniqueCode] += $planCountWithWorkArea;
                    if (!array_key_exists($stationUniqueCode, $planWithStations)) {
                        $planWithStations[$stationUniqueCode] = [
                            'stationName' => $plan['name'],
                            'billStatus' => '',
                            'billId' => 0,
                            'subModels' => []
                        ];
                    }
                    if ($bills->isNotEmpty()) {
                        foreach ($bills as $bill) {
                            #年、月、车站
                            if ($bill->station_name == $plan['name']) {
                                $planWithStations[$stationUniqueCode]['billStatus'] = $bill->status;
                                $planWithStations[$stationUniqueCode]['billId'] = $bill->id;
                            }
                        }
                    }
                    $planWithModels = $plan['work_areas'][$workAreaId]['models'] ?? [];
                    foreach ($planWithModels as $subModelUniqueCode => $model) {
                        if (!array_key_exists($subModelUniqueCode, $subModels)) $subModels[$subModelUniqueCode] = $model['name'];
                        $planNum = $model['statistics']['plan_device_count'] ?? 0;
                        // style = 2 成品数量不足 1成品数量充足
                        $planWithStations[$stationUniqueCode]['subModels'][$subModelUniqueCode] = [
                            'subModelName' => $model['name'],
                            'style' => 2,
                            'count' => $planNum,
                        ];
                        $usableCount = $usableEntireInstanceCounts[$subModelUniqueCode] ?? 0;
                        if ($usableCount > $planNum) $planWithStations[$stationUniqueCode]['subModels'][$subModelUniqueCode]['style'] = 1;
                    }
                }
            }

            return view("RepairBase.PlanOut.cycleFixWithMonth", [
                'currentYear' => $year,
                'currentMonth' => $month,
                'currentWorkAreaId' => $workAreaId,
                'yearLists' => $yearLists,
                'workAreas' => $workAreas,
                'subModels' => $subModels,
                'planWithStations' => $planWithStations,
                'planTotalWithStations' => $planTotalWithStations,
            ]);
        } catch (\Exception $e) {
            return back()->with('info', '该型号下没有周期修数据');
        }
    }

    /**
     * 周期修年度任务表 - 车站
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|View
     */
    final public function cycleFixWithStation()
    {
        try {
            if (!session('account.work_area_unique_code')) return back()->with('danger', '当前用户没有工区');

            $year = request('year', date('Y'));
            $workAreas = Account::$WORK_AREAS;
            $session_current_work_area = request('workAreaId', array_flip($workAreas)[session('account.work_area')]);
            unset($workAreas[0]);
            $workAreaId = intval(substr(session('account.work_area_unique_code'), 5));

            $file = FileSystem::init(__FILE__);
            $fileDir = storage_path("app/cycleFix");
            $yearLists = $file->setPath($fileDir)->join('yearList.json')->fromJson();
            $plans = $file->setPath("{$fileDir}/{$year}")->join('planDeviceAsStation.json')->fromJson();
            $stations = [];
            foreach ($plans as $stationUniqueCode => $plan) {
                if (!array_key_exists($stationUniqueCode, $stations)) $stations[$stationUniqueCode] = $plan['name'];
            }
            if (array_key_exists(request('stationUniqueCode', ''), $stations)) {
                $stationUniqueCode = request('stationUniqueCode');
            } else {
                $stationUniqueCode = array_key_first($stations);
            }

            // 检索可用设备
            $usableEntireInstanceCounts = AccountFacade::workAreaWithDb(
                DB::table('entire_instances as ei')
                    ->selectRaw('count(ei.identity_code) as count, ei.model_unique_code')
                    ->leftJoin(DB::raw('entire_instance_locks eil'), 'ei.identity_code', '=', 'eil.entire_instance_identity_code')
                    ->where('ei.deleted_at', null)
                    ->where('ei.status', 'FIXED')
                    ->where('eil.entire_instance_identity_code', null)
                    ->groupBy(['ei.model_unique_code']),
                $workAreas[$workAreaId]
            )
                ->pluck('count', 'ei.model_unique_code')
                ->toArray();

            // 所有任务
            $bills = DB::table('repair_base_plan_out_cycle_fix_bills')
                ->orderByDesc('created_at')
                ->where('year', $year)
                ->where('station_unique_code', $stationUniqueCode)
                ->where('work_area_id', $workAreaId)
                ->get();

            $planWithMonths = [];
            $planTotalWithMonths = [];
            $subModels = [];
            for ($i = 1; $i < 13; $i++) {
                if (!array_key_exists($i, $planWithMonths)) {
                    $planWithMonths[$i] = [
                        'billStatus' => '',
                        'billId' => '0',
                        'subModels' => []
                    ];
                }
                if (!array_key_exists($i, $planTotalWithMonths)) $planTotalWithMonths[$i] = 0;
                $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                $planMonthWithFile = $file->setPath("{$fileDir}/{$year}/{$year}-{$month}")->join('planDeviceAsStation.json')->fromJson();
                $planWithModels = $planMonthWithFile[$stationUniqueCode]['work_areas'][$workAreaId]['models'] ?? [];
                if (!empty($planWithModels)) {
                    foreach ($planWithModels as $subModelUniqueCode => $planWithModel) {
                        if (!array_key_exists($subModelUniqueCode, $subModels)) $subModels[$subModelUniqueCode] = $planWithModel['name'];
                        $planNum = $planWithModel['statistics']['plan_device_count'] ?? 0;
                        $planTotalWithMonths[$i] += $planNum;
                        if ($bills->isNotEmpty()) {
                            foreach ($bills as $bill) {
                                #年、月、车站
                                if ($bill->month == $i) {
                                    $planWithMonths[$i]['billStatus'] = $bill->status;
                                    $planWithMonths[$i]['billId'] = $bill->id;
                                }
                            }
                        }
                        // style = 2 成品数量不足 1成品数量充足
                        $planWithMonths[$i]['subModels'][$subModelUniqueCode] = [
                            'subModelName' => $planWithModel['name'],
                            'style' => 2,
                            'count' => $planNum,
                        ];

                        $usableCount = $usableEntireInstanceCounts[$subModelUniqueCode] ?? 0;
                        if ($usableCount > $planNum) $planWithMonths[$i]['subModels'][$subModelUniqueCode]['style'] = 1;
                    }
                }
            }

            return view("RepairBase.PlanOut.cycleFixWithStation", [
                'currentYear' => $year,
                'currentStationUniqueCode' => $stationUniqueCode,
                'currentWorkAreaId' => $workAreaId,
                'yearLists' => $yearLists,
                'workAreas' => $workAreas,
                'stations' => $stations,
                'planWithMonths' => $planWithMonths,
                'planTotalWithMonths' => $planTotalWithMonths,
                'subModels' => $subModels,
            ]);
        } catch (Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 生成任务
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    final public function postCycleFix(Request $request)
    {
        $year = $request->get('year', '');
        $month = str_pad(strval($request->get('month', date('m'))), 2, '0', STR_PAD_LEFT);
        $stationUniqueCode = $request->get('stationUniqueCode', '');
        $number = $request->get('number', 0);
        $currentWorkAreaId = $request->get('workAreaId', '');
        $subModels = $request->get('subModels', []);
        if (empty($year) || empty($month) || empty($stationUniqueCode) || empty($currentWorkAreaId)) return JsonResponseFacade::errorValidate("参数不足");
        if ($number == 0) return JsonResponseFacade::errorValidate("周期修出所计划设备数量不能为0");
        $file = FileSystem::init(__FILE__);
        $fileDir = storage_path("app/cycleFix");
        $plans = $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$stationUniqueCode}.json")->fromJson();
        $a = "{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation/{$stationUniqueCode}.json";
        if (empty($plans)) return JsonResponseFacade::errorEmpty("周期修计划文件不存在：{$a}");
        $planWithModels = $plans['models'];
        if (empty($planWithModels)) return JsonResponseFacade::errorEmpty("周期修计划器材文件不存在");
        $stationName = $plans['name'];
        if (empty($subModels)) return JsonResponseFacade::errorValidate("{$stationName}车站的周期修计划为空");
        $serial_number = "{$currentWorkAreaId}-{$stationUniqueCode}-{$year}-{$month}";
        $repeat = DB::table('repair_base_plan_out_cycle_fix_bills')->where('serial_number', $serial_number)->first();
        if (empty($repeat)) {
            $return = DB::transaction(function () use (
                $serial_number,
                $number,
                $year,
                $month,
                $stationName,
                $stationUniqueCode,
                $currentWorkAreaId,
                $subModels,
                $planWithModels
            ) {
                // 新增任务
                $newBillId = DB::table('repair_base_plan_out_cycle_fix_bills')->insertGetId([
                    'created_at' => $this->_current_time,
                    'updated_at' => $this->_current_time,
                    'serial_number' => $serial_number,
                    'operator_id' => session('account.id'),
                    'status' => 'ORIGIN',
                    'number' => $number,
                    'year' => $year,
                    'month' => $month,
                    'station_name' => $stationName,
                    'station_unique_code' => $stationUniqueCode,
                    'work_area_id' => $currentWorkAreaId,
                ]);
                $oldCodes = [];
                $repairBasePlanOutCycleFixEntireInstanceInserts = [];
                $remarks = [];
                foreach ($planWithModels as $subModelUniqueCode => $planWithModel) {
                    foreach ($planWithModel['devices'] as $oldCode => $device) {
                        if (!in_array($oldCode, $oldCodes)) $oldCodes[] = $oldCode;
                        if (!array_key_exists($oldCode, $remarks)) {
                            $remarks[$oldCode] = self::makeLockRemark($oldCode, [
                                'work_area_id' => $currentWorkAreaId,
                                'station_name' => $stationName,
                                'year' => $year,
                                'month' => $month,
                            ]);
                        }
                        // 周期修任务设备关联
                        $repairBasePlanOutCycleFixEntireInstanceInserts[] = [
                            'bill_id' => $newBillId,
                            'new' => '',
                            'old' => $oldCode,
                            'station_name' => $stationName,
                            'location' => $device['location_code'],
                            'new_tid' => '',
                            'old_tid' => '',
                            'is_scan' => 0,
                            'station_unique_code' => $stationUniqueCode
                        ];
                    }
                }
                EntireInstanceLock::setOnlyLocks(
                    $oldCodes,
                    [$this->_lock_name],
                    $remarks,
                    function () use ($repairBasePlanOutCycleFixEntireInstanceInserts) {
                        DB::table('repair_base_plan_out_cycle_fix_entire_instances')->insert($repairBasePlanOutCycleFixEntireInstanceInserts);
                    }
                );
                return $newBillId;
            });
            return JsonResponseFacade::dict(["href" => "/repairBase/planOut/cycleFix/{$return}?type=continuous"], "新建成功");
        } else {
            return JsonResponseFacade::dict(["href" => "/repairBase/planOut/cycleFix/{$repeat->id}?type=continuous"], "添加成功");
        }
    }

    /**
     * 生成锁备注
     * @param string $code
     * @param array $bill
     * @return string
     */
    final public static function makeLockRemark(string $code, array $bill)
    {
        $work_areas = Account::$WORK_AREAS;
        return "设备器材：{$code}，在周期修出所中被使用。详情：工区：{$work_areas[$bill['work_area_id']]}；车站：{$bill['station_name']}；年：{$bill['year']}月：{$bill['month']}";
    }

    /**
     * 任务详情
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|View
     */
    public function getShowCycleFix(int $id)
    {
        try {
            $bill = DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $id)->first();
            if (empty($bill)) return back()->with('info', '数据不存在');

            // 当前任务设备型号
            $billWithModels = DB::table('repair_base_plan_out_cycle_fix_entire_instances as rbi')
                ->select('ei.model_unique_code', 'ei.model_name', 'ei.maintain_station_name', 'ei.maintain_location_code', 'ei.crossroad_number')
                ->leftJoin(DB::raw('entire_instances ei'), 'rbi.old', '=', 'ei.identity_code')
                ->whereNull('ei.deleted_at')
                ->where('rbi.bill_id', $id)
                ->get();
            $all_mission_models = $billWithModels->pluck('model_name', 'model_unique_code');
            $breakdownCountWithOlds = [];
            if (!empty($billWithModels)) {
                $breakdownCountWithOlds = DB::table('breakdown_logs')
                    ->selectRaw(implode(',', [
                        'count(*) as count',
                        'concat(maintain_station_name, maintain_location_code, crossroad_number) as install_location',
                    ]))
                    ->whereIn('maintain_station_name', $billWithModels->pluck('maintain_station_name')->unique()->toArray())
                    ->groupBy(['maintain_station_name', 'maintain_location_code', 'crossroad_number'])
                    ->pluck('count', 'install_location')
                    ->toArray();
            }

            $current_mission_model_unique_code = request('model_unique_code', '') ?? '';

            $repair_base_plan_out_cycle_fix_db = RepairBasePlanOutCycleFixEntireInstance::with([
                'WithEntireInstance',
                'WithEntireInstanceOld',
                'WithEntireInstanceOld.InstallPosition',
            ])
                ->when(
                    $current_mission_model_unique_code,
                    function ($query, $current_mission_model_unique_code) {
                        $query->whereHas('WithEntireInstanceOld', function ($query) use ($current_mission_model_unique_code) {
                            $query->where('model_unique_code', $current_mission_model_unique_code);
                        });
                    }
                )
                ->where('bill_id', $id);
            $new_entire_instance_identity_codes = $repair_base_plan_out_cycle_fix_db->get()->pluck('new');
            $new_entire_instance_identity_codes = $new_entire_instance_identity_codes
                ->filter(function ($val) {
                    return !empty($val);
                })
                ->values();

            $repair_base_plan_out_cycle_fix_entire_instances = request('type') == 'continuous' ? $repair_base_plan_out_cycle_fix_db->get() : $repair_base_plan_out_cycle_fix_db->paginate(50);

            // 获取库房未占用成品设备
            $entire_instances = DB::table('entire_instances as ei')
                ->select(['ei.model_unique_code', 'ei.identity_code'])
                ->leftJoin(DB::raw('entire_instance_locks eil'), 'ei.identity_code', '=', 'eil.entire_instance_identity_code')
                ->whereNull('ei.deleted_at')
                ->where('ei.status', 'FIXED')
                ->whereNull('eil.entire_instance_identity_code')
                ->where('ei.model_unique_code', $current_mission_model_unique_code)
                ->when(
                    !empty($billWithModels),
                    function ($query) use ($billWithModels) {
                        return $query->whereIn('ei.model_unique_code', $billWithModels->pluck('model_unique_code')->unique()->toArray());
                    }
                )
                ->orderByDesc('ei.made_at')
                ->pluck('ei.model_unique_code', 'ei.identity_code')
                ->toArray();
            $new_entire_instances = [];

            foreach ($entire_instances as $identity_code => $modelUniqueCode) $new_entire_instances[$modelUniqueCode][] = $identity_code;

            $view_name = (request('type')) == 'continuous' ? 'RepairBase.PlanOut.cycleFixShow-continuous' : 'RepairBase.PlanOut.cycleFixShow';

            return view($view_name, [
                'newEntireInstances' => $new_entire_instances,
                'repairBasePlanOutCycleFixEntireInstances' => $repair_base_plan_out_cycle_fix_entire_instances,
                'current_bill_id' => $id,
                'breakdownCountWithOlds' => $breakdownCountWithOlds,
                'new_entire_instance_identity_codes_as_json' => $new_entire_instance_identity_codes,
                'all_mission_models' => $all_mission_models,
            ]);
        } catch (Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 周期修更新（替换设备）
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function updateCycleFix(Request $request, int $id)
    {
        try {
            $newCode = $request->get('newCode', '');
            $oldCode = $request->get('oldCode', '');
            $repairBasePlanOutCycleFixEntireInstance = RepairBasePlanOutCycleFixEntireInstance::with(['WithEntireInstanceOld'])->where('bill_id', $id)->where('old', $oldCode)->where('out_warehouse_sn', '')->firstOrFail();
            $bill = DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $id)->first();
            if (empty($bill)) return HttpResponseHelper::errorEmpty('任务数据不存在');

            /**
             * 新设备加锁
             * @return bool
             * @throws \App\Exceptions\EntireInstanceLockException
             */
            $entireInstanceLock = function () use ($id, $oldCode, $newCode, $repairBasePlanOutCycleFixEntireInstance, $bill) {
                EntireInstanceLock::setOnlyLock(
                    $newCode,
                    [$this->_lock_name],
                    self::makeLockRemark($newCode, (array)$bill),
                    function () use ($id, $oldCode, $newCode, $repairBasePlanOutCycleFixEntireInstance, $bill) {
                        // 修改绑定记录
                        DB::table('repair_base_plan_out_cycle_fix_entire_instances')->where('bill_id', $id)->where('old', $oldCode)->update(['new' => $newCode, 'is_scan' => 0]);
                        // 修改绑定设备位置
                        EntireInstanceFacade::copyLocation($oldCode, $newCode);
                        // 修改缓存数据
                        $year = $bill->year;
                        $month = str_pad($bill->month, 2, '0', STR_PAD_LEFT);
                        $stationUniqueCode = $bill->station_unique_code;
                        $file = FileSystem::init(__FILE__);
                        $fileDir = storage_path("app/cycleFix");
                        $plans = $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$stationUniqueCode}.json")->fromJson();
                        $plans['models'][$repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld->model_unique_code]['devices'][$oldCode]['new_identity_code'] = $newCode;
                        $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$stationUniqueCode}.json")->toJson($plans);
                    }
                );

                return true;
            };
            if (empty($repairBasePlanOutCycleFixEntireInstance->new)) {
                $entireInstanceLock();
            } else {
                EntireInstanceLock::freeLock(
                    $repairBasePlanOutCycleFixEntireInstance->new,
                    [$this->_lock_name],
                    function () use ($entireInstanceLock, $repairBasePlanOutCycleFixEntireInstance) {
                        EntireInstanceFacade::clearLocation($repairBasePlanOutCycleFixEntireInstance->new);
                        $entireInstanceLock();
                    }
                );
            }

            return HttpResponseHelper::created('替换成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('该设备不可进行替换或数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 批量替换
     * @param Request $request
     * @param int $billId
     * @return \Illuminate\Http\JsonResponse
     */
    final public function cycleFixWithReplaces(Request $request, int $billId)
    {
        try {
            $bill = DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $billId)->first();
            if (empty($bill)) return HttpResponseHelper::errorEmpty('任务不存在');
            $repairBasePlanOutCycleFixEntireInstances = RepairBasePlanOutCycleFixEntireInstance::with(['WithEntireInstanceOld'])->where('bill_id', $billId)->where('new', '')->whereIn('old', $request->get('oldCodes', ''))->where('out_warehouse_sn', '')->get();
            $year = $bill->year;
            $month = str_pad($bill->month, 2, '0', STR_PAD_LEFT);
            $stationUniqueCode = $bill->station_unique_code;
            $file = FileSystem::init(__FILE__);
            $fileDir = storage_path("app/cycleFix");
            $plans = $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$stationUniqueCode}.json")->fromJson();
            // 当前任务设备信息
            $billModels = [];
            foreach ($repairBasePlanOutCycleFixEntireInstances as $repairBasePlanOutCycleFixEntireInstance) {
                if ($repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld) {
                    $billModels[$repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld->model_unique_code][] = [
                        'old' => $repairBasePlanOutCycleFixEntireInstance->old,
                        'location' => $repairBasePlanOutCycleFixEntireInstance->location,
                        'station' => $repairBasePlanOutCycleFixEntireInstance->station
                    ];
                }
            }
            if (empty($billModels)) return HttpResponseHelper::errorEmpty('未替换任务设备数据不存在');

            // 获取库房成品设备
            $entireInstances = AccountFacade::workAreaWithDb(
                DB::table('entire_instances as ei')
                    ->select(['ei.model_unique_code', 'ei.identity_code'])
                    ->leftJoin(DB::raw('entire_instance_locks eil'), 'ei.identity_code', '=', 'eil.entire_instance_identity_code')
                    ->where('ei.deleted_at', null)
                    ->where('ei.status', 'FIXED')
                    ->where('eil.entire_instance_identity_code', null)
                    ->when(
                        !empty($billModels),
                        function ($query) use ($billModels) {
                            return $query->whereIn('ei.model_unique_code', array_keys($billModels));
                        }
                    ),
                Account::$WORK_AREAS[$bill->work_area_id]
            )->get();

            $new_entire_instances = [];
            foreach ($entireInstances as $entireInstance) $new_entire_instances[$entireInstance->model_unique_code][] = $entireInstance->identity_code;
            if (empty($new_entire_instances)) return HttpResponseHelper::errorEmpty('可替换设备为空');

            foreach ($billModels as $modelUniqueCode => $billModel) {
                $i = 0;
                foreach ($billModel as $model) {
                    $newCode = '';
                    if (array_key_exists($modelUniqueCode, $new_entire_instances)) $newCode = empty($new_entire_instances[$modelUniqueCode][$i]) ? '' : $new_entire_instances[$modelUniqueCode][$i];
                    if (empty($newCode)) break;
                    EntireInstanceLock::setOnlyLock(
                        $newCode,
                        [$this->_lock_name],
                        self::makeLockRemark($newCode, (array)$bill),
                        function () use ($modelUniqueCode, $model, $plans, $billId, $newCode) {
                            // 修改绑定记录
                            DB::table('repair_base_plan_out_cycle_fix_entire_instances')->where('bill_id', $billId)->where('old', $model['old'])->update(['new' => $newCode, 'is_scan' => 0]);
                            // 修改绑定设备位置
                            EntireInstanceFacade::copyLocation($model['old'], $newCode);
                        }
                    );
                    // 修改缓存数据
                    $plans['models'][$modelUniqueCode]['devices'][$model['old']]['new_identity_code'] = $newCode;
                    $i++;
                }
            }
            $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$stationUniqueCode}.json")->toJson($plans);

            return HttpResponseHelper::created('批量替换成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 取消替换
     * @param Request $request
     * @param int $billId
     * @return \Illuminate\Http\JsonResponse
     */
    final public function cycleFixWithUnReplaces(Request $request, int $billId)
    {
        try {
            $bill = DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $billId)->first();
            if (empty($bill)) return HttpResponseHelper::errorEmpty('任务不存在');
            $repairBasePlanOutCycleFixEntireInstances = RepairBasePlanOutCycleFixEntireInstance::with(['WithEntireInstanceOld'])->where('bill_id', $billId)->whereIn('old', $request->get('oldCodes'))->where('out_warehouse_sn', '')->get();
            if ($repairBasePlanOutCycleFixEntireInstances->isEmpty()) return HttpResponseHelper::errorEmpty('可取消替换任务设备不存在');
            $year = $bill->year;
            $month = str_pad($bill->month, 2, '0', STR_PAD_LEFT);
            $stationUniqueCode = $bill->station_unique_code;
            $file = FileSystem::init(__FILE__);
            $fileDir = storage_path("app/cycleFix");
            $plans = $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$stationUniqueCode}.json")->fromJson();
            $newCodes = [];
            $oldCodes = [];
            foreach ($repairBasePlanOutCycleFixEntireInstances as $repairBasePlanOutCycleFixEntireInstance) {
                if ($repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld) {
                    $newCodes[] = $repairBasePlanOutCycleFixEntireInstance->new;
                    $oldCodes[] = $repairBasePlanOutCycleFixEntireInstance->old;
                    $plans['models'][$repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld->model_unique_code]['devices'][$repairBasePlanOutCycleFixEntireInstance->old]['new_identity_code'] = '';
                }
            }
            EntireInstanceLock::freeLocks(
                $newCodes,
                [$this->_lock_name],
                function () use ($newCodes, $oldCodes, $plans, $file, $billId, $fileDir, $year, $month, $stationUniqueCode) {
                    // 取消替换设备位置
                    EntireInstanceFacade::clearLocations($newCodes);
                    // 修改绑定记录
                    DB::table('repair_base_plan_out_cycle_fix_entire_instances')->where('bill_id', $billId)->whereIn('old', $oldCodes)->update(['new' => '', 'is_scan' => 0]);
                    // 更新缓存数据
                    $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$stationUniqueCode}.json")->toJson($plans);
                }
            );

            return HttpResponseHelper::created('取消替换成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 出所扫码页面
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|View
     */
    final public function getCycleFixOut(int $id)
    {
        try {
            $cycleFixEntireInstances = RepairBasePlanOutCycleFixEntireInstance::with(['WithEntireInstance'])->where('bill_id', $id)->where('new', '<>', '')->where('out_warehouse_sn', '')->get();
            $isOnclick = true;
            foreach ($cycleFixEntireInstances as $cycleFixEntireInstance) {
                if ($cycleFixEntireInstance->is_scan == 0) {
                    $isOnclick = false;
                    break;
                }
            }

            return view('RepairBase.PlanOut.cycleFixOut', [
                'cycleFixEntireInstances' => $cycleFixEntireInstances,
                'current_bill_id' => $id,
                'isOnclick' => $isOnclick,
            ]);
        } catch (\Exception $exception) {
            return back()->with('info', $exception->getMessage());
        }
    }

    /**
     * 出所扫码
     * @param Request $request
     * @param int $billId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function updateScanCycleFixOut(Request $request, int $billId): JsonResponse
    {
        try {
            $new = $request->get('qrCodeContent', '');

            $repairBasePlanOutCycleFixEntireInstance = RepairBasePlanOutCycleFixEntireInstance::with([])->where('bill_id', $billId)->where('new', $new)->where('out_warehouse_sn', '')->firstOrFail();
            if ($repairBasePlanOutCycleFixEntireInstance->is_scan == 1) return HttpResponseHelper::errorValidate('已经扫码');
            $repairBasePlanOutCycleFixEntireInstance->fill(['is_scan' => 1]);
            $repairBasePlanOutCycleFixEntireInstance->saveOrFail();

            return HttpResponseHelper::created('成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 取消出所扫码
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function destroyScanCycleFixOut(int $id): JsonResponse
    {
        try {
            $repairBasePlanOutCycleFixEntireInstance = RepairBasePlanOutCycleFixEntireInstance::with([])->where('id', $id)->firstOrFail();
            $repairBasePlanOutCycleFixEntireInstance->fill(['is_scan' => 0]);
            $repairBasePlanOutCycleFixEntireInstance->saveOrFail();

            return HttpResponseHelper::created('成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 确认出所
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function storeScanCycleFixOut(Request $request): JsonResponse
    {
        try {
            $work_area = session('account.work_area');
            if (array_flip(Account::$WORK_AREAS)[$work_area] == 0) return HttpResponseHelper::errorValidate('该用户没有所属工区');
            if (!session('account.work_area_unique_code')) return HttpResponseHelper::errorValidate('当前用户没有所属工区');

            $processed_at = now()->format('Y-m-d H:i:s');

            $bill_id = $request->get('bill_id', '');
            $check = DB::table('repair_base_plan_out_cycle_fix_entire_instances')->where('bill_id', $bill_id)->where('is_scan', 0)->where('new', '<>', '')->where('out_warehouse_sn', '')->get();
            if ($check->isNotEmpty()) return HttpResponseHelper::errorValidate('存在未扫码设备');

            // 获取出所任务单详情
            $bill = DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $bill_id)->first();
            if (empty($bill)) return HttpResponseHelper::errorEmpty('出所任务不存在');
            // 获取车间
            $station = null;
            $station = DB::table('maintains')->where('deleted_at', null)->where('type', 'STATION')->where('unique_code', $bill->station_unique_code)->first();
            if (!$station) return HttpResponseHelper::errorEmpty('车站不存在');
            $sceneWorkshop = null;
            $sceneWorkshop = DB::table('maintains')->where('deleted_at', null)->where('type', 'SCENE_WORKSHOP')->where('unique_code', $station->parent_unique_code)->first();
            if (!$sceneWorkshop) return HttpResponseHelper::errorEmpty('现场车间不存在');

            $serialNumber = CodeFacade::makeSerialNumber('OUT');
            $url = "/warehouse/report/{$serialNumber}?show_type=D&direction=OUT";
            $repairEntireInstances = RepairBasePlanOutCycleFixEntireInstance::with([
                'WithEntireInstance',
                'WithEntireInstance.InstallPosition',
                'Bill',
            ])
                ->where('bill_id', $bill_id)
                ->where('is_scan', 1)
                ->where('out_warehouse_sn', '')
                ->where('new', '<>', '')
                ->get();

            // 创建出所单
            $processor_id = $request->get('processor_id', session('account.id'));
            $warehouseReport = [
                'created_at' => $this->_current_time,
                'updated_at' => $this->_current_time,
                'processor_id' => $processor_id,
                'processed_at' => $processed_at,
                'connection_name' => $request->get('connection_name', ''),
                'connection_phone' => $request->get('connection_phone', ''),
                'type' => 'INSTALL',
                'direction' => 'OUT',
                'serial_number' => $serialNumber,
                'scene_workshop_name' => @$sceneWorkshop->name ?: '无',
                'station_name' => @$station->name ?: '无',
                'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
                'work_area_unique_code' => session('account.work_area_unique_code'),
            ];
            // 创建出所单 → 设备
            $warehouseReportEntireInstances = [];
            $entireInstanceLogs = [];
            $out_entire_instance_correspondences = [];
            $identityCodes = [];
            $ids = [];
            $olds = [];
            foreach ($repairEntireInstances as $repairEntireInstance) {
                $identityCodes[] = $repairEntireInstance->new;
                $olds[] = $repairEntireInstance->old;
                $ids[] = $repairEntireInstance->id;

                // 出所单
                $warehouseReportEntireInstances[] = [
                    'created_at' => $processed_at,
                    'updated_at' => $processed_at,
                    'warehouse_report_serial_number' => $serialNumber,
                    'entire_instance_identity_code' => $repairEntireInstance->new,
                    'maintain_station_name' => $repairEntireInstance->WithEntireInstance->maintain_station_name ?? '',
                    'maintain_location_code' => $repairEntireInstance->WithEntireInstance->maintain_location_code ?? '',
                    'crossroad_number' => $repairEntireInstance->WithEntireInstance->crossroad_number ?? '',
                    'traction' => $repairEntireInstance->WithEntireInstance->traction ?? '',
                    'line_name' => $repairEntireInstance->WithEntireInstance->line_name ?? '',
                    'crossroad_type' => $repairEntireInstance->WithEntireInstance->crossroad_type ?? '',
                    'extrusion_protect' => $repairEntireInstance->WithEntireInstance->extrusion_protect ?? '',
                    'point_switch_group_type' => $repairEntireInstance->WithEntireInstance->point_switch_group_type ?? '',
                    'open_direction' => $repairEntireInstance->WithEntireInstance->open_direction ?? '',
                    'said_rod' => $repairEntireInstance->WithEntireInstance->said_rod ?? '',
                ];
                // 出所设备位置
                $out_entire_instance_correspondences[] = [
                    'old' => $repairEntireInstance->old,
                    'new' => $repairEntireInstance->new,
                    'location' => $repairEntireInstance->location ?? '',
                    'station' => $repairEntireInstance->station_name ?? '',
                    'new_tid' => $repairEntireInstance->new_tid ?? '',
                    'old_tid' => $repairEntireInstance->old_tid ?? '',
                    'out_warehouse_sn' => $serialNumber,
                    'is_scan' => 1,
                    'account_id' => $processor_id,
                ];

                // 新设备出所日志
                $entireInstanceLogs[] = [
                    'created_at' => $processed_at,
                    'updated_at' => $processed_at,
                    'name' => '周期修出所',
                    'description' => implode('；', [
                        '经办人：' . session('account.nickname'),
                        '联系人：' . $request->get('connection_name') ?? '无',
                        '联系电话：' . $request->get('connection_phone') ?? '无',
                        '车站：' . @$sceneWorkshop->name ?: '' . @$station->name ?: '无',
                        '位置：' . (@$repairEntireInstance->WithEntireInstance->InstallPosition->real_name ?: @$repairEntireInstance->WithEntireInstance->maintain_location_code
                        . @$repairEntireInstance->WithEntireInstance->crossroad_number
                            ? @$repairEntireInstance->WithEntireInstance->crossroad_number . ' ' . @$repairEntireInstance->WithEntireInstance->open_direction
                            : '')
                    ]),
                    'entire_instance_identity_code' => $repairEntireInstance->new,
                    'type' => 1,
                    'url' => $url,
                    'operator_id' => session('account.id'),
                    'station_unique_code' => @$station->unique_code,
                ];
            }

            EntireInstanceLock::freeLocks(
                array_merge($olds, $identityCodes),
                [$this->_lock_name],
                function () use (
                    $warehouseReport,
                    $serialNumber,
                    $ids,
                    $identityCodes,
                    $warehouseReportEntireInstances,
                    $entireInstanceLogs,
                    $bill_id,
                    $out_entire_instance_correspondences,
                    $olds
                ) {
                    // 插入出所单
                    DB::table('warehouse_reports')->insert($warehouseReport);
                    // 插入出所单编号
                    DB::table('repair_base_plan_out_cycle_fix_entire_instances')->whereIn('id', $ids)->update([
                        'out_warehouse_sn' => $serialNumber
                    ]);
                    // 修改设备状态
                    DB::table('entire_instances')->whereNull('deleted_at')->whereIn('identity_code', $identityCodes)->update([
                        'updated_at' => $this->_current_time,
                        'installed_at' => now(),
                        'status' => env("ORGANIZATION_CODE") != "B049" ? 'TRANSFER_OUT' : "INSTALLED",
                        'last_warehouse_report_serial_number_by_out' => $serialNumber,
                        'location_unique_code' => '',
                        'is_bind_location' => 0,
                        'last_out_at' => $this->_current_time
                    ]);
                    // 写入出所单 → 设备
                    DB::table('warehouse_report_entire_instances')->insert($warehouseReportEntireInstances);
                    // 写入出所设备位置
                    DB::table('out_entire_instance_correspondences')->insert($out_entire_instance_correspondences);

                    // 重新计算周期修
                    foreach ($identityCodes as $identityCode) {
                        $next_fixing_datum = EntireInstanceFacade::nextFixingTimeWithIdentityCode($identityCode, $this->_current_time);
                        DB::table('entire_instances as ei')->where('identity_code', $identityCode)->update($next_fixing_datum);
                    }

                    // 全部出所任务完成
                    $is_finish = RepairBasePlanOutCycleFixEntireInstance::with([])->where('bill_id', $bill_id)->where('out_warehouse_sn', '')->get();
                    if ($is_finish->isEmpty()) DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $bill_id)->update(['updated_at' => $this->_current_time, 'status' => 'FINISH']);
                    // 插入操作记录
                    EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);
                    // 修改旧设备器材：状态-入所在途
                    DB::table('entire_instances')->where('deleted_at', null)->whereIn('identity_code', $olds)->update([
                        'updated_at' => $this->_current_time,
                        'status' => 'TRANSFER_IN',
                    ]);
                }
            );

            return HttpResponseHelper::data(['url' => $url, 'message' => '出所成功']);
        } catch (\Exception $e) {
            return HttpResponseHelper::error('异常错误', [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]);
        }
    }

    /**
     * 确认出所（连续扫码）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function storeScanCycleFixOutForContinuous(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $bind_entire_instances = $request->get('bind_entire_instances', []) ?? [];

            if (empty($bind_entire_instances)) return JsonResponseFacade::errorValidate('请先扫码添加设备器材');

            if (!session('account.work_area_unique_code')) return JsonResponseFacade::errorForbidden('当前用户没有所属工区');

            $bill_id = $request->get('bill_id', '');

            // 获取出所任务单详情
            $bill = RepairBasePlanOutCycleFixBill::with(['Station', 'Station.Parent',])->where('id', $bill_id)->first();
            if (!$bill) return JsonResponseFacade::errorForbidden('周期修出所任务不存在');
            $station = @$bill->Station ?: null;
            if (!$station) return JsonResponseFacade::errorForbidden('没有找到任务对应的车站不存在');
            $scene_workshop = @$bill->Station->Parent ?: null;
            if (!$scene_workshop) return JsonResponseFacade::errorForbidden('没有找到任务对应的现场车间不存在');

            $diff = [];
            $old_entire_instances = EntireInstance::with([])->select([
                'identity_code',
                'maintain_station_name',
                'maintain_workshop_name',
                'crossroad_number',
                'traction',
                'line_name',
                'crossroad_type',
                'extrusion_protect',
                'point_switch_group_type',
                'open_direction',
                'said_rod',
                'maintain_location_code',
            ])
                ->whereIn('identity_code', array_keys($bind_entire_instances))
                ->get();
            $diff = array_diff($old_entire_instances->pluck('identity_code')->toArray(), array_keys($bind_entire_instances));
            if (!empty($diff)) return JsonResponseFacade::errorEmpty("以下设备器材没有找到（上道使用）：<br>" . implode("<br>", $diff));

            $diff = [];
            $new_entire_instances = EntireInstance::with([])
                ->select(['identity_code'])
                ->whereIn('identity_code', array_values($bind_entire_instances))
                ->get();
            $diff = array_diff($new_entire_instances->pluck('identity_code')->toArray(), array_values($bind_entire_instances));
            if (!empty($diff)) return JsonResponseFacade::errorEmpty("以下设备器材没有找到（所内备品）：<br>" . implode("<br>", $diff));

            if (($old_entire_instances->count() !== $new_entire_instances->count())) return JsonResponseFacade::errorForbidden('上道使用设备器材和所内备品设备器材数量无法匹配');

            $entire_instance_locks = EntireInstanceLock::with([])->select(['remark',])->where('lock_name', '<>', 'CYCLEFIX')->whereIn('entire_instance_identity_code', array_keys($bind_entire_instances))->get();
            if ($entire_instance_locks->isNotEmpty()) return JsonResponseFacade::errorForbidden("以下设备器材被锁：<br>" . implode("<br>", $entire_instance_locks->pluck('remark')->toArray()));

            // 获取周期修缓存数据
            $year = $bill->year;
            $month = $bill->month;
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);
            $file = FileSystem::init(__FILE__);
            $fileDir = storage_path("app/cycleFix");
            $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$bill->station_unique_code}.json");
            if (!file_exists($file->current())) return JsonResponseFacade::errorForbidden("周期修缓存数据不存在");
            $plans = $file->fromJson();

            $old_entire_instances2 = [];
            foreach ($old_entire_instances as $old_entire_instance) {
                $old_entire_instances2[$old_entire_instance->identity_code] = $old_entire_instance;
            }

            $flip_bind_entire_instances = array_flip($bind_entire_instances);
            foreach ($new_entire_instances as $new_entire_instance) {
                $tmp = @$old_entire_instances2[$flip_bind_entire_instances[$new_entire_instance->identity_code]];
                if (!$tmp) return JsonResponseFacade::errorForbidden("没有找到所内备品({$new_entire_instance->identity_code})绑定的上道使用设备器材");

                // 修改替换位置
                DB::table('entire_instances as ei')
                    ->where('ei.identity_code', $new_entire_instance->identity_code)
                    ->update([
                        'updated_at' => now(),
                        'maintain_station_name' => @$tmp->maintain_station_name ?: '',
                        'maintain_workshop_name' => @$tmp->maintain_workshop_name ?: '',
                        'crossroad_number' => @$tmp->crossroad_number ?: '',
                        'traction' => @$tmp->traction ?: '',
                        'line_name' => @$tmp->line_name ?: '',
                        'crossroad_type' => @$tmp->crossroad_type ?: '',
                        'extrusion_protect' => @$tmp->extrusion_protect ?: '',
                        'point_switch_group_type' => @$tmp->point_switch_group_type ?: '',
                        'open_direction' => @$tmp->open_direction ?: '',
                        'said_rod' => @$tmp->said_rod ?: '',
                        'last_out_at' => now(),
                        'maintain_location_code' => @$tmp->maintain_location_code ?: '',
                    ]);

                // 修改缓存数据
                if (!@$bind_entire_instances[$tmp->identity_code]) return JsonResponseFacade::errorForbidden("没有找到上道使用({$tmp->identity_code})绑定的所内备品");
                $plans['models'][$tmp->model_unique_code]['devices'][$tmp->identity_code]['new_identity_code'] = $bind_entire_instances[$tmp->identity_code];
            }

            // 办理出所
            $warehouse_report_ret = WarehouseReportFacade::standardBatchOut(
                EntireInstance::with([])->whereIn('identity_code', array_values($bind_entire_instances))->get(),
                now()->format('Y-m-d'),
                $request->get('connection_name') ?? '',
                $request->get('connection_phone') ?? ''
            );
            if (!$warehouse_report_ret['ret']) return JsonResponseFacade::errorForbidden($warehouse_report_ret['msg']);
            $warehouse_report_sn = $warehouse_report_ret['warehouse_report_sn'];

            // 相关设备器材解锁
            $unlock = EntireInstanceLock::freeLocks(array_keys($bind_entire_instances), [$this->_lock_name]);

            if (!empty($warehouse_report_sn)) {
                // 修改周期修任务设备器材数据
                foreach ($new_entire_instances as $new_entire_instance) {
                    $tmp = @$old_entire_instances2[$flip_bind_entire_instances[$new_entire_instance->identity_code]];
                    if (!$tmp) return JsonResponseFacade::errorForbidden("没有找到所内备品({$new_entire_instance->identity_code})绑定的上道使用设备器材");

                    DB::table('repair_base_plan_out_cycle_fix_entire_instances as rbpocfei')
                        ->where('rbpocfei.old', $tmp->identity_code)
                        ->update([
                            'new' => $new_entire_instance->identity_code,
                            'location' => $tmp->maintain_location_code ?: ($tmp->crossroad_number ? $tmp->crossroad_number . ' ' . $tmp->open_direction : ''),
                            'station_name' => @$bill->Station->name ?: '无',
                            'is_scan' => false,
                            'out_warehouse_sn' => $warehouse_report_sn,
                            'station_unique_code' => @$bill->Station->unique_code ?: '',
                        ]);
                }

                DB::commit();
            } else {
                DB::rollBack();
            }

            // 检查周期修任务是否完成
            $check_un_finish = RepairBasePlanOutCycleFixEntireInstance::with([])->where('bill_id', $bill_id)->where('out_warehouse_sn', '')->exists();
            if (!$check_un_finish) DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $bill_id)->update(['updated_at' => now(), 'status' => 'FINISH']);

            return JsonResponseFacade::dict([
                'url' => "/warehouse/report/{$warehouse_report_sn}?direction=OUT&show_type=D",
                'check_un_finish' => $check_un_finish,
            ], '出所成功');
        } catch (\Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 周期修任务-关闭
     * @param $billId
     * @return mixed
     */
    final public function billWithClose($billId)
    {
        try {
            DB::beginTransaction();
            $bill = DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $billId)->first();
            if (empty($bill)) return JsonResponseFacade::errorEmpty();
            $repairBasePlanOutCycleFixEntireInstances = RepairBasePlanOutCycleFixEntireInstance::with(['WithEntireInstanceOld'])->where('bill_id', $billId)->where('out_warehouse_sn', '')->get();
            if ($repairBasePlanOutCycleFixEntireInstances->isEmpty()) return JsonResponseFacade::errorEmpty();
            $newCodes = [];
            $oldCodes = [];
            foreach ($repairBasePlanOutCycleFixEntireInstances as $repairBasePlanOutCycleFixEntireInstance) {
                if (!empty($repairBasePlanOutCycleFixEntireInstance->new)) {
                    if (!in_array($repairBasePlanOutCycleFixEntireInstance->new, $newCodes)) $newCodes[] = $repairBasePlanOutCycleFixEntireInstance->new;
                }
                if (!array_key_exists($repairBasePlanOutCycleFixEntireInstance->old, $oldCodes)) {
                    $oldCodes[$repairBasePlanOutCycleFixEntireInstance->old] = $repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld->model_unique_code ?? '';
                }
            }

            $unlock = EntireInstanceLock::freeLocks(array_merge(array_keys($oldCodes), $newCodes), [$this->_lock_name]);

            // 修改缓存数据
            $year = $bill->year;
            $month = str_pad($bill->month, 2, '0', STR_PAD_LEFT);
            $stationUniqueCode = $bill->station_unique_code;
            $file = FileSystem::init(__FILE__);
            $fileDir = storage_path("app/cycleFix");
            $plans = $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$stationUniqueCode}.json")->fromJson();
            if (!empty($plans)) {
                foreach ($oldCodes as $oldCode => $modelUniqueCode) {
                    if (!empty($modelUniqueCode)) {
                        $plans['models'][$modelUniqueCode]['devices'][$oldCode]['new_identity_code'] = '';
                        $file->setPath("{$fileDir}/{$year}/{$year}-{$month}/planDeviceStation")->join("{$stationUniqueCode}.json")->toJson($plans);
                    }
                }
            }
            // 修改绑定记录
            DB::table('repair_base_plan_out_cycle_fix_entire_instances')->where('bill_id', $bill->id)->whereIn('old', array_keys($oldCodes))->update(['new' => '', 'is_scan' => 0]);
            // 清楚新设备位置
            if (!empty($newCodes)) EntireInstanceFacade::clearLocations($newCodes);
            // 修改任务-关闭
            DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $bill->id)->update(['updated_at' => $this->_current_time, 'status' => 'CLOSE']);

            $unlock ? DB::commit() : DB::rollBack();

            return JsonResponseFacade::deleted(['unlock' => $unlock,], '关闭成功');
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 周期修任务-开启
     * @param $billId
     * @return mixed
     */
    final public function billWithOpen($billId)
    {
        try {
            $bill = DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $billId)->first();
            if (empty($bill)) return JsonResponseFacade::errorEmpty();
            $repairBasePlanOutCycleFixEntireInstances = RepairBasePlanOutCycleFixEntireInstance::with(['WithEntireInstanceOld'])->where('bill_id', $billId)->where('out_warehouse_sn', '')->get();
            if ($repairBasePlanOutCycleFixEntireInstances->isEmpty()) return JsonResponseFacade::errorEmpty();
            $oldCodes = [];
            $remarks = [];
            $currentWorkAreaId = $bill->work_area_id;
            $stationName = $bill->station_name;
            $year = $bill->year;
            $month = str_pad($bill->month, 2, '0', STR_PAD_LEFT);
            foreach ($repairBasePlanOutCycleFixEntireInstances as $repairBasePlanOutCycleFixEntireInstance) {
                if (!in_array($repairBasePlanOutCycleFixEntireInstance->old, $oldCodes)) $oldCodes[] = $repairBasePlanOutCycleFixEntireInstance->old;
                if (!array_key_exists($repairBasePlanOutCycleFixEntireInstance->old, $remarks)) {
                    $remarks[$repairBasePlanOutCycleFixEntireInstance->old] = self::makeLockRemark($repairBasePlanOutCycleFixEntireInstance->old, [
                        'work_area_id' => $currentWorkAreaId,
                        'station_name' => $stationName,
                        'year' => $year,
                        'month' => $month,
                    ]);
                }
            }
            EntireInstanceLock::setOnlyLocks(
                $oldCodes,
                [$this->_lock_name],
                $remarks,
                function () use ($bill) {
                    // 修改任务-开启
                    DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $bill->id)->update(['updated_at' => $this->_current_time, 'status' => 'ORIGIN']);
                }
            );

            return JsonResponseFacade::created([], '开启成功');
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 验证扫码编号
     */
    final public function getCheckEntireInstanceForContinuous()
    {
        try {
            $old_entire_instance_identity_code = request('old_entire_instance_identity_code');
            $new_entire_instance_identity_code = request('new_entire_instance_identity_code');

            $old_first = substr($old_entire_instance_identity_code, 0, 7);
            $new_first = substr($new_entire_instance_identity_code, 0, 7);
            if ($old_first != $new_first) {
                $old_entire_instance = DB::table('entire_instances as ei')
                    ->select(['category_unique_code', 'entire_model_unique_code', 'model_unique_code'])
                    ->whereNull('deleted_at')
                    ->where('status', '<>', 'SCRAP')
                    ->where('identity_code', $old_entire_instance_identity_code)
                    ->first();
                if (!$old_entire_instance) return JsonResponseFacade::errorEmpty('待替换设备器材没有找到');

                $new_entire_instance = DB::table('entire_instances as ei')
                    ->select(['ei.identity_code', 'ei.category_unique_code', 'ei.entire_model_unique_code', 'ei.model_unique_code', 'eil.remark',])
                    ->leftJoin(DB::raw('entire_instance_locks eil'), 'eil.entire_instance_identity_code', '=', 'ei.identity_code')
                    ->whereNull('ei.deleted_at')
                    ->where('ei.status', '<>', 'SCRAP')
                    ->where('ei.identity_code', $new_entire_instance_identity_code)
                    ->first();
                if (!$new_entire_instance) return JsonResponseFacade::errorEmpty('所内备品设备器材没有找到');
                if ($new_entire_instance->remark) return JsonResponseFacade::errorForbidden($new_entire_instance->remark);

                if ($old_entire_instance->category_unique_code != $new_entire_instance->category_unique_code) {
                    return JsonResponseFacade::errorForbidden('种类不匹配', ['']);
                }
                if ($old_entire_instance->entire_model_unique_code != $new_entire_instance->entire_model_unique_code) {
                    return JsonResponseFacade::errorForbidden('类型不匹配');
                }
                if ($old_entire_instance->model_unique_code != $new_entire_instance->model_unique_code) {
                    return JsonResponseFacade::errorForbidden('型号不匹配');
                }
            }

            $is_identity_code = CodeFacade::isIdentityCode($old_entire_instance_identity_code);
            if (!$is_identity_code) return JsonResponseFacade::errorForbidden("格式错误：只支持唯一编号（{$old_entire_instance_identity_code}）");
            $is_identity_code = CodeFacade::isIdentityCode($new_entire_instance_identity_code);
            if (!$is_identity_code) return JsonResponseFacade::errorForbidden("格式错误：只支持唯一编号（{$new_entire_instance_identity_code}）");

            return JsonResponseFacade::ok();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
