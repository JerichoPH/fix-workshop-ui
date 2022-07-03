<?php

namespace App\Http\Controllers\V2\ParagraphCenter;

use App\Facades\CodeFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\ParagraphCenter\ParagraphCenterMeasurement;
use App\Model\ParagraphCenter\ParagraphCenterMeasurementStep;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MeasurementController extends Controller
{
    /**
     * 保存检修模板
     */
    final public function store(Request $request)
    {
        try {
            $category_unique_code = $request->get("category_unique_code", "");
            $entire_model_unique_code = $request->get("entire_model_unique_code", "");
            $sub_model_unique_code = $request->get("sub_model_unique_code", "");
            $account_id = $request->get("account_id", 0);
            $business_type = $request->get("business_type", 2);
            $type = $request->get("type", 0);
            $name = $request->get("name", "");
            $data = $request->get("data", []);

            if (ParagraphCenterMeasurement::with([])->where("name", $name)->exists())
                return JsonResponseFacade::errorValidate("名称重复");
            if ($business_type == 2) {
                if (empty($category_unique_code)) return JsonResponseFacade::errorValidate("检修模板种类不存在");
                if (empty($entire_model_unique_code)) return JsonResponseFacade::errorValidate("检修模板类型不存在");
            }
            if (empty($data)) return JsonResponseFacade::errorValidate("检修模板项目不存在");
            if (!Account::with([])->where("id", $account_id)->exists())
                return JsonResponseFacade::errorValidate("用户不存在");

            DB::beginTransaction();
            $paragraph_center_measurement = ParagraphCenterMeasurement::with([])->create([
                "account_id" => $account_id,
                "serial_number" => CodeFacade::makeSerialNumber("PARAGRAPH_CENTER_MEASUREMENT"),
                "name" => $name,
                "type" => $type,
                "business_type" => $business_type,
            ]);
            $k = 0;
            collect($data)->each(function ($datum) use ($paragraph_center_measurement, &$k) {
                ParagraphCenterMeasurementStep::with([])->create([
                    "uuid" => @$datum["uuid"] ?: "",
                    "paragraph_center_measurement_sn" => $paragraph_center_measurement->serial_number,
                    "sort" => $k + 1,
                    "data" => $datum["data"],
                ]);
            });
            DB::commit();
            return JsonResponseFacade::created(["id" => $paragraph_center_measurement->id,], "检修模板创建成功");
        } catch (Exception $e) {
            DB::rollBack();
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 编辑检修模板
     * @param Request $request
     * @param string $serial_number
     * @return mixed
     * @throws Throwable
     */
    final public function update(Request $request, string $serial_number)
    {
        $paragraph_center_measurement = ParagraphCenterMeasurement::with([])->where("serial_number", $serial_number)->firstOrFail();
        if ($name = $request->get("name", "") ?? "") {
            $paragraph_center_measurement->fill(["name" => $name,])->saveOrFail();
        }
        $data = $request->get("data", []);
        if (empty($data)) return JsonResponseFacade::errorValidate("检修模板步骤不存在");
        foreach ($data as $k => $datum) {
            $uuid = $datum["uuid"] ?? "";
            DB::table("paragraph_center_measurement_steps")
                ->updateOrInsert(
                    [
                        "paragraph_center_measurement_sn" => $paragraph_center_measurement->serial_number,
                        "uuid" => $uuid,
                    ],
                    [
                        "updated_at" => now(),
                        "uuid" => $uuid,
                        "paragraph_center_measurement_sn" => $paragraph_center_measurement->serial_number,
                        "sort" => $k + 1,
                        "data" => $datum["data"],
                    ]
                );
        }

        return JsonResponseFacade::updated(['id' => $paragraph_center_measurement->id], "检修模板编辑成功");
    }
}
