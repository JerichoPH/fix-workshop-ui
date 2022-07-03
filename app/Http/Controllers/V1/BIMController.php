<?php

namespace App\Http\Controllers\V1;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Throwable;

class BIMController extends Controller
{
    /**
     * 获取设备列表
     * @return JsonResponse
     */
    final public function getEntireInstances(): JsonResponse
    {
        try {
            if (!request('station_name')) return JsonResponseFacade::errorValidate('车站必填');
            // if (!request('crossroad_number')) return JsonResponseFacade::errorValidate('道岔号必填');

            $entire_instances = EntireInstance::with([])
                ->select([
                    'identity_code',
                    'serial_number',
                    'crossroad_number',
                    'open_direction',
                    'maintain_station_name',
                    'category_name',
                    'model_name',
                    'installed_at',
                    'fixer_name',
                    'fixed_at',
                    'checker_name',
                    'checked_at',
                    'status',
                ])
                ->where('maintain_station_name', request('station_name'))
                // ->where('crossroad_number', request('crossroad_number'))
                ->when(
                    request('open_direction'),
                    function ($query, $open_direction) {
                        $query->where('open_direction', $open_direction);
                    }
                )
                ->where('id', '<', 5520)
                ->get();

            return JsonResponseFacade::data(['entire_instances' => $entire_instances]);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    public function getEntireInstance(string $identity_code): JsonResponse
    {
        try {
            $entire_instance = EntireInstance::with([
                'PartInstances',
                'PartInstances.PartCategory',
                'PartInstances.PartModel',
            ])
                ->select([
                    'identity_code',
                    'serial_number',
                    'crossroad_number',
                    'open_direction',
                    'maintain_station_name',
                    'category_name',
                    'model_name',
                    'installed_at',
                    'fixer_name',
                    'fixed_at',
                    'checker_name',
                    'checked_at',
                ])
                ->where('id', '<', 5520)
                ->firstOrFail();

            return JsonResponseFacade::data(['entire_instance' => $entire_instance]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    final public function getEntireInstanceLogs(string $identity_code): JsonResponse
    {
        try {
            $entire_instance_logs = EntireInstanceLog::with([])
                ->select([
                    'created_at',
                    'name',
                    'description',
                    'entire_instance_identity_code',
                    'type',
                ])
                ->where('entire_instance_identity_code', $identity_code)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            return JsonResponseFacade::data(['entire_instance_logs' => $entire_instance_logs]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
