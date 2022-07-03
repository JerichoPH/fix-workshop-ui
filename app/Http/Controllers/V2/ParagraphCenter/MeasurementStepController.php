<?php

namespace App\Http\Controllers\V2\ParagraphCenter;

use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\ParagraphCenter\ParagraphCenterMeasurementStep;
use Exception;

class MeasurementStepController extends Controller
{
    /**
     * 列表
     * @return mixed
     */
    final public function index()
    {
        try {
            try {
                $paragraph_center_measurement_steps = (new ParagraphCenterMeasurementStep)
                    ->ReadMany(["overhaul_template_id",])
                    ->when(
                        request("overhaul_template_id"),
                        function ($query, $paragraph_center_measurement_sn) {
                            $query->where("paragraph_center_measurement_sn", $paragraph_center_measurement_sn);
                        }
                    )
                    ->get();
                return JsonResponseFacade::dict($paragraph_center_measurement_steps);
            } catch (Exception $e) {
                return JsonResponseFacade::errorException($e);
            }
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    final public function create()
    {

    }
}
