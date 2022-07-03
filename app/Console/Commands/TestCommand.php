<?php

namespace App\Console\Commands;

use App\Exceptions\EmptyException;
use App\Exceptions\ExcelInException;
use App\Model\EntireInstanceLog;
use App\Model\EntireInstanceUseReport;
use App\Model\Install\InstallPosition;
use App\Model\Install\InstallRoom;
use App\Model\Install\InstallTier;
use App\Model\Maintain;
use App\Model\RepairBasePlanOutCycleFixBill;
use App\Model\RepairBasePlanOutCycleFixEntireInstance;
use App\Model\WarehouseReport;
use App\Services\ExcelCellService;
use App\Services\ExcelReaderService;
use App\Services\ExcelRowService;
use App\Services\ExcelWriterService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use PHPExcel_Exception;
use PHPExcel_Reader_Exception;
use Throwable;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test {operator}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试';

    public function t4()
    {
        $a = json_decode(file_get_contents(storage_path('b.json')), true);
        $b = [];
        foreach ($a as $scn => $entire_instances) {
            if (!array_key_exists($scn, $b)) $b[$scn] = [];
            foreach ($entire_instances as $entire_instance) {
                $b[$scn][] = $entire_instance['identity_code'];
            }
        }

        foreach ($b as $scn => $identity_codes) {
            $ret = DB::table('entire_instances as ei')
                ->whereIn('ei.identity_code', $identity_codes)
                ->update(['maintain_workshop_name' => $scn,]);
            dump($scn, $ret);
        }

        $ret = DB::table('entire_instances as ei')
            ->whereIn('ei.status', ['FIXED', 'FIXING',])
            ->update(['maintain_workshop_name' => env('JWT_ISS')]);

        dump($ret);

        unlink(storage_path('b.json'));
    }

    /**
     * @return int
     * @throws ExcelInException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function p2e()
    {
        $current_row = 2;
        $excel = ExcelReadHelper::FROM_STORAGE(storage_path('p2e.csv'))
            ->originRow($current_row)
            ->withSheetIndex(0);

        $errors = [];
        $error_rows_for_identity_code = [];
        $error_rows_for_serial_number = [];
        $edit_count = 0;

        foreach ($excel['success'] as $row_datum) {
            if (empty(array_filter($row_datum, function ($value) {
                return !empty($value);
            }))) continue;

            try {
                // list($code, $install_position_unique_code, $manual_install_position_unique_code) = $row_datum;
                list($code, $install_position_unique_code) = $row_datum;
                $code = trim(strval($code));
                $install_position_unique_code = trim(strval($install_position_unique_code));
            } catch (Exception $e) {
                $pattern = '/Undefined offset: /';
                $offset = preg_replace($pattern, '', $e->getMessage());
                $column_name = ExcelWriteHelper::int2Excel($offset);
                throw new ExcelInException("读取：{$column_name}列失败。");
            }

            $ret = DB::table('entire_instances as ei')->where('serial_number', $code)->update([
                'maintain_location_code' => $install_position_unique_code,
            ]);

            $this->comment("{$code} {$install_position_unique_code} {$ret}");
        }
        return 0;
    }

    /**
     * 张吉怀线，防雷分线室增加到8位
     */
    public function installPositionAddTo8()
    {
        // DB::beginTransaction();
        try {
            $station_unique_codes = DB::table('maintains')
                ->whereIn('name', [
                    '张吉怀中继1站'
                    , '沙堤所'
                    , '张吉怀中继2站'
                    , '张吉怀中继4站'
                    , '张吉怀中继5站'
                    , '芙蓉镇'
                    , '张吉怀中继6站'
                    , '古丈西'
                    , '张吉怀中继7站'
                    , '张吉怀中继8站'
                    , '张吉怀中继9站'
                    , '吉首东站'
                    , '凤凰古城站'
                    , '张吉怀中继11站'
                    , '麻阳西站'
                    , '张吉怀中继12'
                    , '张吉怀中继13站'
                    , '张吉怀中继14站'
                    , '龙形村所'
                    , '怀化南存车场'
                    , '张家界西张吉怀场'
                    , 'RBC机房'
                    , '张吉怀中继3站'
                    , '张吉怀中继10站'

                ])
                ->pluck('unique_code')
                ->toArray();

            $new_install_positions = [];

            $install_tiers = InstallTier::with([
                'WithInstallPositions',
                'WithInstallShelf',
                'WithInstallShelf.WithInstallPlatoon',
                'WithInstallShelf.WithInstallPlatoon.WithInstallRoom',
                'WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation',
            ])
                ->whereHas('WithInstallShelf.WithInstallPlatoon.WithInstallRoom', function ($WithInstallRoom) use ($station_unique_codes) {
                    $WithInstallRoom
                        ->whereIn('station_unique_code', $station_unique_codes)
                        ->where('type', '13');
                })
                ->get()
                ->each(function ($install_tier) {
                    $count = $install_tier->WithInstallPositions->count();
                    $insert_data = [];

                    if ($count < 8) {
                        $need = 8 - $count;
                        dump("{$install_tier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name} {$install_tier->name} {$count} {$need}");

                        $new_install_positions = InstallPosition::generateUniqueCodes($install_tier->unique_code, $need);
                        $ret = DB::table('install_positions')->insert($new_install_positions);
                        dump($ret);

                        // $new_install_positions = InstallPosition::generateUniqueCodes($install_tier->unique_code, $need);
                        // dump($new_install_positions);
                        // InstallPosition::with([])->create($new_install_positions);
                    }
                });
            $this->info('finish');

            // DB::commit();
        } catch (Throwable $e) {
            // DB::rollback();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * Execute the console command.
     * @throws Exception
     */
    final public function handle(): void
    {
        $this->{$this->argument("operator")}();
    }

    /**
     * 海口仓库器材位置清空
     */
    private function hk_inside_warehouse_location_clear(): void
    {
        // 获取需要清洗的仓库位置
        $position_unique_codes = DB::table("positions as p")
            ->selectRaw("p.unique_code")
            ->join(DB::raw("tiers t"), "p.tier_unique_code", "=", "t.unique_code")
            ->join(DB::raw("shelves s"), "t.shelf_unique_code", "=", "s.unique_code")
            ->join(DB::raw("platoons pl"), "s.platoon_unique_code", "=", "pl.unique_code")
            ->join(DB::raw("areas a"), "pl.area_unique_code", "=", "a.unique_code")
            ->where("a.unique_code", "B074C010101")
            ->get()
            ->pluck("unique_code")
            ->toArray();

        // 清洗前保存原有位置
        file_put_contents(
            storage_path("hk_warehouse_location_clear.json"),
            DB::table("entire_instances")
                ->select("identity_code", "location_unique_code", "in_warehouse_time", "in_warehouse", "warehousein_at")
                ->whereIn("location_unique_code", $position_unique_codes)
                ->get()
                ->groupBy("location_unique_code")
                ->toJson(256)
        );

        // 清洗仓库位置
        DB::table("entire_instances")
            ->whereIn("location_unique_code", $position_unique_codes)
            ->update([
                "in_warehouse_time" => null,
                "in_warehouse" => false,
                "warehousein_at" => null,
                "location_unique_code" => "",
            ]);
    }

    /**
     * 清空非所内器材仓库位置
     */
    private function hk_outside_warehouse_location_clear(): void
    {
        DB::table("entire_instances")
            ->whereNotIn("status", ["FIXED", "FIXING", "SCRAP"])
            ->update([
                "in_warehouse_time" => null,
                "in_warehouse" => false,
                "warehousein_at" => null,
                "location_unique_code" => "",
            ]);
    }

    private function read()
    {
        $entire_instances = DB::table('entire_instances')
            ->select(['identity_code', 'maintain_station_name',])
            ->whereNull('deleted_at')
            ->where('status', '<>', 'SCRAP')
            ->where('maintain_station_name', '<>', '')
            ->whereNotNull('maintain_station_name')
            ->get()
            ->groupBy(['maintain_station_name'])
            ->each(function ($item, $maintain_station_name) {
                file_put_contents(storage_path("app/maintainStations/{$maintain_station_name}.json"), json_encode($item, 256));
            });

        $maintain_station_names = $entire_instances->keys()->toJson(256);
        file_put_contents(storage_path("app/maintainStations/names.json"), $maintain_station_names);
    }

    private function write()
    {
        DB::beginTransaction();

        DB::table('entire_instances')->where('maintain_station_name', '光明城站')->update(['maintain_station_name' => '']);

        $maintain_station_names = json_decode(file_get_contents(storage_path('app/maintainStations/names.json')), true);
        foreach ($maintain_station_names as $maintain_station_name) {
            $entire_instances = json_decode(file_get_contents(storage_path("app/maintainStations/{$maintain_station_name}.json")), true);
            $identity_codes = array_pluck($entire_instances, 'identity_code');
            $result = DB::table('entire_instances as ei')
                ->whereIn('ei.identity_code', $identity_codes)
                ->update(['maintain_station_name' => $maintain_station_name]);
            dump("{$maintain_station_name} {$result}");
        }

        DB::commit();
    }

    private function t1()
    {
        $entire_instances = DB::table('entire_instances')
            ->select(['identity_code', 'maintain_station_name',])
            ->whereNull('deleted_at')
            ->where('status', '<>', 'SCRAP')
            ->where('maintain_station_name', '<>', '')
            ->whereNotNull('maintain_station_name')
            ->get()
            ->groupBy(['maintain_station_name']);
        $ret = file_put_contents(storage_path('a.json'), $entire_instances->toJson(256));
        dump($ret);
    }

    private function t2()
    {
        $a = json_decode(file_get_contents(storage_path('a.json')), true);
        $b = [];
        foreach ($a as $sn => $entire_instances) {
            if (!array_key_exists($sn, $b)) $b[$sn] = [];
            foreach ($entire_instances as $entire_instance) {
                $b[$sn][] = $entire_instance['identity_code'];
            }
        }

        foreach ($b as $sn => $identity_codes) {
            $ret = DB::table('entire_instances as ei')
                ->whereIn('ei.identity_code', $identity_codes)
                ->update(['maintain_station_name' => $sn,]);
            dump($sn, $ret);
        }

        $ret = DB::table('entire_instances as ei')
            ->whereIn('ei.status', ['FIXED', 'FIXING',])
            ->update(['maintain_station_name' => '']);
        dump($ret);

        unlink(storage_path('a.json'));
    }

    private function t3()
    {
        $entire_instances = DB::table('entire_instances')
            ->select(['identity_code', 'maintain_workshop_name',])
            ->whereNull('deleted_at')
            ->where('status', '<>', 'SCRAP')
            ->where('maintain_workshop_name', '<>', '')
            ->whereNotNull('maintain_workshop_name')
            ->get()
            ->groupBy(['maintain_workshop_name']);
        $ret = file_put_contents(storage_path('b.json'), $entire_instances->toJson(256));
        dump($ret);
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws Exception
     */
    final private function t(): void
    {
        $excel_reader = ExcelReaderService::File(storage_path("6.8附件3.xls"))
            ->SetOriginRow(2)
            ->SetFinishColNumber(9)
            ->ReadBySheetIndex();
        $excel_data = $excel_reader->GetData(["序号", "车", "原车站编号", "车站编号", "线别", "线别编号", "所属站段", "修订说明", "段代码"]);

        $excel_writer = ExcelWriterService::Init();
        $excel_writer_sheet = $excel_writer->GetSheet();

        // 制作表头
        ExcelRowService::Init()
            ->SetRow(1)
            ->SetExcelCells([
                ExcelCellService::Init("代码"),
                ExcelCellService::Init("名称"),
            ])
            ->Write($excel_writer_sheet);

        // 写入数据
        $current_row = 2;
        $excel_data->pluck("线别编号", "线别")->each(function ($line_unique_code, $line_name) use (&$excel_writer_sheet, &$current_row) {
            ExcelRowService::Init()
                ->SetRow($current_row++)
                ->SetExcelCells([
                    ExcelCellService::Init($line_unique_code),
                    ExcelCellService::Init($line_name),
                ])
                ->Write($excel_writer_sheet);
        });

        $excel_writer->Save(storage_path("all-lines"));
    }

    /**
     * 部件复制到整件表
     * @return int
     * @throws Exception
     */
    final private function partCopyToEntire(): int
    {
        // @todo: 第一步 通过部件型号寻找对应型号
        // $tmp = DB::table('part_instances as pi')
        //     ->select('pi.part_model_name')
        //     ->groupBy(['pi.part_model_name'])
        //     ->pluck('part_model_name')
        //     ->toArray();
        //
        // foreach ($tmp as $t) {
        //     $a = DB::table('entire_models as sm')->select('sm.unique_code', 'sm.name')->where('sm.name', $t)->where('name', $t)->first();
        //     if(!$a) continue;
        //     $b = DB::table('part_instances as pi')->where('pi.device_model_unique_code','')->where('pi.part_model_name',$t)->update(['pi.device_model_unique_code'=>$a->unique_code]);
        //     dump($b);
        // }
        // dd('ok');

        // @todo: 第二步 复制错误的唯一编号到old_identity_code
        // DB::select("update part_instances set old_identity_code = identity_code where true");
        // dd('ok');

        // @todo: 第三步 重新赋码
        // DB::table('part_instances as pi')
        //     ->orderByDesc('pi.device_model_unique_code')
        //     ->where('pi.device_model_unique_code', '<>', '')
        //     ->get()
        //     ->groupBy('device_model_unique_code')
        //     ->each(function ($part_instances, $device_model_unique_code) {
        //         $entire_instance_count = DB::table('entire_instance_counts as eic')
        //             ->where('eic.entire_model_unique_code', $device_model_unique_code)
        //             ->first();
        //
        //         if (!$entire_instance_count) {
        //             $next_count = 0;
        //         } else {
        //             $next_count = intval($entire_instance_count->count);
        //         }
        //
        //         $part_instances->each(function ($part_instance) use ($device_model_unique_code, &$next_count) {
        //             $new_identity_code = $device_model_unique_code . env('ORGANIZATION_CODE') . str_pad(++$next_count, 7, '0', STR_PAD_LEFT) . 'H';
        //
        //             DB::table('part_instances')->where('id', $part_instance->id)->update(['identity_code' => $new_identity_code]);
        //             dump($new_identity_code);
        //         });
        //
        //         $entire_instance_count = EntireInstanceCount::with([])->where('entire_model_unique_code', $device_model_unique_code)->first();
        //         if ($entire_instance_count) {
        //             $entire_instance_count->fill(['count' => $next_count])->saveOrFail();
        //         } else {
        //             EntireInstanceCount::with([])->create([
        //                 'entire_model_unique_code' => $device_model_unique_code,
        //                 'count' => $next_count,
        //             ]);
        //         }
        //         dump('-------------' . $next_count);
        //     });
        // dd('ok');

        // @todo: 第四步 部件信息复制到整件表
        $part_models_in_part_instances = DB::table('part_instances as pi')
            ->select(['pi.device_model_unique_code'])
            ->groupBy(['pi.device_model_unique_code'])
            ->pluck('device_model_unique_code');
        $sub_models = DB::table('entire_models as sm')
            ->selectRaw(implode(',', [
                'c.name as cn',
                'c.unique_code as cu',
                'em.name as en',
                'em.unique_code eu',
                'sm.name as sn',
                'sm.unique_code as su'
            ]))
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->whereIn('sm.unique_code', $part_models_in_part_instances)
            ->get()
            ->toArray();
        $sub_models2 = [];
        foreach ($sub_models as $sub_model) $sub_models2[$sub_model->su] = $sub_model;

        DB::beginTransaction();

        try {
            DB::table('part_instances as pi')
                ->orderBy('id')
                ->where('pi.device_model_unique_code', '!=', '')
                ->chunk(50, function ($part_instances) use ($sub_models2) {
                    $insert_data = [];
                    $part_instances->each(function ($part_instance) use (&$insert_data, $sub_models2) {
                        dump('--------------');
                        dump(@(array)$sub_models2[$part_instance->device_model_unique_code], $part_instance->device_model_unique_code);
                        ['cu' => $cu, 'cn' => $cn, 'sn' => $sn] = (array)$sub_models2[$part_instance->device_model_unique_code];
                        if (!$cu) throw new Exception("{$part_instance->device_model_unique_code} 种类不存在");

                        $insert_data[] = [
                            'created_at' => $part_instance->created_at,
                            'updated_at' => $part_instance->updated_at,
                            'part_model_unique_code' => $part_instance->part_model_unique_code,
                            'part_model_name' => $part_instance->part_model_name,
                            'entire_instance_identity_code' => $part_instance->entire_instance_identity_code,
                            'category_unique_code' => $cu,
                            'category_name' => $cn,
                            'entire_model_unique_code' => $part_instance->device_model_unique_code,
                            'model_unique_code' => $part_instance->device_model_unique_code,
                            'model_name' => $sn,
                            'part_category_id' => $part_instance->part_category_id,
                            'identity_code' => $part_instance->identity_code,
                            'factory_device_code' => $part_instance->factory_device_code,
                            'factory_name' => $part_instance->factory_name,
                            'made_at' => $part_instance->made_at,
                            'scarping_at' => $part_instance->scraping_at,
                            'work_area_unique_code' => $part_instance->work_area_unique_code,
                            'status' => $part_instance->status,
                            'is_part' => true,
                            'serial_number' => $part_instance->old_identity_code,
                        ];

                        dump($part_instance->id);
                    });

                    $insert_ret = DB::table('entire_instances')->insert($insert_data);
                    dump("执行结果：{$insert_ret}");
                    dump("----------------------");
                });
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }

        return 0;
    }

    /**
     * 合并后的种类型，添加到海口数据库
     */
    final private function biToJson()
    {
        $equipment_categories = DB::table('equipment_categories as ec')->get();
        file_put_contents(storage_path('equipment_categories.json'), $equipment_categories->toJson());

        $equipment_models = DB::table('equipment_models as em')->get();
        file_put_contents(storage_path('equipment_models.json'), $equipment_models->toJson());

        $equipment_sub_models = DB::table('equipment_sub_models as esm')->get();
        file_put_contents(storage_path('equipment_sub_models.json'), $equipment_sub_models->toJson());
    }

    final private function SToB074()
    {
        DB::beginTransaction();
        $categories = [];
        DB::table('categories as c')
            ->whereNull('c.deleted_at')
            ->where('unique_code', 'like', 'S%')
            ->orderBy('c.unique_code')
            ->each(function ($category) use (&$categories) {
                $categories[] = [
                    'name' => $category->name,
                    'unique_code' => $category->unique_code,
                ];
            });
        DB::table('new_categories')->insert($categories);

        $entire_models = [];
        DB::table('entire_models as em')
            ->whereNull('em.deleted_at')
            ->where('category_unique_code', 'like', 'S%')
            ->orderBy('em.unique_code')
            ->each(function ($entire_model) use (&$entire_models) {
                $entire_models[] = [
                    'unique_code' => $entire_model->unique_code,
                    'name' => $entire_model->name,
                    'category_unique_code' => $entire_model->category_unique_code,
                    'is_sub_model' => false,
                    'fix_cycle_value' => $entire_model->fix_cycle_value,
                ];
            });
        DB::table('new_entire_models')->insert($entire_models);
        DB::commit();
    }

    final private function jsonToB074()
    {
        DB::beginTransaction();
        // 读取类型周期修
        $entire_models_for_fix_cycle_value = DB::table('entire_models as em')
            ->select(['em.name', 'em.fix_cycle_value'])
            ->whereNull('em.deleted_at')
            ->where('em.is_sub_model', false)
            ->where('em.fix_cycle_value', '>', 0)
            ->groupBy(['em.name'])
            ->get()
            ->pluck('fix_cycle_value', 'name');

        $sub_models_for_fix_cycle_value = DB::table('entire_models as sm')
            ->select(['sm.name', 'sm.fix_cycle_value'])
            ->whereNull('sm.deleted_at')
            ->where('sm.is_sub_model', true)
            ->where('sm.fix_cycle_value', '>', 0)
            ->groupBy(['sm.name'])
            ->get()
            ->pluck('fix_cycle_value', 'name');

        $new_categories = [];
        $equipment_categories = collect(json_decode(file_get_contents(storage_path('equipment_categories.json'))));
        $equipment_categories->each(function ($equipment_category) use (&$new_categories) {
            $new_categories[] = [
                'unique_code' => $equipment_category->unique_code,
                'name' => $equipment_category->name,
            ];
        });
        DB::table('new_categories')->insert($new_categories);

        $new_entire_models = [];
        $equipment_models = collect(json_decode(file_get_contents(storage_path('equipment_models.json'))));
        $equipment_models->each(function ($equipment_model) use (&$new_entire_models, $entire_models_for_fix_cycle_value) {
            $new_entire_models[] = [
                'unique_code' => $equipment_model->unique_code,
                'name' => $equipment_model->name,
                'category_unique_code' => $equipment_model->equipment_category_unique_code,
                'is_sub_model' => false,
                'fix_cycle_value' => $entire_models_for_fix_cycle_value->get($equipment_model->name, 0) ?? 0,
            ];
        });
        DB::table('new_entire_models')->insert($new_entire_models);

        $equipment_sub_models = collect(json_decode(file_get_contents(storage_path('equipment_sub_models.json'))));
        $equipment_sub_models
            ->chunk(50)
            ->each(function ($sub_models) use ($sub_models_for_fix_cycle_value) {
                $new_sub_models = [];
                $sub_models->each(function ($sub_model) use (&$new_sub_models, $sub_models_for_fix_cycle_value) {
                    $new_sub_models[] = [
                        'unique_code' => $sub_model->unique_code,
                        'name' => $sub_model->name,
                        'category_unique_code' => substr($sub_model->equipment_model_unique_code, 0, 3),
                        'parent_unique_code' => $sub_model->equipment_model_unique_code,
                        'is_sub_model' => true,
                        'fix_cycle_value' => $sub_models_for_fix_cycle_value->get($sub_model->name, 0) ?? 0,
                    ];
                });
                DB::table('new_entire_models')->insert($new_sub_models);
            });

        DB::commit();
    }

    final private function taggingLogs()
    {
        EntireInstanceLog::with(["Operator:nickname,id"])
            ->where("name", "赋码")
            ->whereHas("Operator")
            ->groupBy(["operator_id"])
            ->get()
            ->each(function ($entire_instance_log) {
                DB::table("entire_instance_logs")
                    ->where("name", "赋码")
                    ->where("operator_id", $entire_instance_log->operator_id)
                    ->update(["description" => $entire_instance_log->description . "操作人：" . $entire_instance_log->Operator->nickname . "；"]);
            });
    }

    /**
     * 删除肇庆多余的器材
     */
    final private function zq_repeat()
    {
        // 删除1226
        $a = array_pluck(DB::select("
        select id
from entire_instances
WHERE serial_number in (select serial_number from entire_instances GROUP BY serial_number HAVING count(*) > 1)
  AND created_at like '2021-05-26%'
  AND category_unique_code = 'Q01'
"), "id");
        $a_r = DB::table("entire_instances")->whereIn("id", $a)->update(["deleted_at" => now()->format("Y-m-d 00:00:00"),]);
        $this->info("删除：$a_r");

        // 删除 2021-05-26
        $b = array_pluck(DB::select("
        select id
from entire_instances
where created_at like '2021-05-26%'
  and updated_at like '2021-05-26%'
  and category_unique_code = 'Q01'
"), "id");
        $b_r = DB::table("entire_instances")->whereIn("id", $b)->update(["deleted_at" => now()->format("Y-m-d 00:00:00"),]);
        $this->info("删除：$b_r");
    }

    final private function installRoomName()
    {
        // DB::statement("alter table install_rooms add name varchar(50) default '' not null comment '房间名称'");
        // DB::statement("alter table install_tiers add sort int default 0 null comment '排序'");
        // DB::statement("alter table install_positions add sort int default 0 not null comment '排序'");

        $install_rooms = InstallRoom::with([])->get();
        $install_rooms->each(
        /**
         * @throws Throwable
         */
            function (InstallRoom $install_room) {
                $install_room->fill(["name" => $install_room->type->text])->saveOrFail();
            });
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws Exception
     * @throws Throwable
     */
    final private function sosEntireInstanceLog()
    {
        $organization_code = strtolower(env("ORGANIZATION_CODE"));

        $excel_reader = ExcelReaderService::File(storage_path("$organization_code-entire-instance-logs.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColNumber(2)
            ->ReadBySheetIndex()
            ->Close();
        $excel_data = $excel_reader->GetData(["日志ID", "车站ID"]);

        if ($excel_data->isNotEmpty()) {
            $excel_data->each(function ($excel_datum) {
                $station = Maintain::with([])->where("id", $excel_datum["车站ID"])->first();
                if (!$station) {
                    $this->error("车站不存在：{$excel_datum["车站ID"]}");
                    return null;
                }

                $entire_instance_log = EntireInstanceLog::with([])->where("id", $excel_datum["日志ID"])->first();
                if (!$entire_instance_log) {
                    $this->error("日志不存在：{$excel_datum["日志ID"]}");
                    return null;
                }

                $entire_instance_log->fill(["station_unique_code" => $station->unique_code])->saveOrFail();
                $this->info("修改：{$excel_datum["日志ID"]} ➡ {$excel_datum["车站ID"]} $station->name $station->unique_code");
            });
        }
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws Exception
     * @throws Throwable
     */
    final private function sosWarehouseReport()
    {
        $organization_code = strtolower(env("ORGANIZATION_CODE"));

        $excel_reader = ExcelReaderService::File(storage_path("$organization_code-warehouse-reports.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColNumber(2)
            ->ReadBySheetIndex()
            ->Close();
        $excel_data = $excel_reader->GetData(["ID", "车站"]);

        if ($excel_data->isNotEmpty()) {
            $excel_data->each(function ($excel_datum) {
                $station = Maintain::with([])->where("id", $excel_datum["车站"])->first();
                if (!$station) {
                    $this->error("车站不存在：{$excel_datum["车站"]}");
                    return null;
                }

                $warehouse_report = WarehouseReport::with([])->where("id", $excel_datum["ID"])->first();
                if (!$warehouse_report) {
                    $this->error("出入所单不存在：{$excel_datum["ID"]}");
                    return null;
                }

                $warehouse_report->fill(["maintain_station_unique_code" => $station->unique_code])->saveOrFail();
                $this->info("修改：{$excel_datum["ID"]} ➡ {$excel_datum["车站"]} $station->name $station->unique_code");
            });
        }
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws Throwable
     */
    final private function sosEntireInstanceUseReport()
    {
        $organization_code = strtolower(env("ORGANIZATION_CODE"));

        $excel_reader = ExcelReaderService::File(storage_path("$organization_code-entire-instance-use-reports.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColNumber(2)
            ->ReadBySheetIndex()
            ->Close();
        $excel_data = $excel_reader->GetData(["ID", "车站"]);

        if ($excel_data->isNotEmpty()) {
            $excel_data->each(function ($excel_datum) {
                $station = Maintain::with([])->where("id", $excel_datum["车站"])->first();
                if (!$station) {
                    $this->error("车站不存在：{$excel_datum["车站"]}");
                    return null;
                }

                $entire_instance_use_report = EntireInstanceUseReport::with([])->where("id", $excel_datum["ID"])->first();
                if (!$entire_instance_use_report) {
                    $this->error("上道记录不存在：{$excel_datum["ID"]}");
                    return null;
                }

                $entire_instance_use_report->fill(["maintain_station_unique_code" => $station->unique_code])->saveOrFail();
                $this->info("修改：{$excel_datum["ID"]} ➡ {$excel_datum["车站"]} $station->name $station->unique_code");
            });
        }
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws Throwable
     */
    final private function sosRepairBasePlanOutCycleFixBill()
    {
        $organization_code = strtolower(env("ORGANIZATION_CODE"));

        $excel_reader = ExcelReaderService::File(storage_path("$organization_code-repair-base-plan-out-cycle-fix-bills.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColNumber(2)
            ->ReadBySheetIndex()
            ->Close();
        $excel_data = $excel_reader->GetData(["ID", "车站"]);

        if ($excel_data->isNotEmpty()) {
            $excel_data->each(function ($excel_datum) {
                $station = Maintain::with([])->where("id", $excel_datum["车站"])->first();
                if (!$station) {
                    $this->error("车站不存在：{$excel_datum["车站"]}");
                    return null;
                }

                $repair_base_plan_out_cycle_fix_bill = RepairBasePlanOutCycleFixBill::with([])->where("id", $excel_datum["ID"])->first();
                if (!$repair_base_plan_out_cycle_fix_bill) {
                    $this->error("周期修任务不存在：{$excel_datum["ID"]}");
                    return null;
                }

                $repair_base_plan_out_cycle_fix_bill->fill(["station_unique_code" => $station->unique_code])->saveOrFail();
                $this->info("修改：{$excel_datum["ID"]} ➡ {$excel_datum["车站"]} $station->name $station->unique_code");
            });
        }
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws Throwable
     */
    final private function sosRepairBasePlanOutCycleFixEntireInstance()
    {
        $organization_code = strtolower(env("ORGANIZATION_CODE"));

        $excel_reader = ExcelReaderService::File(storage_path("$organization_code-repair-base-plan-out-cycle-fix-entire-instances.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColNumber(2)
            ->ReadBySheetIndex()
            ->Close();
        $excel_data = $excel_reader->GetData(["ID", "车站"]);

        if ($excel_data->isNotEmpty()) {
            $excel_data->each(function ($excel_datum) {
                $station = Maintain::with([])->where("id", $excel_datum["车站"])->first();
                if (!$station) {
                    $this->error("车站不存在：{$excel_datum["车站"]}");
                    return null;
                }

                $repair_base_plan_out_cycle_fix_entire_instance = RepairBasePlanOutCycleFixEntireInstance::with([])->where("id", $excel_datum["ID"])->first();
                if (!$repair_base_plan_out_cycle_fix_entire_instance) {
                    $this->error("周期修器材不存在：{$excel_datum["ID"]}");
                    return null;
                }

                $repair_base_plan_out_cycle_fix_entire_instance->fill(["station_unique_code" => $station->unique_code])->saveOrFail();
                $this->info("修改：{$excel_datum["ID"]} ➡ {$excel_datum["车站"]} $station->name $station->unique_code");
            });
        }
    }

    final public function sosSceneWorkshop(): void
    {
        WarehouseReport::with(["Station"])
            ->where("maintain_station_unique_code", "<>", "")
            ->get()
            ->each(
            /**
             * @throws Throwable
             */
                function (WarehouseReport $datum) {
                    $datum->fill(["scene_workshop_unique_code" => @$datum->Station->Parent->unique_code ?: "", "scene_workshop_name" => @$datum->Station->Parent->name ?: ""])->saveOrFail();
                    $scene_workshop_name = @$datum->Station->Parent->name ?: "无";
                    $scene_workshop_unique_code = @$datum->Station->Parent->unique_code ?: "无";
                    $this->info("修改出入所单： $datum->id $datum->Station->unique_code $datum->Station->name ➡ $scene_workshop_unique_code $scene_workshop_name");
                });

        EntireInstanceUseReport::with(["Station"])
            ->where("maintain_station_unique_code", "<>", "")
            ->get()
            ->each(
            /**
             * @throws Throwable
             */
                function (EntireInstanceUseReport $datum) {
                    $datum->fill(["scene_workshop_unique_code" => @$datum->Station->Parent->unique_code ?: ""])->saveOrFail();
                    $scene_workshop_name = @$datum->Station->Parent->name ?: "无";
                    $scene_workshop_unique_code = @$datum->Station->Parent->unique_code ?: "无";
                    $this->info("修改上道使用记录： $datum->id $datum->Station->unique_code $datum->Station->name ➡ $scene_workshop_unique_code $scene_workshop_name");
                });
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws Exception
     * @throws Throwable
     */
    final private function sosInstallPosition(): void
    {
        try {
            $organization_code = strtolower(env("ORGANIZATION_CODE"));
            $excel_reader = ExcelReaderService::File(storage_path("$organization_code-install-positions.xlsx"))
                ->SetOriginRow(2)
                ->SetFInishColText("B")
                ->ReadBySheetIndex()
                ->Close();
            $excel_data = $excel_reader->GetData(["机房代码", "车站ID"]);

            if ($excel_data->isNotEmpty()) {
                $excel_data->each(function ($excel_datum) {
                    $install_room = InstallRoom::with([])->where("unique_code", $excel_datum["机房代码"])->first();
                    if (!$install_room) throw new EmptyException("机房不存在");

                    $station = Maintain::with([])->where("id", $excel_datum["车站ID"])->first();
                    if (!$station) throw new EmptyException("车站不存在");
                    $install_room->fill(["station_unique_code" => $station->unqiue_code])->saveOrFail();

                    $this->info("修改：{$excel_datum["机房代码"]} → $station->unique_code $station->name");
                });
            }
        } catch (Exception $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
}
