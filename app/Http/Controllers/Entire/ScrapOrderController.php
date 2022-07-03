<?php

namespace App\Http\Controllers\Entire;

use App\Exceptions\EmptyException;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\ScrapTempEntireInstance;
use App\Model\Warehouse;
use App\Model\WarehouseMaterial;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use stdClass;

class ScrapOrderController extends Controller
{
    /**
     * 报废单列表
     * @return Factory|Application|View
     */
    final public function index()
    {
        if (request()->ajax()) {
            $scrap_orders = (new Warehouse)
                ->ReadMany()
                ->with(["WithAccount"])
                ->where("direction", "SCRAP")
                ->when(
                    request("use_made_at") == 1,
                    function ($query) {
                        if (!empty(request("updated_at"))) {
                            $tmp_updated_at = explode("~", request("updated_at"));
                            $tmp_left = Carbon::createFromFormat("Y-m-d", $tmp_updated_at[0])->startOfDay()->format("Y-m-d H:i:s");
                            $tmp_right = Carbon::createFromFormat("Y-m-d", $tmp_updated_at[1])->endOfDay()->format("Y-m-d H:i:s");
                            $query->whereBetween("updated_at", [$tmp_left, $tmp_right]);
                        }
                    }
                )
                ->orderBy("updated_at", "desc")
                ->get()
                ->map(function ($datum) {
                    $datum["original_state"] = $datum->prototype("state");
                    return $datum;
                });
            return JsonResponseFacade::dict(["scrap_orders" => $scrap_orders,]);
        } else {
            return view("Entire.ScrapOrder.index");
        }
    }

    /**
     * 创建报废页面
     * @return Factory|Application|View
     */
    final public function create()
    {
        return view("Entire.ScrapOrder.create");
    }

    /**
     * @throws EmptyException
     */
    final public function store(Request $request)
    {
        $scrap_temp_entire_instances = ScrapTempEntireInstance::with(["EntireInstance"])->where("processor_id", session("account.id"))->get();
        if ($scrap_temp_entire_instances->isEmpty()) {
            throw new EmptyException("请扫码添加待报废器材");
        }

        $res = DB::transaction(function () use ($scrap_temp_entire_instances): stdClass {
            $scrap_order = Warehouse::with([])->create([
                "state" => "END",
                "unique_code" => Warehouse::generateUniqueCode("SCRAP"),
                "direction" => "SCRAP",
                "account_id" => session("account.id"),
            ]);

            $count = 0;

            $scrap_temp_entire_instances->each(function ($scrap_temp_entire_instance) use ($scrap_order, &$count) {
                $entire_instance = $scrap_temp_entire_instance->EntireInstance;
                if (!$entire_instance) return null;
                $entire_instance
                    ->fill([
                        "status" => "SCRAP",
                        // "location_unique_code" => "",
                        // "warehousein_at" => null,
                        // "in_warehouse_time" => null,
                        // "maintain_station_name" => "",
                        // "line_unique_code" => "",
                        // "last_out_at" => null,
                        // "last_installed_time" => 0,
                        // "maintain_location_code" => "",
                        // "crossroad_number" => "",
                        // "open_direction" => "",
                        // "next_fixing_time" => 0,
                        // "next_fixing_day" => null,
                        // "next_fixing_month" => null,
                        // "fixer_name" => "",
                        // "fixed_at" => null,
                        // "checker_name" => "",
                        // "checked_at" => null,
                        // "spot_fixer_name" => "",
                        // "spot_fixed_at" => null,
                    ])
                    ->saveOrFail();
                WarehouseMaterial::with([])
                    ->create([
                        'material_unique_code' => $entire_instance->identity_code,
                        'warehouse_unique_code' => $scrap_order->unique_code,
                        'material_type' => 'ENTIRE'
                    ]);

                EntireInstanceLogFacade::makeOne(
                    session("account.id"),
                    "",
                    "报废",
                    $entire_instance->identity_code,
                    0,
                    "/storehouse/index/{$scrap_order->id}",
                    "经办人：" . session('account.nickname')
                );

                $scrap_temp_entire_instance->delete();

                $count++;
            });

            return (object)["count" => $count, "url" => "/storehouse/index/{$scrap_order->id}"];
        });

        return JsonResponseFacade::deleted(["url" => $res->url,], "成功报废：{$res->count}台");
    }

    final public function edit()
    {

    }
}
