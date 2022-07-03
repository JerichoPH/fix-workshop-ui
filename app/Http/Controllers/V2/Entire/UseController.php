<?php

namespace App\Http\Controllers\V2\Entire;

use App\Exceptions\EmptyException;
use App\Exceptions\ValidateException;
use App\Facades\EntireInstanceFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Validations\Api\V2\FixCycleInstallValidation;
use Illuminate\Http\Request;
use Throwable;

class UseController extends Controller
{
    /**
     * 周期修上道
     * @throws Throwable
     * @throws ValidateException
     */
    public function FixCycleInstall(Request $request)
    {
        $validation = new FixCycleInstallValidation($request);
        $v = $validation->check();
        if ($v->fails()) throw new ValidateException($v->errors()->first());

        $old_entire_instance_identity_code = $request->get("old_entire_instance_identity_code");
        $new_entire_instance_identity_code = $request->get("new_entire_instance_identity_code");

        $old_entire_instance = EntireInstance::with(["Station"])->where("identity_code", $old_entire_instance_identity_code)->first();
        if (!$old_entire_instance) throw new EmptyException("下道器材不存在");

        $new_entire_instance = EntireInstance::with(["Station"])->where("identity_code", $new_entire_instance_identity_code)->first();
        if (!$new_entire_instance) throw new EmptyException("上道器材不存在");

        // 下道器材日志
        EntireInstanceLog::with([])
            ->create([
                "created_at" => now(),
                "updated_at" => now(),
                "name" => "周期修下道",
                "description" => implode("；", [
                    "{$old_entire_instance->identity_code}被{$new_entire_instance->identity_code}更换",
                    "位置：$old_entire_instance->use_position_name",
                    "操作人：" . session("account.nickname"),
                ]),
                "entire_instance_identity_code" => $old_entire_instance->identity_code,
                "type" => 4,
                "url" => "",
                "operator_id" => session("account.id"),
                "station_unique_code" => @$old_entire_instance->Station->unique_code ?? "",
            ]);
        // 上道器材日志
        EntireInstanceLog::with([])
            ->create([
                "created_at" => now(),
                "updated_at" => now(),
                "name" => "上道",
                "description" => implode("；", [
                    "{$new_entire_instance->identity_code}更换$old_entire_instance->identity_code",
                    "位置：$old_entire_instance->use_position_name",
                    "操作人：" . session("account.nickname")
                ]),
                "entire_instance_identity_code" => $new_entire_instance->identity_code,
                "type" => 4,
                "url" => "",
                "operator_id" => session("account.id"),
                "station_unique_code" => @$old_entire_instance->Station->unique_code ?? "",
            ]);

        // 交换器材上道位置
        [
            "old_entire_instance" => $old_entire_instance,
            "new_entire_instance" => $new_entire_instance
        ] = EntireInstanceFacade::changeUsePosition($old_entire_instance, $new_entire_instance);
        $new_entire_instance->saveOrFail();
        $old_entire_instance->saveOrFail();

        return JsonResponseFacade::updated([], "上道成功");
    }
}
