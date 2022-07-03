<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Facades\QueryBuilderFacade;
use App\Serializers\EntireInstanceSerializer;
use Illuminate\Support\Facades\DB;

class RemindController extends Controller
{
    public function index()
    {
        // generate scraped statistics
        $scraped_statistics = EntireInstanceSerializer::ins([
            EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
            EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
        ])
            ->GenerateQueryRelationShipQ()
            ->selectRaw(implode(",", [
                "count(c.unique_code)  as aggregate",
                "c.unique_code         as category_unique_code",
                "c.name                as category_name",
                "s.unique_code         as station_unique_code",
                "s.name                as station_name",
            ]))
            ->where("ei.maintain_station_name", "<>", "")
            ->where("ei.scarping_at", "<", now())
            ->whereNull("ei.deleted_at")
            ->where("ei.status", "<>", "SCRAP")
            ->whereNull("c.deleted_at")
            ->groupBy([
                "c.unique_code",
                "c.name",
                "s.unique_code",
                "s.name",
            ])
            ->get();

        // $scrapedStatistics = DB::table("entire_instances as ei")
        //     ->selectRaw(implode(",", [
        //         "count(c.unique_code)  as aggregate",
        //         "c.unique_code         as category_unique_code",
        //         "c.name                as category_name",
        //         "s.unique_code         as station_unique_code",
        //         "s.name                as station_name",
        //     ]))
        //     ->join(DB::raw("categories c "), "c.unique_code", "=", "ei.category_unique_code")
        //     ->join(DB::raw("maintains s"), "s.name", "=", "ei.maintain_station_name")
        //     ->where("ei.scarping_at", "<", now())
        //     ->whereNull("ei.deleted_at")
        //     ->where("ei.status", "<>", "SCRAP")
        //     ->whereNull("c.deleted_at")
        //     ->groupBy([
        //         "c.unique_code",
        //         "c.name",
        //         "s.unique_code",
        //         "s.name",
        //     ])
        //     ->get();

        // generate cycle fix plan
        $cycleFixPlanStatistics = collect([]);
        $cycle_fix_plan_statistics = collect([]);
        if (session("account.work_area_unique_code")) {
            $cycle_fix_plan_statistics = EntireInstanceSerializer::ins([
                EntireInstanceSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                EntireInstanceSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(",", [
                    "count(c.unique_code)  as aggregate",
                    "c.unique_code         as category_unique_code",
                    "c.name                as category_name",
                    "s.unique_code         as station_unique_code",
                    "s.name                as station_name",
                ]))
                ->where("ei.maintain_station_name", "<>", "")
                ->whereIn("ei.status", ["INSTALLED", "INSTALLING"])
                ->where("ei.work_area_unique_code", session("account.work_area_unique_code"))
                ->whereNotNull("ei.next_fixing_day")
                ->whereBetween("ei.next_fixing_day", [now()->startOfMonth(), now()->endOfMonth(),])
                ->groupBy(["c.unique_code", "c.name", "s.unique_code", "s.name"])
                ->get();

            // $cycleFixPlanStatistics = DB::table("entire_instances as ei")
            //     ->selectRaw(implode(",", [
            //         "count(c.unique_code)  as aggregate",
            //         "c.unique_code         as category_unique_code",
            //         "c.name                as category_name",
            //         "s.unique_code         as station_unique_code",
            //         "s.name                as station_name",
            //     ]))
            //     ->join(DB::raw("categories c"), "c.unique_code", "=", "ei.category_unique_code")
            //     ->join(DB::raw("maintains s"), "s.name", "=", "ei.maintain_station_name")
            //     ->join(DB::raw("maintains sc"), "sc.unique_code", "=", "s.parent_unique_code")
            //     ->whereNull("ei.deleted_at")
            //     ->whereIn("ei.status", ["INSTALLED", "INSTALLING"])
            //     ->whereNull("c.deleted_at")
            //     ->where("work_area_unique_code", session("account.work_area_unique_code"))
            //     ->whereNotNull("next_fixing_day")
            //     ->whereBetween("next_fixing_day", [now()->startOfMonth(), now()->endOfMonth(),])
            //     ->groupBy(["c.unique_code", "c.name", "s.unique_code", "s.name"])
            //     ->get();
        }

        // generate fixed overdue 6 month statistics
        $fixedOverdue6MonthStatistics = collect([]);
        $sql = collect([]);
        if ((env("ORGANIZATION_CODE") === "B074") && session("account.work_area_unique_code")) {
            // if (session("account.work_area_unique_code")) {
            $fixedOverdue6MonthStatistics = QueryBuilderFacade::unionAll(
                DB::table("entire_instances as ei")
                    ->selectRaw(implode(",", [
                        "count(sm.unique_code) as aggregate",
                        "c.unique_code as category_unique_code",
                        "c.name as category_name",
                        "em.unique_code as entire_model_unique_code",
                        "em.name as entire_model_name",
                        "sm.unique_code as sub_model_unique_code",
                        "sm.name as sub_model_name",
                    ]))
                    ->join(DB::raw("entire_models sm"), "ei.model_unique_code", "=", "sm.unique_code")
                    ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                    ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                    ->whereNull("ei.deleted_at")
                    ->where("ei.status", "FIXED")
                    ->whereNull("sm.deleted_at")
                    ->whereNull("em.deleted_at")
                    ->whereNull("c.deleted_at")
                    ->where("sm.is_sub_model", true)
                    ->where("em.is_sub_model", false)
                    ->where("ei.work_area_unique_code", session("account.work_area_unique_code"))
                    ->whereBetween("ei.checked_at", ["0001-01-01 00:00:00", now()->subMonths(6)->format("Y-m-d 00:00:00")])
                    ->groupBy(["c.unique_code", "c.name", "em.unique_code", "em.name", "sm.unique_code", "sm.name"]),
                DB::table("entire_instances as ei")
                    ->selectRaw(implode(",", [
                        "count(em.unique_code) as aggregate",
                        "c.unique_code as category_unique_code",
                        "c.name as category_name",
                        "em.unique_code as entire_model_unique_code",
                        "em.name as entire_model_name",
                        "'' as sub_model_unique_code",
                        "'' as sub_model_name",
                    ]))
                    ->join(DB::raw("entire_models em"), "ei.entire_model_unique_code", "=", "em.unique_code")
                    ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                    ->whereNull("ei.deleted_at")
                    ->where("ei.status", "FIXED")
                    ->whereNull("em.deleted_at")
                    ->whereNull("c.deleted_at")
                    ->where("em.is_sub_model", false)
                    ->where("ei.work_area_unique_code", session("account.work_area_unique_code"))
                    ->whereBetween("ei.checked_at", ["0001-01-01 00:00:00", now()->subMonths(6)->format("Y-m-d 00:00:00")])
                    ->groupBy(["c.unique_code", "c.name", "em.unique_code", "em.name"])
            )
                ->get();
        }

        return JsonResponseFacade::dict([
            "scraped_statistics" => $scraped_statistics,
            "cycle_fix_plan_statistics" => $cycle_fix_plan_statistics,
            "fixed_overdue_6_month_statistics" => $fixedOverdue6MonthStatistics,
            "sql" => $sql,
        ]);
    }
}
