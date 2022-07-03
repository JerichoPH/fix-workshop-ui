<?php

namespace App\Http\Controllers\RepairBase;

use App\Exceptions\EmptyException;
use App\Exceptions\ValidateException;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\BreakdownType;
use App\Model\Line;
use App\Model\Maintain;
use App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes;
use App\Model\RepairBaseBreakdownOrderTempEntireInstance;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BreakdownOrderTempEntireInstanceController extends Controller
{
    /**
     * 编辑故障修待入所器材信息
     * @param Request $request
     * @param int $id
     * @return mixed
     * @throws EmptyException
     * @throws ValidateException
     * @throws Throwable
     */
    final public function Put(Request $request, int $id)
    {
        try {
            if (!$repair_base_breakdown_order_temp_entire_instance = RepairBaseBreakdownOrderTempEntireInstance::with([])
                ->where("id", $id)
                ->first())
                throw new EmptyException("没有找到器材");

            /**
             * 处理入所故障
             * @throws Throwable
             */
            $processBreakdownType = function () use ($request, &$repair_base_breakdown_order_temp_entire_instance, $id) {
                // 处理入所故障
                $breakdown_type_ids = $request->get("breakdown_type_ids", []) ?? [];
                $breakdown_type_ids = BreakdownType::with([])
                        ->whereIn("id", $breakdown_type_ids)
                        ->pluck("id") ?? collect([]);

                // 清空原有绑定关系
                PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes::with([])
                    ->where("repair_base_breakdown_order_temp_entire_instance_id", $id)
                    ->delete();
                if ($breakdown_type_ids->isNotEmpty()) {
                    $breakdown_type_ids->each(function ($breakdown_type_id) use ($id) {
                        PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes::with([])
                            ->create([
                                "repair_base_breakdown_order_temp_entire_instance_id" => $id,
                                "breakdown_type_id" => $breakdown_type_id,
                            ]);
                    });
                }

                // 保存入所故障备注
                $repair_base_breakdown_order_temp_entire_instance->warehouse_in_breakdown_note = @$request->get("warehouse_in_breakdown_note") ?? null;
            };

            /**
             * 处理来源
             * @throws EmptyException
             * @throws ValidateException
             * @throws Throwable
             */
            $processSource = function () use ($request, &$repair_base_breakdown_order_temp_entire_instance) {
                if (
                    !($request->get("workshop_unique_code", "") ?: "")
                    && !($request->get("station_unique_code", "") ?: "")
                    && !($request->get("line_unique_code", "") ?: "")
                )
                    throw new ValidateException("车间、车站、线别必选其中之一");

                if ($request->get("workshop_unique_code")) {
                    if (!Maintain::with([])->where("type", "SCENE_WORKSHOP")->where("unique_code", $request->get("workshop_unique_code"))->exists()) {
                        throw new EmptyException("车间不存在");
                    }
                }
                if ($request->get("station_unique_code")) {
                    if (!Maintain::with([])->where("type", "STATION")->where("unique_code", $request->get("station_unique_code"))->exists()) {
                        throw new EmptyException("车站不存在");
                    }
                }
                if ($request->get("line_unique_code")) {
                    if (!Line::with([])->where("unique_code", $request->get("line_unique_code"))->exists()) {
                        throw new EmptyException("线别不存在");
                    }
                }

                $repair_base_breakdown_order_temp_entire_instance
                    ->fill([
                        "workshop_unique_code" => $request->get("workshop_unique_code"),
                        "station_unique_code" => $request->get("station_unique_code"),
                        "line_unique_code" => $request->get("line_unique_code"),
                        "maintain_location_code" => "",
                    ]);
            };

            /**
             * 处理现场故障描述
             * @throws Throwable
             * @throws ValidateException
             */
            $processStationBreakdown = function () use ($request, &$repair_base_breakdown_order_temp_entire_instance) {
                $submitted_date = $request->get("station_breakdown_submitted_date") ?: "";
                // if (empty($submitted_date)) throw new ValidateException("请填写故障发生日期");
                $submitted_time = $request->get("station_breakdown_submitted_time") ?: "";
                // if (empty($submitted_time)) throw new ValidateException("请填写故障发生时间");

                if ($submitted_date && $submitted_time) {
                    try {
                        $submitted_at = Carbon::parse("$submitted_date $submitted_time")->format("Y-m-d H:i:00");
                    } catch (Exception $e) {
                        $submitted_at = null;
                        // throw new ValidateException("故障发生日期或时间格式不正确");
                    }
                } else {
                    $submitted_at = null;
                }

                $repair_base_breakdown_order_temp_entire_instance
                    ->fill([
                        "station_breakdown_submitter_name" => @$request->get("station_breakdown_submitter_name", "") ?: "",
                        "station_breakdown_explain" => @$request->get("station_breakdown_explain", "") ?? null,
                        "station_breakdown_submitted_at" => @$submitted_at ?? null,
                    ]);
            };

            DB::beginTransaction();
            $processBreakdownType();
            $processSource();
            $processStationBreakdown();
            $repair_base_breakdown_order_temp_entire_instance->saveOrFail();
            DB::commit();

            return JsonResponseFacade::updated(["entire_instance" => $repair_base_breakdown_order_temp_entire_instance,], "保存成功");
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
