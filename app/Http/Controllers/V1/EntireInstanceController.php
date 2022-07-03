<?php

namespace App\Http\Controllers\V1;

use App\Facades\CodeFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\Maintain;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;

class EntireInstanceController extends Controller
{
    use Helpers;

    private $_entireModelStatus = [
        'BUY_IN' => '新入所',
        'INSTALLING' => '备品',
        'INSTALLED' => '上道',
        'FIXING' => '检修中/等待检修',
        'FIXED' => '成品',
        'RETURN_FACTORY' => '返厂维修',
        'FACTORY_RETURN' => '返厂入所',
        'SCRAP' => '报废',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            if (request('code', null) == null) return HttpResponseHelper::errorValidate('编码不能为空');

            $getBuilder = function (): Builder {
                return DB::table('entire_instances as ei')
                    ->select([
                        'ei.created_at',
                        'ei.updated_at',
                        'ei.identity_code',
                        'ei.factory_name',
                        'ei.factory_device_code',
                        'ei.serial_number',
                        'ei.maintain_station_name',
                        'ei.maintain_location_code',
                        'ei.installed_at',
                        'ei.category_name as category_name',
                        'ei.status',
                        'ei.to_direction',
                        'ei.crossroad_number',
                        'ei.traction',
                        'ei.line_name',
                        'ei.open_direction',
                        'ei.said_rod',
                        'ei.note',
                        'em.name as entire_model_name',
                        'ei.model_name',
                    ])
                    ->leftJoin(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
                    ->where('ei.deleted_at', null);
            };
            switch (request('type')) {
                case 1:
                default:
                    # 通过厂编号获取整件数据
                    $entireInstances = $getBuilder()->where('ei.factory_device_code', request('code'))->get()->toArray();
                    break;
                case 2:
                    # 通过所编号获取整件数据
                    $entireInstances = $getBuilder()->where('ei.serial_number', request('code'))->get()->toArray();
                    break;
                case 3:
                    # 通过唯一编号获取数据
                    $entireInstances = $getBuilder()->where('ei.identity_code', request('code'))->get()->toArray();
                    break;
                case 4:
                    # 通过RFID TID获取数据
                    $entireInstances = $getBuilder()->where('ei.rfid_code', request('code'))->get()->toArray();
                    break;
                case 5:
                    # 通过RFID EPC获取数据
                    $identityCode = CodeFacade::hexToIdentityCode(request('code'));
                    $entireInstances = $getBuilder()->where('ei.identity_code', $identityCode)->get()->toArray();
                    break;
            }

            foreach ($entireInstances as $entireInstance) {
                $entireInstance->status_name = $this->_entireModelStatus[$entireInstance->status];
                $station_code = strpos($entireInstance->identity_code, "B");
                $station = substr($entireInstance->identity_code, $station_code, 4);
                switch ($station) {
                    case "B048":
                        $stationName = "广州";
                        break;
                    case "B049":
                        $stationName = "株洲";
                        break;
                    case "B050":
                        $stationName = "怀化";
                        break;
                    case "B051":
                        $stationName = "衡阳";
                        break;
                    case "B052":
                        $stationName = "惠州";
                        break;
                    case "B053":
                        $stationName = "肇庆";
                        break;
                    case "B054":
                        $stationName = "海口";
                        break;
                    default:
                        $stationName = "";
                }
                $entireInstance->station = $stationName;
                substr($entireInstance->identity_code, 0, 1);
            }

            return HttpResponseHelper::data($entireInstances);
        } catch (\Exception $exception) {
            return HttpResponseHelper::errorForbidden($exception->getMessage());
        }
    }

    /**
     * 针对转辙机获取设备器材
     */
    final public function forPointSwitchQuery()
    {
        try {
            return HttpResponseHelper::data(
                DB::table('entire_instances as ei')
                    ->select([
                        'ei.created_at',
                        'ei.updated_at',
                        'ei.identity_code',
                        'ei.factory_name',
                        'ei.factory_device_code',
                        'ei.serial_number',
                        'ei.maintain_station_name',
                        'ei.maintain_location_code',
                        'ei.installed_at',
                        'ei.category_name as category_name',
                        'ei.status',
                        'ei.to_direction',
                        'ei.crossroad_number',
                        'ei.traction',
                        'ei.line_name',
                        'ei.open_direction',
                        'ei.said_rod',
                        'ei.note',
                        'pi.identity_code as pi_identity_code',
                        'pm.name as part_model_name',
                        'em.name as entire_model_name',
                    ])
                    ->join(DB::raw('part_instances pi'), 'pi.entire_instance_identity_code', '=', 'ei.identity_code')
                    ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'pi.part_model_unique_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->when(
                        request('forPointSwitchQuery_type', '1') === '1',
                        function ($query) {
                            # 通过整件唯一编号进行搜索
                            return $query
                                ->where(
                                    'ei.identity_code',
                                    substr(request('code'), 0, 1) === 'S' ?
                                        request('code') :
                                        CodeFacade::hexToIdentityCode(request('code'))
                                );
                        },
                        function ($query) {
                            # 通过部件唯一编号进行搜索
                            return $query->where('pi.identity_code', request('code'));
                        }
                    )
                    ->get()
            );
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $line = $e->getLine();
            $file = $e->getFile();
//            return HttpResponseHelper::errorForbidden("{$msg}\r\n{$line}\r\n{$file}");
            return HttpResponseHelper::errorForbidden($msg);
        }
    }

    final public function getAsBatch()
    {
        return response()->make(CodeFacade::identityCodeToHex(request('identity_code')));
    }

    final public function postAsBatch(Request $request)
    {
        $rfidTids = $request->get('rfid_tids');
        $rfidEpcs = $request->get('rfid_epcs');
        $identityCodes = [];
        foreach ($rfidEpcs as $rfidEpc) $identityCodes[] = CodeFacade::hexToIdentityCode($rfidEpc);
        $identityCodes = array_unique($identityCodes);

        $ret = DB::table('entire_instances')
            ->distinct()
            ->where('deleted_at', null)
            ->where('status', '<>', 'SCRAP')
            ->whereIn('identity_code', $identityCodes)
            ->orWhereIn('rfid_code', $rfidTids)
            ->pluck('identity_code');

        return HttpResponseHelper::data($ret);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * 设备器材履历查询
     * Display the specified resource.
     *
     * @param string $code
     * @return \Illuminate\Http\JsonResponse
     */
    final public function show(string $code)
    {
        //switch (strlen($code)) {
        //    case 14:
        //    case 19:
        //        # 360唯一编号
        //        $identityCode = $code;
        //        break;
        //    case 24:
        //        # 电子标签
        //        $firstCode = substr($code, 0, 4);
        //        if ($firstCode == '130E') {
        //            $identityCode = Code::hexToIdentityCode($code);
        //        } else {
        //            $ei = DB::table('entire_instance as ei')->where('rfid_code', $code)->first(['identity_code']);
        //            if (!$ei) return HttpResponseHelper::errorEmpty('未查询到设备器材');
        //            $identityCode = $ei->identity_code;
        //        }
        //}
        # 获取设备器材信息
        $entireInstance = DB::table('entire_instances as ei')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
            ->leftJoin(DB::raw('fix_workflows fw'), 'fw.serial_number', '=', 'ei.fix_workflow_serial_number')
            ->leftJoin(DB::raw('warehouse_reports wr'), 'wr.serial_number', '=', 'ei.last_warehouse_report_serial_number_by_out')
            ->leftJoin(DB::raw('part_instances pi'), 'pi.entire_instance_identity_code', '=', 'ei.identity_code')
            ->leftJoin(DB::raw('part_models pm'), 'pm.unique_code', '=', 'pi.part_model_unique_code')
            ->where('ei.deleted_at', null)
            ->where("ei.identity_code", 'like', "%{$code}%")
            ->first([
                'ei.identity_code as identity_code',
                'ei.factory_name as factory_name',
                'ei.installed_at as installed_time',
                'ei.fix_workflow_serial_number as fix_workflow_serial_number',
                'em.name as entire_model_name',
                'fw.updated_at as fixed_at',
                'wr.updated_at as out_at',
                'pi.identity_code as part_identity_code',
                'pm.name as part_model_name'
            ]);
        if (empty($entireInstance)) return HttpResponseHelper::errorEmpty('设备器材编号不存在');

        # 最后一次检修人
        if ($entireInstance->fix_workflow_serial_number) {
            $lastFixer = DB::table('fix_workflow_processes as fwp')
                ->select(['a.nickname'])
                ->join(DB::raw('accounts a'), 'a.id', '=', 'fwp.processor_id')
                ->where('fwp.deleted_at', null)
                ->where('fwp.fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)
                ->where('fwp.stage', 'FIXED')
                ->orderByDesc('fwp.id')
                ->first();
        } else {
            $lastFixer = null;
        }

        if ($entireInstance->fix_workflow_serial_number) {
            $lastChecker = DB::table('fix_workflow_processes as fwp')
                ->select(['a.nickname'])
                ->join(DB::raw('accounts a'), 'a.id', '=', 'fwp.processor_id')
                ->where('fwp.deleted_at', null)
                ->where('fwp.fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)
                ->whereIn('fwp.stage', ['CHECKED', 'WORKSHOP', 'SECTION', 'SECTION_CHIEF'])
                ->orderByDesc('fwp.id')
                ->first();
        } else {
            $lastChecker = null;
        }

        $entireInstance->last_fixer = $lastFixer ? $lastFixer->nickname : '';
        $entireInstance->last_checker = $lastChecker ? $lastChecker->nickname : '';

        # 获取部件信息
        $partInstances = DB::table('part_instances as pi')
            ->select([
                'pi.identity_code',
                'pi.factory_name',
                'pm.name as part_model_name',
                'pi.self_category',
                'pi.self_model',
            ])
            ->leftJoin(DB::raw('part_models pm'), 'pm.unique_code', '=', 'pi.part_model_unique_code')
            ->where('pi.deleted_at', null)
            ->where('pi.entire_instance_identity_code', $code)
            ->get();
        $partInstances2 = [];
        foreach ($partInstances as $partInstance) {
            $partInstances2[$partInstance->self_category][] = $partInstance;
        }
        unset($partInstance);

        $entireInstance->installed_at = $entireInstance->installed_time ? Carbon::createFromTimestamp($entireInstance->installed_time)->format('Y-m-d') : '';

        $entireInstance->fixed_at = $entireInstance->fixed_at ? Carbon::createFromFormat('Y-m-d H:i:s', $entireInstance->fixed_at)->format('Y-m-d') : '';
        $entireInstance->out_at = $entireInstance->out_at ? Carbon::createFromFormat('Y-m-d H:i:s', $entireInstance->out_at)->format('Y-m-d') : '';


        # 获取设备器材日志
        $entireInstanceLogs = DB::table('entire_instance_logs')
            ->where('deleted_at', null)
            ->where('entire_instance_identity_code', $code)
            ->get();

        $entireInstanceLogsWithMonth = [];
        foreach ($entireInstanceLogs as $entireInstanceLog) {
            if (empty($entireInstanceLog->created_at) || is_null($entireInstanceLog->created_at)) continue;
            $month = Carbon::createFromFormat('Y-m-d H:i:s', $entireInstanceLog->created_at)->format('Y-m');
            $entireInstanceLogsWithMonth[$month][] = $entireInstanceLog;
        }

        return HttpResponseHelper::data([
            'entire_instance' => $entireInstance,
            'entire_instance_logs' => $entireInstanceLogsWithMonth,
            'part_instances' => $partInstances2,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * 设备器材绑定rfid tid
     * @param Request $request
     * @param string $identityCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function bindingRfidTid(Request $request, string $identityCode)
    {
        try {
            if (DB::table('entire_instances')->where('rfid_code', $request->get('rfid_tid'))->first()) return HttpResponseHelper::errorValidate('该标签已经被绑定');
            $entireInstance = EntireInstance::where('identity_code', $identityCode)->firstOrFail();
            $entireInstance->fill(['rfid_code' => $request->get('rfid_tid')])->saveOrFail();

            EntireInstanceLogFacade::makeOne(
                session('account.id'),
                '',
                '绑定RFID TID',
                $entireInstance->identity_code,
                3,
                '',
                $request->get('rfid_tid')
            );

            return HttpResponseHelper::created('绑定成功');
        } catch (ModelNotFoundException $e) {
            return HttpResponseHelper::errorEmpty('设备器材不存在');
        } catch (\Exception $e) {
            return HttpResponseHelper::error(env('APP_DEBUG') ? '意外错误：' . $e->getMessage() : '意外错误');
        }
    }

    /**
     * 室外上道安装（单独）
     * @return \Illuminate\Http\JsonResponse
     */
    final public function install()
    {
        $request = TextHelper::parseJson(@file_get_contents('php://input'));
        $entireInstanceLogsForInstall = [];
        $entireInstanceLogsForUnInstall = [];

        foreach ($request as $item) {
            if ($item['old_rfid_tid']) {
                # 替换设备器材上道
                if (!array_key_exists('old_rfid_tid', $item)) return HttpResponseHelper::errorEmpty('旧设备器材编号不能为空');
                if (!array_key_exists('new_rfid_tid', $item)) return HttpResponseHelper::errorEmpty('新设备器材编号不能为空');

                $old_entire_instance = DB::table('entire_instances')->where('identity_code', $item['old_rfid_tid'])->orWhere('serial_number', $item['old_rfid_tid'])->first();
                if (!$old_entire_instance) return HttpResponseHelper::errorEmpty('上道设备器材不存在');

                $new_entire_instance = DB::table('entire_instances')->where('identity_code', $item['new_rfid_tid'])->orWhere('serial_number', $item['new_rfid_tid'])->first();
                if (!$new_entire_instance) return HttpResponseHelper::errorEmpty('待上道设备器材不存在');

                $station = Maintain::with([])->where('name', $old_entire_instance->maintain_station_name)->first();

                $entireInstanceLogsForInstall[] = [
                    'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'name' => '上道（更换）',
                    'entire_instance_identity_code' => $new_entire_instance->identity_code,
                    'type' => 4,
                    'url' => "/search/{$new_entire_instance->identity_code}",
                    'description' => "{$new_entire_instance->identity_code} 更换 {$old_entire_instance->identity_code}",
                    'operator_id' => session('account.id'),
                    'station_unique_code' => @$station->unique_code ?? '',
                ];
                $entireInstanceLogsForUnInstall[] = [
                    'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'name' => '下道（更换）',
                    'entire_instance_identity_code' => $item['old_rfid_tid'],
                    'type' => 4,
                    'url' => "/search/{$old_entire_instance->identity_code}",
                    'description' => "{$old_entire_instance->identity_code} 被 {$new_entire_instance->identity_code} 更换",
                    'operator_id' => session('account.id'),
                    'station_unique_code' => @$station->unique_code ?? '',
                ];

                //获取车间名称
                $parent_unique_code = DB::table('maintains')->where('name', $item['maintain_station_name'])->value('parent_unique_code');
                $maintain_workshop_name = DB::table('maintains')->where('unique_code', $parent_unique_code)->value('name');

                DB::table('entire_instances')->where('identity_code', $old_entire_instance->identity_code)->update(['status' => 'FIXING', 'updated_at' => date('Y-m-d H:i:s')]);
                DB::table('entire_instances')->where('identity_code', $new_entire_instance->identity_code)->update(
                    array_merge(
                        [
                            'maintain_workshop_name' => $maintain_workshop_name,
                            'maintain_station_name' => $item['maintain_station_name'],
                            // 'to_direction' => $item['to_direction'],
                            'crossroad_number' => $item['crossroad_number'],
                            'traction' => $item['traction'],
                            'line_name' => $item['line_name'],
                            'open_direction' => $item['open_direction'],
                            'said_rod' => $item['said_rod'],
                            'note' => $item['note'],
                        ],
                        [
                            'status' => 'INSTALLED',
                            'updated_at' => date('Y-m-d H:i:s'),
                            'installed_at' => now(),
                        ]
                    ));
            } else {
                # 新设备器材上道
                if (!array_key_exists('new_rfid_tid', $item)) return HttpResponseHelper::errorEmpty('待设备器材编号不能为空');
                $new_entire_instance = DB::table('entire_instances')->where('identity_code', $item['new_rfid_tid'])->first();
                if (!$new_entire_instance) return HttpResponseHelper::errorEmpty('设备器材不存在');
                //获取车间名称
                $parent_unique_code = DB::table('maintains')->where('name', $item['maintain_station_name'])->value('parent_unique_code');
                $maintain_workshop_name = DB::table('maintains')->where('unique_code', $parent_unique_code)->value('name');

                $entireInstanceLogsForInstall[] = [
                    'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'name' => '上道（新上道）',
                    'entire_instance_identity_code' => $new_entire_instance->identity_code,
                    'type' => 4,
                    'url' => "/search/{$new_entire_instance->identity_code}",
                ];
                DB::table('entire_instances')->where('identity_code', $new_entire_instance->identity_code)->update([
                    'maintain_workshop_name' => $maintain_workshop_name,
                    'maintain_station_name' => $item['maintain_station_name'],
                    // 'to_direction' => $item['to_direction'],
                    'crossroad_number' => $item['crossroad_number'],
                    'traction' => $item['traction'],
                    'line_name' => $item['line_name'],
                    'open_direction' => $item['open_direction'],
                    'said_rod' => $item['said_rod'],
                    'status' => 'FIXING',
                    'updated_at' => date('Y-m-d H:i:s'),
                    'installed_at' => now(),
                    'note' => $item['note']
                ]);
            }
        }

        # 生成整件操作日志
        EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogsForInstall);
        EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogsForUnInstall);

        return HttpResponseHelper::created("上道成功：" . count($request));
    }

    /**
     * 室内上道安装（单独）
     * @return \Illuminate\Http\JsonResponse
     */
    final public function installs()
    {
        $request = TextHelper::parseJson(@file_get_contents('php://input'));
        $entireInstanceLogsForInstall = [];
        $entireInstanceLogsForUnInstall = [];

        foreach ($request as $item) {

            if ($item['old_rfid_tid']) {
                # 替换设备器材上道
                if (!array_key_exists('old_rfid_tid', $item)) return HttpResponseHelper::errorEmpty('上道设备编号不能为空');
                if (!array_key_exists('new_rfid_tid', $item)) return HttpResponseHelper::errorEmpty('待上道设备编号不能为空');

                $old_entire_instance = DB::table('entire_instances')->where('identity_code', $item['old_rfid_tid'])->orWhere('serial_number', $item['old_rfid_tid'])->first();
                if (!$old_entire_instance) return HttpResponseHelper::errorEmpty('上道设备器材不存在');

//                $new_entire_instance = DB::table('entire_instances')->where('identity_code', $item['new_rfid_tid'])->orWhere('serial_number', $item['new_rfid_tid'])->first();
//                $new_entire_instance = DB::table('entire_instances')->where('identity_code', $item['old_rfid_tid'])->first();
                //获取车间名称
                $station = Maintain::with(['Parent'])->where('name', $item['maintain_station_name'])->first();
                $maintain_workshop_name = @$station->Parent->name ?? '';

//                $new_entire_instance = DB::table('entire_instances')->where('identity_code', $item['new_rfid_tid'])->orWhere('serial_number', $item['new_rfid_tid'])->first();
                $new_entire_instance = DB::table('entire_instances')->where('identity_code', $item['new_rfid_tid'])->first();
                if (!$new_entire_instance) return HttpResponseHelper::errorEmpty('待上道设备器材不存在');

                $entireInstanceLogsForInstall[] = [
                    'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'name' => '上道（更换）',
                    'entire_instance_identity_code' => $new_entire_instance->identity_code,
                    'type' => 4,
                    'url' => "/search/{$new_entire_instance->identity_code}",
                    'description' => "{$new_entire_instance->identity_code} 更换 {$old_entire_instance->identity_code}",
                    'operator_id' => session('account.id'),
                    'station_unique_code' => @$station->unique_code ?? '',
                ];
                $entireInstanceLogsForUnInstall[] = [
                    'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'name' => '下道（更换）',
                    'entire_instance_identity_code' => $item['old_rfid_tid'],
                    'type' => 4,
                    'url' => "/search/{$old_entire_instance->identity_code}",
                    'description' => "{$old_entire_instance->identity_code} 被 {$new_entire_instance->identity_code} 更换",
                    'operator_id' => session('account.id'),
                    'station_unique_code' => @$station->unique_code ?? '',
                ];

                DB::table('entire_instances')->where('identity_code', $old_entire_instance->identity_code)->update(['status' => 'FIXING', 'updated_at' => date('Y-m-d H:i:s')]);
                DB::table('entire_instances')->where('identity_code', $new_entire_instance->identity_code)->update(
                    array_merge(
                        [
                            'maintain_station_name' => $item['maintain_station_name'],
                            'maintain_location_code' => $item['maintain_location_code'],
                            'note' => $item['note'],
                        ],
                        [
                            'status' => 'INSTALLED',
                            'updated_at' => date('Y-m-d H:i:s'),
                            'installed_at' => now(),
                        ]
                    ));
            } else {
                # 新设备器材上道
                if (!array_key_exists('new_rfid_tid', $item)) return HttpResponseHelper::errorEmpty('待上道设备器材编号不能为空');
                $new_entire_instance = DB::table('entire_instances')->where('identity_code', $item['new_rfid_tid'])->first();
                if (!$new_entire_instance) return HttpResponseHelper::errorEmpty('设备器材不存在');

                $entireInstanceLogsForInstall[] = [
                    'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'name' => '上道（新上道）',
                    'entire_instance_identity_code' => $new_entire_instance->identity_code,
                    'type' => 4,
                    'url' => "/search/{$new_entire_instance->identity_code}",
                ];
                //获取车间名称
                $parent_unique_code = DB::table('maintains')->where('name', $item['maintain_station_name'])->value('parent_unique_code');
                $maintain_workshop_name = DB::table('maintains')->where('unique_code', $parent_unique_code)->value('name');

                DB::table('entire_instances')->where('identity_code', $new_entire_instance->identity_code)->update([
                    'maintain_workshop_name' => $maintain_workshop_name,
                    'maintain_station_name' => $item['maintain_station_name'],
                    'maintain_location_code' => $item['maintain_location_code'],
                    'status' => 'FIXING',
                    'updated_at' => date('Y-m-d H:i:s'),
                    'installed_at' => now(),
                    'note' => $item['note']
                ]);
            }
        }

        # 生成整件操作日志
        EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogsForInstall);
        EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogsForUnInstall);

        return HttpResponseHelper::created("上道成功：" . count($request));
    }

    /**
     * 下道（单独）
     * @return \Illuminate\Http\JsonResponse
     */
    final public function uninstall()
    {
        $request = TextHelper::parseJson(@file_get_contents('php://input'));

        $exists = [];
        $noExists = [];
        $entireInstanceLogs = [];
        $count = 0;
        foreach ($request as $code) {
            switch (strlen($code)) {
                default:
                    $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('serial_number', $code)->first(['maintain_station_name', 'identity_code', 'status']);
                    if ($entireInstance == null) {
                        $noExists[] = $code;
                        continue;
                    }
                    $station = Maintain::with([])->where('name', $entireInstance->maintain_station_name)->first();
                    $exists[] = $entireInstance->identity_code;
                    $entireInstanceLogs[] = [
                        'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                        'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                        'name' => '下道',
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                        'type' => 4,
                        'url' => "/search/{$entireInstance->identity_code}",
                        'operator_id' => session('account.id'),
                        'station_unique_code' => @$station->unique_code ?? '',
                    ];
                    break;
                case 14:
                case 19:
                    $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('identity_code', $code)->first(['maintain_station_name', 'identity_code', 'status']);
                    if ($entireInstance == null) {
                        $noExists[] = $code;
                        continue;
                    }
                    $station = Maintain::with([])->where('name', $entireInstance->maintain_station_name)->first();
                    $exists[] = $entireInstance->identity_code;
                    $entireInstanceLogs[] = [
                        'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                        'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                        'name' => '下道',
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                        'type' => 4,
                        'url' => "/search/{$entireInstance->identity_code}",
                        'operator_id' => session('account.id'),
                        'station_unique_code' => @$station->unique_code ?? '',
                    ];
                    break;
                case 24:
                    $firstCode = substr($code, 0, 4);
                    if ($firstCode == '130E' && $firstCode == '130F') {
                        $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('identity_code', CodeFacade::hexToIdentityCode($code))->first(['maintain_station_name', 'identity_code', 'status']);
                        if ($entireInstance == null) {
                            $noExists[] = $code;
                            continue;
                        }
                        $station = Maintain::with([])->where('name', $entireInstance->maintain_station_name)->first();
                        $exists[] = $entireInstance->identity_code;
                        $entireInstanceLogs[] = [
                            'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                            'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                            'name' => '下道',
                            'entire_instance_identity_code' => $entireInstance->identity_code,
                            'type' => 4,
                            'url' => "/search/{$entireInstance->identity_code}",
                            'operator_id' => session('account.id'),
                            'station_unique_code' => @$station->unique_code ?? '',
                        ];
                    } else {
                        $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('rfid_code', $code)->first(['maintain_station_name', 'identity_code', 'status']);
                        if ($entireInstance == null) {
                            $noExists[] = $code;
                            continue;
                        }
                        $station = Maintain::with([])->where('name', $entireInstance->maintain_station_name)->first();
                        $exists[] = $entireInstance->identity_code;
                        $entireInstanceLogs[] = [
                            'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                            'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                            'name' => '下道',
                            'entire_instance_identity_code' => $entireInstance->identity_code,
                            'type' => 4,
                            'url' => "/search/{$entireInstance->identity_code}",
                            'operator_id' => session('account.id'),
                            'station_unique_code' => @$station->unique_code ?? '',
                        ];
                    }
                    break;
            }
        }

        if ($noExists) return HttpResponseHelper::errorForbidden("数据不存在：{$noExists[0]}");

        DB::table('entire_instances')->whereIn('identity_code', $exists)->orWhere('serial_number', $exists)->update(['status' => 'FIXED']);

        EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);
        return HttpResponseHelper::created("下道成功：" . count($exists));
    }

    /**
     * 设备器材入所(唯一编号)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function fixing(Request $request)
    {
        $request = TextHelper::parseJson(@file_get_contents('php://input'));
        DB::transaction(function () use ($request) {
            $serialNumber = CodeFacade::makeSerialNumber('IN');
            # 修改设备器材状态
            DB::table('entire_instances')
                ->where('deleted_at', null)
                ->whereIn('identity_code', $request['data'])
                ->update([
                    'updated_at' => now(),
//                    'maintain_workshop_name' => env('JWT_ISS'),
//                    'crossroad_number' => null,
//                    'source_crossroad_number' => null,
//                    'traction' => null,
//                    'source_traction' => null,
//                    'line_name' => null,
//                    'line_unique_code' => null,
//                    'open_direction' => null,
//                    'said_rod' => null,
//                    'crossroad_type' => null,
//                    'point_switch_group_type' => null,
//                    'extrusion_protect' => null,
//                    'maintain_location_code' => null,
                    'status' => 'FIXING'
                ]);

            # 创建入所单
            $warehouseReport = [
                'created_at' => now(),
                'updated_at' => now(),
                'processor_id' => session('account.id'),
                'processed_at' => now(),
                'connection_name' => $request['connection_name'],
                'connection_phone' => $request['connection_phone'],
                'type' => 'FIXING',
                'direction' => 'IN',
                'serial_number' => $serialNumber,
            ];
            # 插入入所单
            DB::table('warehouse_reports')->insert($warehouseReport);

            # 创建入所单 → 设备
            $warehouseReportEntireInstances = [];
            $entireInstanceLogs = [];
            $identityCodes = DB::table('entire_instances')->whereIn('identity_code', $request['data'])->pluck('identity_code');
            foreach ($identityCodes as $identityCode) {
                # 入所单
                $warehouseReportEntireInstances[] = [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'warehouse_report_serial_number' => $serialNumber,
                    'entire_instance_identity_code' => $identityCode,
                ];

                # 操作日志
                $entireInstanceLogs[] = [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'name' => '入所',
                    'entire_instance_identity_code' => $identityCode,
                    'type' => 1,
                    'url' => "/warehouse/report/{$serialNumber}",
                    'operator_id' => session('account.id'),
                    'station_unique_code' => '',
                ];
            }
            # 插入入所单 → 设备
            DB::table('warehouse_report_entire_instances')->insert($warehouseReportEntireInstances);
            # 插入操作日志
            EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);
        });

        return HttpResponseHelper::created('入所成功：' . count($request['data']));
    }

    /**
     * 出所
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function station(Request $request)
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $request = TextHelper::parseJson(@file_get_contents('php://input'));
        DB::transaction(function () use ($request, $now) {
            $serialNumber = CodeFacade::makeSerialNumber('OUT');

            # 修改设备状态
            DB::table('entire_instances')->where('deleted_at', null)->whereIn('rfid_code', $request['data'])->update([
                'updated_at' => $now,
                'status' => 'TRANSFER_OUT',  # 出所在途
                'maintain_station_name' => $request['maintain_station_name'],
                'last_warehouse_report_serial_number_by_out' => $serialNumber,
                'warehouse_name' => '',
                'location_unique_code' => '',
                'is_bind_location' => 0,
            ]);

            # 创建出所单
            $warehouseReport = [
                'created_at' => $now,
                'updated_at' => $now,
                'processor_id' => session('account.id'),
                'processed_at' => $now,
                'connection_name' => $request['connection_name'],
                'connection_phone' => $request['connection_phone'],
                'type' => 'INSTALL',
                'direction' => 'OUT',
                'serial_number' => $serialNumber,
            ];
            # 插入出所单
            DB::table('warehouse_reports')->insert($warehouseReport);
            $identityCodes = DB::table('entire_instances')->whereIn('rfid_code', $request['data'])->pluck('identity_code');
            # 创建出所单 → 设备
            $warehouseReportEntireInstances = [];
            $entireInstanceLogs = [];
            foreach ($identityCodes as $identityCode) {
                # 出所单
                $warehouseReportEntireInstances[] = [
                    'created_at' => $now,
                    'updated_at' => $now,
                    'warehouse_report_serial_number' => $serialNumber,
                    'entire_instance_identity_code' => $identityCode,
                ];

                # 操作日志
                $entireInstanceLogs[] = [
                    'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
                    'name' => '出所',
                    'entire_instance_identity_code' => $identityCode,
                    'type' => 1,
                    'url' => "/warehouse/report/{$serialNumber}",
                    'operator_id' => session('account.id'),
                    'station_unique_code' => '',
                ];
            }
            # 插入出所单 → 设备
            DB::table('warehouse_report_entire_instances')->insert($warehouseReportEntireInstances);

            # 插入操作记录
            EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);
        });

        return HttpResponseHelper::created('出所成功：' . count($request['data']));
    }

    /**
     * 报废
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function scrap(Request $request)
    {
        $request = TextHelper::parseJson(@file_get_contents('php://input'));

        DB::transaction(function () use ($request) {
            $serialNumber = CodeFacade::makeSerialNumber('OUT');

            # 创建出所单
            $warehouseReport = [
                'created_at' => now(),
                'updated_at' => now(),
                'processor_id' => session('account.id'),
                'processed_at' => now(),
                'type' => 'SCRAP',
                'direction' => 'OUT',
                'serial_number' => $serialNumber,
            ];
            # 插入出所单
            DB::table('warehouse_reports')->insert($warehouseReport);

            $identityCodes = [];
            foreach ($request['data'] as $code) {
                switch (strlen($code)) {
                    case 14:
                    case 19:
                        $identityCodes[] = $code;
                        break;
                    case 24:
                        $firstCode = substr($code, 0, 4);
                        if ($firstCode == '130E' && $firstCode == '130F') {
                            $identityCodes[] = CodeFacade::hexToIdentityCode($code);
                        } else {
                            $entireInstance = DB::table('entire_instances as ei')->where('rfid_code', $code)->first(['identity_code']);
                            $identityCodes[] = $entireInstance->identity_code;
                        }
                        break;
                }
            }

            # 创建出所单 → 设备
            $warehouseReportEntireInstances = [];
            $entireInstanceLogs = [];
            foreach ($identityCodes as $identityCode) {
                # 出所单
                $warehouseReportEntireInstances[] = [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'warehouse_report_serial_number' => $serialNumber,
                    'entire_instance_identity_code' => $identityCode,
                ];

                # 操作日志
                $entireInstanceLogs[] = [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'name' => '报废',
                    'entire_instance_identity_code' => $identityCode,
                    'type' => 1,
                    'url' => "/warehouse/report/{$serialNumber}",
                    'operator_id' => session('account.id'),
                    'station_unique_code' => '',
                ];
            }
            # 插入出所单 → 设备
            DB::table('warehouse_report_entire_instances')->insert($warehouseReportEntireInstances);

            # 插入操作记录
            EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);

            # 修改设备状态
            DB::table('entire_instances')
                ->where('deleted_at', null)
                ->whereIn('identity_code', $identityCodes)
                ->update([
                    'updated_at' => now(),
                    'status' => 'SCRAP',
                    'warehouse_name' => '废品区',
                    'last_warehouse_report_serial_number_by_out' => $serialNumber,
                ]);
        });

        return HttpResponseHelper::created('报废成功：' . count($request['data']));
    }

}
