<?php

namespace App\Http\Controllers;

use App\Exceptions\ExcelInException;
use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\FixWorkflowFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\NewStationFacade;
use App\Model\Account;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceCount;
use App\Model\EntireInstanceLock;
use App\Model\EntireInstanceLog;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\FixWorkflowRecord;
use App\Model\Maintain;
use App\Model\OverhaulEntireInstance;
use App\Model\PartInstance;
use App\Model\PartModel;
use App\Model\V250TaskEntireInstance;
use App\Model\V250TaskOrder;
use App\Model\WarehouseReport;
use App\Model\WarehouseReportEntireInstance;
use App\Model\WorkArea;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use Throwable;

class V250TaskOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return mixed
     */
    final public function index()
    {
        $v250TaskOrders = V250TaskOrder::with([
            'SceneWorkshop',
            'MaintainStation',
            'V250TaskEntireInstances',
            'V250TaskEntireInstances.Fixer',
            'V250TaskEntireInstances.Checker',
            'V250TaskEntireInstances.SpotChecker',
            'Principal',
            'WorkAreaByUniqueCode'
        ])
            ->orderByDesc('id')
            ->where('work_area_unique_code', session('account.work_area_unique_code'));

        $functions = [
            // 新站任务
            'NEW_STATION' => function () use ($v250TaskOrders): View {
                $v250TaskOrders = $v250TaskOrders
                    ->where('type', 'NEW_STATION')
                    ->paginate(env('PAGE_SIZE', 15));
                return view('V250TaskOrder.index', [
                    'taskOrders' => $v250TaskOrders,
                ]);
            },

            // 状态修
            'UNCYCLE_FIX' => function () use ($v250TaskOrders): View {
                $v250TaskOrders = $v250TaskOrders
                    ->where('type', 'UNCYCLE_FIX')
                    ->paginate(env('PAGE_SIZE', 15));
                return view('V250TaskOrder.index__uncycle_fix', [
                    'taskOrders' => $v250TaskOrders,
                ]);
            }
        ];

        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return back()->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function create()
    {
        $functions = [
            // 新站任务
            'NEW_STATION' => function (): View {
                $stations = Maintain::with([])->where('type', 'STATION')->where('parent_unique_code', 'like', env('ORGANIZATION_CODE') . '%')->get();
                $workAreas = WorkArea::with([])->get();
                return view('V250TaskOrder.create', [
                    'stations' => $stations,
                    'workAreas' => $workAreas,
                ]);
            },

            // 状态修
            'UNCYCLE_FIX' => function (): View {
                $stations = Maintain::with([])->where('type', 'STATION')->where('parent_unique_code', 'like', env('ORGANIZATION_CODE') . '%')->get();
                $workAreas = WorkArea::with([])->get();
                return view('V250TaskOrder.create__uncycle_fix',[

                ]);
            },
        ];

        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return redirect('/v250TaskOrder?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return redirect('/v250TaskOrder?type=' . request('type') . '&page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return mixed
     */
    final public function store(Request $request)
    {
        $functions = [
            // 新站任务
            'NEW_STATION' => function () use ($request) {
                // 验证是否重复
                $v250TaskOrderRepeat = V250TaskOrder::with([])
                    ->where('maintain_station_unique_code', $request->get('maintain_station_unique_code'))
                    ->where('work_area_unique_code', $request->get('work_area_unique_code'))
                    ->where('type', $request->get('type'))
                    ->first();
                if ($v250TaskOrderRepeat) return JsonResponseFacade::errorForbidden('任务重复');

                // 验证是否存在车站
                if (!$request->get('maintain_station_unique_code')) return JsonResponseFacade::errorEmpty('请选择车站');
                $maintain = Maintain::with(['Parent'])->where('unique_code', $request->get('maintain_station_unique_code'))->first();
                if (!$maintain) return JsonResponseFacade::errorEmpty('车站不存在');
                if (!$maintain->Parent) return JsonResponseFacade::errorEmpty('该车站没有找到对应的现场车间');

                // 验证是否有截止日期
                if (!$request->get('expiring_at')) return JsonResponseFacade::errorEmpty('截止日期不能为空');
                try {
                    $expiringAt = Carbon::parse($request->get('expiring_at'))->format('Y-m-d');
                } catch (\Throwable $e) {
                    return response()->json(['msg' => '截止日期格式错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
                }

                // 验证是否填写工区
                if (!$request->get('work_area_unique_code')) return JsonResponseFacade::errorEmpty('请选择工区');

                // 创建任务
                $v250TaskOrder = V250TaskOrder::with([])->create([
                    'scene_workshop_unique_code' => $maintain->Parent->unique_code,
                    'maintain_station_unique_code' => $maintain->unique_code,
                    'serial_number' => $newV250TaskOrderSN = V250TaskOrder::getNewSN(strtoupper($request->get('type'))),
                    'expiring_at' => $expiringAt,
                    'principal_id' => session('account.id'),
                    'work_area_unique_code' => $request->get('work_area_unique_code'),
                    'status' => 'PROCESSING',
                    'type' => strtoupper($request->get('type')),
                ]);

                return JsonResponseFacade::created(['task_order' => $v250TaskOrder]);
            },
        ];

        try {
            $func = strtoupper($request->get('type'));
            if (!array_key_exists($func, $functions)) return JsonResponseFacade::errorForbidden('任务类型参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('数据不存在');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Display the specified resource.
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     * @param $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function edit($sn)
    {
        $functions = [
            'NEW_STATION' => function () use ($sn): View {
                $v250_task_order = V250TaskOrder::with([
                    'SceneWorkshop',
                    'MaintainStation',
                    'Principal',
                    'WorkAreaByUniqueCode'
                ])
                    ->where('serial_number', $sn)
                    ->firstOrFail();

                $v250_task_entire_instances = V250TaskEntireInstance::with([
                    'EntireInstance',
                    'EntireInstance.SubModel',
                    'EntireInstance.PartModel',
                    'Fixer',
                    'Checker',
                    'SpotChecker',
                ])
                    ->where('v250_task_order_sn', $sn)
                    ->paginate(200);
                // dd($v250_task_entire_instances);

                return view('V250TaskOrder.edit', [
                    'taskOrder' => $v250_task_order,
                    'sn' => $sn,
                    'taskEntireInstances' => $v250_task_entire_instances,
                ]);
            },
        ];

        try {
            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return redirect('/v250TaskOrder?page=' . request('page', 1))->with('danger', '页面参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {

            return redirect('/v250TaskOrder?page=' . request('page', 1))->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/v250TaskOrder?page=' . request('page', 1))->with('danger', '意外错误');
        }
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
     * @param string $sn
     * @return \Illuminate\Http\JsonResponse
     */
    final public function destroy(string $sn)
    {
        try {
            $v250_task_order = V250TaskOrder::with(['V250TaskEntireInstances'])->where('serial_number', $sn)->firstOrFail();

            if ($v250_task_order->V250TaskEntireInstances->isNotEmpty()) {
                EntireInstanceLock::freeLocks(
                    $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray(),
                    ['NEW_STATION'],
                    function () use ($v250_task_order) {
                        $v250_task_entire_instances = V250TaskEntireInstance::with([])->where('v250_task_order_sn', $v250_task_order->serial_number)->where('is_utilize_used', false)->get();  // 新设备
                        $utilize_used_v250_task_entire_instances = V250TaskOrder::with([])->where('v250_task_order_sn', $v250_task_order->serial_number)->where('is_utilize_used', true)->get();  // 利旧设备

                        V250TaskEntireInstance::with([])->whereIn('id', $v250_task_entire_instances->pluck('id')->toArray())->delete();  // 显出新设备
                        EntireInstanceLog::with([])->whereIn('entire_instance_identity_code', $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  // 删除设备日志（新设备）
                        EntireInstance::with([])->whereIn('identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  // 删除设备（新设备）
                        EntireInstance::with([])->whereIn('identity_code', $utilize_used_v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->update(['v250_task_order_sn' => '']);  // 去掉任务编号（利旧设备）
                        PartInstance::with([])->whereIn('entire_instance_identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  // 删除设备部件（新设备）

                        OverhaulEntireInstance::with([])->whereIn('entire_instance_identity_code', $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->forceDelete();  // 删除 检修分配
                        OverhaulEntireInstance::with([])->whereIn('entire_instance_identity_code', $utilize_used_v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray())->update(['v250_task_order_sn' => '']);  // 检修分配 去掉任务编号（利旧设备）

                        DB::table('v250_workshop_in_entire_instances')->where('v250_task_orders_serial_number', $v250_task_order->serial_number)->delete();
                        DB::table('v250_workshop_out_entire_instances')->where('v250_task_orders_serial_number', $v250_task_order->serial_number)->delete();
                        DB::table('v250_workshop_stay_out')->where('v250_task_orders_serial_number', $v250_task_order->serial_number)->delete();
                        $fix_workflows = FixWorkflow::with([])->whereIn('entire_instance_identity_code', $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray())->get();
                        $fix_workflow_processes = FixWorkflowProcess::with([])->whereIn('fix_workflow_serial_number', $fix_workflows->pluck('serial_number')->toArray())->get();
                        FixWorkflowRecord::with([])->whereIn('fix_workflow_process_serial_number', $fix_workflow_processes->pluck('serial_number')->toArray())->forceDelete();
                        FixWorkflowProcess::with([])->where('id', $fix_workflow_processes->pluck('id')->toArray())->forceDelete();
                        Fixworkflow::with([])->where('id', $fix_workflows->pluck('id')->toArray())->forceDelete();
                    }
                );
            }

            $v250_task_order->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('新站任务不存在');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 下载设备赋码Excel模板
     */
    final public function getDownloadUploadCreateDeviceExcelTemplate()
    {
        try {
            $functions = [
                'NEW_STATION' => function () {
                    return NewStationFacade::downloadUploadCreateDeviceExcelTemplate(request());
                },
            ];

            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return back()->with('danger', '任务类型错误');
            return $functions[$func]();
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 上传设备数据补充excel模板
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function getDownloadUploadEditDeviceExcelTemplate()
    {
        try {
            $functions = [
                'NEW_STATION' => function () {
                    return NewStationFacade::downloadUploadEditDeviceExcelTemplate(request());
                }
            ];

            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return back()->with('danger', '任务类型错误');
            return $functions[$func]();
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 下载设备赋码Excel错误报告
     * @param string $sn
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function getDownloadCreateDeviceErrorExcel(string $sn)
    {
        try {
            $v250_task_order = V250TaskOrder::with([])->where('serial_number', $sn)->firstOrFail();

            $filename = storage_path(request('path'));
            if (!file_exists($filename)) return back()->with('danger', '文件不存在');

            $v250_task_order->fill(['is_upload_create_device_excel_error' => false])->saveOrFail();
            return response()->download($filename, '上传设备赋码错误报告.xls');
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 下载上传设备数据补充Excel错误报告
     * @param string $sn
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function getDownloadEditDeviceErrorExcel(string $sn)
    {
        try {
            $v250_task_order = V250TaskOrder::with([])->where('serial_number', $sn)->firstOrFail();

            $filename = storage_path(request('path'));
            if (!file_exists($filename)) return back()->with('danger', '文件不存在');

            $v250_task_order->fill(['is_upload_edit_device_excel_error' => false])->saveOrFail();
            return response()->download($filename, '上传设备数据补充错误报告.xls');
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 上传设备赋码Excel页面
     * @param string $sn
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|View
     */
    final public function getUploadCreateDevice(string $sn)
    {
        try {
            $functions = [
                'NEW_STATION' => function () use ($sn): View {
                    // 获取当前任务单
                    $v250_task_order = V250TaskOrder::with(['WorkAreaByUniqueCode'])->where('serial_number', $sn)->firstOrFail();
                    // 获取当前任务单所在工区人员
                    $accounts = Account::with([])
                        ->where('workshop_code', env('ORGANIZATION_CODE'))
                        ->where('work_area_unique_code', $v250_task_order->work_area_unique_code)
                        ->get();

                    return view('V250TaskOrder.uploadCreateDevice', [
                        'taskOrder' => $v250_task_order,
                        'accounts' => $accounts,
                    ]);
                },
            ];

            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) return redirect("v250TaskOrder/{$sn}/edit?page=" . request('page', 1))->with('danger', '任务类型参数错误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return redirect("v250TaskOrder/{$sn}/edit?page=" . request('page', 1))->with('danger', '任务单不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect("v250TaskOrder/{$sn}/edit?page=" . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * 上传设备赋码Excel
     * @param Request $request
     * @param string $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postUploadCreateDevice(Request $request, string $sn)
    {
        try {
            // 获取当前任务单
            $v250_task_order = V250TaskOrder::with([])->where('serial_number', $sn)->first();
            if (!$v250_task_order) throw new ExcelInException("没有找到任务：{$sn}");

            // 获取当前任务单所在工区人员
            $accounts = Account::with([])
                ->where('workshop_code', env('organization_code'))
                ->where('work_area_unique_code', session('account.work_area_by_unique_code.unique_code'))
                ->get();

            if (!$request->hasFile('file')) return back()->with('danger', '上传文件失败');
            if (!in_array($request->file('file')->getClientMimeType(), [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ])) return back()->withInput()->with('danger', '只能上传excel，当前格式：' . $request->file('file')->getClientMimeType());

            $functions = [
                'NEW_STATION' => function () use ($request, $sn, $v250_task_order, $accounts) {
                    return NewStationFacade::uploadCreateDevice($request, $sn, $v250_task_order);
                },
            ];

            $func = strtoupper($request->get('type'));
            if (!array_key_exists($func, $functions)) return back()->with('danger', '任务类型错误');
            return $functions[$func]();
        } catch (ExcelInException $e) {
            return back()->with('danger', $e->getMessage());
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 上传设备赋码Excel结果页面，等待打印标签
     * @param string $sn
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|View
     */
    final public function getUploadCreateDeviceReport(string $sn)
    {
        $return_url = "/v250TaskOrder/{$sn}?" . http_build_query([
                'page' => request('page', 1),
                'type' => request('type'),
            ]);

        try {
            if (!request('warehouseReportSN')) return redirect($return_url)->with('danger', '缺少入所单参数');

            // 获取本次入所单的设备
            $identity_codes = WarehouseReportEntireInstance::with([])->where('warehouse_report_serial_number', request('warehouseReportSN'))->pluck('entire_instance_identity_code');
            $v250_task_order = V250TaskOrder::with([])->where('serial_number', $sn)->firstOrFail();
            $v250_task_entire_instances = V250TaskEntireInstance::with([
                'EntireInstance',
                'Fixer',
                'Checker',
                'SpotChecker'
            ])
                ->where('v250_task_order_sn', $sn)
                ->whereIn('entire_instance_identity_code', $identity_codes)
                ->paginate(200);

            // 检查是否有错误（设备赋码）
            $has_create_device_error = false;
            $create_device_error_filename = null;
            if ($v250_task_order->is_upload_create_device_excel_error) {
                $create_device_error_dir = 'v250TaskOrder/upload/' . strtoupper(request('type')) . '/errorExcels/createDevice';
                $create_device_error_filename = "{$create_device_error_dir}/{$sn}.xls";
                if (!is_dir(storage_path($create_device_error_dir))) FileSystem::init(storage_path($create_device_error_dir))->makeDir();
                if (file_exists(storage_path($create_device_error_filename))) $has_create_device_error = true;
            }

            return view('V250TaskOrder.uploadCreateDeviceReport', [
                'taskOrder' => $v250_task_order,
                'taskEntireInstances' => $v250_task_entire_instances,
                'hasCreateDeviceError' => $has_create_device_error,
                'createDeviceErrorFilename' => $create_device_error_filename,
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect($return_url)->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect($return_url)->with('danger', '意外错误');
        }
    }

    /**
     * 上传上道位置Excel 页面
     * @param string $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function getUploadEditDevice(string $sn)
    {
        try {
            $functions = [
                'NEW_STATION' => function () use ($sn): View {
                    // 获取当前任务单
                    $v250_task_order = V250TaskOrder::with([])->where('serial_number', $sn)->firstOrFail();
                    // 获取当前任务单所在工区人员
                    $accounts = Account::with([])
                        ->where('workshop_code', env('ORGANIZATION_CODE'))
                        ->where('work_area_unique_code', $v250_task_order->work_area_unique_code)
                        ->get();

                    return view('V250TaskOrder.uploadEditDevice', [
                        'taskOrder' => $v250_task_order,
                        'accounts' => $accounts,
                    ]);
                },
            ];

            $func = strtoupper(request('type'));
            if (!array_key_exists($func, $functions)) dd('no');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return redirect("v250TaskOrder/{$sn}/edit?page=" . request('page', 1))->with('danger', '任务单不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect("v250TaskOrder/{$sn}/edit?page=" . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * 上传上道位置Excel
     * 位置信息必填，其他信息修改
     * @param Request $request
     * @param string $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postUploadEditDevice(Request $request, string $sn)
    {
        try {
            // 获取当前任务单
            $v250_task_order = V250TaskOrder::with([])->where('serial_number', $sn)->firstOrFail();
            // 获取当前任务单所在工区人员
            $accounts = Account::with([])->where('work_area_unique_code', $v250_task_order->work_area_unique_code)->get();

            if (!$request->hasFile('file')) return back()->with('danger', '上传文件失败');
            if (!in_array($request->file('file')->getClientMimeType(), [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ])) return back()->withInput()->with('danger', '只能上传excel，当前格式：' . $request->file('file')->getClientMimeType());

            $functions = [
                'NEW_STATION' => function () use ($request, $sn, $v250_task_order, $accounts) {
                    return NewStationFacade::uploadEditDevice($request, $sn, $v250_task_order);
                },
            ];

            $func = strtoupper($request->get('type'));
            if (!array_key_exists($func, $functions)) return back()->with('danger', '页面参数有误');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '没有找到任务');
        } catch (ExcelInException $e) {
            return back()->with('danger', $e->getMessage());
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 生成待出所单
     * @param Request $request
     * @param string $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postPrepareWarehouseOut(Request $request, string $sn)
    {
        try {
            $functions = [
                'NEW_STATION' => function () use ($request, $sn) {
                    $v250_task_order = V250TaskOrder::with([])->where('serial_number');

                    $undone_identity_codes = WarehouseReportEntireInstance::with(['WarehouseReport'])
                        ->whereHas('WarehouseReport', function ($WarehouseReport) {
                            $WarehouseReport->where('status', 'UNDONE');
                        })
                        ->pluck('entire_instance_identity_code')
                        ->toArray();
                    $new_identity_codes = array_diff($request->get('identityCodes'), $undone_identity_codes);
                    if (empty($new_identity_codes)) return JsonResponseFacade::errorEmpty('所有设备均已分配待出所单');

                    $scene_workshop = Maintain::with([])->where('unique_code', $v250_task_order->scene_workshop_unique_code)->first();
                    if (!$scene_workshop) return JsonResponseFacade::errorEmpty('现场车间不存在');
                    $station = Maintain::with([])->where('unique_code', $v250_task_order->maintain_station_unique_code)->first();
                    if (!$station) return JsonResponseFacade::errorEmpty('车站不存在');

                    // 创建待出所单
                    $warehouse_report = WarehouseReport::with([])->create([
                        'processor_id' => 0,
                        'processed_at' => null,
                        'type' => strtoupper($request->get('type')),
                        'direction' => 'OUT',
                        'serial_number' => $new_warehouse_report_sn = CodeFacade::makeSerialNumber(strtoupper($request->get('type') . '_OUT')),
                        'scene_workshop_name' => $scene_workshop->name,
                        'station_name' => $station->name,
                        'work_area_id' => 0,
                        'scene_workshop_unique_code' => $scene_workshop->unique_code,
                        'maintain_station_unique_code' => $station->unique_code,
                        'status' => 'UNDONE',
                        'v250_task_order_sn' => $v250_task_order->serial_number,
                    ]);
                    return $warehouse_report;
                },
            ];

            $func = strtoupper($request->get('type'));
            if (!array_key_exists($func, $functions)) return JsonResponseFacade::errorForbidden('任务类型不正确');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '没有找到任务');
        } catch (ExcelInException $e) {
            return back()->with('danger', $e->getMessage());
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 交付
     * @param Request $request
     * @param string $sn
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postDelivery(Request $request, string $sn)
    {
        try {
            $functions = [
                'NEW_STATION' => function () use ($request, $sn) {
                    $v250_task_order = V250TaskOrder::with(['V250TaskEntireInstances' => function ($V250TaskEntireInstances) {
                        $V250TaskEntireInstances->where('is_out', false);
                    }])
                        ->where('serial_number', $sn)
                        ->firstOrFail();
                    if ($v250_task_order->V250TaskEntireInstances->isNotEmpty()) return JsonResponseFacade::errorForbidden('任务中所有设备均出所后才能交付任务');

                    $v250_task_order
                        ->fill([
                            'finished_at' => date('Y-m-d H:i:s'),
                            'status' => 'DONE',
                            'delivery_message' => $request->get('delivery_message'),
                        ])
                        ->saveOrFail();

                    $un_out_entire_instance_count = V250TaskEntireInstance::with([])->where('v250_task_order_sn', $sn)->where('is_out', false)->count();
                    if ($un_out_entire_instance_count > 0) return JsonResponseFacade::errorForbidden("当前任务存在未完成出所的设备器材：{$un_out_entire_instance_count}台。不能交付");

                    // 解锁设备
                    $identity_codes = $v250_task_order->V250TaskEntireInstances->pluck('entire_instance_identity_code')->toArray();
                    EntireInstanceLock::freeLocks($identity_codes, ['NEW_STATION'], function () use ($identity_codes) {
                        EntireInstance::with([])->whereIn('identity_code', $identity_codes)->update(['v250_task_order_sn' => '', 'is_overhaul' => 0]);
                    });

                    return JsonResponseFacade::updated('交付成功');
                },
            ];

            $func = strtoupper($request->get('type'));
            if (!array_key_exists($func, $functions)) return JsonResponseFacade::errorForbidden('任务类型不正确');
            return $functions[$func]();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
