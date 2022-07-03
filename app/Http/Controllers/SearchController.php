<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\QrCodeFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceAlarmLog;
use App\Model\FixWorkflowProcess;
use App\Model\Maintain;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;
use Throwable;

class SearchController extends Controller
{
    /**
     * 搜索详情
     * @param $entireInstanceIdentityCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show($entireInstanceIdentityCode)
    {
        try {
            $entireInstance = EntireInstance::withoutGlobalScope('status')
                ->with([
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
                    'ParentInstance',
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
                ->where('identity_code', $entireInstanceIdentityCode)
                ->firstOrFail();

            // 生成健康码
            $qr_code_base64 = QrCodeFacade::generateBase64ByEntireInstanceStatus($entireInstance->identity_code);

            $all_stations = Maintain::with([])->where('type', 'STATION')->get();

            # 已经存在的部件，循环空部件时不显示
            $partCategoryIds = [];
            foreach ($entireInstance->PartInstances as $partInstance) {
                if (@$partInstance->PartModel->part_category_id)
                    $partCategoryIds[] = @$partInstance->PartModel->part_category_id;
            }

            // # 获取最后一次检修人
            // $fixer = FixWorkflowProcess::with(['Processor'])->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'FIX_AFTER')->first();
            // # 获取最后一次验收人
            // $checker = FixWorkflowProcess::with(['Processor'])->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'CHECKED')->first();

            $entireInstanceLogs = DB::table('entire_instance_logs')
                ->whereNull('deleted_at')
                ->where('entire_instance_identity_code', $entireInstanceIdentityCode)
                ->orderByDesc('id')
                ->get();

            $entireInstanceLogsWithMonth = [];
            $breakdownLogsWithMonth = [];
            try {
                foreach ($entireInstanceLogs as $entireInstanceLog) {
                    $month = Carbon::createFromFormat('Y-m-d H:i:s', $entireInstanceLog->created_at)->format('Y-m');
                    $entireInstanceLogsWithMonth[$month][] = $entireInstanceLog;
                }
                foreach ($entireInstance->BreakdownLogs as $breakdownLog) {
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
                ->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)
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
                ->where('entire_instance_identity_code', $entireInstanceIdentityCode)
                ->orderByDesc('id')
                ->first();
            // dd($lastFixWorkflow);

            # 故障类型
            $breakdownTypes = DB::table('breakdown_types')
                ->whereNull('deleted_at')
                ->where('category_unique_code', $entireInstance->category_unique_code)
                ->pluck('name', 'id')
                ->chunk(3);

            // entire instance alarm logs
            $entire_instance_alarm_logs = EntireInstanceAlarmLog::with(['Station'])
                ->where('entire_instance_identity_code', $entireInstance->identity_code)
                ->orderByDesc('created_at')
                ->get();

            if (!empty($entireInstance->Station->lon) && !empty($entireInstance->Station->lat)) {
                $station_location = [
                    'unique_code' => $entireInstance->Station->unique_code,
                    'name' => $entireInstance->Station->name,
                    'parent_unique_code' => $entireInstance->Station->Parent->unique_code ?? '',
                    'lon' => $entireInstance->Station->lon,
                    'lat' => $entireInstance->Station->lat,
                    'contact' => $entireInstance->Station->contact,
                    'contact_phone' => $entireInstance->Station->contact_phone,
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

            return view('Search.show', [
                'fixWorkflows' => @$entireInstance->FixWorkflows,
                'entireInstance' => @$entireInstance,
                'fixWorkflow' => @$entireInstance->FixWorkflow,
                'lastFixWorkflowRecodeEntire' => @$lastFixWorkflowRecodeEntire,
                'entireInstanceLogs' => collect(@$entireInstanceLogsWithMonth),
                // 'fixer' => @$fixer->Processor ? @$fixer->Processor->nickname : '',
                // 'checker' => @$checker->Processor ? @$checker->Processor->nickname : '',
                // 'fixed_at' => @$fixer->updated_at ? date('Y-m-d', strtotime(@$fixer->updated_at)) : '',
                // 'checker_at' => @$checker->updated_at ? date('Y-m-d', strtotime(@$checker->updated_at)) : '',
                'breakdownLogsWithMonth' => @$breakdownLogsWithMonth,
                'entireInstanceIdentityCode' => @$entireInstanceIdentityCode,
                'check_json_data' => @$check_json_data,
                'lastFixWorkflow' => @$lastFixWorkflow,
                'breakdownTypes' => @$breakdownTypes,
                'partCategoryIds' => @$partCategoryIds,
                'all_stations' => $all_stations,
                'entire_instance_alarm_logs' => $entire_instance_alarm_logs,
                'station_location_as_json' => collect($station_location)->toJson(),
                'qr_code_base64' => $qr_code_base64,
                // 'pivot_model_and_part_categories' => $pivot_model_and_part_categories,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (\Exception $e) {
            $exceptionMessage = $e->getMessage();
            $exceptionLine = $e->getLine();
            $exceptionFile = $e->getFile();
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $exceptionMessage . ':' . $exceptionFile . ':' . $exceptionLine);
        }
    }

    /**
     * 查看绑定设备下所有器材
     * @param string $bindDeviceCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getBindDevice(string $bindDeviceCode)
    {
        try {
            $entireInstances = EntireInstance::with([
                'Category',
            ])
                ->where('bind_device_code', $bindDeviceCode)
                ->orderByDesc('category_unique_code')
                ->orderByDesc('updated_at')
                ->get();

            return view('Search.bindDevice', [
                'bindDeviceCode' => $bindDeviceCode,
                'entireInstances' => $entireInstances,
                'statuses' => EntireInstance::$STATUSES,
            ]);
        } catch (ModelNotFoundException $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 查看绑定道岔号下所有器材
     * @param string $bind_crossroad_number
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getBindCrossroadNumber(string $bind_crossroad_number)
    {
        try {
            $entireInstances = EntireInstance::with([
                'Category',
            ])
                ->where(function ($query) use ($bind_crossroad_number) {
                    $query->where('bind_crossroad_number', $bind_crossroad_number)
                        ->orWhere('crossroad_number', $bind_crossroad_number);
                })
                ->orderByDesc('category_unique_code')
                ->orderByDesc('updated_at')
                ->get();


            return view('Search.bindCrossroadNumber', [
                'bindCrossroadNumber' => $bind_crossroad_number,
                'entireInstances' => $entireInstances,
                'statuses' => EntireInstance::$STATUSES,
            ]);
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }
}
