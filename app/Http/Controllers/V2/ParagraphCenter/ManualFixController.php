<?php

namespace App\Http\Controllers\V2\ParagraphCenter;

use App\Facades\CodeFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ManualFixController extends Controller
{

    final public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $identity_code = $request->get("material_unique_code", "");
            $data = $request->get("data", "");
            $account_id = $request->get("account_id", "");
            $account = Account::with([])->where("id", $account_id)->first();
            if (!$account) return JsonResponseFacade::errorEmpty("检修人不存在");

            $current_stage = $request->get("stage");
            $is_allow = boolval($request->get("is_allow", 1) ?? 1);
            $business_type = $request->get("business_type", 2);
            if (empty($identity_code) || empty($data) || empty($account_id)) return JsonResponseFacade::errorValidate("唯一编码，检测结果不能为空");

            $entire_instance = EntireInstance::with([])->where("identity_code", $identity_code)->first();
            if (!$entire_instance) return JsonResponseFacade::errorEmpty("器材不存在");

            $current_status = (
                $current_stage == 'CHECKED'
                || $current_stage == 'SPOT_CHECK'
                || $current_stage == 'PROJECT_TEST'
                || $current_stage == 'NEW_TEST'
            )
                ? ($is_allow ? 'FIXED' : 'FIXING')
                : 'FIXING';

            $fix_workflow = FixWorkflow::with([])
                ->create([
                    "entire_instance_identity_code" => $entire_instance->identity_code,
                    "status" => $current_status,
                    "processor_id" => $account_id,
                    "serial_number" => CodeFacade::makeSerialNumber("FIX_WORKFLOW"),
                    "processed_times" => 0,
                    "stage" => $current_stage,
                    "type" => "FIX",
                ]);

            // 保存到文件
            if(!is_dir(public_path("check"))) mkdir(public_path("check"));
            file_put_contents(public_path("check/{$fix_workflow->serial_number}.json"), json_encode($data, 256));

            FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])
                ->create([
                    'fix_workflow_serial_number' => $fix_workflow->serial_number,
                    'stage' => $current_stage,
                    'type' => 'ENTIRE',
                    'auto_explain' => '',
                    'serial_number' => CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS'),
                    'numerical_order' => '1',
                    'is_allow' => intval($is_allow),
                    'processor_id' => $account_id,
                    'processed_at' => now(),
                    'upload_url' => "/check/{$fix_workflow->serial_number}",
                    'check_type' => "PARAGRAPH_CENTER_MANUAL_FIX",
                    'upload_file_name' => "/check/{$fix_workflow->serial_number}.json",
                ]);

            EntireInstanceLog::with([])
                ->create([
                    'name' => FixWorkflowProcess::$STAGE[$current_stage],
                    'description' => "操作人：{$account->nickname}",
                    'entire_instance_identity_code' => $fix_workflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fix_workflow->serial_number}/edit",
                    'operator_id' => $account->id,
                    'station_unique_code' => '',
                ]);

            $entire_instance->fill(["last_fix_workflow_at" => now(), "fix_workflow_serial_number" => $fix_workflow->serial_number,]);
            if ($is_allow) {
                if (in_array($current_stage, ["CHECKED", "SPOT_CHECK", "PROJECT_TEST", "NEW_TEST",])) {
                    $entire_instance->fill(["checker_name" => $account->nickname, "checked_at" => now(),]);
                } else {
                    $entire_instance->fill(["fixer_name" => $account->nickname, "fixed_at" => now(), "checker_name" => "", "checked_at" => "",]);
                }
            }
            $entire_instance->saveOrFail();

            DB::commit();

            return JsonResponseFacade::created(["id" => $fix_workflow->serial_number], "检修成功");
        } catch (Throwable $e) {
            DB::rollBack();
            return JsonResponseFacade::errorException($e);
        }
    }

    final public function show(string $serial_number)
    {
        $fix_workflow = FixWorkflow::with([])->where("serial_number", $serial_number)->first();
        if (!$fix_workflow) return JsonResponseFacade::errorEmpty("检修记录不存在");
        if (!is_file(public_path("/check/{$fix_workflow->serial_number}.json")))
            return JsonResponseFacade::errorEmpty();

        $file = json_decode(file_get_contents(public_path("/check/{$fix_workflow->serial_number}.json")), true);
        return JsonResponseFacade::dict(["data" => $file,]);
    }
}
