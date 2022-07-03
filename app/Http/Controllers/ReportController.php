<?php

namespace App\Http\Controllers;

use App\Facades\QueryConditionFacade;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\FileSystem;
use Jericho\TextHelper;
use function request;

class ReportController extends Controller
{
    /**
     * 现场车间
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function workshop()
    {
        try {
            # 型号总计数
            $entireModelUniqueCodes = [];
            foreach (DB::table('hengyang')->select('entire_model_unique_code')->where('entire_model_unique_code', '<>', null)->groupBy('entire_model_unique_code')->get() as &$entireModelUniqueCode) {
                $entireModelUniqueCodes[$entireModelUniqueCode->entire_model_unique_code] = DB::table('hengyang')->where('entire_model_unique_code', $entireModelUniqueCode->entire_model_unique_code)->count('id');
            }

            $stationNames = DB::table('hengyang')->select('station_name')->where('station_name', '<>', null)->groupBy('station_name')->get();

            return view($this->view())
                ->with('entireModelUniqueCodes', $entireModelUniqueCodes)
                ->with('stationNames', $stationNames);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    final private function view($viewName = null)
    {
        try {
            $viewName = $viewName ?: request()->route()->getActionMethod();
            return "Report.{$viewName}";
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }
# TODO:::::::删除######################end##################

    /**
     * 车站页面
     * @param string $stationName
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function station(string $stationName)
    {
        try {
            # 型号总计数
            $entireModelUniqueCodes = [];
            foreach (DB::table('hengyang')->select('entire_model_unique_code')
                         ->where('entire_model_unique_code', '<>', null)
                         ->groupBy('entire_model_unique_code')
                         ->where('station_name', $stationName)
                         ->get()
                     as &$entireModelUniqueCode) {
                $entireModelUniqueCodes[$entireModelUniqueCode->entire_model_unique_code] = DB::table('hengyang')->where('entire_model_unique_code', $entireModelUniqueCode->entire_model_unique_code)->count('id');
            }
            return view($this->view())
                ->with('stationName', $stationName)
                ->with('entireModelUniqueCodes', $entireModelUniqueCodes);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }


//    /**
//     * 质量报告 年度
//     * @return Factory|RedirectResponse|\Illuminate\Routing\Redirector|View|string
//     * @throws Exception
//     */
//    final public function qualityYear()
//    {
//        try {
//            $file = FileSystem::init(__FILE__);
//            $year = request("year") ?: date("Y");
//            $root_dir = $file->setPath(storage_path("app/质量报告"))->current();
//
//            if (request("download") == 1) {
//                # 下载质量报告Excel
//                $organization_code = env("ORGANIZATION_NAME");
//                $filename = "{$year}年{$organization_code}质量报告.xlsx";
//                if (is_file(public_path($filename))) unlink(public_path($filename));
//                if (!is_file("{$root_dir}/{$year}/{$filename}")) return "<script>alert('excel文件不存在：{$root_dir}/{$year}/{$filename}');</script>";
//                copy("{$root_dir}/{$year}/{$filename}", public_path("{$filename}"));
//                return redirect(url("/{$filename}"));
//            } elseif (request('download') == 2) {
//                # 下载质量报告Excel
//                $organization_code = env("ORGANIZATION_NAME");
//                $filename = "{$year}年{$organization_code}质量报告-现场车间-站场.xlsx";
//                if (is_file(public_path($filename))) unlink(public_path($filename));
//                if (!is_file("{$root_dir}/{$year}/{$filename}")) return "<script>alert('excel文件不存在：{$root_dir}/{$year}/{$filename}');</script>";
//                copy("{$root_dir}/{$year}/{$filename}", public_path("{$filename}"));
//                return redirect(url("/{$filename}"));
//            }
//
//            # 获取基本信息
//            $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//            $categories = array_flip($file->setPath($root_dir)->joins([$year, "种类.json"])->fromJson());
//
//            $statistics = [
//                "设备" => [],
//                "检修" => [],
//                "故障类型" => [],
//            ];
//
//            # 格式化检修数
//            $fixed = $file->setPath($root_dir)->joins([$year, "检修数.json"])->fromJson();
//            foreach ($fixed as $factory_name => $fixed_item) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                foreach ($fixed_item as $category_name => $item) {
//                    if ($category_name !== "count") {
//                        if (!array_key_exists($category_name, $statistics["检修"])) $statistics["检修"][$category_name] = 0;
//                        $statistics["检修"][$category_name] += $item["count"];
//                    }
//                }
//            }
//
//            # 格式化设备器材数
//            $device = $file->setPath($root_dir)->joins([$year, "{$year}-12", "设备数-种类-供应商.json"])->fromJson();
//            foreach ($device as $category_name => $device_item) if ($category_name !== "count") $statistics["设备"][$category_name] = $device_item["count"];
//
//            # 格式化故障类型
//            $breakdown_type = $file->setPath($root_dir)->joins([$year, "故障类型.json"])->fromJson();
//            foreach ($breakdown_type as $factory_name => $item1) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                foreach ($item1 as $category_name => $item2) {
//                    foreach ($item2 as $entire_model_name => $item3) {
//                        foreach ($item3 as $sub_model_name => $item4) {
//                            foreach ($item4 as $breakdown_type_name => $breakdown_type_count) {
//                                if (!array_key_exists($category_name, $statistics["故障类型"])) $statistics["故障类型"][$category_name] = [];
//                                if (!array_key_exists($breakdown_type_name, $statistics["故障类型"][$category_name])) $statistics["故障类型"][$category_name][$breakdown_type_name] = 0;
//                                $statistics["故障类型"][$category_name][$breakdown_type_name] += $breakdown_type_count;
//                            }
//                        }
//                    }
//                }
//            }
//
//            # 现场车间-站场
//            $device_station = $file->setPath($root_dir)->joins([$year, "设备总数-现场车间-站场.json"])->fromJson();
//            $fixed_station = $file->setPath($root_dir)->joins([$year, "检修数-现场车间-站场.json"])->fromJson();
//
//            return view("Report.qualityYear", [
//                "years" => $years,
//                "categories_as_json" => TextHelper::toJson($categories),
//                "statistics" => $statistics,
//                "statistics_as_json" => TextHelper::toJson($statistics),
//                "device_station" => $device_station,
//                "fixed_station" => $fixed_station,
//            ]);
//        } catch (Exception $e) {
//            return back()->with("info", '暂无数据');
//        }
//    }
//
//    /**
//     * 质量报告 季度
//     * @throws Exception
//     */
//    final public function qualityQuarter()
//    {
//        try {
//            $file = FileSystem::init(__FILE__);
//            $root_dir = $file->setPath(storage_path("app/质量报告"))->current();
//
//            $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//            $year = request("year") ?: date("Y");
//            $categories = array_flip($file->setPath($root_dir)->joins([$year, "种类.json"])->fromJson());
//            $quarters = ["1季度", "2季度", "3季度", "4季度"];
//            $months = ["1季度" => [1, 4], "2季度" => [4, 7], "3季度" => [7, 10], "4季度" => [10, 13]];
//            $month = intval(date("m"));
//            $quarter = request("quarter") ?: $quarters[floor($month / 3) - 1];
//
//            $statistics = [
//                "设备" => [],
//                "检修" => [],
//                "故障类型" => [],
//            ];
//
//            # 格式化检修数
//            $fixed = $file->setPath($root_dir)->joins([$year, "{$year}年 {$quarter}-检修数.json"])->fromJson();
//            foreach ($fixed as $factory_name => $fixed_item) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                foreach ($fixed_item as $category_name => $item) {
//                    if ($category_name !== "count") {
//                        if (!array_key_exists($category_name, $statistics["检修"])) $statistics["检修"][$category_name] = 0;
//                        $statistics["检修"][$category_name] = $item["count"];
//                    }
//                }
//            }
//
//            # 格式化设备器材数
//            for ($i = $months[$quarter][0]; $i < $months[$quarter][1]; $i++) {
//                $I = str_pad(strval($i), 2, '0', STR_PAD_LEFT);
//                $device = $file->setPath($root_dir)->joins([$year, "{$year}-{$I}", "设备数-种类-供应商.json"])->fromJson();
//                foreach ($device as $category_name => $item)
//                    if ($category_name !== "count") {
//                        if (!array_key_exists($category_name, $statistics["设备"])) $statistics["设备"][$category_name] = 0;
//                        $statistics["设备"][$category_name] = $item["count"];
//                    }
//            }
//
//            # 格式化故障类型
//            $breakdown_type = $file->setPath($root_dir)->joins([$year, "{$year}年 {$quarter}-故障类型.json"])->fromJson();
//            foreach ($breakdown_type as $factory_name => $item1) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                foreach ($item1 as $category_name => $item2) {
//                    foreach ($item2 as $entire_model_name => $item3) {
//                        foreach ($item3 as $sub_model_name => $item4) {
//                            foreach ($item4 as $breakdown_type_name => $breakdown_type_count) {
//                                if (!array_key_exists($category_name, $statistics["故障类型"])) $statistics["故障类型"][$category_name] = [];
//                                if (!array_key_exists($breakdown_type_name, $statistics["故障类型"][$category_name])) $statistics["故障类型"][$category_name][$breakdown_type_name] = 0;
//                                $statistics["故障类型"][$category_name][$breakdown_type_name] += $breakdown_type_count;
//                            }
//                        }
//                    }
//                }
//            }
//
//            $device_station = $file->setPath($root_dir)->joins([$year, "{$year}年 {$quarter}-设备总数-现场车间-站场.json"])->fromJson();
//            $fixed_station = $file->setPath($root_dir)->joins([$year, "{$year}年 {$quarter}-检修数-现场车间-站场.json"])->fromJson();
//
//            return view("Report.qualityQuarter", [
//                "year" => $year,
//                "years" => $years,
//                "quarters" => $quarters,
//                "categories_as_json" => TextHelper::toJson($categories),
//                "statistics" => $statistics,
//                "statistics_as_json" => TextHelper::toJson($statistics),
//                "device_station" => $device_station,
//                "fixed_station" => $fixed_station,
//            ]);
//        } catch (Exception $exception) {
//            return back()->with('info', '暂无数据');
//        }
//    }
//
//    /**
//     * 质量报告 月度
//     * @throws Exception
//     */
//    final public function qualityMonth()
//    {
//        try {
//            $file = FileSystem::init(__FILE__);
//            $year = request("year") ?: date("Y");
//            $month = request("month") ?: date("m");
//            $root_dir = $file->setPath(storage_path("app/质量报告"))->current();
//            $categories = array_flip($file->setPath($root_dir)->joins([$year, "种类.json"])->fromJson());
//
//            # 获取基本信息
//            $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//            $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
//
//            $statistics = [
//                "设备" => [],
//                "检修" => [],
//                "故障类型" => [],
//            ];
//
//            # 格式化检修数
//            $fixed = $file->setPath($root_dir)->joins([$year, "{$year}-{$month}", "检修数.json"])->fromJson();
//            foreach ($fixed as $factory_name => $fixed_item) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                foreach ($fixed_item as $category_name => $item) {
//                    if ($category_name !== "count") {
//                        if (!array_key_exists($category_name, $statistics["检修"])) $statistics["检修"][$category_name] = 0;
//                        $statistics["检修"][$category_name] = $item["count"];
//                    }
//                }
//            }
//
//            # 格式化设备器材
//            $device = $file->setPath($root_dir)->joins([$year, "{$year}-{$month}", "设备数-种类-供应商.json"])->fromJson();
//            foreach ($device as $category_name => $item) {
//                if ($category_name !== "count") {
//                    if (!array_key_exists($category_name, $statistics["设备"])) $statistics["设备"][$category_name] = 0;
//                    $statistics["设备"][$category_name] = $item["count"];
//                }
//            }
//
//            # 故障类型
//            $breakdown_type = $file->setPath($root_dir)->joins([$year, "{$year}-{$month}", "故障类型.json"])->fromJson();
//            foreach ($breakdown_type as $factory_name => $item1) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                foreach ($item1 as $category_name => $item2) {
//                    foreach ($item2 as $entire_model_name => $item3) {
//                        foreach ($item3 as $sub_model_name => $item4) {
//                            foreach ($item4 as $breakdown_type_name => $breakdown_type_count) {
//                                if (!array_key_exists($category_name, $statistics["故障类型"])) $statistics["故障类型"][$category_name] = [];
//                                if (!array_key_exists($breakdown_type_name, $statistics["故障类型"][$category_name])) $statistics["故障类型"][$category_name][$breakdown_type_name] = 0;
//                                $statistics["故障类型"][$category_name][$breakdown_type_name] += $breakdown_type_count;
//                            }
//                        }
//                    }
//                }
//            }
//
//            # 现场车间-站场
//            $device_station = $file->setPath($root_dir)->joins([$year, "{$year}-{$month}", "设备总数-现场车间-站场.json"])->fromJson();
//            $fixed_station = $file->setPath($root_dir)->joins([$year, "{$year}-{$month}", "检修数-现场车间-站场.json"])->fromJson();
//
//            return view("Report.qualityMonth", [
//                "years" => $years,
//                "months" => $months,
//                "categories_as_json" => TextHelper::toJson($categories),
//                "statistics" => $statistics,
//                "statistics_as_json" => TextHelper::toJson($statistics),
//                "device_station" => $device_station,
//                "fixed_station" => $fixed_station,
//            ]);
//        } catch (Exception $exception) {
//            return back()->with('info', '暂无数据');
//        }
//    }
//
//    /**
//     * 质量报告 种类 年度
//     * @param string $category_unique_code
//     * @return Factory|RedirectResponse|View
//     * @throws Exception
//     */
//    final public function qualityCategoryYear(string $category_unique_code)
//    {
//        $file = FileSystem::init(__FILE__);
//        $year = request("year") ?: date("Y");
//        $root_dir = $file->setPath(storage_path("app/质量报告"))->current();
//
//        # 获取基本信息
//        $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//        $categories = $file->setPath($root_dir)->joins([$year, "种类.json"])->fromJson();
//
//        try {
//            $statistics = [];
//            $statistic = [
//                "设备" => 0,
//                "检修" => 0,
//                "故障类型" => [],
//            ];
//
//            # 格式化检修数
//            $fixed = $file->setPath($root_dir)->joins([$year, "检修数.json"])->fromJson();
//            foreach ($fixed as $factory_name => $fixed_item) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                if (!array_key_exists($factory_name, $statistics)) $statistics[$factory_name] = $statistic;
//                if (array_key_exists($categories[$category_unique_code], $fixed_item)) $statistics[$factory_name]["检修"] = $fixed_item[$categories[$category_unique_code]]["count"];
//            }
//
//            # 格式化设备器材数
//            $device = $file->setPath($root_dir)->joins([$year, "{$year}-12", "设备数-供应商-种类.json"])->fromJson();
//            foreach ($device as $factory_name => $device_item) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                if ($factory_name !== "count") {
//                    if (!array_key_exists($factory_name, $statistics)) $statistics[$factory_name] = $statistic;
//                    if (array_key_exists($categories[$category_unique_code], $device_item)) $statistics[$factory_name]["设备"] = $device_item[$categories[$category_unique_code]];
//                }
//            }
//
//            # 格式化故障类型
//            $breakdown_type = $file->setPath($root_dir)->joins([$year, "故障类型.json"])->fromJson();
//            foreach ($breakdown_type as $factory_name => $item1) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                foreach ($item1 as $category_name => $item2) {
//                    if ($category_name === $categories[$category_unique_code]) {
//                        foreach ($item2 as $entire_model_name => $item3) {
//                            foreach ($item3 as $sub_model_name => $item4) {
//                                foreach ($item4 as $breakdown_type_name => $breakdown_type_count) {
//                                    if (!array_key_exists($factory_name, $statistics)) $statistics[$factory_name] = $statistic;
//                                    if (!array_key_exists($breakdown_type_name, $statistics[$factory_name]["故障类型"])) $statistics[$factory_name]["故障类型"][$breakdown_type_name] = 0;
//                                    $statistics[$factory_name]["故障类型"][$breakdown_type_name] = $breakdown_type_count;
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//
//            return view("Report.qualityCategoryYear", [
//                "years" => $years,
//                "statistics" => $statistics,
//                "statistics_as_json" => TextHelper::toJson($statistics),
//                "current_category_unique_code" => $category_unique_code,
//                "current_category_name" => $categories[$category_unique_code],
//            ]);
//        } catch (Exception $e) {
//            return back()->with("info", '暂无数据');
//        }
//    }
//
//    /**
//     * 质量报告 种类 季度
//     * @param string $category_unique_code
//     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
//     */
//    final public function qualityCategoryQuarter(string $category_unique_code)
//    {
//        try {
//            $file = FileSystem::init(__FILE__);
//            $root_dir = $file->setPath(storage_path("app/质量报告"))->current();
//
//            $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//            $year = request("year") ?: date("Y");
//            $quarters = ["1季度", "2季度", "3季度", "4季度"];
//            $months = ["1季度" => [1, 4], "2季度" => [4, 7], "3季度" => [7, 10], "4季度" => [10, 13]];
//            $month = request("month") ? intval(request("month")) : intval(date("m"));
//            $quarter = request("quarter") ?: $quarters[intval(floor($month / 3))];
//            $categories = $file->setPath($root_dir)->joins([$year, "种类.json"])->fromJson();
//
//            $statistics = [];
//            $statistic = [
//                "设备" => 0,
//                "检修" => 0,
//                "故障类型" => [],
//            ];
//
//            # 格式化检修数
//            $fixed = $file->setPath($root_dir)->joins([$year, "{$year}年 {$quarter}-检修数.json"])->fromJson();
//            foreach ($fixed as $factory_name => $fixed_item) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                if (!array_key_exists($factory_name, $statistics)) $statistics[$factory_name] = $statistic;
//                if (array_key_exists($categories[$category_unique_code], $fixed_item)) $statistics[$factory_name]["检修"] = $fixed_item[$categories[$category_unique_code]]["count"];
//            }
//
//            # 格式化设备器材数
//            for ($i = $months[$quarter][0]; $i < $months[$quarter][1]; $i++) {
//                $I = str_pad(strval($i), 2, '0', STR_PAD_LEFT);
//                $device = $file->setPath($root_dir)->joins([$year, "{$year}-{$I}", "设备数-种类-供应商.json"])->fromJson();
//                foreach ($device as $category_name => $item)
//                    if ($category_name === $categories[$category_unique_code]) {
//                        foreach ($item as $factory_name => $item2) {
//                            if ($factory_name == 'null' or $factory_name == '') continue;
//                            if ($factory_name !== "count") {
//                                if (!array_key_exists($factory_name, $statistics)) $statistics[$factory_name] = $statistic;
//                                $statistics[$factory_name]["设备"] = $item2;
//                            }
//                        }
//                    }
//            }
//
//            # 格式化故障类型
//            $breakdown_type = $file->setPath($root_dir)->joins([$year, "{$year}年 {$quarter}-故障类型.json"])->fromJson();
//            foreach ($breakdown_type as $factory_name => $item1) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                foreach ($item1 as $category_name => $item2) {
//                    foreach ($item2 as $entire_model_name => $item3) {
//                        foreach ($item3 as $sub_model_name => $item4) {
//                            foreach ($item4 as $breakdown_type_name => $breakdown_type_count) {
//                                if (!array_key_exists($factory_name, $statistics)) $statistics[$factory_name] = $statistic;
//                                if ($category_name === $categories[$category_unique_code]) {
//                                    if (!array_key_exists($breakdown_type_name, $statistics[$factory_name]["故障类型"])) $statistics[$factory_name]["故障类型"][$breakdown_type_name] = 0;
//                                    $statistics[$factory_name]["故障类型"][$breakdown_type_name] += $breakdown_type_count;
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//
//            return view("Report.qualityCategoryQuarter", [
//                "year" => $year,
//                "years" => $years,
//                "quarters" => $quarters,
//                "statistics" => $statistics,
//                "statistics_as_json" => TextHelper::toJson($statistics),
//                "current_category_unique_code" => $category_unique_code,
//                "current_category_name" => $categories[$category_unique_code],
//                "current_quarter" => $quarter,
//            ]);
//        } catch (Exception $exception) {
//            return back()->with('info', '暂无数据');
//        }
//    }
//
//    /**
//     * 质量报告 种类 月度
//     * @param string $category_unique_code
//     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
//     */
//    final public function qualityCategoryMonth(string $category_unique_code)
//    {
//        try {
//            $file = FileSystem::init(__FILE__);
//            $year = request("year") ?: date("Y");
//            $month = request("month") ?: date("m");
//            $root_dir = $file->setPath(storage_path("app/质量报告"))->current();
//            $categories = $file->setPath($root_dir)->joins([$year, "种类.json"])->fromJson();
//
//            # 获取基本信息
//            $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//            $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
//
//            $statistics = [];
//            $statistic = [
//                "设备" => 0,
//                "检修" => 0,
//                "故障类型" => [],
//            ];
//
//            # 格式化检修数
//            $fixed = $file->setPath($root_dir)->joins([$year, "{$year}-{$month}", "检修数.json"])->fromJson();
//            foreach ($fixed as $factory_name => $fixed_item) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                if (!array_key_exists($factory_name, $statistics)) $statistics[$factory_name] = $statistic;
//                if (array_key_exists($categories[$category_unique_code], $fixed_item)) $statistics[$factory_name]["检修"] = $fixed_item[$categories[$category_unique_code]]["count"];
//            }
//
//            # 格式化设备
//            $device = $file->setPath($root_dir)->joins([$year, "{$year}-{$month}", "设备数-种类-供应商.json"])->fromJson();
//            foreach ($device as $category_name => $item) {
//                if ($category_name === $categories[$category_unique_code]) {
//                    foreach ($item as $factory_name => $item2) {
//                        if ($factory_name == 'null' or $factory_name == '') continue;
//                        if ($factory_name !== "count") {
//                            if (!array_key_exists($factory_name, $statistics)) $statistics[$factory_name] = $statistic;
//                            $statistics[$factory_name]["设备"] = $item2;
//                        }
//                    }
//                }
//            }
//
//            # 故障类型
//            $breakdown_type = $file->setPath($root_dir)->joins([$year, "{$year}-{$month}", "故障类型.json"])->fromJson();
//            foreach ($breakdown_type as $factory_name => $item1) {
//                if ($factory_name == 'null' or $factory_name == '') continue;
//                foreach ($item1 as $category_name => $item2) {
//                    foreach ($item2 as $entire_model_name => $item3) {
//                        foreach ($item3 as $sub_model_name => $item4) {
//                            foreach ($item4 as $breakdown_type_name => $breakdown_type_count) {
//                                if (!array_key_exists($factory_name, $statistics)) $statistics[$factory_name] = $statistic;
//                                if ($category_name === $categories[$category_unique_code]) {
//                                    if (!array_key_exists($breakdown_type_name, $statistics[$factory_name]["故障类型"])) $statistics[$factory_name]["故障类型"][$breakdown_type_name] = 0;
//                                    $statistics[$factory_name]["故障类型"][$breakdown_type_name] += $breakdown_type_count;
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//
//            return view("Report.qualityCategoryMonth", [
//                "years" => $years,
//                "months" => $months,
//                "statistics" => $statistics,
//                "statistics_as_json" => TextHelper::toJson($statistics),
//                "current_category_unique_code" => $category_unique_code,
//                "current_category_name" => $categories[$category_unique_code],
//            ]);
//        } catch (Exception $exception) {
//            return back()->with('info', '暂无数据');
//        }
//    }
//
//    /**
//     * 质量报告 现场车间 年度
//     * @param string $workshop_name
//     * @return Factory|RedirectResponse|View
//     * @throws Exception
//     */
//    final public function qualityWorkshopYear(string $workshop_name)
//    {
//        $file = FileSystem::init(__FILE__);
//        $year = request("year") ?: date("Y");
//        $root_dir = $file->setPath(storage_path("app/质量报告"))->current();
//
//        # 获取基本信息
//        $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//
//        try {
//            $statistics = [];
//
//            # 格式化设备数
//            $device = $file->setPath($root_dir)->joins([$year, '设备总数-现场车间-站场.json'])->fromJson();
//            if (array_key_exists($workshop_name, $device)) $device = $device[$workshop_name]['sub'];
//            foreach ($device as $station_name => $count) $statistics[$station_name] = ['设备' => $count, '检修' => 0];
//
//            # 格式化检修数
//            $fixed = $file->setPath($root_dir)->joins([$year, "检修数-现场车间-站场.json"])->fromJson();
//            if (array_key_exists($workshop_name, $fixed)) $fixed = $fixed[$workshop_name]['sub'];
//            foreach ($fixed as $station_name => $count) $statistics[$station_name]['检修'] = $count;
//
//            return view("Report.qualityWorkshopYear", [
//                "years" => $years,
//                "statistics" => $statistics,
//                "statistics_as_json" => TextHelper::toJson($statistics),
//                'current_workshop_name' => $workshop_name,
//            ]);
//        } catch (Exception $e) {
//            return back()->with("info", '暂无数据');
//        }
//    }
//
//    /**
//     * 质量报告 现场车间 季度
//     * @param string $workshop_name
//     * @return Factory|RedirectResponse|View
//     * @throws Exception
//     */
//    final public function qualityWorkshopQuarter(string $workshop_name)
//    {
//        try {
//            $file = FileSystem::init(__FILE__);
//            $root_dir = $file->setPath(storage_path("app/质量报告"))->current();
//
//            $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//            $year = request("year") ?: date("Y");
//            $quarters = ["1季度", "2季度", "3季度", "4季度"];
//            $month = request("month") ? intval(request("month")) : intval(date("m"));
//            $quarter = request("quarter") ?: $quarters[intval(floor($month / 3))];
//
//            $statistics = [];
//
//            # 格式化设备数
//            $device = $file->setPath($root_dir)->joins([$year, "{$year}年 {$quarter}-设备总数-现场车间-站场.json"])->fromJson();
//            if (array_key_exists($workshop_name, $device)) $device = $device[$workshop_name]['sub'];
//            foreach ($device as $station_name => $count) $statistics[$station_name] = ['设备' => $count, '检修' => 0];
//
//            # 格式化检修数
//            $fixed = $file->setPath($root_dir)->joins([$year, "{$year}年 {$quarter}-检修数-现场车间-站场.json"])->fromJson();
//            if (array_key_exists($workshop_name, $fixed)) $fixed = $fixed[$workshop_name]['sub'];
//            foreach ($fixed as $station_name => $count) $statistics[$station_name]['检修'] = $count;
//
//            return view("Report.qualityWorkshopQuarter", [
//                "year" => $year,
//                "years" => $years,
//                "quarters" => $quarters,
//                "statistics" => $statistics,
//                "statistics_as_json" => TextHelper::toJson($statistics),
//                "current_workshop_name" => $workshop_name,
//                "current_quarter" => $quarter,
//            ]);
//        } catch (Exception $exception) {
//            return back()->with('info', '暂无数据');
//        }
//    }
//
//    /**
//     * 质量报告 现场车间 月度
//     * @param string $workshop_name
//     * @return Factory|RedirectResponse|View
//     */
//    final public function qualityWorkshopMonth(string $workshop_name)
//    {
//        try {
//            $file = FileSystem::init(__FILE__);
//            $year = request("year") ?: date("Y");
//            $month = request("month") ?: date("m");
//            $root_dir = $file->setPath(storage_path("app/质量报告"))->current();
//
//            # 获取基本信息
//            $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//            $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
//
//            $statistics = [];
//
//            # 格式化设备数
//            $device = $file->setPath($root_dir)->joins([$year, "{$year}-{$months[intval($month) - 1]}", "设备总数-现场车间-站场.json"])->fromJson();
//            if (array_key_exists($workshop_name, $device)) $device = $device[$workshop_name]['sub'];
//            foreach ($device as $station_name => $count) $statistics[$station_name] = ['设备' => $count, '检修' => 0];
//
//            # 格式化检修数
//            $fixed = $file->setPath($root_dir)->joins([$year, "{$year}-{$months[intval($month) - 1]}", "检修数-现场车间-站场.json"])->fromJson();
//            if (array_key_exists($workshop_name, $fixed)) $fixed = $fixed[$workshop_name]['sub'];
//            foreach ($fixed as $station_name => $count) $statistics[$station_name]['检修'] = $count;
//
//            return view("Report.qualityWorkshopMonth", [
//                "years" => $years,
//                "months" => $months,
//                "statistics" => $statistics,
//                "statistics_as_json" => TextHelper::toJson($statistics),
//                "current_workshop_name" => $workshop_name,
//            ]);
//        } catch (Exception $exception) {
//            dd($exception->getMessage());
//            return back()->with('info', '暂无数据');
//        }
//    }
//
//    /**
//     * 质量报告 检修单
//     * @return Factory|RedirectResponse|View
//     */
//    final public function qualityEntireInstance_bak()
//    {
//        try {
//            $file = FileSystem::init(__FILE__);
//            $root_dir = storage_path("app/质量报告");
//            $year = request("Year", date("Y"));
//
//            $query_condition = QueryFacade::init($root_dir)
//                ->setCategoriesWithFile([$year, "种类.json"])
//                ->setEntireModelsWithFile([$year, "类型.json"])
//                ->setSubModelsWithFile([$year, "型号和子类.json"]);
//
//            if (request('workshop_name')) {
//                $scene_workshop_unique_code = DB::table('maintains')
//                    ->where('deleted_at', null)
//                    ->where('name', request('workshop_name'))
//                    ->where('type', 'SCENE_WORKSHOP')
//                    ->first();
//            }
//
//            $query_condition->make(
//                strval(request("category_unique_code")),
//                strval(request("entire_model_unique_code")),
//                strval(request("sub_model_unique_code")),
//                strval(request("factory_name")),
//                strval(request("scene_workshop_unique_code", request('workshop_name') ? $scene_workshop_unique_code ? $scene_workshop_unique_code->unique_code : '' : '')),
//                strval(request("station_name")),
//                strval(request("status_unique_code"))
//            );
//
//            $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
//            $quarters = ["1季度" => [1, 3], "2季度" => [4, 6], "3季度" => [7, 9], "4季度" => [9, 12]];
//            $months = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
//
//            switch (request("type")) {
//                case "year":
//                default:
//                    $origin_at = Carbon::create($year, 1, 1)->startOfYear()->toDateTimeString();
//                    $finish_at = Carbon::create($year, 1, 1)->endOfYear()->toDateTimeString();
//                    break;
//                case "quarter":
//                    list($origin_month, $finish_month) = $quarters[request("quarter")];
//                    $origin_at = Carbon::create($year, $origin_month, 1)->startOfMonth()->toDateTimeString();
//                    $finish_at = Carbon::create(request("month"), $finish_month, 1)->endOfMonth()->toDateTimeString();
//                    break;
//                case "month":
//                    $origin_at = Carbon::create($year, request("month"), 1)->startOfMonth()->toDateTimeString();
//                    $finish_at = Carbon::create($year, request("month"), 1)->endOfMonth()->toDateTimeString();
//                    break;
//            }
//
//            switch (substr($query_condition->get("current_category_unique_code"), 0, 1)) {
//                case "S":
//                    $entire_instances = DB::table('entire_instances as ei')
//                        ->select([
//                            'ei.identity_code',
//                            'ei.factory_name',
//                            'fw.created_at as fw_created_at',
//                            'ei.status',
//                            'ei.maintain_station_name',
//                            'ei.open_direction',
//                            'ei.said_rod',
//                            'ei.crossroad_number',
//                            'ei.line_name',
//                            'ei.maintain_location_code',
//                            'ei.category_name',
//                            'ei.model_name',
//                        ])
//                        ->join(DB::raw('fix_workflows fw'), 'fw.entire_instance_identity_code', '=', 'ei.identity_code')
//                        ->join(DB::raw('entire_models as sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
//                        ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'sm.parent_unique_code')
//                        ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
//                        ->where('fw.deleted_at', null)
//                        ->where('sm.deleted_at', null)
//                        ->where('em.deleted_at', null)
//                        ->where('c.deleted_at', null)
//                        ->where('ei.deleted_at', null)
//                        ->where('ei.factory_name', '<>', null)
//                        ->where('ei.factory_name', '<>', '')
//                        ->where('fw.status', 'FIXED')
//                        ->whereNotBetween('ei.created_at', [Carbon::now()->startOfMonth()->format('Y-m-d H:i:s'), Carbon::now()->endOfMonth()->format('Y-m-d H:i:s')])
//                        ->when(
//                            $query_condition->get("current_status_unique_code"),
//                            function ($query) use ($query_condition) {
//                                return $query->get("ei.status", $query_condition->get("current_status_unique_code"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("current_factory_name"),
//                            function ($query) use ($query_condition) {
//                                return $query->where("ei.factory_name", $query_condition->get("current_factory_name"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get('current_category_unique_code'),
//                            function ($query) use ($query_condition) {
//                                return $query->where('c.unique_code', $query_condition->get('current_category_unique_code'));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("current_entire_model_unique_code"),
//                            function ($query) use ($query_condition) {
//                                return $query->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("current_sub_model_unique_code"),
//                            function ($query) use ($query_condition) {
//                                return $query->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("maintain_type"),
//                            function ($query) use ($query_condition) {
//                                if ($query_condition->get("maintain_type") == "current_station_name") {
//                                    return $query->where("ei.maintain_station_name", $query_condition->get("current_station_name"));
//                                } elseif ($query_condition->get("maintain_type") == "current_station_names") {
//                                    return $query->whereIn("ei.maintain_station_name", $query_condition->get("current_station_names"));
//                                } else {
//                                    return $query;
//                                }
//                            }
//                        )
//                        ->groupBy('ei.factory_name', 'sm.name', 'em.name', 'c.name', 'ei.identity_code')
//                        ->paginate();
//                    break;
//                case "Q":
//                    $entire_instances = DB::table('entire_instances as ei')
//                        ->select([
//                            'ei.factory_name',
//                            'sm.name as sm',
//                            'em.name as em',
//                            'c.name',
//                            'ei.identity_code',
//                            'fw.created_at as fw_created_at',
//                            'ei.status',
//                            'ei.maintain_station_name',
//                            'ei.open_direction',
//                            'ei.said_rod',
//                            'ei.crossroad_number',
//                            'ei.line_name',
//                            'ei.maintain_location_code',
//                            'ei.category_name',
//                            'ei.model_name',
//                        ])
//                        ->join(DB::raw('fix_workflows fw'), 'fw.entire_instance_identity_code', '=', 'ei.identity_code')
//                        ->join(DB::raw('entire_models as sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
//                        ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'sm.parent_unique_code')
//                        ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
//                        ->where('fw.deleted_at', null)
//                        ->where('sm.deleted_at', null)
//                        ->where('em.deleted_at', null)
//                        ->where('c.deleted_at', null)
//                        ->where('ei.deleted_at', null)
//                        ->where('ei.factory_name', '<>', null)
//                        ->where('ei.factory_name', '<>', '')
//                        ->where('fw.status', 'FIXED')
//                        ->whereNotBetween('ei.created_at', [Carbon::now()->startOfMonth()->format('Y-m-d H:i:s'), Carbon::now()->endOfMonth()->format('Y-m-d H:i:s')])
//                        ->when(
//                            $query_condition->get("current_status_unique_code"),
//                            function ($query) use ($query_condition) {
//                                return $query->get("ei.status", $query_condition->get("current_status_unique_code"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("current_factory_name"),
//                            function ($query) use ($query_condition) {
//                                return $query->where("ei.factory_name", $query_condition->get("current_factory_name"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get('current_category_unique_code'),
//                            function ($query) use ($query_condition) {
//                                return $query->where('c.unique_code', $query_condition->get('current_category_unique_code'));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("current_entire_model_unique_code"),
//                            function ($query) use ($query_condition) {
//                                return $query->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("current_sub_model_unique_code"),
//                            function ($query) use ($query_condition) {
//                                return $query->where("sm.unique_code", $query_condition->get("current_sub_model_unique_code"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("maintain_type"),
//                            function ($query) use ($query_condition) {
//                                if ($query_condition->get("maintain_type") == "current_station_name") {
//                                    return $query->where("ei.maintain_station_name", $query_condition->get("current_station_name"));
//                                } elseif ($query_condition->get("maintain_type") == "current_station_names") {
//                                    return $query->whereIn("ei.maintain_station_name", $query_condition->get("current_station_names"));
//                                } else {
//                                    return $query;
//                                }
//                            }
//                        )
//                        ->groupBy('ei.factory_name', 'sm.name', 'em.name', 'c.name', 'ei.identity_code')
//                        ->paginate();
//                    break;
//                default:
//                    $entire_instances = DB::table('entire_instances as ei')
//                        ->select([
//                            'ei.identity_code',
//                            'ei.factory_name',
//                            'fw.created_at as fw_created_at',
//                            'ei.status',
//                            'ei.maintain_station_name',
//                            'ei.open_direction',
//                            'ei.said_rod',
//                            'ei.crossroad_number',
//                            'ei.line_name',
//                            'ei.maintain_location_code',
//                            'ei.category_name',
//                            'ei.model_name',
//                        ])
//                        ->join(DB::raw('fix_workflows fw'), 'fw.entire_instance_identity_code', '=', 'ei.identity_code')
//                        ->join(DB::raw('entire_models as sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
//                        ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'sm.parent_unique_code')
//                        ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
//                        ->where('fw.deleted_at', null)
//                        ->where('ei.deleted_at', null)
//                        ->where('ei.factory_name', '<>', null)
//                        ->where('ei.factory_name', '<>', '')
//                        ->where('fw.status', 'FIXED')
//                        ->whereNotBetween('ei.created_at', [Carbon::now()->startOfMonth()->format('Y-m-d H:i:s'), Carbon::now()->endOfMonth()->format('Y-m-d H:i:s')])
//                        ->when(
//                            $query_condition->get("current_status_unique_code"),
//                            function ($query) use ($query_condition) {
//                                return $query->get("ei.status", $query_condition->get("current_status_unique_code"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("current_factory_name"),
//                            function ($query) use ($query_condition) {
//                                return $query->where("ei.factory_name", $query_condition->get("current_factory_name"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get('current_category_unique_code'),
//                            function ($query) use ($query_condition) {
//                                return $query->where('c.unique_code', $query_condition->get('current_category_unique_code'));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("current_entire_model_unique_code"),
//                            function ($query) use ($query_condition) {
//                                return $query->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("current_sub_model_unique_code"),
//                            function ($query) use ($query_condition) {
//                                return $query->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
//                            }
//                        )
//                        ->when(
//                            $query_condition->get("maintain_type"),
//                            function ($query) use ($query_condition) {
//                                if ($query_condition->get("maintain_type") == "current_station_name") {
//                                    return $query->where("ei.maintain_station_name", $query_condition->get("current_station_name"));
//                                } elseif ($query_condition->get("maintain_type") == "current_station_names") {
//                                    return $query->whereIn("ei.maintain_station_name", $query_condition->get("current_station_names"));
//                                } else {
//                                    return $query;
//                                }
//                            }
//                        )
//                        ->groupBy('ei.factory_name', 'sm.name', 'em.name', 'c.name', 'ei.identity_code')
//                        ->paginate();
//                    break;
//            }
//
//            return view("Report.qualityEntireInstance", [
//                "years" => $years,
//                "quarters" => $quarters,
//                "months" => $months,
//                "queryConditions" => $query_condition->toJson(),
//                "statuses" => $query_condition->get("statuses"),
//                "entireInstances" => $entire_instances,
//            ]);
//        } catch (Exception $exception) {
//            return back()->with('info', '暂无数据');
//        }
//    }

    # TODO:: 删除END

    /**
     * 一次过检 年度
     * @throws Exception
     */
    final public function ripeYear()
    {
        try {
            $a = explode('-', request('date'));
            if (count($a) == 2) {
                $year = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->year;
            } else {
                $year = request('date', date("Y"));
            }

            $file = FileSystem::init(__FILE__);
            $file_dir = storage_path("app/一次过检");

            if (request('download') == '1') {
                # 下载一次过检Excel
                $organization_code = env("ORGANIZATION_NAME");
                $filename = "{$year}年{$organization_code}一次过检.xlsx";
                if (is_file(public_path($filename))) unlink(public_path($filename));
                if (!is_file("{$file_dir}/{$year}/{$filename}")) return "<script>alert('excel文件不存在：{$file_dir}/{$year}/{$filename}');</script>";
                copy("{$file_dir}/{$year}/{$filename}", public_path("{$filename}"));
                return redirect(url("/{$filename}"));
            }

            $years = $file->setPath($file_dir)->joins(["yearList.json"])->fromJson();
            $categories = array_flip($file->setPath($file_dir)->joins([$year, "种类.json"])->fromJson());

            $statistics_with_category = $file->setPath($file_dir)->joins([$year, "年-种类.json"])->fromJson();
            $statistics_with_account = $file->setPath($file_dir)->joins([$year, "年-人员.json"])->fromJson();

            return view('Report.ripeYear', [
                'years' => $years,
                'categories' => $categories,
                'categories_as_json' => TextHelper::toJson($categories),
                'statistics_with_category' => $statistics_with_category,
                'statistics_with_category_as_json' => TextHelper::toJson($statistics_with_category),
                'statistics_with_account' => $statistics_with_account,
                'statistics_with_account_as_json' => TextHelper::toJson($statistics_with_account),
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 一次过检 季度
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function ripeQuarter()
    {
        try {
            $a = explode('-', request('date'));
            if (count($a) == 2) {
                $year = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->year;
            } else {
                $year = request('date', date("Y"));
            }

            $file = FileSystem::init(__FILE__);
            $file_dir = storage_path("app/一次过检");

            $years = $file->setPath($file_dir)->joins(["yearList.json"])->fromJson();
            $quarters = ["1季度", "2季度", "3季度", "4季度"];
            $month = intval(date("m"));
            $quarter = request("quarter", $quarters[floor($month / 3) - 1]);
            $categories = array_flip($file->setPath($file_dir)->joins([$year, "种类.json"])->fromJson());

            $statistics_with_category = $file->setPath($file_dir)->joins([$year, "{$quarter}-种类.json"])->fromJson();
            $statistics_with_account = $file->setPath($file_dir)->joins([$year, "{$quarter}-人员.json"])->fromJson();

            return view('Report.ripeQuarter', [
                'years' => $years,
                'quarters' => $quarters,
                'categories' => $categories,
                'categories_as_json' => TextHelper::toJson($categories),
                'statistics_with_category' => $statistics_with_category,
                'statistics_with_category_as_json' => TextHelper::toJson($statistics_with_category),
                'statistics_with_account' => $statistics_with_account,
                'statistics_with_account_as_json' => TextHelper::toJson($statistics_with_account),
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 一次过检 月度
     * @throws Exception
     */
    final public function ripeMonth()
    {
        try {
            $file = FileSystem::init(__FILE__);
            $year = request("year") ?: date("Y");
            $month = request("month") ?: date("m");
            $file_dir = $file->setPath(storage_path("app/一次过检"))->current();
            $categories = array_flip($file->setPath($file_dir)->joins([$year, "种类.json"])->fromJson());

            # 获取基本信息
            $years = $file->setPath($file_dir)->join("yearList.json")->fromJson();
            $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

            $statistics_with_category = $file->setPath($file_dir)->joins([$year, "{$year}-{$month}", "月-种类.json"])->fromJson();
            $statistics_with_account = $file->setPath($file_dir)->joins([$year, "{$year}-{$month}", "月-人员.json"])->fromJson();

            return view('Report.ripeMonth', [
                'years' => $years,
                'months' => $months,
                'categories' => $categories,
                'categories_as_json' => TextHelper::toJson($categories),
                'statistics_with_category' => $statistics_with_category,
                'statistics_with_category_as_json' => TextHelper::toJson($statistics_with_category),
                'statistics_with_account' => $statistics_with_account,
                'statistics_with_account_as_json' => TextHelper::toJson($statistics_with_account),
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 一次过检 指定种类 年度
     * @param string $category_unique_code
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function ripeCategoryYear(string $category_unique_code)
    {
        try {
            $a = explode('-', request('date'));
            if (count($a) == 2) {
                $year = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->year;
            } else {
                $year = request('date', date("Y"));
            }

            $file = FileSystem::init(__FILE__);
            $file_dir = storage_path("app/一次过检");

            $years = $file->setPath($file_dir)->joins(["yearList.json"])->fromJson();
            $categories = $file->setPath($file_dir)->joins([$year, "种类.json"])->fromJson();
            $current_category_name = $categories[$category_unique_code];
            $entire_models = $file->setPath($file_dir)->joins([$year, "类型.json"])->fromJson()[$current_category_name];
            $current_work_area = $category_unique_code == 'S03' ? '1' : $category_unique_code == 'Q01' ? '2' : '3';
            $current_work_area_name = $category_unique_code == 'S03' ? '转辙机' : $category_unique_code == 'Q01' ? '继电器' : '综合';


            $statistics_with_entire_model = $file->setPath($file_dir)->joins([$year, "年-类型.json"])->fromJson()[$current_category_name];
            $statistics_with_account = $file->setPath($file_dir)->joins([$year, "年-人员.json"])->fromJson()[$current_work_area];

            return view('Report.ripeCategoryYear', [
                'years' => $years,
                'categories' => array_flip($categories),
                'current_category_unique_code' => $category_unique_code,
                'current_category_name' => $current_category_name,
                'current_work_area_name' => $current_work_area_name,
                'categories_as_json' => TextHelper::toJson(array_flip($categories)),
                'entire_models' => array_flip($entire_models),
                'entire_models_as_json' => TextHelper::toJson(array_flip($entire_models)),
                'statistics_with_entire_model' => $statistics_with_entire_model,
                'statistics_with_entire_model_as_json' => TextHelper::toJson($statistics_with_entire_model),
                'statistics_with_account' => $statistics_with_account,
                'statistics_with_account_as_json' => TextHelper::toJson($statistics_with_account),
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 一次过检 指定种类 季度
     * @param string $category_unique_code
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function ripeCategoryQuarter(string $category_unique_code)
    {
        try {
            $a = explode('-', request('date'));
            if (count($a) == 2) {
                $year = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->year;
            } else {
                $year = request('date', date("Y"));
            }

            $file = FileSystem::init(__FILE__);
            $file_dir = storage_path("app/一次过检");

            $years = $file->setPath($file_dir)->joins(["yearList.json"])->fromJson();
            $quarters = ["1季度", "2季度", "3季度", "4季度"];
            $month = intval(date("m"));
            $quarter = request("quarter", $quarters[floor($month / 3) - 1]);
            $categories = $file->setPath($file_dir)->joins([$year, "种类.json"])->fromJson();
            $current_category_name = $categories[$category_unique_code];
            $entire_models = $file->setPath($file_dir)->joins([$year, "类型.json"])->fromJson()[$current_category_name];
            $current_work_area = $category_unique_code == 'S03' ? '1' : $category_unique_code == 'Q01' ? '2' : '3';
            $current_work_area_name = $category_unique_code == 'S03' ? '转辙机' : $category_unique_code == 'Q01' ? '继电器' : '综合';

            $statistics_with_entire_models = $file->setPath($file_dir)->joins([$year, "{$quarter}-类型.json"])->fromJson()[$current_category_name];
            $statistics_with_account = $file->setPath($file_dir)->joins([$year, "{$quarter}-人员.json"])->fromJson()[$current_work_area];

            return view('Report.ripeCategoryQuarter', [
                'years' => $years,
                'quarters' => $quarters,
                'categories' => array_flip($categories),
                'categories_as_json' => TextHelper::toJson(array_flip($categories)),
                'current_category_unique_code' => $category_unique_code,
                'current_category_name' => $current_category_name,
                'current_work_area_name' => $current_work_area_name,
                'entire_models' => array_flip($entire_models),
                'entire_models_as_json' => TextHelper::toJson(array_flip($entire_models)),
                'statistics_with_entire_models' => $statistics_with_entire_models,
                'statistics_with_entire_models_as_json' => TextHelper::toJson($statistics_with_entire_models),
                'statistics_with_account' => $statistics_with_account,
                'statistics_with_account_as_json' => TextHelper::toJson($statistics_with_account),
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 一次过检 指定种类 月度
     * @param string $category_unique_code
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function ripeCategoryMonth(string $category_unique_code)
    {
        try {
            $file = FileSystem::init(__FILE__);
            $year = request("year") ?: date("Y");
            $month = request("month") ?: date("m");
            $file_dir = $file->setPath(storage_path("app/一次过检"))->current();
            $categories = $file->setPath($file_dir)->joins([$year, "种类.json"])->fromJson();
            $current_category_name = $categories[$category_unique_code];
            $entire_models = $file->setPath($file_dir)->joins([$year, "类型.json"])->fromJson()[$current_category_name];
            $current_work_area = $category_unique_code == 'S03' ? '1' : $category_unique_code == 'Q01' ? '2' : '3';
            $current_work_area_name = $category_unique_code == 'S03' ? '转辙机' : $category_unique_code == 'Q01' ? '继电器' : '综合';

            # 获取基本信息
            $years = $file->setPath($file_dir)->join("yearList.json")->fromJson();
            $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

            $statistics_with_entire_models = $file->setPath($file_dir)->joins([$year, "{$year}-{$month}", "月-类型.json"])->fromJson()[$current_category_name];
            $statistics_with_account = $file->setPath($file_dir)->joins([$year, "{$year}-{$month}", "月-人员.json"])->fromJson()[$current_work_area];

            return view('Report.ripeCategoryMonth', [
                'years' => $years,
                'months' => $months,
                'categories' => array_flip($categories),
                'categories_as_json' => TextHelper::toJson(array_flip($categories)),
                'current_category_unique_code' => $category_unique_code,
                'current_category_name' => $current_category_name,
                'current_work_area_name' => $current_work_area_name,
                'entire_models' => array_flip($entire_models),
                'entire_models_as_json' => TextHelper::toJson(array_flip($entire_models)),
                'statistics_with_entire_models' => $statistics_with_entire_models,
                'statistics_with_entire_models_as_json' => TextHelper::toJson($statistics_with_entire_models),
                'statistics_with_account' => $statistics_with_account,
                'statistics_with_account_as_json' => TextHelper::toJson($statistics_with_account),
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 一次过检 指定类型 年度
     * @param string $entire_model_unique_code
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function ripeEntireModelYear(string $entire_model_unique_code)
    {
        try {
            $a = explode('-', request('date'));
            if (count($a) == 2) {
                $year = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->year;
            } else {
                $year = request('date', date("Y"));
            }

            $file = FileSystem::init(__FILE__);
            $file_dir = storage_path("app/一次过检");

            $years = $file->setPath($file_dir)->joins(["yearList.json"])->fromJson();
            $categories = $file->setPath($file_dir)->joins([$year, "种类.json"])->fromJson();
            $category_unique_code = substr($entire_model_unique_code, 0, 3);
            $current_category_name = $categories[$category_unique_code];
            $entire_models = $file->setPath($file_dir)->joins([$year, "类型.json"])->fromJson()[$current_category_name];
            $current_entire_model_name = $entire_models[$entire_model_unique_code];
            $sub_models = $file->setPath($file_dir)->joins([$year, "型号和子类.json"])->fromJson()[$current_entire_model_name];
            $current_work_area = $category_unique_code == 'S03' ? '1' : $category_unique_code == 'Q01' ? '2' : '3';
            $current_work_area_name = $category_unique_code == 'S03' ? '转辙机' : $category_unique_code == 'Q01' ? '继电器' : '综合';

            $statistics_with_sub_model = $file->setPath($file_dir)->joins([$year, "年-型号和子类.json"])->fromJson()[$current_entire_model_name];
            $statistics_with_account = $file->setPath($file_dir)->joins([$year, "年-人员.json"])->fromJson()[$current_work_area];

            return view('Report.ripeEntireModelYear', [
                'years' => $years,
                'categories' => array_flip($categories),
                'current_category_unique_code' => $category_unique_code,
                'current_category_name' => $current_category_name,
                'current_work_area_name' => $current_work_area_name,
                'categories_as_json' => TextHelper::toJson(array_flip($categories)),
                'entire_models' => array_flip($entire_models),
                'entire_models_as_json' => TextHelper::toJson(array_flip($entire_models)),
                'current_entire_model_unique_code' => $entire_model_unique_code,
                'current_entire_model_name' => $current_entire_model_name,
                'sub_models' => array_flip($sub_models),
                'sub_models_as_json' => TextHelper::toJson(array_flip($sub_models)),
                'statistics_with_sub_model' => $statistics_with_sub_model,
                'statistics_with_sub_model_as_json' => TextHelper::toJson($statistics_with_sub_model),
                'statistics_with_account' => $statistics_with_account,
                'statistics_with_account_as_json' => TextHelper::toJson($statistics_with_account),
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 一次过检 指定类型 季度
     * @param string $entire_model_unique_code
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function ripeEntireModelQuarter(string $entire_model_unique_code)
    {
        try {
            $a = explode('-', request('date'));
            if (count($a) == 2) {
                $year = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->year;
            } else {
                $year = request('date', date("Y"));
            }

            $file = FileSystem::init(__FILE__);
            $file_dir = storage_path("app/一次过检");

            $years = $file->setPath($file_dir)->joins(["yearList.json"])->fromJson();
            $quarters = ["1季度", "2季度", "3季度", "4季度"];
            $month = intval(date("m"));
            $quarter = request("quarter", $quarters[floor($month / 3) - 1]);
            $category_unique_code = substr($entire_model_unique_code, 0, 3);
            $categories = $file->setPath($file_dir)->joins([$year, "种类.json"])->fromJson();
            $current_category_name = $categories[$category_unique_code];
            $entire_models = $file->setPath($file_dir)->joins([$year, "类型.json"])->fromJson()[$current_category_name];
            $current_entire_model_name = $entire_models[$entire_model_unique_code];
            $sub_models = $file->setPath($file_dir)->joins([$year, "型号和子类.json"])->fromJson()[$current_entire_model_name];
            $current_work_area = $category_unique_code == 'S03' ? '1' : $category_unique_code == 'Q01' ? '2' : '3';
            $current_work_area_name = $category_unique_code == 'S03' ? '转辙机' : $category_unique_code == 'Q01' ? '继电器' : '综合';

            $statistics_with_sub_models = $file->setPath($file_dir)->joins([$year, "{$quarter}-型号和子类.json"])->fromJson()[$current_entire_model_name];
            $statistics_with_account = $file->setPath($file_dir)->joins([$year, "{$quarter}-人员.json"])->fromJson()[$current_work_area];

            return view('Report.ripeEntireModelQuarter', [
                'years' => $years,
                'quarters' => $quarters,
                'categories' => array_flip($categories),
                'categories_as_json' => TextHelper::toJson(array_flip($categories)),
                'current_category_unique_code' => $category_unique_code,
                'current_category_name' => $current_category_name,
                'current_work_area_name' => $current_work_area_name,
                'entire_models' => array_flip($entire_models),
                'entire_models_as_json' => TextHelper::toJson(array_flip($entire_models)),
                'current_entire_model_unique_code' => $entire_model_unique_code,
                'current_entire_model_name' => $current_entire_model_name,
                'sub_models' => array_flip($sub_models),
                'sub_model_as_json' => TextHelper::toJson(array_flip($sub_models)),
                'statistics_with_sub_models' => $statistics_with_sub_models,
                'statistics_with_sub_models_as_json' => TextHelper::toJson($statistics_with_sub_models),
                'statistics_with_account' => $statistics_with_account,
                'statistics_with_account_as_json' => TextHelper::toJson($statistics_with_account),
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 一次过检 指定类型 月度
     * @param string $entire_model_unique_code
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function ripeEntireModelMonth(string $entire_model_unique_code)
    {
        try {
            $file = FileSystem::init(__FILE__);
            $year = request("year") ?: date("Y");
            $month = request("month") ?: date("m");
            $file_dir = $file->setPath(storage_path("app/一次过检"))->current();
            $categories = $file->setPath($file_dir)->joins([$year, "种类.json"])->fromJson();
            $category_unique_code = substr($entire_model_unique_code, 0, 3);
            $current_category_name = $categories[$category_unique_code];
            $entire_models = $file->setPath($file_dir)->joins([$year, "类型.json"])->fromJson()[$current_category_name];
            $current_entire_model_name = $entire_models[$entire_model_unique_code];
            $sub_models = $file->setPath($file_dir)->joins([$year, "型号和子类.json"])->fromJson()[$current_entire_model_name];
            $current_work_area = $category_unique_code == 'S03' ? '1' : $category_unique_code == 'Q01' ? '2' : '3';
            $current_work_area_name = $category_unique_code == 'S03' ? '转辙机' : $category_unique_code == 'Q01' ? '继电器' : '综合';

            # 获取基本信息
            $years = $file->setPath($file_dir)->join("yearList.json")->fromJson();
            $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

            $statistics_with_sub_models = $file->setPath($file_dir)->joins([$year, "{$year}-{$month}", "月-类型.json"])->fromJson()[$current_category_name];
            $statistics_with_account = $file->setPath($file_dir)->joins([$year, "{$year}-{$month}", "月-人员.json"])->fromJson()[$current_work_area];

            return view('Report.ripeEntireModelMonth', [
                'years' => $years,
                'months' => $months,
                'categories' => array_flip($categories),
                'categories_as_json' => TextHelper::toJson(array_flip($categories)),
                'current_category_unique_code' => $category_unique_code,
                'current_category_name' => $current_category_name,
                'current_work_area_name' => $current_work_area_name,
                'entire_models' => array_flip($entire_models),
                'entire_models_as_json' => TextHelper::toJson(array_flip($entire_models)),
                'current_entire_model_unique_code' => $entire_model_unique_code,
                'current_entire_model_name' => $current_entire_model_name,
                'sub_models' => array_flip($sub_models),
                'sub_models_as_json' => TextHelper::toJson(array_flip($sub_models)),
                'statistics_with_sub_models' => $statistics_with_sub_models,
                'statistics_with_sub_models_as_json' => TextHelper::toJson($statistics_with_sub_models),
                'statistics_with_account' => $statistics_with_account,
                'statistics_with_account_as_json' => TextHelper::toJson($statistics_with_account),
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 一次过检 设备列表
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|View
     */
    final public function ripeEntireInstance()
    {
        try {
            $file = FileSystem::init(__FILE__);
            $root_dir = storage_path("app/一次过检");
            $year = request("Year", date("Y"));

            $query_condition = QueryConditionFacade::init($root_dir)
                ->setCategoriesWithFile([$year, "种类.json"])
                ->setEntireModelsWithFile([$year, "类型.json"])
                ->setSubModelsWithFile([$year, "型号和子类.json"]);

            $query_condition->make(
                strval(request("category_unique_code")),
                strval(request("entire_model_unique_code")),
                strval(request("sub_model_unique_code")),
                strval(request("factory_name")),
                strval(request("scene_workshop_unique_code")),
                strval(request("station_name")),
                strval(request("status_unique_code"))
            );

            $years = $file->setPath($root_dir)->join("yearList.json")->fromJson();
            $quarters = ["1季度" => [1, 3], "2季度" => [4, 6], "3季度" => [7, 9], "4季度" => [9, 12]];
            $months = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];

            switch (request("type")) {
                case "year":
                default:
                    $origin_at = Carbon::create($year, 1, 1)->startOfYear()->toDateTimeString();
                    $finish_at = Carbon::create($year, 1, 1)->endOfYear()->toDateTimeString();
                    break;
                case "quarter":
                    list($origin_month, $finish_month) = $quarters[request("quarter")];
                    $origin_at = Carbon::create($year, $origin_month, 1)->startOfMonth()->toDateTimeString();
                    $finish_at = Carbon::create(request("month"), $finish_month, 1)->endOfMonth()->toDateTimeString();
                    break;
                case "month":
                    $origin_at = Carbon::create($year, request("month"), 1)->startOfMonth()->toDateTimeString();
                    $finish_at = Carbon::create($year, request("month"), 1)->endOfMonth()->toDateTimeString();
                    break;
            }

            switch (substr($query_condition->get("current_category_unique_code"), 0, 1)) {
                case "S":
                    $entire_instances = DB::table("fix_workflow_processes as fwp")
                        ->distinct()
                        ->select([
                            "fw.serial_number",
                            "ei.identity_code",
                            "ei.factory_name",
                            "fw.created_at as fw_created_at",
                            "ei.status",
                            "ei.maintain_station_name",
                            "ei.open_direction",
                            "ei.said_rod",
                            "ei.crossroad_number",
                            "ei.line_name",
                            "ei.maintain_location_code",
                            "ei.category_name",
                            "ei.model_name",
                        ])
                        ->join(DB::raw("fix_workflows fw"), "fw.serial_number", "=", "fwp.fix_workflow_serial_number")
                        ->join(DB::raw("entire_instances ei"), "ei.identity_code", "=", "fw.entire_instance_identity_code")
                        ->join(DB::raw("part_instances pi"), "pi.entire_instance_identity_code", "=", "ei.identity_code")
                        ->join(DB::raw("part_models pm"), "pm.unique_code", "=", "pi.part_model_unique_code")
                        ->join(DB::raw("entire_models em"), "em.unique_code", "=", "pm.entire_model_unique_code")
                        ->join(DB::raw("categories c"), "c.unique_code", "=", "ei.category_unique_code")
                        ->where("fwp.stage", "FIX_AFTER")
                        ->where("fw.status", "FIXED")
                        ->whereBetween("fw.created_at", [$origin_at, $finish_at])
                        ->where("fwp.deleted_at", null)
                        ->where("ei.deleted_at", null)
                        ->where("c.deleted_at", null)
                        ->where("em.deleted_at", null)
                        ->where("pm.deleted_at", null)
                        ->groupBy("fwp.serial_number")
                        ->having(DB::raw("count(fwp.serial_number)"), 1)
                        ->where("ei.category_unique_code", $query_condition->get("current_category_unique_code"))
                        ->when(
                            $query_condition->get("current_status_unique_code"),
                            function ($query) use ($query_condition) {
                                return $query->get("ei.status", $query_condition->get("current_status_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_factory_name"),
                            function ($query) use ($query_condition) {
                                return $query->where("ei.factory_name", $query_condition->get("current_factory_name"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_entire_model_unique_code"),
                            function ($query) use ($query_condition) {
                                return $query->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_sub_model_unique_code"),
                            function ($query) use ($query_condition) {
                                return $query->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("maintain_type"),
                            function ($query) use ($query_condition) {
                                if ($query_condition->get("maintain_type") == "current_station_name") {
                                    return $query->where("ei.maintain_station_name", $query_condition->get("current_station_name"));
                                } elseif ($query_condition->get("maintain_type") == "current_station_names") {
                                    return $query->whereIn("ei.maintain_station_name", $query_condition->get("current_station_names"));
                                } else {
                                    return $query;
                                }
                            }
                        )
                        ->paginate();
                    break;
                case "Q":
                    $entire_instances = DB::table("fix_workflow_processes as fwp")
                        ->distinct()
                        ->select([
                            "fw.serial_number",
                            "ei.identity_code",
                            "ei.factory_name",
                            "fw.created_at as fw_created_at",
                            "ei.status",
                            "ei.maintain_station_name",
                            "ei.open_direction",
                            "ei.said_rod",
                            "ei.crossroad_number",
                            "ei.line_name",
                            "ei.maintain_location_code",
                            "ei.category_name",
                            "ei.model_name",
                        ])
                        ->join(DB::raw("fix_workflows fw"), "fw.serial_number", "=", "fwp.fix_workflow_serial_number")
                        ->join(DB::raw("entire_instances ei"), "ei.identity_code", "=", "fw.entire_instance_identity_code")
                        ->join(DB::raw("categories c"), "c.unique_code", "=", "ei.category_unique_code")
                        ->join(DB::raw("entire_models sm"), "sm.unique_code", "=", "ei.entire_model_unique_code")
                        ->join(DB::raw("entire_models em"), "em.unique_code", "=", "sm.parent_unique_code")
                        ->where("fwp.stage", "FIX_AFTER")
                        ->where("fw.status", "FIXED")
                        ->whereBetween("fw.created_at", [$origin_at, $finish_at])
                        ->where("fwp.deleted_at", null)
                        ->where("ei.deleted_at", null)
                        ->where("c.deleted_at", null)
                        ->where("em.deleted_at", null)
                        ->where("sm.deleted_at", null)
                        ->groupBy("fwp.serial_number")
                        ->having(DB::raw("count(fwp.serial_number)"), 1)
                        ->where("ei.category_unique_code", $query_condition->get("current_category_unique_code"))
                        ->when(
                            $query_condition->get("current_status_unique_code"),
                            function ($query) use ($query_condition) {
                                return $query->get("ei.status", $query_condition->get("current_status_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_factory_name"),
                            function ($query) use ($query_condition) {
                                return $query->where("ei.factory_name", $query_condition->get("current_factory_name"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_entire_model_unique_code"),
                            function ($query) use ($query_condition) {
                                return $query->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_sub_model_unique_code"),
                            function ($query) use ($query_condition) {
                                return $query->where("sm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("maintain_type"),
                            function ($query) use ($query_condition) {
                                if ($query_condition->get("maintain_type") == "current_station_name") {
                                    return $query->where("ei.maintain_station_name", $query_condition->get("current_station_name"));
                                } elseif ($query_condition->get("maintain_type") == "current_station_names") {
                                    return $query->whereIn("ei.maintain_station_name", $query_condition->get("current_station_names"));
                                } else {
                                    return $query;
                                }
                            }
                        )
                        ->paginate();
                    break;
                default:
                    $fix_workflow_serial_numbers = DB::table("fix_workflow_processes as fwp")
                        ->distinct()
                        ->select(["fw.serial_number"])
                        ->join(DB::raw("fix_workflows fw"), "fw.serial_number", "=", "fwp.fix_workflow_serial_number")
                        ->join(DB::raw("entire_instances ei"), "ei.identity_code", "=", "fw.entire_instance_identity_code")
                        ->where("fw.deleted_at", null)
                        ->where("fw.status", "FIXED")
                        ->whereBetween("fw.created_at", [$origin_at, $finish_at])
                        ->where("ei.deleted_at", null)
                        ->where("ei.status", "<>", "SCRAP")
                        ->when(
                            $query_condition->get("current_status_unique_code"),
                            function ($query) use ($query_condition) {
                                return $query->get("ei.status", $query_condition->get("current_status_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_factory_name"),
                            function ($query) use ($query_condition) {
                                return $query->where("ei.factory_name", $query_condition->get("current_factory_name"));
                            }
                        )
                        ->when(
                            $query_condition->get("maintain_type"),
                            function ($query) use ($query_condition) {
                                if ($query_condition->get("maintain_type") == "current_station_name") {
                                    return $query->where("ei.maintain_station_name", $query_condition->get("current_station_name"));
                                } elseif ($query_condition->get("maintain_type") == "current_station_names") {
                                    return $query->whereIn("ei.maintain_station_name", $query_condition->get("current_station_names"));
                                } else {
                                    return $query;
                                }
                            }
                        )
                        ->pluck("fw.serial_number");

                    $entire_instances = DB::table("fix_workflow_processes as fwp")
                        ->distinct()
                        ->select([
                            "fw.serial_number",
                            "fw.entire_instance_identity_code as identity_code",
                            "ei.factory_name",
                            "fw.created_at as fw_created_at",
                            "ei.status",
                            "ei.maintain_station_name",
                            "ei.open_direction",
                            "ei.said_rod",
                            "ei.crossroad_number",
                            "ei.line_name",
                            "ei.maintain_location_code",
                            "ei.category_name",
                            "ei.model_name",
                        ])
                        ->join(DB::raw("fix_workflows fw"), "fw.serial_number", "=", "fwp.fix_workflow_serial_number")
                        ->join(DB::raw("entire_instances ei"), "ei.identity_code", "=", "fw.entire_instance_identity_code")
                        ->where("fw.deleted_at", null)
                        ->where("fw.status", "FIXED")
                        ->whereBetween("fw.created_at", [$origin_at, $finish_at])
                        ->where("ei.deleted_at", null)
                        ->where("ei.status", "<>", "SCRAP")
                        ->when(
                            $query_condition->get("current_status_unique_code"),
                            function ($query) use ($query_condition) {
                                return $query->get("ei.status", $query_condition->get("current_status_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_factory_name"),
                            function ($query) use ($query_condition) {
                                return $query->where("ei.factory_name", $query_condition->get("current_factory_name"));
                            }
                        )
                        ->when(
                            $query_condition->get("maintain_type"),
                            function ($query) use ($query_condition) {
                                if ($query_condition->get("maintain_type") == "current_station_name") {
                                    return $query->where("ei.maintain_station_name", $query_condition->get("current_station_name"));
                                } elseif ($query_condition->get("maintain_type") == "current_station_names") {
                                    return $query->whereIn("ei.maintain_station_name", $query_condition->get("current_station_names"));
                                } else {
                                    return $query;
                                }
                            }
                        )
                        ->whereIn("fw.serial_number", $fix_workflow_serial_numbers)
                        ->limit(15)->offset((request('page', 1) - 1) * 15)->get();

                    $entire_instances = new LengthAwarePaginator(
                        $entire_instances,
                        count($fix_workflow_serial_numbers),
                        15,
                        request('page', 1),
                        ['path' => url('/query'), 'pageName' => 'page',]
                    );
                    break;
            }

            return view("Report.ripeEntireInstance", [
                "years" => $years,
                "quarters" => $quarters,
                "months" => $months,
                "queryConditions" => $query_condition->toJson(),
                "statuses" => $query_condition->get("statuses"),
                "entireInstances" => $entire_instances,
            ]);
        } catch (Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 临时生产任务（全部）
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|\Illuminate\Routing\Redirector|View|string
     */
    final public function getTemporaryTaskProductionMain()
    {
        try {
            $a = explode('-', request('date'));
            if (count($a) == 2) {
                $year = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->year;
            } else {
                $year = request('date', date("Y"));
            }

            $file_dir = storage_path("app/临时检修任务/{$year}");

            if (request('download') == '1') {
                $filename = "{$year}年{$this->_organizationName}临时生产任务统计";
                # 文件复制式
                if (is_file(public_path("{$filename}.xlsx"))) unlink(public_path("{$filename}.xlsx"));
                if (!is_file("{$file_dir}/{$filename}.xlsx")) return "<script>alert('excel文件不存在：{$file_dir}/{$filename}.xlsx');</script>";
                copy("{$file_dir}/{$filename}.xlsx", public_path("{$filename}.xlsx"));
                return redirect(url("/{$filename}.xlsx"));
            }

            $ttpm_date_list = is_file(storage_path('app/临时生产任务/dateList.json')) ? Texthelper::parseJson(file_get_contents(storage_path('app/临时生产任务/dateList.json'))) : [];

            # 获取种类统计
            $ttpm_categories = TextHelper::parseJson(file_get_contents("{$file_dir}/种类.json"));
            $ttpm_categories_flip = array_flip($ttpm_categories);
            $ttpm_mission_with_year = json_decode(file_get_contents("{$file_dir}/任务-种类.json"), true);
            $ttpm_fixed_with_year = json_decode(file_get_contents("{$file_dir}/检修-种类.json"), true);
            $ttpm_mission_with_month = [];
            $ttpm_fixed_with_month = [];
            for ($i = 1; $i < 13; $i++) {
                $m = str_pad(strval($i), 2, '0', STR_PAD_LEFT);
                $ttpm_mission_with_month[] = json_decode(file_get_contents("{$file_dir}/{$year}-{$m}/任务-种类.json"), true);
                $ttpm_fixed_with_month[] = json_decode(file_get_contents("{$file_dir}/{$year}-{$m}/检修-种类.json"), true);
            }

            return view("Report.TemporaryTask.Production.Main.index", [
                'year' => $year,
                'date' => request('date', Carbon::now()->format('Y-m')),
                'ttpm_date_list' => $ttpm_date_list,
                'ttpm_categories' => $ttpm_categories,
                'ttpm_categories_flip' => $ttpm_categories_flip,
                'ttpm_categories_flip_as_json' => json_encode($ttpm_categories_flip),
                'ttpm_mission_with_year' => $ttpm_mission_with_year,
                'ttpm_fixed_with_year' => $ttpm_fixed_with_year,
                'ttpm_mission_with_month' => $ttpm_mission_with_month,
                'ttpm_fixed_with_month' => $ttpm_fixed_with_month,
                'ttpm_mission_with_year_as_json' => json_encode($ttpm_mission_with_year),
                'ttpm_fixed_with_year_as_json' => json_encode($ttpm_fixed_with_year),
                'ttpm_mission_with_month_as_json' => json_encode($ttpm_mission_with_month),
                'ttpm_fixed_with_month_as_json' => json_encode($ttpm_fixed_with_month),
            ]);
        } catch (\Exception $e) {
            return back()->with('danger', '暂无任务');
        }
    }

    /**
     * 临时生产任务 指定种类
     * @param string $category_unique_code
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|\Illuminate\Routing\Redirector|View|string'
     */
    final public function getTemporaryTaskProductionMainWithCategory(string $category_unique_code)
    {
        try {
            $a = explode('-', request('date'));
            if (count($a) == 2) {
                $year = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->year;
            } else {
                $year = request('date', date("Y"));
            }

            $file_dir = storage_path("app/临时检修任务/{$year}");

            if (request('download') == '1') {
                $filename = "{$year}年{$this->_organizationName}临时生产任务统计";
                # 文件复制式
                if (is_file(public_path("{$filename}.xlsx"))) unlink(public_path("{$filename}.xlsx"));
                if (!is_file("{$file_dir}/{$filename}.xlsx")) return "<script>alert('excel文件不存在：{$file_dir}/{$filename}.xlsx');</script>";
                copy("{$file_dir}/{$filename}.xlsx", public_path("{$filename}.xlsx"));
                return redirect(url("/{$filename}.xlsx"));
            }

            $ttpm_date_list = is_file(storage_path('app/临时生产任务/dateList.json')) ? Texthelper::parseJson(file_get_contents(storage_path('app/临时生产任务/dateList.json'))) : [];

            # 获取种类
            $ttpm_categories = json_decode(file_get_contents("{$file_dir}/种类.json"), true);
            $ttpm_categories_flip = array_flip($ttpm_categories);
            $current_category_name = $ttpm_categories[$category_unique_code];

            # 获取类型统计
            $ttpm_entire_models = json_decode(file_get_contents("{$file_dir}/类型-种类.json"), true)[$current_category_name];
            $ttpm_entire_models_flip = array_flip($ttpm_entire_models);
            $ttpm_mission_with_year = json_decode(file_get_contents("{$file_dir}/任务-类型.json"), true);
            $ttpm_fixed_with_year = json_decode(file_get_contents("{$file_dir}/检修-类型.json"), true);
            $ttpm_mission_with_month = [];
            $ttpm_fixed_with_month = [];
            for ($i = 1; $i < 13; $i++) {
                $m = str_pad(strval($i), 2, '0', STR_PAD_LEFT);
                $ttpm_mission_with_month[] = json_decode(file_get_contents("{$file_dir}/{$year}-{$m}/任务-类型.json"), true);
                $ttpm_fixed_with_month[] = json_decode(file_get_contents("{$file_dir}/{$year}-{$m}/检修-类型.json"), true);
            }

            return view("Report.TemporaryTask.Production.Main.withCategory", [
                'year' => $year,
                'date' => request('date', Carbon::now()->format('Y-m')),
                'ttpm_date_list' => $ttpm_date_list,
                'ttpm_categories' => $ttpm_categories,
                'ttpm_categories_flip' => $ttpm_categories_flip,
                'ttpm_categories_flip_as_json' => json_encode($ttpm_categories_flip),
                'current_category_name' => $current_category_name,
                'current_category_unique_code' => $category_unique_code,
                'ttpm_entire_models' => $ttpm_entire_models,
                'ttpm_entire_models_flip' => $ttpm_entire_models_flip,
                'ttpm_entire_models_flip_as_json' => json_encode($ttpm_entire_models_flip),
                'ttpm_mission_with_year' => $ttpm_mission_with_year,
                'ttpm_fixed_with_year' => $ttpm_fixed_with_year,
                'ttpm_mission_with_month' => $ttpm_mission_with_month,
                'ttpm_fixed_with_month' => $ttpm_fixed_with_month,
                'ttpm_mission_with_year_as_json' => json_encode($ttpm_mission_with_year),
                'ttpm_fixed_with_year_as_json' => json_encode($ttpm_fixed_with_year),
                'ttpm_mission_with_month_as_json' => json_encode($ttpm_mission_with_month),
                'ttpm_fixed_with_month_as_json' => json_encode($ttpm_fixed_with_month),
            ]);
        } catch (\Exception $e) {
            return back()->with('danger', '暂无任务');
        }
    }

    /**
     * 临时生产任务 指定类型
     * @param string $entire_model_unique_code
     * @return Factory|\Illuminate\Foundation\Application|RedirectResponse|\Illuminate\Routing\Redirector|View|string
     */
    final public function getTemporaryTaskProductionMainWithEntireModel(string $entire_model_unique_code)
    {
        $current_category_unique_code = substr($entire_model_unique_code, 0, 3);
        try {
            $a = explode('-', request('date'));
            if (count($a) == 2) {
                $year = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->year;
            } else {
                $year = request('date', date("Y"));
            }

            $file_dir = storage_path("app/临时检修任务/{$year}");

            if (request('download') == '1') {
                $filename = "{$year}年{$this->_organizationName}临时生产任务统计";
                # 文件复制式
                if (is_file(public_path("{$filename}.xlsx"))) unlink(public_path("{$filename}.xlsx"));
                if (!is_file("{$file_dir}/{$filename}.xlsx")) return "<script>alert('excel文件不存在：{$file_dir}/{$filename}.xlsx');</script>";
                copy("{$file_dir}/{$filename}.xlsx", public_path("{$filename}.xlsx"));
                return redirect(url("/{$filename}.xlsx"));
            }

            $ttpm_date_list = is_file(storage_path('app/临时生产任务/dateList.json')) ? Texthelper::parseJson(file_get_contents(storage_path('app/临时生产任务/dateList.json'))) : [];

            # 获取种类
            $ttpm_categories = json_decode(file_get_contents("{$file_dir}/种类.json"), true);
            $ttpm_categories_flip = array_flip($ttpm_categories);
            $current_category_name = $ttpm_categories[$current_category_unique_code];

            # 获取类型
            $ttpm_entire_models = json_decode(file_get_contents("{$file_dir}/类型-种类.json"), true)[$current_category_name];
            $ttpm_entire_models_flip = array_flip($ttpm_entire_models);
            $current_entire_model_name = $ttpm_entire_models[$entire_model_unique_code];

            # 获取子类和型号统计
            $ttpm_sub_models = json_decode(file_get_contents("{$file_dir}/子类和型号-类型.json"), true)[$current_entire_model_name];
            $ttpm_sub_models_flip = array_flip($ttpm_sub_models);
            $ttpm_mission_with_year = json_decode(file_get_contents("{$file_dir}/任务-子类和型号.json"), true);
            $ttpm_fixed_with_year = json_decode(file_get_contents("{$file_dir}/检修-子类和型号.json"), true);
            $ttpm_mission_with_month = [];
            $ttpm_fixed_with_month = [];
            for ($i = 1; $i < 13; $i++) {
                $m = str_pad(strval($i), 2, '0', STR_PAD_LEFT);
                $ttpm_mission_with_month[] = json_decode(file_get_contents("{$file_dir}/{$year}-{$m}/任务-子类和型号.json"), true);
                $ttpm_fixed_with_month[] = json_decode(file_get_contents("{$file_dir}/{$year}-{$m}/检修-子类和型号.json"), true);
            }

            return view("Report.TemporaryTask.Production.Main.withCategory", [
                'year' => $year,
                'date' => request('date', Carbon::now()->format('Y-m')),
                'ttpm_date_list' => $ttpm_date_list,
                'ttpm_categories' => $ttpm_categories,
                'ttpm_categories_flip' => $ttpm_categories_flip,
                'ttpm_categories_flip_as_json' => json_encode($ttpm_categories_flip),
                'current_category_name' => $current_category_name,
                'current_category_unique_code' => $current_category_unique_code,
                'ttpm_entire_models' => $ttpm_entire_models,
                'ttpm_entire_models_flip' => $ttpm_entire_models_flip,
                'ttpm_entire_models_flip_as_json' => json_encode($ttpm_entire_models_flip),
                'current_entire_model_name' => $current_entire_model_name,
                'current_entire_unique_code' => $entire_model_unique_code,
                'ttpm_sub_models' => $ttpm_sub_models,
                'ttpm_sub_models_flip' => $ttpm_sub_models_flip,
                'ttpm_sub_models_flip_as_json' => json_encode($ttpm_sub_models_flip),
                'ttpm_mission_with_year' => $ttpm_mission_with_year,
                'ttpm_fixed_with_year' => $ttpm_fixed_with_year,
                'ttpm_mission_with_month' => $ttpm_mission_with_month,
                'ttpm_fixed_with_month' => $ttpm_fixed_with_month,
                'ttpm_mission_with_year_as_json' => json_encode($ttpm_mission_with_year),
                'ttpm_fixed_with_year_as_json' => json_encode($ttpm_fixed_with_year),
                'ttpm_mission_with_month_as_json' => json_encode($ttpm_mission_with_month),
                'ttpm_fixed_with_month_as_json' => json_encode($ttpm_fixed_with_month),
            ]);
        } catch (\Exception $e) {
            return back()->with('danger', '暂无任务');
        }
    }
}
