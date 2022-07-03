<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceAlarmLog;
use App\Model\EntireInstanceLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Throwable;

class EntireInstanceAlarmLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        try {
            $entire_instance_alarm_logs = EntireInstanceAlarmLog::with([
                'EntireInstance',
                'EntireInstance.Category',
                'EntireInstance.SubModel',
                'EntireInstance.SubModel.Parent',
                'EntireInstance.PartModel',
                'EntireInstance.PartModel.Parent',
                'Station',
            ])
                ->orderByDesc('created_at')
                ->where('status', 'WARNING')
                ->get();

            return JsonResponseFacade::dict(['entire_instance_alarm_logs' => $entire_instance_alarm_logs]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
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
        try {
            $entire_instance = EntireInstance::with([
                'Station',
            ])
                ->where('status', '<>', 'SCRAP')
                ->where('identity_code', $request->get('entire_instance_identity_code'))
                ->firstOrFail();

            // add entire instance alarm log
            $entire_instance_alarm_log = EntireInstanceAlarmLog::with([])->create([
                'entire_instance_identity_code' => $entire_instance->identity_code,
                'station_unique_code' => @$entire_instance->Station->unique_code ?? '',
                'alarm_at' => $request->get('alarm_at'),
                'alarm_level' => $request->get('alarm_level'),
                'alarm_content' => $request->get('alarm_content'),
                'alarm_cause' => $request->get('alarm_cause'),
                'msg_id' => '',
                'status' => 'WARNING',
            ]);

            // add entire instance log
            $entire_instance_log = EntireInstanceLog::with([])->create([
                'name' => '报警',
                'description' => implode(',', [
                    '报警级别：' . $request->get('alarm_level'),
                    '报警时间：' . $request->get('alarm_at'),
                    '报警内容：' . $request->get('alarm_content'),
                    '报警原因：' . $request->get('alarm_cause'),
                ]),
                'entire_instance_identity_code' => $entire_instance->identity_code,
                'type' => 5,
                'url' => '',
                'material_type' => 'ENTIRE',
                'operator_id' => session('account.id'),
                'station_unique_code' => @$entire_instance->Station->unique_code ?? '',
            ]);

            return JsonResponseFacade::created([
                'entire_instance_alarm_log' => $entire_instance_alarm_log,
                'entire_instance_log' => $entire_instance_log,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("设备器材：{$request->get('entire_instance_identity_code')}不存在");
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
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
     * manual release
     * @param $id
     */
    final public function putManualRelease($id)
    {
        try {
            $entire_instance_alarm_log = EntireInstanceAlarmLog::with([])->where('id', $id)->firstOrFail();
            $entire_instance_alarm_log->fill(['status' => 'MANUAL_RELEASE'])->saveOrFail();

            // add log for entire instance detail
            EntireInstanceLog::with([])->create([
                'name' => '消除报警',
                'description' => session('account.nickname') . "消除报警",
                'entire_instance_identity_code' => $entire_instance_alarm_log->entire_instance_identity_code,
                'type' => 5,
                'url' => '',
                'material_type' => 'ENTIRE',
                'operator_id' => session('account.id'),
                'station_unique_code' => $entire_instance_alarm_log->station_unique_code,
            ]);

            return JsonResponseFacade::updated(['entire_instance_alarm_log' => $entire_instance_alarm_log]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
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
