<?php

namespace App\Http\Controllers\Entire;

use App\Exceptions\EmptyException;
use App\Exceptions\ValidateException;
use App\Facades\CodeFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\ScrapTempEntireInstance;
use Exception;
use Illuminate\Http\Request;

class ScrapTempEntireInstanceController extends Controller
{
    /**
     * 获取待报废列表
     * @return mixed
     */
    final public function index()
    {
        $scrap_temp_entire_instances = (new ScrapTempEntireInstance)
            ->ReadMany(["processor_id"])
            ->with(["EntireInstance"])
            ->when(
                request("processor_id"),
                function ($query, $processor_id) {
                    $query->where("processor_id", $processor_id);
                }
            )
            ->get();
        if ($scrap_temp_entire_instances->isNotEmpty()) {
            $scrap_temp_entire_instances->map(function ($datum) {
                $datum->EntireInstance["full_kind_name"] = $datum->EntireInstance->full_kind_name;
                return $datum;
            });
        }
        return JsonResponseFacade::dict(["scrap_temp_entire_instances" => $scrap_temp_entire_instances,]);
    }

    /**
     * 扫码保存待报废器材
     * @param Request $request
     * @return mixed
     * @throws EmptyException
     * @throws ValidateException
     */
    final public function store(Request $request)
    {
        $code = $request->get("identity_code");
        if (CodeFacade::isIdentityCode($code)) {
            $entire_instance = EntireInstance::with([])->where("identity_code", $code)->first();
            if (!$entire_instance) throw new EmptyException("器材不存在或已经被报废");
            $entire_instances = collect([$entire_instance]);
        } else {
            $entire_instances = EntireInstance::with([])->where("serial_number", $code)->get();
            if (!$entire_instances) throw new EmptyException("器材不存在或已经被报废");
        }

        $entire_instances->each(function ($entire_instance) {
            if ($scrap_temp_entire_instance = (new ScrapTempEntireInstance)->with(["Processor"])->where("entire_instance_identity_code", $entire_instance->identity_code)->first()) {
                throw new ValidateException("该器材已经被：{$scrap_temp_entire_instance->Processor->nickname}扫码");
            }
        });

        $entire_instances->each(function ($entire_instance) {
            ScrapTempEntireInstance::with([])->Create(
                [
                    "entire_instance_identity_code" => $entire_instance->identity_code,
                    "processor_id" => session("account.id"),
                ]
            );
        });

        return JsonResponseFacade::created([], "成功添加：" . $entire_instances->count() . "台器材");
    }

    /**
     * @throws Exception
     */
    final public function destroy(string $identity_code)
    {
        $scrap_temp_entire_instance = ScrapTempEntireInstance::with([])->where("entire_instance_identity_code", $identity_code)->where("processor_id", session("account.id"))->first();
        $scrap_temp_entire_instance->delete();
        return JsonResponseFacade::deleted();
    }
}
