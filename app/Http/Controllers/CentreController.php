<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\Centre;
use App\Model\Line;
use App\Validations\Web\CentreStoreValidation;
use App\Validations\Web\CentreUpdateValidation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class CentreController extends Controller
{
    /**
     * 列表
     * @return Factory|Application|View
     */
    final public function index()
    {
        $centres = (new Centre)
            ->ReadMany(["line_unique_codes"])
            ->when(request("line_unique_codes"), function ($query, $line_unique_codes) {
                $centre_ids = DB::table("pivot_line_centres as plc")
                    ->select("plc.centre_id")
                    ->join(DB::raw("`lines` l"), "l.id", "=", "plc.line_id")
                    ->whereIn("l.unique_code", $line_unique_codes)
                    ->get()
                    ->pluck("centre_id")
                    ->toArray();
                $query->whereIn("id", $centre_ids);
            });

        if (request()->ajax()) {
            return JsonResponseFacade::dict(['centres' => $centres->get(),]);
        } else {
            return view("Centre.index");
        }
    }

    /**
     * 新建
     * @param Request $request
     * @return mixed
     */
    final public function store(Request $request)
    {
        $validation = (new CentreStoreValidation($request));
        $v = $validation->check()
            ->after(function ($validator) use ($request) {
                if (Centre::with([])->where("unique_code", $request->get("unique_code"))->exists()) {
                    $validator->errors()->add("unique_code", "中心代码重复");
                }
                if (Centre::with([])->where("name", $request->get("name"))->exists()) {
                    $validator->errors()->add("name", "中心名称重复");
                }
            });
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

        $validated = $validation->validated();

        $centre = Centre::with([])->create($validated->toArray());

        // 绑定线别对应关系
        if ($request->get("line_unique_codes")) $centre->bindLines($request->get("line_unique_codes"));

        return JsonResponseFacade::created(['centre' => $centre,]);
    }

    /**
     * @param string $unique_code
     */
    final public function show(string $unique_code)
    {
        return JsonResponseFacade::dict(["centre" => (new Centre)->ReadOneByUniqueCode($unique_code)->first(),]);
    }

    /**
     * 更新
     * @param Request $request
     * @param string $unique_code
     * @return mixed
     * @throws Throwable
     */
    final public function update(Request $request, string $unique_code)
    {
        $centre = Centre::with([])->where("unique_code", $unique_code)->firstOrFail();

        $validation = (new CentreUpdateValidation($request));
        $v = $validation->check()->after(function ($validator) use ($request, $unique_code) {
            if (Centre::with([])
                ->where("unique_code", "<>", $unique_code)
                ->where("name", $request->get("name"))
                ->exists()) {
                $validator->errors()->add("name", "名称重复");
            }
        });
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

        $validated = $validation->validated();

        $centre->fill($validated->toArray())->saveOrFail();

        // 绑定线别对应关系
        if ($request->get("line_unique_codes")) $centre->bindLines($request->get("line_unique_codes"));

        return JsonResponseFacade::updated(["centre" => $centre,]);
    }
}
