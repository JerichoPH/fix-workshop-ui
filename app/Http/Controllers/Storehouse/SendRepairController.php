<?php

namespace App\Http\Controllers\Storehouse;

use App\Facades\EntireInstanceLogFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Model\PartInstance;
use App\Model\SendRepair;
use App\Model\SendRepairInstance;
use App\Model\TmpMaterial;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\FileSystem;
use Jericho\HttpResponseHelper;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SendRepairController extends Controller
{
    private $_current_time = null;
    private $_sign = '';

    public function __construct()
    {
        $this->_current_time = date('Y-m-d H:i:s');
        $this->_sign = 'SEND_REPAIR';
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $originAt = Carbon::now()->startOfMonth()->toDateString();
            $finishAt = Carbon::now()->endOfMonth()->toDateString();
            $updated_at = request('updated_at');
            $use_made_at = request('use_made_at');
            $material_unique_code = request('material_unique_code', '');
            $materialType = '';
            if (!empty($material_unique_code)) {
                $materialType = DB::table('send_repair_instances')->where('material_unique_code', $material_unique_code)->value('material_type');
            }

            $sendRepairs = SendRepair::with(['WithSendRepairInstance', 'WithAccount', 'WithFromMaintain', 'WithToFactory', 'WithToMaintain'])
                ->when(
                    session('account.read_scope') === 1,
                    function ($query) {
                        return $query->where('account_id', session('account.id'));
                    }
                )
                ->when(
                    $use_made_at == 1,
                    function ($query) use ($updated_at) {
                        if (!empty($updated_at)) {
                            $tmp_updated_at = explode('~', $updated_at);
                            $tmp_left = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[0])->startOfDay()->format('Y-m-d H:i:s');
                            $tmp_right = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[1])->endOfDay()->format('Y-m-d H:i:s');
                            return $query->whereBetween('updated_at', [$tmp_left, $tmp_right]);
                        }
                    }
                )
                ->when(
                    !empty($material_unique_code),
                    function ($query) use ($material_unique_code, $materialType) {
                        if ($materialType == 'ENTIRE') {
                            return $query->whereHas('WithSendRepairInstance.WithEntireInstance', function ($q) use ($material_unique_code) {
                                $q->where('material_unique_code', $material_unique_code);
                            });
                        } else {
                            return $query->whereHas('WithSendRepairInstance.WithPartInstance', function ($q) use ($material_unique_code) {
                                $q->where('material_unique_code', $material_unique_code);
                            });
                        }
                    }
                )
                ->orderByDesc('updated_at')
                ->paginate();
            $sendRepairUniqueCodes = array_column($sendRepairs->toArray()['data'], 'unique_code');
            $statistics = [];
            $material_counts = 0;
            if (!empty($sendRepairUniqueCodes)) {
                $entireCounts = DB::table('send_repair_instances as sri')
                    ->selectRaw('count(sri.material_unique_code) as count, sri.send_repair_unique_code, ei.category_name, ei.model_name')
                    ->join(DB::raw('entire_instances ei'), 'sri.material_unique_code', '=', 'ei.identity_code')
                    ->whereIn('sri.send_repair_unique_code', $sendRepairUniqueCodes)
                    ->groupBy(['sri.send_repair_unique_code', 'ei.category_name', 'ei.model_name'])
                    ->get();
                foreach ($entireCounts as $entireCount) {
                    $statistics[$entireCount->send_repair_unique_code][] = [
                        'category_name' => $entireCount->category_name,
                        'model_name' => $entireCount->model_name,
                        'count' => $entireCount->count
                    ];
                    $material_counts += $entireCount->count;
                }
                $partCounts = DB::table('send_repair_instances as sri')
                    ->selectRaw('count(sri.material_unique_code) as count, sri.send_repair_unique_code, pc.name as part_category_name, pi.part_model_name, c.name as category_name')
                    ->join(DB::raw('part_instances pi'), 'sri.material_unique_code', '=', 'pi.identity_code')
                    ->join(DB::raw('part_categories pc'), 'pi.part_category_id', '=', 'pc.id')
                    ->join(DB::raw('categories c'), 'pi.category_unique_code', '=', 'c.unique_code')
                    ->whereIn('sri.send_repair_unique_code', $sendRepairUniqueCodes)
                    ->groupBy(['sri.send_repair_unique_code', 'pc.name', 'pi.part_model_name', 'c.name'])
                    ->get();
                foreach ($partCounts as $partCount) {
                    $statistics[$partCount->send_repair_unique_code][] = [
                        'category_name' => $partCount->category_name . ' ' . $partCount->part_category_name,
                        'model_name' => $partCount->part_model_name,
                        'count' => $partCount->count
                    ];
                    $material_counts += $partCount->count;
                }
            }

            return view('Storehouse.SendRepair.index', [
                'sendRepairs' => $sendRepairs,
                'originAt' => empty($updated_at) ? $originAt : $updated_at[0],
                'finishAt' => empty($updated_at) ? $finishAt : $updated_at[1],
                'statistics' => $statistics,
                'material_counts' => $material_counts,
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 送修-确认送修模态框
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create()
    {
        try {
            $factories = DB::table('factories')->where('deleted_at', null)->pluck('name', 'unique_code')->toArray();
            $workshop = DB::table('maintains')->where('parent_unique_code', env('ORGANIZATION_CODE'))->where('type', 'WORKSHOP')->first();
            return view('Storehouse.SendRepair.create', [
                'factories' => $factories,
                'workshop' => $workshop,
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 确认送修
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    final public function store(Request $request)
    {
        try {
            $tmpMaterials = TmpMaterial::with(['WithEntireInstance'])->where('account_id', session('account.id'))->where('state', $this->_sign)->get();
            if ($tmpMaterials->isEmpty()) return JsonResponseFacade::errorEmpty("请选择送修器材");
            $from_code = $request->get('from_code', '');
            $to_code = $request->get('to_code', '');
            $from = DB::table('maintains')->where('parent_unique_code', env('ORGANIZATION_CODE'))->where('unique_code', $from_code)->select('unique_code', 'name')->first();
            $to = DB::table('factories')->where('unique_code', $to_code)->select('unique_code', 'name')->first();
            if (empty($from)) return JsonResponseFacade::errorEmpty('送修单位不能为空');
            if (empty($to)) return JsonResponseFacade::errorEmpty('接收单位不能为空');

            $sendRepairUniqueCode = DB::transaction(function () use ($tmpMaterials, $from, $to) {
                $sendRepair = new SendRepair();
                $sendRepairUniqueCode = $sendRepair->getUniqueCode();
                $repair_day = request('repair_day', 0);
                $repair_due_at = null;
                if ($repair_day > 0) $repair_due_at = date('Y-m-d H:i:s', $repair_day * 24 * 3600 + time());
                $sendRepair->fill([
                    'unique_code' => $sendRepairUniqueCode,
                    'state' => 'START',
                    'account_id' => request('account_id', session('account.id')),
                    'from_code' => $from->unique_code,
                    'to_code' => $to->unique_code,
                    'to_name' => empty(request('to_name')) ? '' : request('to_name'),
                    'to_phone' => empty(request('to_phone')) ? '' : request('to_phone'),
                    'repair_day' => $repair_day,
                    'repair_due_at' => $repair_due_at,
                ]);
                $sendRepair->saveOrFail();
                $tmpBreakdownLogs = DB::table('tmp_breakdown_logs as tbl')
                    ->leftJoin(DB::raw('breakdown_types bt'), 'tbl.breakdown_type_id', '=', 'bt.id')
                    ->select(['tbl.material_unique_code', 'tbl.breakdown_type_id', 'tbl.account_id', 'bt.name as breakdown_type_name'])
                    ->where('tbl.account_id', session('account.id'))
                    ->get();
                $breakdownTypeWithMaterials = [];
                foreach ($tmpBreakdownLogs as $tmpBreakdownLog) {
                    $breakdownTypeWithMaterials[$tmpBreakdownLog->material_unique_code][] = [
                        'breakdown_type_id' => $tmpBreakdownLog->breakdown_type_id,
                        'breakdown_type_name' => $tmpBreakdownLog->breakdown_type_name
                    ];
                }
                foreach ($tmpMaterials as $tmpMaterial) {
                    EntireInstanceLock::freeLock(
                        $tmpMaterial->material_unique_code,
                        [$this->_sign],
                        function () use ($tmpMaterial, $sendRepairUniqueCode, $from, $to, $breakdownTypeWithMaterials) {
                            $repair_material_id = DB::table('send_repair_instances')->insertGetId([
                                'created_at' => $this->_current_time,
                                'updated_at' => $this->_current_time,
                                'material_unique_code' => $tmpMaterial->material_unique_code,
                                'send_repair_unique_code' => $sendRepairUniqueCode,
                                'repair_remark' => $tmpMaterial->repair_remark,
                                'repair_desc' => $tmpMaterial->repair_desc,
                                'material_type' => $tmpMaterial->material_type['value']
                            ]);
                            $description = "送修单位：{$from->name}；接收单位：{$to->name}；";
                            if (!empty($tmpSendRepairMaterial->repair_remark)) $description .= "送修备注：{$tmpSendRepairMaterial->repair_remark}；";
                            if (!empty($tmpSendRepairMaterial->repair_desc)) $description .= "故障描述：{$tmpSendRepairMaterial->repair_desc}；";
                            EntireInstanceLogFacade::makeOne(
                                request('account_id', session('account.id')),
                                '',
                                '送修',
                                $tmpMaterial->material_unique_code,
                                0,
                                "/storehouse/sendRepair/{$sendRepairUniqueCode}",
                                $description,
                                $tmpMaterial->material_type['value']
                            );
                            if ($tmpMaterial->material_type['value'] == 'ENTIRE') {
                                DB::table('entire_instances')->where('identity_code', $tmpMaterial->material_unique_code)->update([
                                    'status' => $this->_sign,
                                    'updated_at' => $this->_current_time,
                                    'last_repair_material_id' => $repair_material_id,
                                    'warehousein_at' => null,
                                    'location_unique_code' => null,
                                ]);
                            }
                            if ($tmpMaterial->material_type['value'] == 'PART') {
                                DB::table('part_instances')->where('identity_code', $tmpMaterial->material_unique_code)->update([
                                    'status' => $this->_sign,
                                    'updated_at' => $this->_current_time,
                                    'last_repair_material_id' => $repair_material_id
                                ]);
                            }

                            # 故障
                            if (array_key_exists($tmpMaterial->material_unique_code, $breakdownTypeWithMaterials)) {
                                $explain = $tmpMaterial->repair_desc ?? '';
                                $breakdownLogId = '';
                                if ($tmpMaterial->material_type['value'] == 'ENTIRE') {
                                    $breakdownLogId = DB::table('breakdown_logs')->insertGetId([
                                        'created_at' => $this->_current_time,
                                        'updated_at' => $this->_current_time,
                                        'entire_instance_identity_code' => $tmpMaterial->material_unique_code,
                                        'explain' => $explain,
                                        'type' => 'WAREHOUSE_IN',
                                        'scene_workshop_name' => $tmpMaterial->WithEntireInstance->maintain_workshop_name ?? '',
                                        'maintain_station_name' => $tmpMaterial->WithEntireInstance->maintain_station_name ?? '',
                                        'maintain_location_code' => $tmpMaterial->WithEntireInstance->maintain_location_code ?? '',
                                        'crossroad_number' => $tmpMaterial->WithEntireInstance->crossroad_number ?? '',
                                        'traction' => $tmpMaterial->WithEntireInstance->traction ?? '',
                                        'line_name' => $tmpMaterial->WithEntireInstance->line_name ?? '',
                                        'open_direction' => $tmpMaterial->WithEntireInstance->open_direction ?? '',
                                        'said_rod' => $tmpMaterial->WithEntireInstance->said_rod ?? '',
                                        'crossroad_type' => $tmpMaterial->WithEntireInstance->crossroad_type ?? '',
                                        'point_switch_group_type' => $tmpMaterial->WithEntireInstance->point_switch_group_type ?? '',
                                        'extrusion_protect' => $tmpMaterial->WithEntireInstance->extrusion_protect ?? '',
                                        'submitter_name' => session('account.account', ''),
                                        'submitted_at' => $this->_current_time,
                                        'material_type' => $tmpMaterial->material_type['value']
                                    ]);
                                }
                                if ($tmpMaterial->material_type['value'] == 'PART') {
                                    $breakdownLogId = DB::table('breakdown_logs')->insertGetId([
                                        'created_at' => $this->_current_time,
                                        'updated_at' => $this->_current_time,
                                        'entire_instance_identity_code' => $tmpMaterial->material_unique_code,
                                        'explain' => $explain,
                                        'type' => 'WAREHOUSE_IN',
                                        'scene_workshop_name' => '',
                                        'maintain_station_name' => '',
                                        'maintain_location_code' => '',
                                        'crossroad_number' => '',
                                        'traction' => '',
                                        'line_name' => '',
                                        'open_direction' => '',
                                        'said_rod' => '',
                                        'crossroad_type' => '',
                                        'point_switch_group_type' => '',
                                        'extrusion_protect' => '',
                                        'submitter_name' => session('account.account', ''),
                                        'submitted_at' => $this->_current_time,
                                        'material_type' => $tmpMaterial->material_type['value']
                                    ]);
                                }
                                if (!empty($breakdownLogId)) {
                                    $tmpBreakdownNames = [];
                                    foreach ($breakdownTypeWithMaterials[$tmpMaterial->material_unique_code] as $breakdownType) {
                                        DB::table('pivot_breakdown_log_and_breakdown_types')->insert([
                                            'created_at' => $this->_current_time,
                                            'updated_at' => $this->_current_time,
                                            'breakdown_log_id' => $breakdownLogId,
                                            'breakdown_type_id' => $breakdownType['breakdown_type_id']
                                        ]);
                                        $tmpBreakdownNames[] = $breakdownType['breakdown_type_name'];
                                    }
                                    # 日志
                                    $description = "送修故障；";
                                    if (!empty($tmpBreakdownNames)) $description .= '故障类型：' . implode('、', $tmpBreakdownNames) . '；';
                                    if (!empty($explain)) $description .= "故障描述：{$explain}；";
                                    EntireInstanceLogFacade::makeOne(
                                        session('account.id'),
                                        '',
                                        '送修故障',
                                        $tmpMaterial->material_unique_code,
                                        0,
                                        "",
                                        $description,
                                        $tmpMaterial->material_type['value']
                                    );
                                }
                            }

                            DB::table('tmp_materials')->where('id', $tmpMaterial->id)->delete();
                            DB::table('tmp_breakdown_logs')->where('material_unique_code', $tmpMaterial->material_unique_code)->where('account_id', session('account.id'))->where('material_type', $tmpMaterial->material_type['value'])->delete();
                        }
                    );
                }

                return $sendRepairUniqueCode;
            });
            return JsonResponseFacade::created(['unique_code' => $sendRepairUniqueCode], '送修成功');
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty($exception);
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * Display the specified resource.
     * @param $uniqueCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show($uniqueCode)
    {
        try {
            $sendRepair = SendRepair::with([
                'WithAccount',
                'WithSendRepairInstance.WithEntireInstance' => function ($entireInstance) {
                    return $entireInstance->withTrashed();
                },
                'WithSendRepairInstance.WithPartInstance' => function ($partInstance) {
                    return $partInstance->withTrashed();
                },
                'WithFromMaintain',
                'WithToFactory',
                'WithToMaintain'
            ])
                ->where('unique_code', $uniqueCode)
                ->firstOrFail();

            $qr_code_content = json_encode(["unique_code" => $uniqueCode, "type" => "SEND_REPAIR",]);
            $qr_code = QrCode::format('png')->size(140)->margin(0)->generate($qr_code_content);

            return view('Storehouse.SendRepair.show', [
                'sendRepair' => $sendRepair,
                "qr_code" => $qr_code,
                "qr_code_content" => $qr_code_content,
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage() . $exception->getLine() . $exception->getFile());
        }
    }

    /**
     * 设备验收页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function sendRepairWithCheck()
    {
        try {
            $sendRepairs = SendRepair::with(['WithAccount', 'WithSendRepairInstance'])
                ->where('state', '<>', 'END')
                ->where('state', '<>', 'CANCEL')
                ->when(
                    session('account.read_scope') === 1,
                    function ($query) {
                        return $query->where('account_id', session('account.id'));
                    }
                )
                ->orderByDesc('updated_at')
                ->get();
            $statistics = [];
            if ($sendRepairs->isNotEmpty()) {
                foreach ($sendRepairs as $sendRepair) {
                    if (!array_key_exists($sendRepair->unique_code, $statistics)) $statistics[$sendRepair->unique_code] = ['all' => 0, 'check' => 0];
                    foreach ($sendRepair->WithSendRepairInstance as $sendRepairInstance) {
                        if ($sendRepairInstance->is_check == 1) {
                            $statistics[$sendRepair->unique_code]['check'] += 1;
                        }
                        $statistics[$sendRepair->unique_code]['all'] += 1;
                    }
                }
            }

            return view('Storehouse.SendRepair.sendRepairWithCheck', [
                'sendRepairs' => $sendRepairs,
                'statistics' => $statistics
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage() . $exception->getLine());
        }
    }

    /**
     * 验收送修设备
     * @param string $materialUniqueCode
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function sendRepairWithDoCheck(string $materialUniqueCode)
    {
        try {
            $sendRepairInstance = SendRepairInstance::with(['WithSendRepair'])
                ->when(
                    session('account.read_scope') === 1,
                    function ($query) {
                        return $query->whereHas('WithSendRepair', function ($q) {
                            $q->where('account_id', session('account.id'));
                        });
                    }

                )->whereHas('WithSendRepair', function ($query) {
                    return $query->whereIn('state', ['START', 'HANDLEING']);
                })
                ->where('material_unique_code', $materialUniqueCode)->firstOrFail();
            if ($sendRepairInstance->is_check == 1) return response()->json(['message' => '重复扫码'], 422);
            $sendRepairInstance->fill([
                'is_check' => 1
            ]);
            $sendRepairInstance->saveOrFail();
            $sendRepair = SendRepair::with(['WithSendRepairInstance'])->where('unique_code', $sendRepairInstance->send_repair_unique_code)->firstOrFail();
            $allCount = 0;
            $checkCount = 0;
            if (!empty($sendRepair->WithSendRepairInstance)) {
                foreach ($sendRepair->WithSendRepairInstance as $instance) {
                    $allCount++;
                    if ($instance->is_check == 1) $checkCount++;
                }
            }
            if ($checkCount == $allCount && $allCount != 0) {
                $sendRepair->fill([
                    'state' => 'END'
                ]);
            } else {
                $sendRepair->fill([
                    'state' => 'HANDLEING'
                ]);
            }
            $sendRepair->saveOrFail();
            if ($sendRepairInstance->material_type['value'] == 'PART') {
                DB::table('part_instances')->where('identity_code', $materialUniqueCode)->update([
                    'status' => 'FIXING',
                    'updated_at' => $this->_current_time,
                ]);
            }
            if ($sendRepairInstance->material_type['value'] == 'ENTIRE') {
                DB::table('entire_instances')->where('identity_code', $materialUniqueCode)->update([
                    'status' => 'FIXING',
                    'updated_at' => $this->_current_time,
                ]);
            }
            # 日志
            EntireInstanceLogFacade::makeOne(
                session('account.id'),
                '',
                '验收',
                $materialUniqueCode,
                0,
                "",
                '送修验收',
                $sendRepairInstance->material_type['value']
            );
            EntireInstanceLogFacade::makeOne(
                session('account.id'),
                '',
                '入所',
                $materialUniqueCode,
                0,
                "",
                '送修验收入所',
                $sendRepairInstance->material_type['value']
            );

            return JsonResponseFacade::ok('验收成功');
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty('该器材不是送修验收器材 ');
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 送修单设备列表
     * @param string $sendRepairUniqueCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function instanceWithSendRepair(string $sendRepairUniqueCode)
    {
        try {
            $sendRepairInstances = SendRepairInstance::with([
                'WithEntireInstance' => function ($entireInstance) {
                    return $entireInstance->withTrashed();
                },
                'WithPartInstance' => function ($partInstance) {
                    return $partInstance->withTrashed();
                },
            ])->where('send_repair_unique_code', $sendRepairUniqueCode)->orderByDesc('updated_at')->get();
            return view('Storehouse.SendRepair.instanceWithSendRepair', [
                'sendRepairInstances' => $sendRepairInstances,
                'sendRepairUniqueCode' => $sendRepairUniqueCode,
                'faultStatus' => SendRepairInstance::$FAULT_STATUS,
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage() . $exception->getLine());
        }
    }

    /**
     * 更改送修设备故障故障状态
     * @return \Illuminate\Http\JsonResponse
     */
    final public function updateSendRepairWithInstanceFaultStatus()
    {
        try {
            $send_repair_unique_code = request('send_repair_unique_code', '');
            $material_unique_code = request('material_unique_code', '');
            $fault_status = request('fault_status', 0);
            if (empty($send_repair_unique_code) || empty($material_unique_code)) return response()->json(['message' => '参数不足'], 422);
            DB::table('send_repair_instances')->where('send_repair_unique_code', $send_repair_unique_code)->where('material_unique_code', $material_unique_code)->update([
                'fault_status' => $fault_status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return JsonResponseFacade::updated();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 上传检测窗口文件
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getUploadRepair()
    {
        try {
            return view('Storehouse.SendRepair.uploadRepairModal', [
                'send_repair_unique_code' => request('send_repair_unique_code'),
                'material_unique_code' => request('material_unique_code')
            ]);
        } catch (\Exception $e) {
            return back()->with('danger', $e->getMessage() . $e->getLine());
        }
    }

    /**
     * 上传送修报告
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Throwable
     */
    final public function postUploadRepair()
    {
        try {
            $send_repair_unique_code = request('send_repair_unique_code', '');
            $material_unique_code = request('material_unique_code', '');
            if (empty($send_repair_unique_code) || empty($material_unique_code)) return back()->with('danger', '参数不足');
            $sendRepairInstance = SendRepairInstance::with([])->where('send_repair_unique_code', $send_repair_unique_code)->where('material_unique_code', $material_unique_code)->firstOrFail();
            $file = request()->file('file', null);
            if (empty($file) && is_null($file)) return back()->with('danger', '上传文件失败');
            $repair_report_name = $file->getClientOriginalName();
            $filename = date('YmdHis') . session('account.id') . strval(rand(1000, 9999)) . '.' . $file->getClientOriginalExtension();
            $path = storage_path('upload/sendRepair/report');
            if (!is_dir($path)) FileSystem::init($path)->makeDir($path);
            $file->move($path, $filename);
            $sendRepairInstance->fill([
                'repair_report_url' => 'upload/sendRepair/report/' . $filename,
                'repair_report_name' => $repair_report_name
            ]);
            $sendRepairInstance->saveOrFail();

            return back()->with('success', '上传成功');
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 下载送修文件
     * @param int $id
     * @param string $file_type
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function downloadSendRepairFile(int $id, string $file_type)
    {
        try {
            switch ($file_type) {
                case 'report':
                    $sendRepairInstance = SendRepairInstance::with([])->where('id', $id)->firstOrFail();
                    return response()->download(storage_path($sendRepairInstance->repair_report_url), $sendRepairInstance->repair_report_name);
                    break;
                default:
                    break;
            }

        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 送修设备列表
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function sendRepairWithInstance()
    {
        try {
            $identity_code = request('identity_code', '');
            $status_code = request('status_code', '');
            $materialType = request('materialType', 'ENTIRE');
            $category_unique_code = request('category_unique_code', '');
            $entire_model_unique_code = request('entire_model_unique_code', '');
            $sub_model_unique_code = request('sub_model_unique_code', '');
            $storehouse_unique_code = request('storehouse_unique_code', '');
            $area_unique_code = request('area_unique_code', '');
            $platoon_unique_code = request('platoon_unique_code', '');
            $shelf_unique_code = request('shelf_unique_code', '');
            $tier_unique_code = request('tier_unique_code', '');
            $position_unique_code = request('position_unique_code', '');

            $entireInstances = [];
            $tmpMaterials = [];
            $sendRepairInstances = [];
            if (!empty(request()->keys())) {
                $otherUniqueCodes = DB::table('entire_instance_locks')->where('lock_name', '<>', $this->_sign)->pluck('entire_instance_identity_code')->toArray();
                switch ($materialType) {
                    default:
                    case 'ENTIRE':
                        # 搜索整件
                        $partInstanceSql = DB::table('entire_instances as ei')
                            ->selectRaw("ei.identity_code,ei.factory_name,c.name as category_name,'' as part_category_name,ei.model_name,ei.serial_number,ei.status,ei.location_unique_code,position.name as position_name,tier.name as tier_name,shelf.name as shelf_name,platoon.name as platoon_name,area.name as area_name,storehous.name as storehous_name,'ENTIRE' as material_type")
                            ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                            ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                            ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                            ->where("ei.deleted_at", null)
                            ->where('pm.deleted_at', null)
                            ->where('pc.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('em.is_sub_model', false)
                            ->where('c.deleted_at', null)
                            ->where('ei.status', '<>', 'SCRAP')
                            ->when(
                                !empty($otherUniqueCodes),
                                function ($q) use ($otherUniqueCodes) {
                                    return $q->whereNotIn('ei.identity_code', $otherUniqueCodes);
                                }
                            )
                            ->when(
                                !empty($status_code),
                                function ($q) use ($status_code) {
                                    return $q->where("ei.status", $status_code);
                                }
                            )
                            ->when(
                                !empty($identity_code),
                                function ($q) use ($identity_code) {
                                    return $q->where("ei.identity_code", $identity_code);
                                }
                            )
                            ->when(
                                !empty($category_unique_code),
                                function ($q) use ($category_unique_code) {
                                    return $q->where('c.unique_code', $category_unique_code);
                                }
                            )
                            ->when(
                                !empty($entire_model_unique_code),
                                function ($q) use ($entire_model_unique_code) {
                                    return $q->where("em.unique_code", $entire_model_unique_code);
                                }
                            )
                            ->when(
                                !empty($sub_model_unique_code),
                                function ($q) use ($sub_model_unique_code) {
                                    return $q->where("pm.unique_code", $sub_model_unique_code);
                                }
                            )
                            ->when(
                                !empty($storehouse_unique_code),
                                function ($q) use ($storehouse_unique_code) {
                                    return $q->where("storehous.unique_code", $storehouse_unique_code);
                                })
                            ->when(
                                !empty($area_unique_code),
                                function ($q) use ($area_unique_code) {
                                    return $q->where("area.unique_code", $area_unique_code);
                                })
                            ->when(
                                !empty($platoon_unique_code),
                                function ($q) use ($platoon_unique_code) {
                                    return $q->where("platoon.unique_code", $platoon_unique_code);
                                })
                            ->when(
                                !empty($shelf_unique_code),
                                function ($q) use ($shelf_unique_code) {
                                    return $q->where("shelf.unique_code", $shelf_unique_code);
                                })
                            ->when(
                                !empty($tier_unique_code),
                                function ($q) use ($tier_unique_code) {
                                    return $q->where("tier.unique_code", $tier_unique_code);
                                })
                            ->when(
                                !empty($position_unique_code),
                                function ($q) use ($position_unique_code) {
                                    return $q->where("ei.location_unique_code", $position_unique_code);
                                })
                            ->orderByDesc('ei.updated_at');

                        $entireInstanceSql = DB::table('entire_instances as ei')
                            ->selectRaw("ei.identity_code,ei.factory_name,'' as part_category_name,c.name as category_name,ei.model_name,ei.serial_number,ei.status,ei.location_unique_code,position.name as position_name,tier.name as tier_name,shelf.name as shelf_name,platoon.name as platoon_name,area.name as area_name,storehous.name as storehous_name,'ENTIRE' as material_type")
                            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                            ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                            ->where("ei.deleted_at", null)
                            ->where('sm.deleted_at', null)
                            ->where('sm.is_sub_model', true)
                            ->where('em.deleted_at', null)
                            ->where('em.is_sub_model', false)
                            ->where('c.deleted_at', null)
                            ->where('ei.status', '<>', 'SCRAP')
                            ->when(
                                !empty($otherUniqueCodes),
                                function ($q) use ($otherUniqueCodes) {
                                    return $q->whereNotIn('ei.identity_code', $otherUniqueCodes);
                                }
                            )
                            ->when(
                                !empty($status_code),
                                function ($q) use ($status_code) {
                                    return $q->where("ei.status", $status_code);
                                }
                            )
                            ->when(
                                !empty($identity_code),
                                function ($q) use ($identity_code) {
                                    return $q->where("ei.identity_code", $identity_code);
                                }
                            )
                            ->when(
                                !empty($category_unique_code),
                                function ($q) use ($category_unique_code) {
                                    return $q->where('c.unique_code', $category_unique_code);
                                }
                            )
                            ->when(
                                !empty($entire_model_unique_code),
                                function ($q) use ($entire_model_unique_code) {
                                    return $q->where("em.unique_code", $entire_model_unique_code);
                                }
                            )
                            ->when(
                                !empty($sub_model_unique_code),
                                function ($q) use ($sub_model_unique_code) {
                                    return $q->where("sm.unique_code", $sub_model_unique_code);
                                }
                            )
                            ->when(
                                !empty($storehouse_unique_code),
                                function ($q) use ($storehouse_unique_code) {
                                    return $q->where("storehous.unique_code", $storehouse_unique_code);
                                })
                            ->when(
                                !empty($area_unique_code),
                                function ($q) use ($area_unique_code) {
                                    return $q->where("area.unique_code", $area_unique_code);
                                })
                            ->when(
                                !empty($platoon_unique_code),
                                function ($q) use ($platoon_unique_code) {
                                    return $q->where("platoon.unique_code", $platoon_unique_code);
                                })
                            ->when(
                                !empty($shelf_unique_code),
                                function ($q) use ($shelf_unique_code) {
                                    return $q->where("shelf.unique_code", $shelf_unique_code);
                                })
                            ->when(
                                !empty($tier_unique_code),
                                function ($q) use ($tier_unique_code) {
                                    return $q->where("tier.unique_code", $tier_unique_code);
                                })
                            ->when(
                                !empty($position_unique_code),
                                function ($q) use ($position_unique_code) {
                                    return $q->where("ei.location_unique_code", $position_unique_code);
                                })
                            ->orderByDesc('ei.updated_at')
                            ->unionAll($partInstanceSql);

                        $entireInstances = DB::table(DB::raw("({$entireInstanceSql->toSql()}) as a"))->mergeBindings($entireInstanceSql)->paginate();
                        break;
                    case 'PART':
                        # 搜索部件
                        $entireInstances = DB::table("part_instances as pi")
                            ->selectRaw("pi.identity_code,pi.factory_name,pm.name as model_name,pc.name as part_category_name,c.name as category_name,'' as serial_number,pi.status,pi.location_unique_code,position.name as position_name,tier.name as tier_name,shelf.name as shelf_name,platoon.name as platoon_name,area.name as area_name,storehous.name as storehous_name,'PART' as material_type")
                            ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'pi.part_model_unique_code')
                            ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                            ->leftJoin(DB::raw('positions position'), 'pi.location_unique_code', '=', 'position.unique_code')
                            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                            ->where("pi.deleted_at", null)
                            ->where('pc.deleted_at', null)
                            ->where('pi.status', '<>', 'SCRAP')
                            ->when(
                                !empty($otherUniqueCodes),
                                function ($q) use ($otherUniqueCodes) {
                                    return $q->whereNotIn('pi.identity_code', $otherUniqueCodes);
                                }
                            )
                            ->when(
                                !empty($status_code),
                                function ($q) use ($status_code) {
                                    return $q->where("pi.status", $status_code);
                                }
                            )
                            ->when(
                                !empty($identity_code),
                                function ($q) use ($identity_code) {
                                    return $q->where("pi.identity_code", $identity_code);
                                }
                            )
                            ->when(
                                !empty($category_unique_code),
                                function ($q) use ($category_unique_code) {
                                    return $q->where('c.unique_code', $category_unique_code);
                                }
                            )
                            ->when(
                                !empty($entire_model_unique_code),
                                function ($q) use ($entire_model_unique_code) {
                                    return $q->where("em.unique_code", $entire_model_unique_code);
                                }
                            )
                            ->when(
                                !empty($sub_model_unique_code),
                                function ($q) use ($sub_model_unique_code) {
                                    return $q->where("pm.unique_code", $sub_model_unique_code);
                                }
                            )
                            ->when(
                                !empty($storehouse_unique_code),
                                function ($q) use ($storehouse_unique_code) {
                                    return $q->where("storehous.unique_code", $storehouse_unique_code);
                                })
                            ->when(
                                !empty($area_unique_code),
                                function ($q) use ($area_unique_code) {
                                    return $q->where("area.unique_code", $area_unique_code);
                                })
                            ->when(
                                !empty($platoon_unique_code),
                                function ($q) use ($platoon_unique_code) {
                                    return $q->where("platoon.unique_code", $platoon_unique_code);
                                })
                            ->when(
                                !empty($shelf_unique_code),
                                function ($q) use ($shelf_unique_code) {
                                    return $q->where("shelf.unique_code", $shelf_unique_code);
                                })
                            ->when(
                                !empty($tier_unique_code),
                                function ($q) use ($tier_unique_code) {
                                    return $q->where("tier.unique_code", $tier_unique_code);
                                })
                            ->when(
                                !empty($position_unique_code),
                                function ($q) use ($position_unique_code) {
                                    return $q->where("pi.location_unique_code", $position_unique_code);
                                })
                            ->orderByDesc("pi.id")
                            ->paginate();
                        break;
                }
                $sendRepair = DB::table('send_repairs as sr')
                    ->select('sri.material_unique_code', 'sri.repair_desc', 'sri.repair_remark')
                    ->leftJoin(DB::raw('send_repair_instances sri'), 'sr.unique_code', '=', 'sri.send_repair_unique_code')
                    ->where('sr.state', '<>', 'END')
                    ->where('sr.state', '<>', 'CANCEL')
                    ->get()
                    ->toArray();
                foreach ($sendRepair as $item) {
                    $sendRepairInstances[$item->material_unique_code] = [
                        'repair_desc' => $item->repair_desc,
                        'repair_remark' => $item->repair_remark,
                    ];
                }
                $tmpMaterials = DB::table('tmp_materials')->where('account_id', session('account.id'))->where('state', $this->_sign)->pluck('repair_remark', 'material_unique_code')->toArray();
            }
            $categories = DB::table('categories')->where('deleted_at', null)->select('name', 'unique_code')->get()->toArray();
            $storehouses = DB::table('storehouses')->select('name', 'unique_code')->get()->toArray();


            $bound_breakdown_types = DB::table("tmp_breakdown_logs as tbl")
                ->selectRaw(join(",", [
                    "tbl.material_unique_code",
                    "bt.name"
                ]))
                ->leftJoin(DB::raw("breakdown_types bt"), "bt.id", "=", "tbl.breakdown_type_id")
                ->where("tbl.account_id", session("account.id"))
                ->get()
                ->groupBy("material_unique_code");

            return view('Storehouse.SendRepair.sendRepairWithInstance', [
                'currentMaterialType' => $materialType,
                'categories' => $categories,
                'storehouses' => $storehouses,
                "entireInstances" => $entireInstances,
                "statuses" => EntireInstance::$STATUSES,
                'tmpMaterials' => $tmpMaterials,
                'sendRepairInstances' => $sendRepairInstances,
                'bound_breakdown_types' => $bound_breakdown_types,
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 送修 - 添加临时设备
     * @return \Illuminate\Http\JsonResponse
     */
    final public function tmpMaterialWithStore()
    {
        try {
            $identityCode = request('identityCode', '');
            $materialType = request('materialType', 'ENTIRE');
            if (empty($identityCode) || empty($materialType)) return HttpResponseHelper::errorValidate('参数不足');

            $checks = TmpMaterial::with(['WithEntireInstance', 'WithPartInstance'])->where('account_id', session('account.id'))->where('state', $this->_sign)->get();
            if ($checks->isNotEmpty()) {
                if ($materialType == 'ENTIRE') {
                    $material = EntireInstance::with([])->where('identity_code', $identityCode)->firstOrFail();
                } else {
                    $material = PartInstance::with([])->where('identity_code', $identityCode)->firstOrFail();
                }

                foreach ($checks as $check) {
                    if ($materialType == 'ENTIRE') {
                        if (!empty($check->WithEntireInstance)) {
                            if ($material->factory_name != $check->WithEntireInstance->factory_name) {
                                return HttpResponseHelper::errorValidate('不能添加不同供应商设备，请处理，编号：' . $check->material_unique_code);
                                break;
                            }
                        }
                    } else {
                        if (!empty($check->WithPartInstance)) {
                            if ($material->factory_name != $check->WithPartInstance->factory_name) {
                                return HttpResponseHelper::errorValidate('不能添加不同供应商设备，请处理，编号：' . $check->material_unique_code);
                                break;
                            }
                        }
                    }

                }
            }

            $materialTypeName = $materialType == 'ENTIRE' ? '整件' : '部件';
            EntireInstanceLock::setOnlyLock(
                $identityCode,
                [$this->_sign],
                "{$materialTypeName}设备器材：{$identityCode}，在送修中被使用。详情：送修操作人员：" . session('account.account'),
                function () use ($identityCode, $materialType) {
                    DB::table('tmp_materials')->updateOrInsert(
                        ['material_unique_code' => $identityCode, 'account_id' => session('account.id'), 'state' => $this->_sign],
                        ['material_unique_code' => $identityCode, 'account_id' => session('account.id'), 'state' => $this->_sign, 'material_type' => $materialType]
                    );
                }
            );
            return HttpResponseHelper::created('添加成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage(), [get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()]);
        }
    }

    /**
     * 送修 - 编辑临时设备
     * @param string $identityCode
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function tmpMaterialWithUpdate(string $identityCode)
    {
        try {
            $tmpMaterial = TmpMaterial::with([])->where('material_unique_code', $identityCode)->where('state', $this->_sign)->firstOrFail();
            $tmpMaterial->fill([
                request('field', 'repair_remark') => request('value', '')
            ]);
            $tmpMaterial->saveOrFail();

            return HttpResponseHelper::created('编辑成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage(), [get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()]);
        }
    }

    /**
     * 送修 - 删除临时设备
     * @param string $identityCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function tmpMaterialWithDestroy(string $identityCode)
    {
        try {
            $tmpMaterial = TmpMaterial::with([])->where('material_unique_code', $identityCode)->where('state', $this->_sign)->firstOrFail();
            EntireInstanceLock::freeLock(
                $tmpMaterial->material_unique_code,
                [$this->_sign],
                function () use ($tmpMaterial) {
                    $tmpMaterial->delete();
                }
            );

            return HttpResponseHelper::created('删除成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage(), [get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()]);
        }
    }

    /**
     * 送修 - 故障类型模态框
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function tmpBreakdownLogWithCreate()
    {
        try {
            $identityCode = request('identityCode', '');
            $materialType = request('materialType', 'ENTIRE');
            if (empty($identityCode) || empty($materialType)) return back()->with('danger', '参数不足');
            if ($materialType == 'ENTIRE') {
                $material = EntireInstance::with([])->where('identity_code', $identityCode)->firstOrFail();
            } else {
                $material = PartInstance::with([])->where('identity_code', $identityCode)->firstOrFail();
            }
            $breakdownTypes = DB::table('breakdown_types')->where('deleted_at', null)->where('category_unique_code', $material->category_unique_code)->pluck('name', 'id')->toArray();

            $tmpBreakdownTypeIds = DB::table('tmp_breakdown_logs')->where('material_unique_code', $identityCode)->where('account_id', session('account.id'))->pluck('breakdown_type_id')->toArray();
            $tmpMaterial = DB::table('tmp_materials')->where('material_type', $materialType)->where('material_unique_code', $identityCode)->where('state', $this->_sign)->where('account_id', session('account.id'))->first();

            return view('Storehouse.SendRepair.breakdownTypeModal', [
                'currentIdentityCode' => $identityCode,
                'currentMaterialType' => $materialType,
                'breakdownTypes' => $breakdownTypes,
                'tmpBreakdownTypeIds' => $tmpBreakdownTypeIds,
                'tmpMaterial' => $tmpMaterial
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '设备数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 送修 - 添加临时故障类型
     * @return \Illuminate\Http\JsonResponse
     */
    final public function tmpBreakdownLogWithStore()
    {
        try {
            DB::transaction(function () {
                $identityCode = request('identityCode', '');
                $materialType = request('materialType', 'ENTIRE');
                if (empty($identityCode)) return back()->with('danger', '设备编码不存在');
                $breakdownTypeIds = request('breakdownTypeIds', []);
                DB::table('tmp_breakdown_logs')->where('account_id', session('account.id'))->where('material_type', $materialType)->where('material_unique_code', $identityCode)->delete();
                if (!empty($breakdownTypeIds)) {
                    $tmpInserts = [];
                    foreach ($breakdownTypeIds as $breakdownTypeId) {
                        $tmpInserts[] = [
                            'material_unique_code' => $identityCode,
                            'breakdown_type_id' => $breakdownTypeId,
                            'account_id' => session('account.id'),
                            'material_type' => $materialType
                        ];
                    }
                    DB::table('tmp_breakdown_logs')->insert($tmpInserts);
                }
                $repairDesc = request('repairDesc', '');
                DB::table('tmp_materials')->where('material_type', $materialType)->where('material_unique_code', $identityCode)->where('state', $this->_sign)->where('account_id', session('account.id'))->update(['repair_desc' => $repairDesc ?? '']);
            });

            return HttpResponseHelper::created('成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }


}
