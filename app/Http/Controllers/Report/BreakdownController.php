<?php

namespace App\Http\Controllers\Report;

use App\Facades\CommonFacade;
use App\Facades\KindsFacade;
use App\Facades\OrganizationFacade;
use App\Facades\TextFacade;
use App\Http\Controllers\Controller;
use App\Model\BreakdownType;
use App\Model\Install\InstallPosition;
use App\Serializers\BreakdownSerializer;
use Illuminate\Support\Facades\DB;

class BreakdownController extends Controller
{
    final public function index()
    {
        if (request()->ajax()) {
            return $this->__generateStatistics();
        } else {
            $categories = KindsFacade::getCategories([], function ($query) {
                return $query->where("c.is_show", true);
            });
            $entire_models = KindsFacade::getEntireModelsByCategory();
            $sub_models = KindsFacade::getModelsByEntireModel();
            $breakdown_types = BreakdownType::with([])->get();
            $scene_workshops = OrganizationFacade::getSceneWorkshops([], function ($query) {
                return $query->where("sc.is_show", true);
            });
            $stations = OrganizationFacade::getStationsBySceneWorkshop([], function ($query) {
                return $query->where("s.is_show", true);
            });
            $lines = Organizationfacade::getLines([], function ($db) {
                return $db->where("is_show", true);
            });

            return view("Report.Breakdown.index", [
                "categories" => $categories,
                "entire_models" => $entire_models,
                "sub_models" => $sub_models,
                "breakdown_types" => $breakdown_types,
                "scene_workshops" => $scene_workshops,
                "stations" => $stations,
                "lines" => $lines,
            ]);
        }
    }

    /**
     * 获取
     * @return array
     */
    private function __generateStatistics(): array
    {
        $count_with_categories = DB::table("entire_instances as ei")
            ->selectRaw("count(c.unique_code) as aggregate, c.unique_code")
            ->join(DB::raw("entire_models sm"), "ei.model_unique_code", "=", "sm.unique_code")
            ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
            ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
            ->groupBy(["c.unique_code",])
            ->pluck("aggregate", "unique_code")
            ->toArray();

        $breakdown_count_with_sub_models = [];
        $breakdown_count_with_categories = [];

        $breakdown_statistics_query = BreakdownSerializer::INIT()
            ->generateStatisticRelationshipQ()
            ->selectRaw(implode(",", [
                "count(sm.unique_code) as aggregate",  // 型号数量统计
                "DATE_FORMAT(rbboei.created_at, '%Y-%m-%d') as warehouse_return_at",  // 入所日期
                "ei.identity_code",  // 唯一编号
                "ei.serial_number",  // 所编号
                "DATE_FORMAT(ei.made_at, '%Y-%m-%d') as made_at",  // 出厂日期
                "rbboei.fix_duty_officer",  // 返修责任者
                "rbboei.last_fixer_name", // 上一次检修人
                "rbboei.last_checker_name",  // 上一次验收人
                "c.unique_code as category_unique_code",  // 种类代码
                "c.name as category_name",  // 种类名称
                "em.unique_code as entire_model_unique_code",  // 类型代码
                "em.name as entire_model_name",  // 类型名称
                "sm.unique_code as sub_model_unique_code",  // 型号代码
                "sm.name as sub_model_name",  // 型号名称
                "sc.unique_code as scene_workshop_unique_code",  // 车间代码
                "sc.name as scene_workshop_name",  // 车间名称
                "s.unique_code as station_unique_code",  // 车站代码
                "s.name as station_name",  // 车站名称
                "l.unique_code as line_unique_code",  // 线别代码
                "l.name as line_name",  // 线别名称
                "rbboei.breakdown_type_names",  // 故障类型名称
                "ei.factory_name as factory_name",  // 厂家
                "rbboei.maintain_location_code",  // 室内组合位置
                "rbboei.crossroad_number",  // 道岔号
                "rbboei.open_direction",  // 开向
                "rbboei.warehouse_in_breakdown_note", // 入所故障备注
            ]))
            ->groupBy(["c.unique_code", "em.unique_code", "sm.unique_code", "sc.unique_code", "s.unique_code",])
            ->orderByDesc("line_name")
            ->orderByDesc("scene_workshop_name")
            ->orderByDesc("station_name")
            ->get();

        // 上道位置名称
        $install_positions = InstallPosition::with([])
            ->whereIn("unique_code", $breakdown_statistics_query->pluck("maintain_location_code")->unique()->filter()->values()->toArray())
            ->get()
            ->map(function ($install_position) {
                return ["unique_code" => $install_position->unique_code, "real_name" => $install_position->real_name,];
            })
            ->pluck("real_name", "unique_code")
            ->toArray();

        // 运用设备总数（型号）
        $count_with_sub_models = DB::table("entire_instances as ei")
            ->selectRaw("count(sm.unique_code) as aggregate, sm.unique_code as sub_model_unique_code, c.unique_code as category_unique_code")
            ->join(DB::raw("entire_models sm"), "ei.entire_model_unique_code", "=", "sm.unique_code")
            ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
            ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
            ->whereIn("sm.unique_code", @$breakdown_statistics_query->where("sub_model_unique_code", "<>", "")->pluck("sub_model_unique_code")->filter()->unique()->values()->toArray())
            ->groupBy(["sm.unique_code", "c.unique_code"])
            ->get()
            ->pluck("aggregate", "sub_model_unique_code")
            ->toArray();

        $breakdown_statistics_query
            ->each(function ($datum)
            use (
                &$breakdown_count_with_sub_models,
                &$breakdown_count_with_categories
            ) {
                $breakdown_count_with_sub_models = CommonFacade::ShouldBind($datum->sub_model_unique_code, $breakdown_count_with_sub_models, 0);
                $breakdown_count_with_sub_models[$datum->sub_model_unique_code] += 1;

                $breakdown_count_with_categories = CommonFacade::ShouldBind($datum->category_unique_code, $breakdown_count_with_categories, 0);
                $breakdown_count_with_categories[$datum->category_unique_code] += 1;
            });

        $breakdown_statistics_query->map(function ($datum) use (
            $install_positions,
            &$breakdown_count_with_sub_models,
            $count_with_sub_models,
            $count_with_categories,
            $breakdown_count_with_categories
        ) {
            $datum->name = $datum->entire_model_name != $datum->sub_model_name ? $datum->sub_model_name : $datum->entire_model_name;
            $datum->made_at = @$datum->made_at ?: "未填";
            $datum->warehouse_return_at = @$datum->warehouse_return_at ?: "未填";
            $datum->factory_name = @$datum->factory_name ?: "未填";
            $datum->use_position = TextFacade::joinWithNotEmpty(" ", [
                @$datum->line_name ?: "",
                @$datum->scene_workshop_name ?: "",
                @$datum->station_name ?: "",
                @$datum->maintain_location_code ? (@$install_positions[$datum->maintain_location_code] ?: $datum->maintain_location_code) : "",
                @$datum->crossroad_number ?: "",
                @$datum->open_direction ?: "",
            ]) ?: "未填";
            $datum->warehouse_return_reason = @TextFacade::joinWithNotEmpty("&emsp;&emsp;备注：", [@$datum->breakdown_type_names ?: "", @$datum->warehouse_in_breakdown_note ?: "",]) ?: "未填";
            $datum->count_with_sub_model = @$count_with_sub_models[$datum->sub_model_unique_code] ?: 0;
            $datum->breakdown_count_with_sub_model = @$breakdown_count_with_sub_models[$datum->sub_model_unique_code] ?: 0;
            $datum->repair_rate = "-";
            if ($datum->count_with_sub_model > 0 && $datum->breakdown_count_with_sub_model > 0) {
                $datum->repair_rate = (round($datum->breakdown_count_with_sub_model / $datum->count_with_sub_model, 6) * 100) . "%";
            }
            $datum->count_with_category = @$count_with_categories[$datum->category_unique_code] ?: 0;
            $datum->breakdown_count_with_category = @$breakdown_count_with_categories[$datum->category_unique_code] ?: 0;
            if ($datum->count_with_category > 0 && $datum->breakdown_count_with_category > 0) {
                $datum->repair_rate_with_category = (round($datum->breakdown_count_with_category / $datum->count_with_category, 6) * 100) . "%";
            } else {
                $datum->repair_rate_with_cateogry = "0.00%";
            }
            return array_map(function ($value) {
                return @$value ?: "未填";
            }, (array)$datum);
        });

        // $breakdown_statistics = collect($breakdown_statistics_query)->groupBy("sub_model_unique_code")->toArray();
        $breakdown_statistics = $breakdown_statistics_query->groupBy("sub_model_unique_code")->all();
        ksort($breakdown_statistics);  // 根据型号代码进行排序

        // *** 非故障修统计 ***
        // $entire_instance_statistics = DB::table("entire_instances as ei")
        //     ->join(DB::raw("entire_models sm"), "ei.model_unique_code", "=", "sm.unique_code")
        //     ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
        //     ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
        //     ->selectRaw(implode(",", [
        //         "count(sm.unique_code) as aggregate",
        //         "c.unique_code as category_unique_code",
        //         "c.name as category_name",
        //         "em.unique_code as entire_model_unique_code",
        //         "em.name as entire_model_name",
        //         "sm.unique_code as sub_model_unique_code",
        //         "sm.name as sub_model_name",
        //     ]))
        //     ->groupBy(["c.unique_code", "c.name", "em.unique_code", "em.name", "sm.unique_code", "sm.name",])
        //     ->orderBy("c.unique_code")
        //     ->orderBy("em.unique_code")
        //     ->orderBy("sm.unique_code")
        //     ->get()
        //     ->map(function ($datum) {
        //         $datum->name = $datum->entire_model_name != $datum->sub_model_name ? $datum->sub_model_name : $datum->entire_model_name;
        //         $datum->identity_code = "";
        //         $datum->use_position = "";
        //         $datum->made_at = "";
        //         $datum->warehouse_return_at = "";
        //         $datum->warehouse_return_reson = "";
        //         $datum->fix_datuy_officer = "";
        //         $datum->last_fixer_name = "";
        //         $datum->last_checker_name = "";
        //         $datum->count_with_sub_model = $datum->aggregate;
        //         $datum->breakdown_count_with_sub_model = 0;
        //         $datum->repair_rate = "-";
        //         $datum->repair_rate_category = "-";
        //         $datum->factory_name = "";
        //         $datum->line_name = "";
        //         return $datum;
        //     });
        //
        // $entire_instance_statistics->map(function ($datum) use ($count_with_categories, $breakdown_count_with_categories) {
        //     $datum->count_with_category = @$count_with_categories[$datum->category_unique_code] ?: 0;
        //     $datum->breakdown_count_with_category = @$breakdown_count_with_categories[$datum->category_unique_code] ?: 0;
        //     $datum->repair_rate_with_cateogry = "-";
        //     if ($datum->count_with_category > 0 && $datum->breakdown_count_with_category > 0) {
        //         $datum->repair_rate_with_category = (round($datum->breakdown_count_with_category / $datum->count_with_category, 6) * 100) . "%";
        //     }
        // });
        //
        // $entire_instance_statistics->each(function ($datum) use (&$breakdown_statistics) {
        //     if (!array_key_exists($datum->sub_model_unique_code, $breakdown_statistics)) {
        //         $breakdown_statistics[$datum->sub_model_unique_code] = [$datum];
        //     }
        // });

        $breakdown_statistics = array_collapse($breakdown_statistics);

        return [
            "code" => 0,
            "msg" => "读取成功",
            "count" => count($breakdown_statistics),
            // "data" => $entire_instance_statistics_query->all(),
            "data" => array_values($breakdown_statistics),
            "count_with_categories" => $count_with_categories,
            "count_with_sub_models" => $count_with_sub_models,
            "breakdown_count_with_categories" => $breakdown_count_with_categories,
            "breakdown_count_with_sub_models" => $breakdown_count_with_sub_models,
        ];
    }
}
