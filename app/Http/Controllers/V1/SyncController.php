<?php

namespace App\Http\Controllers\V1;

use App\Facades\JsonResponseFacade;
use App\Facades\OrganizationFacade;
use App\Http\Controllers\Controller;
use App\Model\Area;
use App\Model\Factory;
use App\Model\Line;
use App\Model\Maintain;
use App\Model\Platoon;
use App\Model\Position;
use App\Model\Shelf;
use App\Model\SourceName;
use App\Model\Storehouse;
use App\Model\Tier;
use App\Model\WorkArea;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncController extends Controller
{
    /**
     * 设备种类型同步
     * @param Request $request
     * @return mixed
     */
    final public function PostFacilityType(Request $request)
    {
        $new_categories = collect($request->get("facility_categories", []) ?? []);
        $new_entire_models = collect($request->get("facility_models", []) ?? []);
        $new_sub_models = collect($request->get("facility_sub_models", []) ?? []);

        if ($new_categories->isNotEmpty()) {
            $new_categories->each(function ($new_category) {
                DB::table("categories")
                    ->updateOrInsert([
                        "unique_code" => $new_category["unique_code"],
                    ], [
                        "name" => $new_category["name"],
                        "unique_code" => $new_category["unique_code"],
                    ]);
            });
        }

        if ($new_entire_models->isNotEmpty()) {
            $new_entire_models->each(function ($new_entire_model) {
                DB::table("entire_models")
                    ->updateOrInsert([
                        "unique_code" => $new_entire_model["unique_code"],
                        "category_unique_code" => $new_entire_model["facility_category_unique_code"],
                        "is_sub_model" => false,
                    ], [
                        "name" => $new_entire_model["name"],
                        "unique_code" => $new_entire_model["unique_code"],
                        "category_unique_code" => $new_entire_model["facility_category_unique_code"],
                        "is_sub_model" => false,
                    ]);
            });
        }

        if ($new_sub_models->isNotEmpty()) {
            $new_sub_models->each(function ($new_sub_model) {
                DB::table("entire_models")
                    ->updateOrInsert([
                        "unique_code" => $new_sub_model["unique_code"],
                        "parent_unique_code" => $new_sub_model["facility_model_unique_code"],
                        "category_unique_code" => substr($new_sub_model["facility_model_unique_code"], 0, 3),
                        "is_sub_model" => true,
                    ], [
                        "name" => $new_sub_model["name"],
                        "unique_code" => $new_sub_model["unique_code"],
                        "parent_unique_code" => $new_sub_model["facility_model_unique_code"],
                        "category_unique_code" => substr($new_sub_model["facility_model_unique_code"], 0, 3),
                        "is_sub_model" => true,
                    ]);
            });
        }

        return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . "段种类型同步成功");
    }

    /**
     * 器材种类型同步
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    final public function PostEquipmentType(Request $request)
    {
        $new_categories = collect($request->get("equipment_categories", []) ?? []);
        $new_entire_models = collect($request->get("equipment_models", []) ?? []);
        $new_sub_models = collect($request->get("equipment_sub_models", []) ?? []);

        if ($new_categories->isNotEmpty()) {
            $new_categories->each(function ($new_category) {
                DB::table("categories")
                    ->updateOrInsert([
                        "unique_code" => $new_category["unique_code"],
                    ], [
                        "created_at" => $new_category["created_at"],
                        "updated_at" => $new_category["updated_at"],
                        "name" => $new_category["name"],
                        "unique_code" => $new_category["unique_code"],
                    ]);
            });
        }

        if ($new_entire_models->isNotEmpty()) {
            $new_entire_models->each(function ($new_entire_model) {
                DB::table("entire_models")
                    ->updateOrInsert([
                        "unique_code" => $new_entire_model["unique_code"],
                        "category_unique_code" => $new_entire_model["equipment_category_unique_code"],
                        "is_sub_model" => false,
                    ], [
                        "created_at" => $new_entire_model["created_at"],
                        "updated_at" => $new_entire_model["updated_at"],
                        "name" => $new_entire_model["name"],
                        "unique_code" => $new_entire_model["unique_code"],
                        "category_unique_code" => $new_entire_model["equipment_category_unique_code"],
                        "is_sub_model" => false,
                    ]);
            });
        }

        if ($new_sub_models->isNotEmpty()) {
            $new_sub_models->each(function ($new_sub_model) {
                DB::table("entire_models")
                    ->updateOrInsert([
                        "unique_code" => $new_sub_model["unique_code"],
                        "parent_unique_code" => $new_sub_model["equipment_model_unique_code"],
                        "category_unique_code" => substr($new_sub_model["equipment_model_unique_code"], 0, 3),
                        "is_sub_model" => true,
                    ], [
                        "created_at" => $new_sub_model["created_at"],
                        "updated_at" => $new_sub_model["updated_at"],
                        "name" => $new_sub_model["name"],
                        "unique_code" => $new_sub_model["unique_code"],
                        "parent_unique_code" => $new_sub_model["equipment_model_unique_code"],
                        "category_unique_code" => substr($new_sub_model["equipment_model_unique_code"], 0, 3),
                        "is_sub_model" => true,
                    ]);
            });
        }
        return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . "段器材种类型同步成功");
    }

    /**
     * 线别同步-列表
     * @param Request $request
     * @return mixed
     */
    final public function PostLine(Request $request)
    {
        try {
            Log::channel("sync-from-paragraph-center")->info("sync line request", $request->all());

            $lines = $request->get("lines", []) ?? [];
            if (!empty($lines)) {
                collect($lines)->each(function ($line) {
                    // 更新线别
                    Line::with([])->updateOrInsert([
                        "unique_code" => $line["unique_code"],
                    ], [
                        "created_at" => $line["created_at"],
                        "updated_at" => now(),
                        "unique_code" => $line["unique_code"],
                        "name" => $line["name"],
                    ]);
                });
            }


            $pivot_line_and_stations = $request->get("pivot_line_and_stations", []) ?? [];
            if (!empty($pivot_line_and_stations)) {
                $lines = Line::with([])->get();
                $stations = Maintain::with([])->get();

                collect($pivot_line_and_stations)
                    ->each(function ($pivot_line_and_station) use ($lines, $stations) {
                        $line_unique_code = @$pivot_line_and_station["line_unique_code"] ?? "";
                        $station_unique_code = @$pivot_line_and_station["station_unique_code"] ?? "";
                        $line = $lines->where("unique_code", $line_unique_code)->first();
                        $station = $stations->where("unique_code", $station_unique_code)->first();

                        if ($line_unique_code && $station_unique_code && $line && $station) {
                            DB::table("lines_maintains")->updateOrInsert(["lines_id" => $line->id, "maintains_id" => $station->id,]);
                        }
                    });
            }

            Log::channel("sync-from-paragraph-center")->info("sync equipment type end--------");
            return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . "段线别同步成功");
        } catch (Exception $e) {
            Log::channel("sync-from-paragraph-center")->info("sync equipment type exception", [$e->getMessage(), $e->getFile(), $e->getLine()]);
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 车间同步-列表
     * @param Request $request
     * @return mixed
     * @throws Throwable
     */
    final public function PostWorkshop(Request $request)
    {
        try {
            $types = [
                1 => "SCENE_WORKSHOP",  // 现场车间
                2 => "WORKSHOP",  // 检修车间
                3 => "ELECTRON",  // 电子车间
                4 => "VEHICLE",  // 车载车间
                5 => "DUMP",  // 驼峰车间
            ];

            Log::channel("sync-from-paragraph-center")->info("sync workshop request", $request->all());
            collect($request->all())
                ->each(function ($sc) use ($types) {
                    $type = $types[$sc["type"]] ?? false;
                    if (!$type) return JsonResponseFacade::errorForbidden("车间类型状态错误");

                    $old_name = "";
                    $scene_workshop = Maintain::with([])->where("type", "!=", "STATION")
                        ->where("unique_code", $sc["unique_code"])->first();

                    if ($scene_workshop) {
                        $old_name = $scene_workshop->name;

                        $scene_workshop
                            ->fill([
                                "unique_code" => $sc["unique_code"],
                                "name" => $sc["name"],
                                "parent_unique_code" => $sc["paragraph_unique_code"],
                                "lon" => $sc["lon"],
                                "lat" => $sc["lat"],
                                "type" => $type,
                                "contact" => $sc["contact"],
                                "contact_phone" => $sc["contact_phone"],
                                "contact_address" => $sc["contact_address"],
                            ])
                            ->saveOrFail();
                    } else {
                        $scene_workshop = Maintain::with([])
                            ->insert([
                                "created_at" => $sc["created_at"],
                                "updated_at" => now(),
                                "unique_code" => $sc["unique_code"],
                                "name" => $sc["name"],
                                "parent_unique_code" => $sc["paragraph_unique_code"],
                                "lon" => $sc["lon"],
                                "lat" => $sc["lat"],
                                "type" => $type,
                                "contact" => $sc["contact"],
                                "contact_phone" => $sc["contact_phone"],
                                "contact_address" => $sc["contact_address"],
                            ]);
                        $old_name = $scene_workshop->name;
                    }

                    if ($old_name != $sc["name"]) {
                        DB::table("maintains")->where("name", $old_name)->update(["name" => $sc["name"],]);
                        DB::table("breakdown_logs")->where("scene_workshop_name", $old_name)->update(["scene_workshop_name" => $sc["name"],]);
                        DB::table("collect_device_order_entire_instances")->where("maintain_workshop_name", $old_name)->update(["maintain_workshop_name" => $sc["name"],]);
                        DB::table("entire_instances")->where("maintain_workshop_name", $old_name)->update(["maintain_workshop_name" => $sc["name"],]);
                        DB::table("entire_instances")->where("last_maintain_workshop_name", $old_name)->update(["last_maintain_workshop_name" => $sc["name"],]);
                        DB::table("print_new_location_and_old_entire_instances")->where("old_maintain_workshop_name", $old_name)->update(["old_maintain_workshop_name" => $sc["name"],]);
                        DB::table("repair_base_breakdown_order_entire_instances")->where("scene_workshop_name", $old_name)->update(["scene_workshop_name" => $sc["name"],]);
                        DB::table("station_locations")->where("scene_workshop_name", $old_name)->update(["scene_workshop_name" => $sc["name"],]);
                        DB::table("temp_station_position")->where("scene_workshop_name", $old_name)->update(["scene_workshop_name" => $sc["name"],]);
                        DB::table("warehouse_reports")->where("scene_workshop_name", $old_name)->update(["scene_workshop_name" => $sc["name"],]);
                        DB::table("warehouse_report_display_board_statistics")->where("scene_workshop_name", $old_name)->update(["scene_workshop_name" => $sc["name"],]);
                    }
                });
            Log::channel("sync-from-paragraph-center")->info("sync workshop end--------");
            return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . "段车间同步成功");
        } catch (Exception $e) {
            Log::channel("sync-from-paragraph-center")->info("sync workshop exception", [$e->getMessage(), $e->getFile(), $e->getLine()]);
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 车站同步-列表
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    final public function PostStation(Request $request)
    {
        try {
            Log::channel("sync-from-paragraph-center")->info("sync station request", $request->all());

            $stations = $request->get("stations", []) ?: [];
            if (!empty($stations)) {
                collect($stations)->each(function ($station) {
                    $old_station = Maintain::with([])
                        ->where("type", "STATION")
                        ->where("unique_code", $station["unique_code"])
                        ->first();
                    $old_station_name = $old_station->name;
                    if ($old_station) {
                        $old_station
                            ->fill([
                                "unique_code" => $station["unique_code"],
                                "name" => $station["name"],
                                "parent_unique_code" => $station["workshop_unique_code"],
                                "lon" => $station["lon"],
                                "lat" => $station["lat"],
                                "contact" => $station["contact"],
                                "contact_phone" => $station["contact_phone"],
                                "contact_address" => $station["contact_address"],
                            ])
                            ->saveOrFail();
                        if ($station["name"] != $old_station_name) {
                            OrganizationFacade::UpdateStationName($old_station_name, $station["name"]);
                        }
                    } else {
                        Maintain::with([])
                            ->insert([
                                "created_at" => $station["created_at"],
                                "updated_at" => now(),
                                "unique_code" => $station["unique_code"],
                                "name" => $station["name"],
                                "parent_unique_code" => $station["workshop_unique_code"],
                                "lon" => $station["lon"],
                                "lat" => $station["lat"],
                                "contact" => $station["contact"],
                                "contact_phone" => $station["contact_phone"],
                                "contact_address" => $station["contact_address"],
                            ]);
                    }
                });
            }

            $lines = Line::with([])->get();
            $stations = Maintain::with([])
                ->where("type", "STATION")
                ->get();

            $pivot_line_and_stations = $request->get("pivot_line_and_stations", []) ?: [];
            if (!empty($pivot_line_and_stations)) {
                collect($pivot_line_and_stations)
                    ->each(function ($pivot_line_and_stations) use ($lines, $stations) {
                        [
                            "created_at" => $created_at,
                            "updated_at" => $updated_at,
                            "line_unique_code" => $line_unique_code,
                            "station_unique_code" => $station_unique_code,
                        ] = $pivot_line_and_stations;

                        $line = $lines->where("unique_code", $line_unique_code)->first();
                        $station = $stations->where("unique_code", $station_unique_code)->first();

                        if ($line_unique_code && $station_unique_code && $line && $station) {
                            DB::table("lines_maintains")
                                ->updateOrInsert(
                                    [
                                        "lines_id" => $line->id,
                                        "stations_id" => $station->id,
                                    ],
                                    [
                                        "created_at" => $created_at,
                                        "updated_at" => $updated_at,
                                        "line_unique_code" => $line_unique_code,
                                        "station_unique_code" => $station_unique_code,
                                    ]
                                );
                        }
                    });
            }

            Log::channel("sync-from-paragraph-center")->info("sync station end--------");
            return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . "段车站同步成功");
        } catch (Exception $e) {
            Log::channel("sync-from-paragraph-center")->info("sync station exception", [$e->getMessage(), $e->getFile(), $e->getLine()]);
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 同步供应商
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    final public function PostFactory(Request $request)
    {
        try {
            Log::channel("sync-from-paragraph-center")->info("sync factory request", $request->all());
            collect($request->all())->each(function ($f) {
                // 更新车间
                $factory = Factory::with([])->where("name", $f["name"])->first();
                if ($factory) {
                    $old_name = $factory->name;
                    $factory->fill(["name" => $f["name"], "unique_code" => $f["name"],])->saveOrFail();
                } else {
                    $factory = Factory::with([])->create(["name" => $f["name"], "unique_code" => $f["name"],]);
                    $old_name = $factory->name;
                }

                // 批量更新设备器材相关供应商名称
                if ($old_name != $f["name"]) {
                    DB::table("entire_instances")->where("factory_name", $old_name)->update(["factory_name" => $f["name"],]);
                }
            });
            Log::channel("sync-from-paragraph-center")->info("sync factory end--------");
            return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . "段供应商同步成功");
        } catch (Exception $e) {
            Log::channel("sync-from-paragraph-center")->info("sync factory exception", [$e->getMessage(), $e->getFile(), $e->getLine()]);
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 同步工区
     * @param Request $request
     * @return mixed
     * @throws Throwable
     */
    final public function PostWorkArea(Request $request)
    {
        try {
            Log::channel("sync-from-paragraph-center")->info("sync work_area request", $request->all());
            collect($request->all())->each(function ($wa) {
                // 更新工区
                $work_area = WorkArea::with([])
                    ->withoutGlobalScope("is_show")
                    ->where("unique_code", $wa["unique_code"])
                    ->first();
                if ($work_area) {
                    $work_area
                        ->fill([
                            "created_at" => $wa["created_at"],
                            "name" => $wa["name"],
                            "workshop_unique_code" => $wa["workshop_unique_code"],
                        ])
                        ->saveOrFail();
                } else {
                    WorkArea::with([])
                        ->create([
                            "created_at" => $wa["created_at"],
                            "unique_code" => $wa["unique_code"],
                            "name" => $wa["name"],
                            "workshop_unique_code" => $wa["workshop_unique_code"],
                        ]);
                }
            });
            Log::channel("sync-from-paragraph-center")->info("sync work_area end--------");
            return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . "段工区同步成功");
        } catch (Exception $e) {
            Log::channel("sync-from-paragraph-center")->info("sync work_area exception", [$e->getMessage(), $e->getFile(), $e->getLine()]);
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 同步上道位置
     * @param Request $request
     * @return mixed
     */
    final public function PostInstallLocation(Request $request)
    {
        try {
            $data = $request->all();
            Log::channel('sync')->info('sync install location request', $data);
            $install_rooms = @$data['install_rooms'] ?: [];
            $install_platoons = @$data['install_platoons'] ?: [];
            $install_shelves = @$data['install_shelves'] ?: [];
            $install_tiers = @$data['install_tiers'] ?: [];
            $install_positions = @$data['install_positions'] ?: [];
            if (!empty($install_rooms)) {
                array_map(function ($install_room) {
                    DB::table('install_rooms')->updateOrInsert([
                        'unique_code' => $install_room['unique_code']
                    ], [
                        'created_at' => $install_room['created_at'],
                        'updated_at' => now(),
                        'unique_code' => $install_room['unique_code'],
                        'station_unique_code' => $install_room['station_unique_code'],
                        'type' => $install_room['type']
                    ]);
                }, $install_rooms);
            }
            if (!empty($install_platoons)) {
                array_map(function ($install_platoon) {
                    DB::table('install_platoons')->updateOrInsert([
                        'unique_code' => $install_platoon['unique_code']
                    ], [
                        'created_at' => $install_platoon['created_at'],
                        'updated_at' => now(),
                        'name' => $install_platoon['name'],
                        'unique_code' => $install_platoon['unique_code'],
                        'install_room_unique_code' => $install_platoon['install_room_unique_code']
                    ]);
                }, $install_platoons);
            }
            if (!empty($install_shelves)) {
                array_map(function ($install_shelf) {
                    DB::table('install_shelves')->updateOrInsert([
                        'unique_code' => $install_shelf['unique_code']
                    ], [
                        'created_at' => $install_shelf['created_at'],
                        'updated_at' => now(),
                        'name' => $install_shelf['name'],
                        'unique_code' => $install_shelf['unique_code'],
                        'install_platoon_unique_code' => $install_shelf['install_platoon_unique_code'],
                    ]);
                }, $install_shelves);
            }
            if (!empty($install_tiers)) {
                array_map(function ($install_tier) {
                    DB::table('install_tiers')->updateOrInsert([
                        'unique_code' => $install_tier['unique_code']
                    ], [
                        'created_at' => $install_tier['created_at'],
                        'updated_at' => now(),
                        'name' => $install_tier['name'],
                        'unique_code' => $install_tier['unique_code'],
                        'install_shelf_unique_code' => $install_tier['install_shelf_unique_code'],
                    ]);
                }, $install_tiers);
            }
            if (!empty($install_positions)) {
                array_map(function ($install_position) {
                    DB::table('install_positions')->updateOrInsert([
                        'unique_code' => $install_position['unique_code']
                    ], [
                        'created_at' => $install_position['created_at'],
                        'updated_at' => now(),
                        'name' => $install_position['name'],
                        'unique_code' => $install_position['unique_code'],
                        'install_tier_unique_code' => $install_position['install_tier_unique_code'],
                        'volume' => @$install_position['volume'] ?? 1,
                    ]);
                }, $install_positions);
            }
            Log::channel('sync')->info('sync install location end');
            return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . '段上道位置同步成功');
        } catch (Exception $e) {
            Log::channel('sync')->error('sync install location exception', [$e->getMessage(), $e->getFile(), $e->getLine()]);
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 同步来源
     * @param Request $request
     * @return mixed
     */
    final public function PostSource(Request $request)
    {
        try {
            $request_data = collect($request->all());
            if ($request_data->get("source_names")) {
                collect($request_data->get("source_names"))
                    ->each(function ($source_name) {
                        SourceName::with([])->updateOrInsert(
                            [
                                "unique_code" => $source_name["unique_code"],
                            ], [
                                "created_at" => $source_name["created_at"],
                                "updated_at" => now(),
                                "name" => $source_name["name"],
                                "source_type" => $source_name["source_type_unique_code"],
                            ]
                        );
                    });
            }

            Log::channel("sync-from-paragraph-center")->info("sync source end--------");

            return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . "来源同步成功");
        } catch (Exception $e) {
            Log::channel("sync-from-paragraph-center")->info("sync source exception", [$e->getMessage(), $e->getFile(), $e->getLine()]);
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 仓库位置同步 段中心 → 车间
     */
    final public function PostLocation(Request $request)
    {
        try {
            DB::beginTransaction();
            $request_data = collect($request->all());

            if ($request_data->get("storehouses")) {
                $storehouses = collect($request_data->get("storehouses"));

                $storehouses->each(function ($storehouse) {
                    Storehouse::with([])->updateOrInsert([
                        "unique_code" => $storehouse["unique_code"],
                        "name" => $storehouse["name"],
                    ], [
                        "created_at" => $storehouse["created_at"],
                        "updated_at" => now(),
                        "workshop_unique_code" => $storehouse["workshop_unique_code"],
                        "work_area_unique_code" => $storehouse["work_area_unique_code"],
                        "paragraph_unique_code" => $storehouse["paragraph_unique_code"],
                    ]);
                });
            }

            if ($request_data->get("areas")) {
                $areas = collect($request_data->get("areas"));

                $areas->each(function ($area) {
                    Area::with([])->updateOrInsert([
                        "unique_code" => $area["unique_code"],
                        "name" => $area["name"],
                    ], [
                        "created_at" => $area["created_at"],
                        "updated_at" => now(),
                        "storehouse_unique_code" => $area["storehouse_unique_code"],
                        "paragraph_unique_code" => $area["paragraph_unique_code"],
                    ]);
                });
            }

            if ($request_data->get("platoons")) {
                $platoons = collect($request_data->get("platoons"));

                $platoons->each(function ($platoon) {
                    Platoon::with([])->updateOrInsert([
                        "unique_code" => $platoon["unique_code"],
                        "name" => $platoon["name"],
                    ], [
                        "created_at" => $platoon["created_at"],
                        "updated_at" => now(),
                        "area_unique_code" => $platoon["area_unique_code"],
                        "paragraph_unique_code" => $platoon["paragraph_unique_code"],
                    ]);
                });
            }

            if ($request_data->get("shelves")) {
                $shelves = collect($request_data->get("shelves"));

                $shelves->each(function ($shelf) {
                    Shelf::with([])->updateOrInsert([
                        "unique_code" => $shelf["unique_code"],
                        "name" => $shelf["name"],
                    ], [
                        "created_at" => $shelf["created_at"],
                        "updated_at" => now(),
                        "platoon_unique_code" => $shelf["platoon_unique_code"],
                        "paragraph_unique_code" => $shelf["paragraph_unique_code"],
                    ]);
                });
            }

            if ($request_data->get("tiers")) {
                $tiers = collect($request_data->get("tiers"));

                $tiers->each(function ($tier) {
                    Tier::with([])->updateOrInsert([
                        "unique_code" => $tier["unique_code"],
                        "name" => $tier["name"],
                    ], [
                        "created_at" => $tier["created_at"],
                        "updated_at" => now(),
                        "shelf_unique_code" => $tier["shelf_unique_code"],
                        "paragraph_unique_code" => $tier["paragraph_unique_code"],
                    ]);
                });
            }

            if ($request_data->get("positions")) {
                $positions = collect($request_data->get("positions"));

                $positions->each(function ($position) {
                    Position::with([])->updateOrInsert([
                        "unique_code" => $position["unique_code"],
                        "name" => $position["name"],
                    ], [
                        "created_at" => $position["created_at"],
                        "updated_at" => now(),
                        "tier_unique_code" => $position["tier_unique_code"],
                        "paragraph_unique_code" => $position["paragraph_unique_code"],
                    ]);
                });
            }

            DB::commit();
            Log::channel("sync-from-paragraph-center")->info("sync location end--------");

            return JsonResponseFacade::ok(env("ORGANIZATION_NAME") . "仓库位置同步成功");
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel("sync-from-paragraph-center")->info("sync location exception", [$e->getMessage(), $e->getFile(), $e->getLine()]);
            return JsonResponseFacade::errorException($e);
        }
    }
}
