<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\RepairBaseCycleFixMissionRecord;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;

class CycleFixController extends Controller
{
    public $workAreas = [];
    public $workAreaTypes = [];

    public function __construct()
    {
        $this->_workAreas = array_flip(Account::$WORK_AREAS);
        $this->_workAreaTypes = Account::$WORK_AREA_TYPES;
    }

    /**
     * 周期修（全部）
     */
    final public function cycleFix()
    {
        try {
            return view('Report.CycleFix.cycleFix');
        } catch (\Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 周期修（种类视角）
     * @param string $categoryUniqueCode
     * @return mixed
     */
    final public function cycleFixWithCategory(string $categoryUniqueCode = null)
    {
        try {
            return view('Report.CycleFix.cycleFixWithCategory', ['currentCategoryUniqueCode' => $categoryUniqueCode]);
        } catch (\Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 获取周期修所有种类数据
     */
    final public function getReportForCategoriesWithYear()
    {
        try {
            $year = request('year', date('Y'));
            $month = null;
            $dirname = storage_path("app/cycleFix/{$year}");
            if (!is_dir($dirname)) return response()->json(['msg' => '没有找到周期修统计数据'], 404);

            # 循环读取每个月的数据
            $statistics = [];
            for ($i = 1; $i < 13; ++$i) {
                $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                $month_dirname = "{$dirname}/{$year}-{$month}";
                if (!is_dir($month_dirname)) return response()->json(['msg' => '没有找到周期修统计数据'], 404);
                $tmp = json_decode(file_get_contents("{$month_dirname}/statistics.json"), true);
                if (!array_key_exists($month, $statistics)) $statistics[$month] = [];
                foreach ($tmp as $workAreaId => $t) {
                    foreach ($t['categories'] as $cu => $c) {
                        $statistics[$month][$cu] = $c;
                    }
                }
            }

            # 获取任务统计
            $originAt = Carbon::create($year, 1, 1)->firstOfYear()->format('Y-m-d');
            $finishAt = Carbon::create($year, 1, 1)->endOfYear()->format('Y-m-d');
            $missions = [];
            foreach (DB::select("
select sum(rbcfmr.number)                as aggregate,
       rbcfmr.category_unique_code                as cu,
       rbcfmr.category_name                       as cn,
       DATE_FORMAT(rbcfmr.completing_at, '%m') as month
from repair_base_cycle_fix_mission_records as rbcfmr
where rbcfmr.completing_at between ? and ?
group by rbcfmr.category_unique_code, rbcfmr.category_name, month", [$originAt, $finishAt]) as $item) {
                $missions[$item->cu][$item->month] = $item;
            }

            return response()->json(['msg' => '读取成功', 'year' => $year, 'statistics' => $statistics, 'missions' => $missions], 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => '意外错误', 'details' => [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 获取周期修制定种类数据
     * @param string $categoryUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getReportForCategoryWithYear(string $categoryUniqueCode)
    {
        try {
            $year = request('year', date('Y'));
            $month = null;
            $rootDir = storage_path('app/cycleFix');
            $dirname = "{$rootDir}/{$year}";
            if (!is_dir($dirname)) return response()->json(['msg' => '没有找到周期修统计数据'], 404);

            # 循环读取每个月的数据
            $statistics = [];
            for ($i = 1; $i < 13; ++$i) {
                $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                $month_dirname = "{$dirname}/{$year}-{$month}";
                if (!is_dir($month_dirname)) return response()->json(['msg' => '没有找到周期修统计数据'], 404);
                $tmp = json_decode(file_get_contents("{$month_dirname}/statistics.json"), true);
                if (!array_key_exists($month, $statistics)) $statistics[$month] = [];
                foreach ($tmp as $workAreaId => $t) {
                    foreach ($t['categories'] as $cu => $c) {
                        if ($cu == $categoryUniqueCode) {
                            foreach ($c['subs'] as $emu => $em) {
                                foreach ($em['subs'] as $mu => $m) {
                                    $statistics[$month][$mu] = $m;
                                }
                            }
                        }
                    }
                }
            }

            $cycleFixYears = json_decode(file_get_contents("{$rootDir}/yearList.json"), true);
            $cycleFixMonths = json_decode(file_get_contents("{$rootDir}/dateList.json"), true);

            # 获取管内设备数
            $entireInstancesWithModel = [];
            foreach (DB::select("
select count(ei.identity_code) as aggregate,
       ei.model_unique_code    as mu,
       ei.model_name           as mn,
       ei.fix_cycle_value      as fcv
from entire_instances as ei
where ei.deleted_at is null
  and ei.status <> 'SCRAP'
  and ei.category_unique_code = ?
group by ei.model_unique_code, ei.model_name", [$categoryUniqueCode]) as $item) {
                $entireInstancesWithModel[$item->mu] = $item;
            }

            # 成品设备数
            $entireInstancesForFixedWithModel = [];
            foreach (DB::select("
select count(ei.identity_code) as aggregate,
       ei.model_unique_code    as mu,
       ei.model_name           as mn,
       ei.fix_cycle_value      as fcv
from entire_instances as ei
where ei.deleted_at is null
  and ei.status = 'FIXED'
  and ei.category_unique_code = ?
group by ei.model_unique_code, ei.model_name", [$categoryUniqueCode]) as $item) {
                $entireInstancesForFixedWithModel[$item->mu] = $item;
            }

            # 获取任务统计
            $originAt = Carbon::create($year, 1, 1)->firstOfYear()->format('Y-m-d');
            $finishAt = Carbon::create($year, 1, 1)->endOfYear()->format('Y-m-d');
            $missions = [];
            foreach (DB::select("
select sum(rbcfmr.number)                      as aggregate,
       rbcfmr.model_unique_code                as mu,
       rbcfmr.model_name                       as mn,
       DATE_FORMAT(rbcfmr.completing_at, '%m') as month
from repair_base_cycle_fix_mission_records as rbcfmr
where rbcfmr.completing_at between ? and ?
  and rbcfmr.category_unique_code = ?
group by rbcfmr.model_unique_code, rbcfmr.model_name, month", [$originAt, $finishAt, $categoryUniqueCode]) as $item) {
                $missions[$item->mu][$item->month] = $item;
            }

            return response()->json([
                'msg' => '读取成功',
                'year' => $year,
                'cycleFixYears' => $cycleFixYears,
                'cycleFixMonths' => $cycleFixMonths,
                'statistics' => $statistics,
                'entireInstancesWithModel' => $entireInstancesWithModel,
                'entireInstancesForFixedWithModel' => $entireInstancesForFixedWithModel,
                'missions' => $missions,
            ]);
        } catch (\Exception $e) {
            return response()->json(['msg' => '意外错误', 'details' => [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 周期修（类型视角）
     * @param string $entireModelUniqueCode
     * @return Factory|RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector|View
     */
    final public function cycleFixWithEntireModelAsMission(string $entireModelUniqueCode)
    {
        try {
            $year = request('year', Carbon::now()->year);
            $fileDir = storage_path("app/周期修/{$year}");

            if (request('download') == '1') {
                $filename = "{$year}年{$this->_organizationName}周期修统计";
                if (is_file(public_path("{$filename}.xlsx"))) unlink(public_path("{$filename}.xlsx"));
                if (!is_file("{$fileDir}/{$filename}.xlsx")) return response()->make("<script>alert('excel文件不存在');</script>");
                copy("{$fileDir}/{$filename}.xlsx", public_path("{$filename}.xlsx"));
                return redirect(url("/{$filename}.xlsx"));
            }

            $cycleFixCategories = json_decode(file_get_contents(storage_path("app/basicInfo/kinds.json")), true);
            $currentCategoryName = $cycleFixCategories[substr($entireModelUniqueCode, 0, 3)];
            $currentEntireModelsWithCategoryName = TextHelper::parseJson(file_get_contents("{$fileDir}/类型2.json"))[$currentCategoryName];
            $cycleFixEntireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/类型.json"));
            $currentEntireModelName = $cycleFixEntireModels[$entireModelUniqueCode];
            $cycleFixValues = TextHelper::parseJson(file_get_contents("{$fileDir}/检修周期年限-型号和子类-类型.json"))[$currentEntireModelName];
            $cycleFixSubModels = TextHelper::parseJson(file_get_contents("{$fileDir}/型号和子类-类型.json"));
            $missionWithSubModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/列-任务-型号和子类.json"))[$currentEntireModelName];
            $fixedWithSubModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/列-完成-型号和子类.json"))[$currentEntireModelName];
            $realWithSubModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/列-实际-型号和子类.json"))[$currentEntireModelName];
            $planWithSubModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/列-计划.json"))[$currentCategoryName]['sub'][$currentEntireModelName]['sub'];
            $planWithSubModelAsColumnSerial = [];
            foreach ($planWithSubModelAsColumn as $subModelName => $item) $planWithSubModelAsColumnSerial[$subModelName] = $item['count'];

            $missionWithSubModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/行-任务-型号和子类.json"))[$currentEntireModelName];
            $fixedWithSubModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/行-完成-型号和子类.json"))[$currentEntireModelName];
            $realWithSubModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/行-实际-型号和子类.json"))[$currentEntireModelName];
            $planWithSubModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/行-计划.json"))['sub'][$currentCategoryName]['sub'][$currentEntireModelName]['statistics'];

            $cycleFixTotal = TextHelper::parseJson(file_get_contents("{$fileDir}/总数-型号和子类.json"))[$currentEntireModelName];
            $cycleFixFixedTotal = TextHelper::parseJson(file_get_contents("{$fileDir}/成品-型号和子类.json"))[$currentEntireModelName];
            $missionWithSubModelAsNames = array_keys($missionWithSubModelAsColumn);
            $missionWithSubModelAsMonth = [];
            $fixedWithSubModelAsMonth = [];
            $realWithSubModelAsMonth = [];
            $planWithSubModelAsMonth = [];
            $missionWithSubModelAsMonthForStation = [];

            for ($m = 1; $m <= 12; $m++) {
                $m = str_pad($m, 2, '0', STR_PAD_LEFT);
                $missionWithSubModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}-{$m}/任务-型号和子类.json"))[$currentEntireModelName];
                $fixedWithSubModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}-{$m}/完成-型号和子类.json"))[$currentEntireModelName];
                $realWithSubModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}-{$m}/实际-型号和子类.json"))[$currentEntireModelName];

                $tmp = [];
                foreach (TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}-{$m}/计划.json"))[$currentCategoryName]['sub'][$currentEntireModelName]['sub'] as $subModelName => $item) $tmp[$subModelName] = $item['count'];
                $planWithSubModelAsMonth[] = $tmp;

                $missionWithSubModelAsMonthForStation[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}-{$m}/任务-型号和子类-车站.json"))[$currentEntireModelName];
            }

            return view('Report.CycleFix.cycleFixWithEntireModelAsMission', [
                'year' => $year,
                'date' => Carbon::now()->format('Y-m'),
                'currentEntireModelsWithCategoryName' => $currentEntireModelsWithCategoryName,
                'cycleFixEntireModels' => $cycleFixEntireModels,
                'cycleFixSubModels' => $cycleFixSubModels[$cycleFixEntireModels[$entireModelUniqueCode]],
                'currentEntireModelUniqueCode' => $entireModelUniqueCode,
                'missionWithSubModelAsColumn' => $missionWithSubModelAsColumn,
                'fixedWithSubModelAsColumn' => $fixedWithSubModelAsColumn,
                'realWithSubModelAsColumn' => $realWithSubModelAsColumn,
                'planWithSubModelAsColumn' => $planWithSubModelAsColumnSerial,
                'missionWithSubModelAsRow' => $missionWithSubModelAsRow,
                'fixedWithSubModelAsRow' => $fixedWithSubModelAsRow,
                'realWithSubModelAsRow' => $realWithSubModelAsRow,
                'planWithSubModelAsRow' => $planWithSubModelAsRow,
                'missionWithSubModelAsNames' => TextHelper::toJson($missionWithSubModelAsNames),
                'missionWithSubModelAsMonth' => $missionWithSubModelAsMonth,
                'fixedWithSubModelAsMonth' => $fixedWithSubModelAsMonth,
                'realWithSubModelAsMonth' => $realWithSubModelAsMonth,
                'planWithSubModelAsMonth' => $planWithSubModelAsMonth,
                'cycleFixTotal' => $cycleFixTotal,
                'cycleFixFixedTotal' => $cycleFixFixedTotal,
                'missionWithSubModelAsMonthForStation' => $missionWithSubModelAsMonthForStation,
                'cycleFixValues' => $cycleFixValues,
            ]);
        } catch (\Exception $exception) {
            return back()->with('info', '下级无数据');
        }
    }

    /**
     * 周期修（任务分配）
     * @param string $categoryUniqueCode
     * @return Factory|RedirectResponse|View
     */
    final public function cycleFixWithEntireModelAsPlan(string $categoryUniqueCode)
    {
        $workAreasForCategory = ['S03' => 1, 'Q01' => 2];
        try {
            # 获取当前工区
            $workArea = $this->_workAreaTypes[session('account.work_area_by_unique_code.type')] ?? 0;

            # 获取当前时间
            if (request()->has('date')) {
                list($year, $month) = explode('-', request('date', date('Y-m')));
            } else {
                $now = Carbon::now();
                $year = $now->year;
                $month = str_pad($now->month, 2, '0', STR_PAD_LEFT);
            }
            $date = "{$year}-{$month}";
            $originAt = Carbon::createFromFormat('Y-m-d', "{$date}-01")->startOfMonth()->format('Y-m-d');
            $finishAt = Carbon::createFromFormat('Y-m-d', "{$date}-01")->endOfMonth()->format('Y-m-d');

            # 判断缓存文件是否存在
            $fileDir = storage_path('app/cycleFix');
            if (!is_file("{$fileDir}/{$year}/statistics.json")) return back()->with('danger', '周期修缓存文件不存在');
            $statistics = json_decode(file_get_contents("{$fileDir}/{$year}/statistics.json"), true);
            $dateList = json_decode(file_get_contents("{$fileDir}/dateList.json"), true);
            if ($workArea > 0) $statistics = [$statistics[$workArea] ?? []];
            if (empty($statistics[0])) return back()->with('danger', '当前工区没有周期修计划');

            # 生成种类列表（当前所选工区）
            $categories = [];
            foreach ($statistics as $statistic) {
                foreach ($statistic['categories'] as $cu => $category) {
                    $categories[$cu] = $category['name'];
                }
            }
            $currentCategoryUniqueCode = request()->has('categoryUniqueCode') ? request('categoryUniqueCode', array_key_first($categories)) : array_key_first($categories);
            $currentCategoryName = request()->has('categoryUniqueCode') ? $categories[request('categoryUniqueCode', array_key_first($categories))] : array_first($categories);
            $workAreaWithCategory = @$workAreasForCategory[$currentCategoryUniqueCode] ?? 3;

            # 获取人员
            $accounts = DB::table('accounts')
                ->when($workArea > 0, function ($query) use ($workArea) {
                    return $query->where('work_area', $workArea)
                    ->orWhere('work_area_unique_code',session('account.work_area_by_unique_code.uniuqe_code'));
                })
                ->where('deleted_at')
                ->where('supervision', false)
                ->get()
                ->groupBy('work_area')
                ->toArray();

            # 获取任务内容
            $missions = RepairBaseCycleFixMissionRecord::with(['BelongsToAccount'])
                ->whereBetween('completing_at', [$originAt, $finishAt])
                ->get()
                ->groupBy('belongs_to_account_id')
                ->toArray();

            return view("Report.CycleFix.cycleFixWithEntireModelAsPlan", [
                'year' => $year,
                'month' => $month,
                'date' => $date,
                'dateList' => $dateList,
                'workArea' => $workArea,
                'categoriesAsJson' => json_encode($categories),
                'currentCategoryUniqueCode' => $currentCategoryUniqueCode,
                'currentCategoryName' => $currentCategoryName,
                'workAreaWithCategory' => $workAreaWithCategory,
                'accountsAsJson' => json_encode($accounts),
                'missionsAsJson' => json_encode($missions),
                'statisticsAsJson' => json_encode($statistics),
            ]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $line = $e->getLine();
            $file = $e->getFile();
            dd($msg, $line, $file);
            return back()->with('info', '该型号下没有周期修数据');
        }
    }

    /**
     * 保存任务分配值
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function savePlan(Request $request)
    {
        try {
            if (!$request->has('date')) return HttpResponseHelper::errorEmpty('时间参数丢失');
            $completingAt = $request->get('date') . '-01';

            $ret = [];
            DB::beginTransaction();
            $a = [];
            $insert = [];
            foreach ($request->except('date') as $key => $number) {
                list($mu, $mn, $ai) = explode(':', base64_decode($key));
                $a[] = [$mu, $mn, $ai];
                # 如果不存在就创建
                $repairBaseCycleFixMissionRecord = RepairBaseCycleFixMissionRecord::with([])
                    ->where('completing_at', $completingAt)
                    ->where('model_unique_code', $mu)
                    ->where('belongs_to_account_id', $ai)
                    ->first();

                if ($repairBaseCycleFixMissionRecord) {
                    $repairBaseCycleFixMissionRecord->fill(['model_unique_code' => $mu, 'model_name' => $mn, 'completing_at' => $completingAt, 'number' => $number])->save();
                    $ret[] = $repairBaseCycleFixMissionRecord;
                } else {
                    $category = DB::table('categories as c')
                        ->where('deleted_at', null)
                        ->where('unique_code', substr($mu, 0, 3))
                        ->first();
                    if (!$category) return HttpResponseHelper::errorEmpty("种类不存在");
                    $insert[] = [
                        'created_at' => date('Y-m-d'),
                        'updated_at' => date('Y-m-d'),
                        'belongs_to_account_id' => $ai,
                        'number' => $number,
                        'model_unique_code' => $mu,
                        'model_name' => $mn,
                        'completing_at' => $completingAt,
                        'category_unique_code' => $category->unique_code,
                        'category_name' => $category->name
                    ];
                }
            }
            RepairBaseCycleFixMissionRecord::with([])->insert($insert);
            DB::commit();

            # 重新统计全年数据
            //            CycleFixFacade::refreshPlanColumn($year);
            //            CycleFixFacade::refreshPlanRow($year);

            return HttpResponseHelper::created('保存成功', [$insert, $a]);
        } catch (\Throwable $e) {
            return HttpResponseHelper::errorForbidden('意外错误', [$e->getMessage(), $e->getFile(), $e->getLine()]);
        }
    }

    /**
     * 生成计划Excel
     */
    final public function makeExcelWithPlan()
    {
        try {
            $cellKey = [
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
                'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
                'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
                'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
            ];

            $date = request('date', date('Y-m'));
            $filename = "周期修检修工作分配({$date})";

            $originAt = Carbon::createFromFormat('Y-m-d', "{$date}-01")->startOfMonth()->format('Y-m-d');
            $finishAt = Carbon::createFromFormat('Y-m-d', "{$date}-01")->endOfMonth()->format('Y-m-d');
            $missions = RepairBaseCycleFixMissionRecord::with(['BelongsToAccount'])
                ->whereBetween('completing_at', [$originAt, $finishAt])
                ->where('category_unique_code', request('category_unique_code'))
                //                ->where('number', '>', 0)
                ->get();

            $models = [];
            foreach ($missions->groupBy('model_unique_code') as $modelUniqueCode => $item) {
                $models[$modelUniqueCode] = array_first($item)->model_name;
            }

            ExcelWriteHelper::download(
                function ($excel) use ($cellKey, $missions, $models) {
                    $excel->setActiveSheetIndex(0);
                    $currentSheet = $excel->getActiveSheet();

                    # 定义首行
                    $col = 2;
                    $currentSheet->setCellValue('A1', '型号/人员');
                    $currentSheet->setCellValue('B1', '合计');
                    $currentSheet->getColumnDimension('A')->setWidth(20);
                    foreach ($missions->groupBy('belongs_to_account_id') as $accountId => $mission) {
                        $currentSheet->setCellValue("{$cellKey[$col]}1", array_first($mission)->BelongsToAccount->nickname);
                        $currentSheet->getColumnDimension("{$cellKey[$col]}")->setWidth(15);
                        $col++;
                    }

                    # 定义首列
                    $row = 2;
                    foreach ($models as $modelUniqueCode => $modelName) {
                        $currentSheet->setCellValue("A{$row}", $modelName);
                        $col = 1;
                        $countCol = 0;
                        foreach ($missions->groupBy('model_unique_code')[$modelUniqueCode] as $item) {
                            $col++;
                            $currentSheet->setCellValue("{$cellKey[$col]}{$row}", $item->number);
                            $countCol += $item->number;
                        }
                        $currentSheet->setCellValue("B{$row}", $countCol);
                        $row++;
                    }

                    return $excel;
                },
                $filename
            );
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '无数据');
        }
    }
}
