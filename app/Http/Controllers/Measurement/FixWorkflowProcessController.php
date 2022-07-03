<?php

namespace App\Http\Controllers\Measurement;

use App\Facades\CodeFacade;
use App\Http\Controllers\Controller;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\FixWorkflowRecord;
use App\Model\PartInstance;
use App\Model\PivotEntireModelAndPartModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Jericho\HttpResponseHelper;

class FixWorkflowProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $fixWorkflowProcesses = FixWorkflowProcess::with([])->where('type', request('type'))
            ->where('fix_workflow_serial_number', request('fixWorkflowSerialNumber'))
            ->orderByDesc('id')
            ->paginate();

        return view('Measurement.FixWorkflowProcess.index')
            ->with('fixWorkflowProcesses', $fixWorkflowProcesses);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function create()
    {
        $part_instances = null;
        if (request('type') == 'PART') {
            $fixWorkflow = FixWorkflow::with([
                'EntireInstance',
                'EntireInstance.PartInstances',
                'EntireInstance.PartInstances.PartCategory',
            ])
                ->where('serial_number', request('fixWorkflowSerialNumber'))
                ->first();
            if (!$fixWorkflow->EntireInstance) return HttpResponseHelper::errorEmpty('没有找到对应设备');
            if (!$fixWorkflow->EntireInstance->PartInstances) return HttpResponseHelper::errorEmpty('没有找到部件列表');
            $part_instances = $fixWorkflow->EntireInstance->PartInstances;
        }

        return view('Measurement.FixWorkflowProcess.create_ajax', [
            'type' => request('type'),
            'part_instances' => $part_instances,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return array|\Illuminate\Http\Response
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        try {
            $newFixWorkflowProcessSerialNumber = null;

            DB::transaction(function () use ($request, &$newFixWorkflowProcessSerialNumber) {
                # 获取检测单和检测单总数
                $fixWorkflowProcessCount = FixWorkflowProcess::with([])
                        ->where('fix_workflow_serial_number', $request->get('fix_workflow_serial_number'))
                        ->where('type', $request->get('type'))
                        ->where('stage', $request->get('stage'))
                        ->count('id') + 1;

                # 如果有部件编号（部件检测）
                $part_instance_auto_explain = "";
                if ($request->get('part_instance_identity_code')) {
                    $part_instance = PartInstance::with(['PartModel', 'PartModel.PartCategory'])->where('identity_code', $request->get('part_instance_identity_code'))->first();
                    if (!$part_instance) throw new \Exception('部件不存在');
                    $part_instance_auto_explain = "({$part_instance->PartModel->PartCategory->name}:{$part_instance->identity_code})";
                }

                # 新建检测单
                $fixWorkflowProcess = new FixWorkflowProcess;
                $newFixWorkflowProcessSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS');
                $fixWorkflowProcess->fill(
                    array_merge(
                        $request->all(), [
                            'serial_number' => $newFixWorkflowProcessSerialNumber,
                            'auto_explain' => "第{$fixWorkflowProcessCount}次：" . FixWorkflowProcess::$STAGE[$request->get('stage')] . $part_instance_auto_explain,
                            'numerical_order' => $fixWorkflowProcessCount,
                        ]
                    )
                )
                    ->saveOrFail();

                # 保存检修单检测次数
                $fixWorkflow = FixWorkflow::with([])->where('serial_number', $request->get('fix_workflow_serial_number'))->firstOrFail();
                $fixWorkflow->fill(['processed_times' => $fixWorkflowProcessCount])->saveOrFail();

                # 创建空测试数据
                $fixWorkflow = FixWorkflow::with([
                    'EntireInstance',
                    'EntireInstance.Measurements' => function ($q) {
                        $q->where('part_model_unique_code', null);
                    },
                    'EntireInstance.PartInstances',
                    'EntireInstance.PartInstances.PartModel',
                    'EntireInstance.PartInstances.PartModel.Measurements',
                ])
                    ->where('serial_number', $request->get('fix_workflow_serial_number'))
                    ->firstOrFail();

                $i = 0;
                $fixWorkflowRecords = [];
                switch ($request->get('type')) {
                    case 'ENTIRE':
                        # 非额外测试项
                        $unExtraTagMeasurements = DB::table('measurements')
                            ->where('deleted_at', null)
                            ->where('entire_model_unique_code', $fixWorkflow->EntireInstance->entire_model_unique_code)
                            ->where('part_model_unique_code', null)
                            ->where('is_extra_tag', 0)
                            ->get()
                            ->toArray();

                        # 额外测试项
                        $extraTagMeasurements = DB::table('measurements')
                            ->where('deleted_at', null)
                            ->where('entire_model_unique_code', $fixWorkflow->EntireInstance->entire_model_unique_code)
                            ->where('part_model_unique_code', null)
                            ->where('is_extra_tag', 1)
                            ->whereIn(
                                'extra_tag',
                                DB::table('pivot_entire_instance_and_extra_tags')->where('entire_instance_identity_code', $fixWorkflow->entire_instance_identity_code)->pluck('extra_tag')->toArray()
                            )
                            ->get()
                            ->toArray();

                        foreach (array_merge($unExtraTagMeasurements, $extraTagMeasurements) as $item) {
                            $i++;
                            $fixWorkflowRecords[] = [
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'fix_workflow_process_serial_number' => $newFixWorkflowProcessSerialNumber,
                                'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                                'measurement_identity_code' => $item->identity_code,
                                'serial_number' => CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS_ENTIRE') . "_{$i}",
                                'type' => $request->get('type'),
                            ];
                        }
                        break;
                    case 'PART':
                        $part_instance = PartInstance::with(['PartModel', 'PartModel.Measurements'])->where('identity_code', $request->get('part_instance_identity_code'))->first();
                        $part_instance->fill(['status' => 'FIXING'])->save();  # 修改部件状态
                        foreach ($part_instance->PartModel->Measurements as $measurement) {
                            $i++;
                            $fixWorkflowRecords[] = [
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'fix_workflow_process_serial_number' => $newFixWorkflowProcessSerialNumber,
                                'part_instance_identity_code' => $part_instance->identity_code,
                                'measurement_identity_code' => $measurement->identity_code,
                                'serial_number' => CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS_PART') . "_{$i}",
                                'type' => $request->get('type'),
                            ];
                        }
                        break;
                }
                if (!$fixWorkflowRecords) throw new \Exception('没有测试模板');
                if (!DB::table('fix_workflow_records')->insert($fixWorkflowRecords)) throw new \Exception('创建检测空记录失败');

                # 更新检修单阶段
                $fixWorkflow->fill(['stage' => $request->get('stage')])->saveOrFail();

                # 修改整件状态
                $fixWorkflow->EntireInstance->fill(['status' => 'FIXING', 'in_warehouse' => false])->saveOrFail();

                # 修改部件状态
                DB::table('part_instances')->where('entire_instance_identity_code', $fixWorkflow->EntireInstance)->update(['status' => 'FIXING']);
            });

            return Response::make($newFixWorkflowProcessSerialNumber);
        } catch (ModelNotFoundException $e) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
//            return response()->make(get_class($e) . "\r\n{$msg}\r\n{$file}\r\n{$line}", 500);
            return response()->make("{$msg}", 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param string $serialNumber
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function edit(string $serialNumber)
    {
        try {
            $fixWorkflowProcess = FixWorkflowProcess::with([
                'FixWorkflow',
                'FixWorkflow.EntireInstance',
                'FixWorkflow.EntireInstance.PartInstances' => function ($partInstances) {
                    $partInstances->whereHas("PartCategory", function ($query) {
                        $query->where("is_need_detection", true);
                    });
                },
                'FixWorkflow.EntireInstance.PartInstances.PartCategory',
                'FixWorkflow.EntireInstance.PartInstances.PartModel',
                'FixWorkflow.EntireInstance.PartInstances.PartModel.Measurements',
                'FixWorkflowRecords' => function ($fixWorkflowRecord) {
                    $fixWorkflowRecord->orderBy('measurement_identity_code');
                },
                'FixWorkflowRecords.EntireInstance',
                'FixWorkflowRecords.EntireInstance.EntireModel',
                'FixWorkflowRecords.PartInstance',
                'FixWorkflowRecords.PartInstance.PartModel',
                'FixWorkflowRecords.Measurement',
                'FixWorkflowRecords.Processor'
            ])
                ->where('serial_number', $serialNumber)
                ->firstOrFail();

            # 获取该检修车间下的人员
            $accounts = DB::table('accounts')
                ->where('deleted_at', null)
                ->where('workshop_code', env('ORGANIZATION_CODE', null))
                ->get();

            return view('Measurement.FixWorkflowProcess.edit')
                ->with('fixWorkflowSerialNumber', request('fixWorkflowSerialNumber'))
                ->with('accounts', $accounts)
                ->with('type', request('type'))
                ->with('page', request('page'))
                ->with('fixWorkflowProcess', $fixWorkflowProcess);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionFile = $exception->getFile();
            $exceptionLine = $exception->getLine();
            return back()->withInput()->with('danger', "{$exceptionMessage}<br>{$exceptionFile}<br>{$exceptionLine}");
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param string $fixWorkflowProcessSerialNumber
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function update(Request $request, string $fixWorkflowProcessSerialNumber)
    {
        try {
            # 检查当前测试结果是否存在不合格
            $fixWorkflowProcessCount = count(FixWorkflowProcess::with(['FixWorkflowRecords'])->where('serial_number', $fixWorkflowProcessSerialNumber)->firstOrFail()->FixWorkflowRecords);  # 该测试单应该具有的测试数据总数
            $fixWorkflowRecordIsAllowCount = FixWorkflowRecord::where('fix_workflow_process_serial_number', $fixWorkflowProcessSerialNumber)->where('is_allow', 1)->count('id');
            $fixWorkflowProcessIsAllow = ($fixWorkflowProcessCount == $fixWorkflowRecordIsAllowCount);

            # 保存备注信息
            $fixWorkflowProcess = FixWorkflowProcess::where('serial_number', $fixWorkflowProcessSerialNumber)->firstOrFail();
            $fixWorkflowProcess->fill([
                'fix_workflow_serial_number'=>$request->get('fix_workflow_serial_number',''),
                'note'=>$request->get('note',''),
                'processed_at'=>$request->get('processed_at',''),
                'processor_id'=>$request->get('processor_id',''),
                'type'=>$request->get('type',''),
                'is_allow' => $fixWorkflowProcessIsAllow,
            ])
                ->saveOrFail();

            $fixWorkflow = FixWorkflow::with(["EntireInstance"])->where("serial_number", $fixWorkflowProcess->fix_workflow_serial_number)->firstOrFail();
            # 检查是否有部件
            $hasPartModel = PivotEntireModelAndPartModel::where('entire_model_unique_code', $fixWorkflow->EntireInstance->entire_model_unique_code)->count('part_model_unique_code') > 0;# 获取最后一次检测单

            # 获取最后一次检测数据
            $lastFixWorkflowProcessEntire = FixWorkflowProcess::with(['FixWorkflow'])
                ->orderByDesc('id')
                ->where('type', 'ENTIRE')
                ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                ->where("is_allow", true)
                ->first(['stage']);

            $lastFixWorkflowProcessPart = FixWorkflowProcess::with(['FixWorkflow'])
                ->orderByDesc('id')
                ->where('type', 'PART')
                ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                ->where("is_allow", true)
                ->first(['stage']);

            if ($hasPartModel) {
                # 如果有部件（如果是修后检则改变为待检）
                if ($lastFixWorkflowProcessEntire != null && $lastFixWorkflowProcessPart != null) {
                    if ($lastFixWorkflowProcessEntire->flipStage($lastFixWorkflowProcessEntire->stage) == 'FIX_AFTER'
                        && $lastFixWorkflowProcessPart->flipStage($lastFixWorkflowProcessPart->stage) == 'FIX_AFTER') {
                        $fixWorkflow->fill(['stage' => 'WAIT_CHECK'])->saveOrFail();
                    }

                    # 如果是待检则改变为检修完成
                    if ($lastFixWorkflowProcessEntire->flipStage($lastFixWorkflowProcessEntire->stage) == 'WAIT_CHECK'
                        && $lastFixWorkflowProcessPart->flipStage($lastFixWorkflowProcessPart->stage) == 'WAIT_CHECK') {
                        $fixWorkflow->fill(['stage' => 'FIXED'])->saveOrFail();
                    }
                }
            } else {
                if ($lastFixWorkflowProcessEntire != null) {
                    # 没有部件（如果是修后检则改变为待检）
                    if ($lastFixWorkflowProcessEntire->flipStage($lastFixWorkflowProcessEntire->stage) == 'FIX_AFTER') $fixWorkflow->fill(['stage' => 'WAIT_CHECK'])->saveOrFail();

                    # 如果是待检则改变为检修完成
                    if ($lastFixWorkflowProcessEntire->flipStage($lastFixWorkflowProcessEntire->stage) == 'FIX_AFTER') $fixWorkflow->fill(['stage' => 'WAIT_CHECK'])->saveOrFail();
                }
            }
            return response()->make('编辑成功');
        } catch (ModelNotFoundException $e) {
            return response()->make('数据不存在', 404);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $line = $e->getLine();
            $file = $e->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return response::make(get_class($e) . "\r\n{$msg}\r\n{$line}\r\n{$file}", 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $fixWorkflowProcessSerialNumber
     * @return \Illuminate\Http\Response
     */
    public function destroy($fixWorkflowProcessSerialNumber)
    {
        try {
            $fixWorkflowProcess = FixWorkflowProcess::where('serial_number', $fixWorkflowProcessSerialNumber)->firstOrFail();
            $fixWorkflowProcess->delete();
            if (!$fixWorkflowProcess->trashed()) return Response::make('删除失败', 500);

            return Response::make('删除成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make($exceptionMessage, 500);
        }
    }

    /**
     * 部件检测窗口（用来选择需要检测的部件）
     * @return \Illuminate\Http\Response
     */
    public function getPart()
    {
        try {
            $partInstances = PartInstance::where(
                'entire_instance_identity_code',
                request('entireInstanceIdentityCode')
            )
                ->paginate();

            $fixWorkflow = FixWorkflow::where('serial_number', request('fixWorkflowIdentityCode'))->fisrtOrFail();

            return view($this->view())
                ->with('partInstances', $partInstances)
                ->with('fixWorkflow', $fixWorkflow);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->withInput()->with('danger', '意外错误');
        }
    }

    private function view($viewName = null): string
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "Measurement.FixWorkflowProcess.{$viewName}";
    }

    public function postFixWorkflowProcessPart()
    {

    }
}
