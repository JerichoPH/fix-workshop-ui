<?php

namespace App\Http\Controllers\Display;

use App\Facades\CommonFacade;
use App\Facades\QrCodeFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceAlarmLog;
use App\Model\FixWorkflowProcess;
use App\Model\Maintain;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class EntireInstanceController extends Controller
{
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
        //
    }

    /**
     * Display the specified resource.
     * @param string $identity_code
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View|string
     */
    final public function show(string $identity_code)
    {
        try {
            if (request('is_s01') == 1) {
                $a = DB::table('entire_instances as ei')
                    ->select(['identity_code'])
                    ->whereNull('ei.deleted_at')
                    ->where('ei.status', '!=', 'SCRAP')
                    ->where('ei.crossroad_number', $identity_code)
                    ->where('ei.category_unique_code', 'S01')
                    ->first();
                if (!$a) return '<h1>错误：数据不存在</h1>';
                $identity_code = $a->identity_code;
            }

            $entire_instance = EntireInstance::with([
                'EntireModel',
                'EntireModel.Category',
                'EntireModel.Category.PartCategories',
                'EntireModel.Measurements',
                'EntireModel.Measurements.PartModel',
                'EntireModel.EntireModelImages' => function ($EntireModelImages) {
                    $EntireModelImages->limit(2);
                },
                'SubModel.EntireModelImages' => function ($EntireModelImages) {
                    $EntireModelImages->limit(2);
                },
                'WarehouseReportByOut',
                'PartInstances',
                'PartInstances.PartModel',
                'PartInstances.PartModel.PartCategory',
                'PartInstances.PartModel.PartModelImages' => function ($PartModelImages) {
                    $PartModelImages->limit(1);
                },
                'FixWorkflows' => function ($fixWorkflow) {
                    $fixWorkflow->orderByDesc('id');
                },
                'FixWorkflow.WarehouseReport',
                'FixWorkflow.Processor',
                'FixWorkflow.FixWorkflowProcesses',
                'FixWorkflow.FixWorkflowProcesses.Measurement',
                'FixWorkflow.FixWorkflowProcesses.Processor',
                'FixWorkflow.FixWorkflowProcesses.Measurement.PartModel',
                'FixWorkflow.EntireInstance.PartInstances',
                'FixWorkflow.EntireInstance.PartInstances.PartModel',
                'WithPosition',
                'WithPosition.WithTier',
                'WithPosition.WithTier.WithShelf',
                'WithPosition.WithTier.WithShelf.WithPlatoon',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse',
                'BreakdownLogs' => function ($BreakdownLogs) {
                    $BreakdownLogs->orderByDesc('id');
                },
                'BreakdownLogs.BreakdownTypes',
                'Station',
                'Station.Parent',
                'WithSendRepairInstances',
                'InstallPosition',
            ])
                ->withTrashed()
                ->where('identity_code', $identity_code)
                ->firstOrFail();

            // 生成健康码
            $qr_code_base64 = QrCodeFacade::generateBase64ByEntireInstanceStatus($entire_instance->identity_code);

            $all_stations = Maintain::with([])->where('type', 'STATION')->get();

            # 已经存在的部件，循环空部件时不显示
            $partCategoryIds = [];
            foreach ($entire_instance->PartInstances as $partInstance) {
                $partCategoryIds[] = $partInstance->PartModel->part_category_id;
            }

            // # 获取最后一次检修人
            // $fixer = FixWorkflowProcess::with(['Processor'])->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'FIX_AFTER')->first();
            // # 获取最后一次验收人
            // $checker = FixWorkflowProcess::with(['Processor'])->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'CHECKED')->first();

            $entireInstanceLogs = DB::table('entire_instance_logs')
                ->where('deleted_at', null)
                ->where('entire_instance_identity_code', $identity_code)
                ->orderByDesc('id')
                ->get();

            $entireInstanceLogsWithMonth = [];
            $breakdownLogsWithMonth = [];
            try {
                foreach ($entireInstanceLogs as $entireInstanceLog) {
                    $month = Carbon::createFromFormat('Y-m-d H:i:s', $entireInstanceLog->created_at)->format('Y-m');
                    $entireInstanceLogsWithMonth[$month][] = $entireInstanceLog;
                }
                foreach ($entire_instance->BreakdownLogs as $breakdownLog) {
                    $month = Carbon::createFromFormat('Y-m-d H:i:s', $breakdownLog->created_at)->format('Y-m');
                    $breakdownLogsWithMonth[$month][] = $breakdownLog;
                }
            } catch (\Exception $e) {
            }
            krsort($entireInstanceLogsWithMonth);
            krsort($breakdownLogsWithMonth);

            # 获取最后一次检测记录（左下侧显示）
            $lastFixWorkflowRecodeEntire = FixWorkflowProcess::with([
                'FixWorkflowRecords',
                'FixWorkflowRecords.Measurement',
                'FixWorkflowRecords.EntireInstance',
                'FixWorkflowRecords.EntireInstance.EntireModel',
            ])
                ->orderByDesc('id')
                ->where('fix_workflow_serial_number', $entire_instance->fix_workflow_serial_number)
                ->first();
            $check_json_data = [];
            switch (@$lastFixWorkflowRecodeEntire->check_type ?? '') {
                case 'JSON':
                    $file = public_path($lastFixWorkflowRecodeEntire->upload_url);
                    if (is_file($file)) {
                        $json = TextHelper::parseJson(file_get_contents("{$file}"));
                        if (!empty($json)) {
                            $check_json_data = @$json['body']['测试项目'];
                        }
                    }
                    break;
                case 'JSON2':
                    $file = public_path($lastFixWorkflowRecodeEntire->upload_url);
                    if (is_file($file)) {
                        $json = TextHelper::parseJson(file_get_contents("{$file}"));
                        if (!empty($json)) {
                            $check_json_data = @$json['body'];
                        }
                    }
                    break;
            }
            # 上一张检测单
            $lastFixWorkflow = DB::table('fix_workflows')
                ->select('entire_instance_identity_code', 'stage', 'status', 'serial_number')
                ->where('entire_instance_identity_code', $identity_code)
                ->orderByDesc('id')
                ->first();
            // dd($lastFixWorkflow);

            # 故障类型
            $breakdownTypes = DB::table('breakdown_types')
                ->where('deleted_at', null)
                ->where('category_unique_code', $entire_instance->category_unique_code)
                ->pluck('name', 'id')
                ->chunk(3);

            // entire instance alarm logs
            $entire_instance_alarm_logs = EntireInstanceAlarmLog::with(['Station'])
                ->where('entire_instance_identity_code', $entire_instance->identity_code)
                ->orderByDesc('created_at')
                ->get();

            if (!empty($entire_instance->Station->lon) && !empty($entire_instance->Station->lat)) {
                $station_location = [
                    'unique_code' => $entire_instance->Station->unique_code,
                    'name' => $entire_instance->Station->name,
                    'parent_unique_code' => $entire_instance->Station->Parent->unique_code ?? '',
                    'lon' => $entire_instance->Station->lon,
                    'lat' => $entire_instance->Station->lat,
                    'contact' => $entire_instance->Station->contact,
                    'contact_phone' => $entire_instance->Station->contact_phone,
                ];
            } else {
                $fix_workshop = Maintain::with([])->where('unique_code', env('CURRENT_WORKSHOP_UNIQUE_CODE'))->first();
                $station_location = [
                    'unique_code' => @$fix_workshop->unique_code ?? '',
                    'name' => @$fix_workshop->name ?? '',
                    'parent_unique_code' => @$fix_workshop->parent_unique_code ?? '',
                    'lon' => @$fix_workshop->lon ?? '',
                    'lat' => @$fix_workshop->lat ?? '',
                    'contact' => @$fix_workshop->contact ?? '',
                    'contact_phone' => @$fix_workshop->contact_phone ?? '',
                ];
            }
            // $pivot_model_and_part_categories = DB::table('pivot_model_and_part_categories')
            //     ->where($entireInstance->category_unique_code)
            //     ->get();


            $is_s01 = substr($entire_instance->identity_code, 0, 3) === 'S01';
            $is_s = substr($entire_instance->identity_code, 0, 1) === 'S';
            $bind_entire_instances_q = collect([]);
            $bind_entire_instances_s = collect([]);
            $view_name = 'Display.EntireInstance.show_Q';
            if ($entire_instance->crossroad_number) {
                if ($is_s01) {
                    $bind_entire_instances_s = EntireInstance::with([])->where('category_unique_code', '!=', 'S01')->where('crossroad_number', '!=', '')->where('crossroad_number', $entire_instance->crossroad_number)->get();  // 获取道岔上的设备
                    $bind_entire_instances_q = EntireInstance::with([])->where('category_unique_code', 'like', 'Q%')->where('bind_device_code', '!=', '')->where('bind_device_code', $entire_instance->identity_code)->get();  // 获取道岔上的器材
                    $view_name = 'Display.EntireInstance.show_S01';
                } else if (!$is_s01 && $is_s) {
                    $bind_entire_instances_q = EntireInstance::with([])->where('category_unique_code', 'like', 'Q%')->where('bind_device_code', '!=', '')->where('bind_device_code', $entire_instance->bind_device_code)->get();  // 获取道岔上的器材
                    $view_name = $is_s01 ? 'Display.EntireInstance.show_S01' : 'Display.EntireInstance.show_S';
                }
            }

            return view(($view_name), [
                'fixWorkflows' => @$entire_instance->FixWorkflows,
                'entireInstance' => @$entire_instance,
                'fixWorkflow' => @$entire_instance->FixWorkflow,
                'lastFixWorkflowRecodeEntire' => @$lastFixWorkflowRecodeEntire,
                'entireInstanceLogs' => collect(@$entireInstanceLogsWithMonth),
                // 'fixer' => @$fixer->Processor ? @$fixer->Processor->nickname : '',
                // 'checker' => @$checker->Processor ? @$checker->Processor->nickname : '',
                // 'fixed_at' => @$fixer->updated_at ? date('Y-m-d', strtotime(@$fixer->updated_at)) : '',
                // 'checker_at' => @$checker->updated_at ? date('Y-m-d', strtotime(@$checker->updated_at)) : '',
                'breakdownLogsWithMonth' => @$breakdownLogsWithMonth,
                'entireInstanceIdentityCode' => @$identity_code,
                'check_json_data' => @$check_json_data,
                'lastFixWorkflow' => @$lastFixWorkflow,
                'breakdownTypes' => @$breakdownTypes,
                'partCategoryIds' => @$partCategoryIds,
                'all_stations' => $all_stations,
                'entire_instance_alarm_logs' => $entire_instance_alarm_logs,
                'station_location_as_json' => collect($station_location)->toJson(),
                'qr_code_base64' => $qr_code_base64,
                // 'pivot_model_and_part_categories' => $pivot_model_and_part_categories,
                'bind_entire_instances_s' => $bind_entire_instances_s,
                'bind_entire_instances_q' => $bind_entire_instances_q,
            ]);
        } catch (ModelNotFoundException $e) {
            return '<h1>错误：数据不存在</h1>';
        } catch (Throwable $e) {
            return "<h1>意外错误</h1><h3>{$e->getMessage()}</h3>";
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
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
