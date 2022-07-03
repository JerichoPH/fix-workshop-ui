<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class BindSerialNumberController extends Controller
{
    /**
     * 列表
     * @return Factory|Application|View
     */
    final public function index()
    {
        if (request()->ajax()) {
            return JsonResponseFacade::dict([
                "data" => DB::table("bind_serial_numbers")
                    ->whereBetween("created_at", [now()->startOfDay(), now()->endOfDay()])
                    ->orderByDesc("id")
                    ->get(),
            ]);
        } else {
            return view("BindSerialNumber.index");
        }
    }

    /**
     * 绑定唯一编号和所编号
     * @throws Throwable
     */
    final public function store(Request $request)
    {
        $identity_code = $request->get("identity_code", "") ?? "";
        $serial_number = $request->get("serial_number", "") ?? "";

        if (empty($identity_code)) return JsonResponseFacade::errorValidate("唯一编号不能为空");
        if (empty($identity_code)) return JsonResponseFacade::errorValidate("所编号不能为空");

        if (EntireInstance::with([])->where("serial_number", $serial_number)->exists()) return JsonResponseFacade::errorForbidden("所编号已经被绑定过");

        $entire_instance = EntireInstance::with([])->where("identity_code", $identity_code)->first();
        if (!$entire_instance) return JsonResponseFacade::errorForbidden("唯一编号不存在或已被删除、报废");
        if (!empty($entire_instance->serial_number)) return JsonResponseFacade::errorForbidden("当前器材已经绑定过所编号");

        $entire_instance->fill(["serial_number" => $serial_number])->saveOrFail();

        DB::table("bind_serial_numbers")->insert([
            "created_at" => now(),
            "updated_at" => now(),
            "identity_code" => $entire_instance->identity_code,
            "serial_number" => $entire_instance->serial_number,
            "category_name" => $entire_instance->Category->name,
            "entire_model_name" => @$entire_instance->SubModel->Parent ? @$entire_instance->SubModel->Parent->name : $entire_instance->SubModel->name,
            "sub_model_name" => @$entire_instance->SubModel->Parent ? $entire_instance->SubModel->name : "",
            "processor_nickname" => session("account.nickname"),
        ]);

        return JsonResponseFacade::ok("绑定成功");
    }
}
