<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\EntireInstanceUseReport;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class UnInstallController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            return view("UnInstall.index");
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 扫码搜索设备器材
     * @param Request $request
     */
    final public function postScan(Request $request)
    {
        try {
            $entire_instance = EntireInstance::with([
                "SubModel",
                "PartModel",
                "InstallPosition",
                "EntireInstanceLock",
            ])
                ->where("identity_code", $request->get("identity_code"))
                ->firstOrFail();

            // 检查设备器材是否带锁 @todo 暂不验证
            if ($entire_instance->EntireInstanceLock)
                return JsonResponseFacade::errorForbidden($entire_instance->EntireInstanceLock->remark ?: "设备器材：{$entire_instance->identity_code}在其他任务中被占用");

            // 检查设备器材是否可以下道
            if ($entire_instance->can_i_un_install !== true)
                return JsonResponseFacade::errorForbidden($entire_instance->can_i_un_install);

            $entire_instance["use_position_name"] = $entire_instance->use_position_name;
            $entire_instance["full_kind_name"] = $entire_instance->full_kind_name;

            return JsonResponseFacade::data(["entire_instance" => $entire_instance,]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
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
            DB::beginTransaction();

            $count = 0;
            EntireInstance::with([
                "Station",
                "Station.Parent",
                "InstallPosition",
                "EntireInstanceLock",
            ])
                ->whereIn("identity_code", $request->get("identity_codes"))
                ->chunk(30, function (Collection $entire_instances) use (&$count) {
                    $entire_instances->each(function (EntireInstance $entire_instance) use (&$count) {
                        // 下道日志
                        EntireInstanceLog::with([])
                            ->create([
                                "created_at" => now(),
                                "updated_at" => now(),
                                "name" => "下道",
                                "description" => implode("；", [
                                    "位置：" . $entire_instance->use_position_name,
                                    "操作人：" . session("account.nickname") ?? "无",
                                ]),
                                "entire_instance_identity_code" => $entire_instance->identity_code,
                                "type" => 4,
                                "url" => "",
                                "operator_id" => session("account.id"),
                                "station_unique_code" => @$entire_instance->Station->unique_code ?? "",
                            ]);

                        // 下道 （继承器材上道位置） 整件
                        $entire_instance->FillInheritInstallPositionForUnInstall(["status" => "TRANSFER_IN",])->saveOrFail();
                        // 下道 （继承器材上道位置） 部件
                        if(!empty($entire_instance->PartInstances)){
                            $entire_instance->PartInstances->each(function(EntireInstance $part_instance){
                                $part_instance->FillInheritInstallPositionForUnInstall(["status"=>"TRANSFER_IN",])->saveOrFail();
                            });
                        }

                        // 记录下道设备器材
                        EntireInstanceUseReport::with([])->create([
                            "id" => EntireInstanceUseReport::generateId(),
                            "entire_instance_identity_code" => $entire_instance->identity_code,
                            "scene_workshop_unique_code" => @$entire_instance->Station->Parent->unique_code ?? "",
                            "maintain_station_unique_code" => @$entire_instance->Station->unique_code ?? "",
                            "maintain_location_code" => @$entire_instance->maintain_location_code ?: "",
                            "processor_id" => session("account.id"),
                            "crossroad_number" => $entire_instance->crossroad_number ?: "",
                            "open_direction" => $entire_instance->open_direction ?: "",
                            "maintain_section_name" => $entire_instance->maintain_section_name ?: "",
                            "maintain_send_or_receive" => $entire_instance->maintain_send_or_receive ?: "",
                            "maintain_signal_post_main_or_indicator_code" => $entire_instance->maintain_signal_post_main_or_indicator_code ?: "",
                            "maintain_signal_post_main_light_position_code" => $entire_instance->maintain_signal_post_main_light_position_code ?: "",
                            "maintain_signal_post_indicator_light_position_code" => $entire_instance->maintain_signal_post_indicator_light_position_code ?: "",
                            "type" => "UNINSTALL",
                            "status" => "DONE",
                        ]);
                    });
                });

            DB::commit();
            return JsonResponseFacade::created([], "下道成功");
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
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
