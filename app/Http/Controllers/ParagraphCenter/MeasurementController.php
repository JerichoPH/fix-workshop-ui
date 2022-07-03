<?php

namespace App\Http\Controllers\ParagraphCenter;

use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\ParagraphCenter\ParagraphCenterMeasurement;
use App\Model\ParagraphCenter\ParagraphCenterMeasurementStep;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeasurementController extends Controller
{
    /**
     * 列表
     * @return Factory|Application|View
     */
    final public function index()
    {
        if (request()->ajax()) {
            $paragraph_center_measurements = (new ParagraphCenterMeasurement)
                ->ReadMany(["name",])
                ->with(["Account",])
                ->whereHas(
                    "Account",
                    function ($Account) {
                        $Account->where("work_area_unique_code", session("account.work_area_unique_code"));
                    }
                )
                ->when(
                    request("name"),
                    function ($query, $name) {
                        $query->where("name", "like", "%{$name}%");
                    }
                );
            return JsonResponseFacade::dict(["paragraph_center_measurements" => $paragraph_center_measurements->get(),]);
        } else {
            return view("ParagraphCenter.Measurement.index");
        }
    }

    /**
     * 新建页面
     * @return Factory|Application|View
     */
    final public function create()
    {
        return view("ParagraphCenter.Measurement.create");
    }

    /**
     * 编辑页面
     * @param string $serial_number
     * @return Factory|Application|View
     */
    final public function edit(string $serial_number)
    {
        $paragraph_center_measurement = ParagraphCenterMeasurement::with([])->where("serial_number", $serial_number)->firstOrFail();
        return view("ParagraphCenter.Measurement.edit", ["paragraph_center_measurement" => $paragraph_center_measurement,]);
    }

    /**
     * 删除
     */
    final public function destroy(string $serial_number)
    {
        $paragraph_center_measurement = ParagraphCenterMeasurement::with([])->where("serial_number", $serial_number)->firstOrFail();
        $paragraph_center_measurement->delete();
        ParagraphCenterMeasurementStep::where("paragraph_center_measurement_sn",$serial_number)->delete();
        return JsonResponseFacade::deleted([], "删除成功");
    }
}
