<?php

namespace App\Http\Controllers\V2\Breakdown;

use App\Exceptions\EmptyException;
use App\Exceptions\ValidateException;
use App\Facades\EntireInstanceFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Validations\Api\V2\BreakdownInstallValidation;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Throwable;

class PostController extends Controller
{
    /**
     * 上报现场故障描述信息
     * @param Request $request
     * @param string $entireInstanceIdentityCode
     * @return mixed
     * @throws Throwable
     */
    final public function PostSceneBreakdownDescription(Request $request, string $entireInstanceIdentityCode)
    {
        [
            "submitter_name" => $submitterName,
            "submitted_at" => $submittedAt,
            "breakdown_description" => $breakdownDescription,
        ] = $request->all();

        $this->writeSceneBreakdownDescription($entireInstanceIdentityCode, $submitterName, $submittedAt, $breakdownDescription);

        return JsonResponseFacade::updated([], "上报成功");
    }

    /**
     * 写入现场故障描述
     * @param string $entireInstanceIdentityCode
     * @param string $submitterName
     * @param string $submittedAt
     * @param string $breakdownDescription
     * @throws EmptyException
     * @throws Throwable
     * @throws ValidateException
     */
    final public function writeSceneBreakdownDescription(string $entireInstanceIdentityCode, string $submitterName, string $submittedAt, string $breakdownDescription)
    {
        if (!$submitterName) throw new ValidateException("上报人不能为空");
        if (!$submittedAt) throw new ValidateException("故障发生时间不能为空");
        try {
            $submittedAt = Carbon::parse($submittedAt)->format("Y-m-d H:i:s");
        } catch (Exception $e) {
            throw new ValidateException("上报时间格式不正确");
        }
        if (!$breakdownDescription) throw new ValidateException("故障描述不可为空");

        $entireInstance = EntireInstance::with([])->where("identity_code", $entireInstanceIdentityCode)->first();
        if (!$entireInstance) throw new EmptyException("器材不存在");

        $entireInstance->last_scene_breakdown_description = json_encode([
            "submitted_at" => $submittedAt,
            "submitted_name" => $submitterName,
            "breakdown_description" => $breakdownDescription,
        ], 256);
        $entireInstance->saveOrFail();
    }

    /**
     * 故障上道
     * @param Request $request
     * @throws ValidateException
     * @throws EmptyException
     * @throws Throwable
     */
    final public function PostInstall(Request $request)
    {
        $validation = new BreakdownInstallValidation($request);
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
                "name" => "故障下道",
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

        // 记录现场故障描述
        $this->writeSceneBreakdownDescription(
            $old_entire_instance->identity_code,
            session("account.nickname"),
            now()->toDateTimeString(),
            $request->get("breakdown_description", "")
        );

        return JsonResponseFacade::updated([], "上道成功");
    }

    /**
     * 故障下道
     * @param Request $request
     * @throws EmptyException
     * @throws Throwable
     * @throws ValidateException
     */
    final public function PostUnInstall(Request $request)
    {
    }
}
