<?php

namespace App\Console\Commands;

use App\Facades\TextFacade;
use App\Model\EntireModel;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class KindClearUpQCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kinds-clear-up:q {operator} {old_conn_code?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $old_conn_code = $this->argument("old_conn_code");
        $this->{$this->argument("operator")}($old_conn_code);
    }

    /**
     * @throws Exception
     */
    private function hk_2022_05_16()
    {
        // 无极缓动继电器 JWXC-H310 → 无极缓放继电器 JWXC-H310
        // $this->__moveModels("Q010301", "010405");
        // 海口 无极缓动继电器 JWXC-H310 >> 无极缓放继电器 JWXC-H310
        // $this->__moveModels("Q010301","Q010405");
        // 海口 交流二元继电器 JRJC-70/240 >> 交流二元继电器 JRJC1-70/240
        // $this->__moveModels("Q010D02","Q010D04");
        // 信号机专用 信号点灯监测装置 BXZ-BJQ >> 道岔专用 限时保护报警器 BXZ-BJQ
        // $this->__moveModels("Q060209","Q040C01");
        // 信号机专用 信号点灯监测装置 PB-3(S) Q060205 >> 信号机专用 信号点灯监测装置 PB-3(S)灯丝断丝定位显示器 Q060207
        // $this->__moveModels("Q060205","Q060207");
        // 信号机专用 点灯单元 BGY-TD110-80 Q060111 >> 变压器 隔离变压器 Q02070O
        // $this->__moveModels("Q060111","Q02070O");
        // 道岔专用 断相保护器 BXZ-BJQ Q04040T >> 道岔专用 限时保护报警器 BXZ-BJQ Q040C01
        // $this->__moveModels("Q04040T","Q040C01");
        // 继电器 交流二元差动继电器 JSDXC-850 Q010K04 >> 继电器 时间继电器 JSDXC-850 Q010F06
        // $this->__moveModels("Q010K04", "Q010F06");
        // 其他类 电阻器 R1-4.4/440 Q130216 >> 轨道电路及电码化 固定抽头变阻器 R1-4.4/440 Q051102
        $this->__moveModels("Q130216", "Q051102");
        // 轨道电路及电码化 电阻器 R1-4.4/440 Q051K0A >> 轨道电路及电码化 固定抽头变阻器 R1-4.4/440 Q051102
        $this->__moveModels("Q051K0A", "Q051102");
        // 其他类 电阻器 R1-2.2/220 Q13022S >> 轨道电路及电码化 固定抽头变阻器 R1-2.2/220 Q051101
        $this->__moveModels("Q13022S", "Q051101");
        // 其他类 元器件（电阻、电容） R1-6.9/690 Q13060A >> 轨道电路及电码化 元器件（电阻、电容） R1-6.9/690 Q051N01
        $this->__moveModels("Q13060A", "Q051N01");
        // 轨道电路及电码化 元器件（电阻、电容） R1-6.9/690 Q051N04 >> 轨道电路及电码化 元器件（电阻、电容） R1-6.9/690 Q051N01
        $this->__moveModels("Q051N04", "Q051N01");
        // 轨道电路及电码化 固定抽头变阻器 R1-6.9/690 Q051104 >> 轨道电路及电码化 元器件（电阻、电容） R1-6.9/690 Q051N01
        $this->__moveModels("Q051104", "Q051N01");

        // 删除多余型号（继电器）
        // $models = [
        //     "无极继电器" => [
        //         "JWXC-7",
        //         "JWXC-2.3",
        //         "JWXC-2000",
        //         "JWXC-310",
        //         "JWXC-H62",
        //         "JWXC1-1700",
        //         "JWXC1-H340",
        //         "JXWC1-1000",
        //         "JXWC1-H600",
        //         "JWXC1-1000",
        //         // "JWXC-H600",
        //     ],
        //     "整流继电器" => [
        //         "JZXC-20000",
        //         "JZXC-H18F1",
        //         "JZXC2-480",
        //         "JZXC-0.56",
        //         "JZXC30H18",
        //         "JZXC3-H18",
        //         "JZXCB-480",
        //         "JZXC-480B",
        //         "JZXC-16F",
        //         "JZXC-480F",
        //         "JZXC-0.14",
        //         "JZXC-H16",
        //         "JZXC-480G",
        //         "JZXC-H142",
        //     ],
        //     "整流加强接点继电器" => [
        //         "JZJXC-7200",
        //         "JZJXC-100",
        //     ],
        //     "单闭磁继电器" => [
        //         "JSBXC-850",
        //         "JSBXC-870B01",
        //         "JSBXC-870B04",
        //     ],
        //     // "交流二元差动继电器" => [
        //     //     "JSDXC-850",
        //     // ],
        //     "无极加强接点继电器" => [
        //         "JWJXC-300/370",
        //         "JWJXC-135/135",
        //         "JWJXC-1700",
        //     ],
        //     "无极缓放继电器" => [
        //         "JWXC-500/H300",
        //         "JWXC1-H600",
        //     ],
        //     "无极加强接点缓放继电器" => [
        //         "JWJXC-H125/0.13",
        //         "JWJXC-H80/0.06",
        //         "JWJXC-H120/0.17",
        //         "JWJXC-125/0.13",
        //     ],
        //     "有极加强接点继电器" => [
        //         "JYJXC-135/220",
        //         "JYJXC-X135/220",
        //         "JYJXC1-160/260",
        //         "JYJC-160/260",
        //         "JYJXC-220/220",
        //         "JYJXC-J3000",
        //         "JYXC-660",
        //     ],
        //     "交流继电器" => [
        //         "JJXC-15",
        //         "JZSJC1",
        //         "JJJC4",
        //     ],
        //     "时间继电器" => [
        //         "JSBXC1-870B01",
        //         "JSBXC1-870B04",
        //         "JSBXC1-870",
        //     ],
        //     "动态继电器" => [
        //         "JSDXC-850",
        //     ],
        //     "灯丝转换继电器" => [
        //         "JZCJ-0.12",
        //         "JZSJC1-0.12",
        //         "JZSJC2-0.12",
        //         "JZCJ2",
        //         "JZSJC2",
        //         "JZSJC-0.12",
        //     ],
        // ];

        // 删除多余型号（道岔专用）
        // $models = [
        //     "断相保护器" => [
        //         "BDX-80",
        //         "BXZ",
        //         "BDQ",
        //         "DBQ",
        //         "DBQC",
        //         "DBQR",
        //         "DBQR-S",
        //         "DBQ-S",
        //         "DBQX",
        //         "QDX1-S",
        //         "DBQR-SB/30S",
        //         "QDXⅡ-S13/30",
        //         "BDX-S13/30",
        //         "QDX2-S15/30",
        //         "ZXB",
        //         "QDX-S13/30",
        //         "DBQ-S-Ⅱ",
        //         "DBQ-SB/30S",
        //         "QDX2-S",
        //         "QDXⅡ-S13(30)",
        //         "QDX1-140",
        //         "BDX-30",
        //         "BDX-W",
        //         "DBQ-SRY",
        //         "DCBHQ-S",
        //         "QDX.R-S30",
        //         "ZXB型直流限时保护器",
        //     ],
        // ];

        // 删除多余型号（一体化点灯单元）
        $models = [
            "一体化点灯单元" => [
                "DZD-BT(5型)",
                "DZD-PD(5型)",
                "PB-3(S)",
                "DDX1-5(J)",
            ],
        ];

        collect($models)->each(function ($sub_model_names, $entire_model_name) {
            collect($sub_model_names)->each(function ($sub_model_name) use ($entire_model_name) {
                $sub_model = DB::table("entire_models as sm")
                    ->select(["sm.id", "sm.unique_code", "sm.name",])
                    ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                    ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                    ->where("sm.name", $sub_model_name)
                    ->where("em.name", $entire_model_name)
                    ->where("c.unique_code", "Q04")
                    ->first();

                if (!$sub_model) {
                    $this->error("跳过：{$sub_model_name}");
                    return null;
                }

                $count = DB::table("entire_instances as ei")
                    ->whereNull("ei.deleted_at")
                    ->where("model_unique_code", $sub_model->unique_code)
                    ->count();

                if ($count > 0) {
                    $this->error("跳过：{$entire_model_name} {$sub_model_name} {$sub_model->unique_code}");
                    return null;
                }

                // 删除型号
                DB::table("entire_models")->where("id", $sub_model->id)->update(["deleted_at" => now(),]);
                $this->info("已删除：{$sub_model_name} {$sub_model->unique_code}");
            });
        });
    }

    /**
     * @throws Exception
     */
    private function __moveModels(string $old_model_unique_code, string $new_model_unique_code)
    {
        if (!$old_model_unique_code) throw new Exception('旧型号不存在');
        if (!$new_model_unique_code) throw new Exception('新型号不存在');

        $old_model = EntireModel::with([])->where('unique_code', $old_model_unique_code)->where('is_sub_model', true)->first();

        if (!$old_model) {
            $this->comment("旧型号不存在：$old_model_unique_code");
        } else {
            $new_model = EntireModel::with([])->where('unique_code', $new_model_unique_code)->where('is_sub_model', true)->first();
            if (!$new_model) $this->comment('新型号不存在');

            DB::table('entire_instances as ei')
                ->whereNull('ei.deleted_at')
                ->where('model_unique_code', $old_model_unique_code)
                ->update([
                    'model_unique_code' => $new_model_unique_code,
                    'entire_model_unique_code' => $new_model_unique_code,
                ]);
            $this->info("移动型号：{$old_model->name}({$old_model->unique_code}) >> {$new_model->unique_code}");

            DB::table('entire_models')->where('unique_code', $old_model_unique_code)->update(['deleted_at' => now()]);
        }
    }

    private function hk_2022_05_07()
    {
        // 需要删除 Q05010G

        // 移动型号
        $move_models = [
            "Q05010V" => "Q052H01",
            "Q130610" => "Q052I01",
        ];

        collect($move_models)
            ->each(/**
             * @throws Exception
             */ function ($new_model_unique_code, $old_model_unique_code) {
                $this->__moveModels($old_model_unique_code, $new_model_unique_code);
            });

        DB::table("entire_models")->where("unique_code", "Q05010G")->update(["deleted_at" => now(),]);
    }

    private function b052()
    {
        $this->__moveModels("Q010702","Q010703");
    }

    private function gz()
    {
        // 移动型号
        $models = [
            // 'Q13022F' => 'Q131763',
            // 'Q13022G' => 'Q131764',
            // 'Q13022H' => 'Q131765',
            // 'Q053406' => 'Q131766',
            // 'Q05340F' => 'Q131767',
            // 'Q05340P' => 'Q13174V',
            // 'Q05340Q' => 'Q13174W',
            // 'Q13022Y' => 'Q131766',
            // 'Q13022N' => 'Q13172I',
            // 'Q13022P' => 'Q131768',
            // 'Q13022Q' => 'Q131769',
            // 'Q053404' => 'Q13176A',
            // 'Q05340B' => 'Q13176B',
            // 'Q05340C' => 'Q13176C',
            // 'Q05340G' => 'Q13176D',
            // 'Q05340H' => 'Q13176E',
            // 'Q05340I' => 'Q13176F',
            // 'Q05340J' => 'Q13176G',
            // 'Q05340K' => 'Q13176H',
            // 'Q05340U' => 'Q13173K',
            // "Q13173W" => "Q131762",
            // "Q05050O",  // 删除
            // "Q05050P",  // 删除
            // "Q05050Q",  // 删除
            // "Q05050R",  // 删除
            // "Q050515" => "Q05020I",
            // "Q020806" => "Q050212",
            // "Q051701" => "Q050212",

        ];

        collect($models)->each(
        /**
         * @throws Exception
         */ function ($new_model_unique_code, $old_model_unique_code) {
            $this->__moveModels($old_model_unique_code, $new_model_unique_code);
        });
    }

    /**
     * 海口断路器修改型号名称
     * @throws Exception
     */
    private function hk_dlq()
    {
        // 移动型号
        $move_models = [
            "Q300105" => "Q30011O",
            "Q30011H" => "Q30018S",
            "Q30011I" => "Q30017Z",
            "Q30011J" => "Q30017X",
        ];
        collect($move_models)
            ->each(function ($new_model_unique_code, $old_model_unique_code) {
                $this->__moveModels($old_model_unique_code, $new_model_unique_code);
            });

        // 型号改名
        // $update_name_models = [
        //     "QA-A-1(13)/0.5" => "QA-1(13)240V-0.5A",
        //     "QA-A-1(13)/1" => "QA-1(13)240V-1A",
        //     "QA-A-1(13)/2" => "QA-1(13)240V-2A",
        //     "QA-A-1(13)/3" => "QA-1(13)240V-3A",
        //     "QA-A-1(13)/5" => "QA-1(13)240V-5A",
        //     "QDC-A-1(13)/0.5" => "QDC-1(13)80V-0.5A",
        //     "QDC-A-1(13)/2" => "QDC-1(13)80V-2A",
        //     "QDC-A-1(13)/5" => "QDC-1(13)80V-5A",
        //     "QDC-A-1(13)/10" => "QDC-1(13)80V-10A",
        //     "QDC-A-2(13)/0.5" => "QDC-2(13)80V-0.5A",
        //     "QDC-A-2(13)/2" => "QDC-2(13)80V-2A",
        //     "QDC-A-2(13)/3" => "QDC-2(13)80V-3A",
        //     "QDC-A-2(13)/5" => "QDC-2(13)80V-5A",
        //     "QDC-A-2(13)/7" => "QDC-2(13)80V-7A",
        //     "QDC-A-2(13)/10" => "QDC-2(13)80V-10A",
        //     "KXD1(2P).DC220V 3A" => "KXD1-DC220V-3A-2P",
        //     "KXD1(2P).DC220V 5A" => "KXD1-DC220V-3A-5P",
        //     "KXD1.AC240V 0.5A" => "KXD1-AC240V-0.5A",
        //     "KXD2.AC240V 1A" => "KXD2-AC240V-1A",
        //     "KXD2.AC240V 3A" => "KXD2-AC240V-3A",
        // ];
        // KindsFacade::updateModelNames($update_name_models, "Q30", "Q3001");
    }

    /**
     * 车间 → 段中心
     */
    final private function syncW2P(): void
    {
        $old_conn_code = $this->argument("old_conn_code");
        if (!$old_conn_code) {
            $this->error("错误：数据库链接名不能为空");
            return;
        }
        $paragraph_center_conn_name = "paragraph_center";

        $__getDB = function (string $old_conn_name): Builder {
            return DB::connection($old_conn_name)
                ->table("entire_instances as ei")
                ->selectRaw(join(",", [
                    "c.name as category_name",
                    "c.unique_code as category_unique_code",
                    "c.nickname as category_nickname",
                    "em.name as entire_model_name",
                    "em.unique_code as entire_model_unique_code",
                    "em.nickname as entire_model_nickname",
                    "sm.name as sub_model_name",
                    "sm.unique_code as sub_model_unique_code",
                ]))
                ->join(DB::raw("entire_models sm"), "ei.model_unique_code", "=", "sm.unique_code")
                ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                ->where("sm.is_sub_model", true)
                ->whereNull("sm.deleted_at")
                ->where("em.is_sub_model", false)
                ->whereNull("em.deleted_at")
                ->where("c.unique_code", "like", "Q%")
                ->whereNull("c.deleted_at");
        };

        $__getDB($old_conn_code)
            ->orderBy("c.unique_code")
            ->orderBy("em.unique_code")
            ->orderBy("sm.unique_code")
            ->groupBy(["sm.unique_code",])
            // ->where("ei.identity_code", "Q070313B0740000001H")
            ->chunk(500, function ($entire_instances) use ($__getDB, $old_conn_code, $paragraph_center_conn_name) {
                $entire_instances->each(function ($datum) use ($__getDB, $old_conn_code, $paragraph_center_conn_name) {
                    [
                        "category_name" => $old_category_name,
                        "category_unique_code" => $old_category_unique_code,
                        "category_nickname" => $old_category_nickname,
                        "entire_model_name" => $old_entire_model_name,
                        "entire_model_unique_code" => $old_entire_model_unique_code,
                        "entire_model_nickname" => $old_entire_model_nickname,
                        "sub_model_name" => $old_sub_model_name,
                        "sub_model_unique_code" => $old_sub_model_unique_code,
                    ] = (array)$datum;

                    // 比对种类
                    $new_category = DB::connection($paragraph_center_conn_name)
                        ->table("equipment_categories")
                        ->where("name", $old_category_name)
                        ->first();
                    if ($new_category) {
                        // 种类存在
                        $new_category_unique_code = $new_category->unique_code;
                        $this->comment("种类存在：$old_category_name");
                    } else {
                        // 种类不存在
                        DB::connection($paragraph_center_conn_name)
                            ->table("equipment_categories")
                            ->insert([
                                "created_at" => now(),
                                "updated_at" => now(),
                                "unique_code" => $new_category_unique_code = $this->__generateCategoryUniqueCode(),
                                "name" => $old_category_name

                            ]);
                        $this->info("种类不存在：$old_category_name >> $new_category_unique_code");
                    }
                    $ret = $__getDB($old_conn_code)
                        ->where("c.name", $old_category_name)
                        ->where("em.name", $old_entire_model_name)
                        ->where("sm.name", $old_sub_model_name)
                        ->update(["ei.new_category_unique_code" => $new_category_unique_code,]);
                    $this->info("修改种类归属关系：$old_category_name $old_category_unique_code >> $new_category_unique_code 共：$ret");

                    // 比对类型
                    $new_entire_model = DB::connection($paragraph_center_conn_name)
                        ->table("equipment_models as eem")
                        ->selectRaw("eem.*")
                        ->join(DB::raw("equipment_categories ec"), "ec.unique_code", "=", "eem.equipment_category_unique_code")
                        ->where("ec.name", $old_category_name)
                        ->where("ec.unique_code", $new_category_unique_code)
                        ->where("eem.name", $old_entire_model_name)
                        ->first();
                    if ($new_entire_model) {
                        // 类型存在
                        $new_entire_model_unique_code = $new_entire_model->unique_code;
                        $this->comment("类型存在：$old_category_name $old_entire_model_name");
                    } else {
                        // 类型不存在
                        DB::connection($paragraph_center_conn_name)
                            ->table("equipment_models")
                            ->insert([
                                "created_at" => now(),
                                "updated_at" => now(),
                                "equipment_category_unique_code" => $new_category_unique_code,
                                "unique_code" => $new_entire_model_unique_code = $this->__generateEntireModelUniqueCode($new_category_unique_code),
                                "name" => $old_entire_model_name,
                            ]);
                        $this->info("类型不存在：$old_category_name $old_entire_model_name >> $new_entire_model_unique_code");
                    }

                    // 比对型号
                    $new_sub_model = DB::connection($paragraph_center_conn_name)
                        ->table("equipment_sub_models as esm")
                        ->selectRaw("esm.*")
                        ->join(DB::raw("equipment_models eem"), "eem.unique_code", "=", "esm.equipment_model_unique_code")
                        ->join(DB::raw("equipment_categories ec"), "ec.unique_code", "=", "eem.equipment_category_unique_code")
                        ->where("esm.name", $old_sub_model_name)
                        ->where("eem.name", $old_entire_model_name)
                        ->where("eem.unique_code", $new_entire_model_unique_code)
                        ->where("ec.name", $old_category_name)
                        ->where("ec.unique_code", $new_category_unique_code)
                        ->first();
                    if ($new_sub_model) {
                        // 型号存在
                        $new_sub_model_unique_code = $new_sub_model->unique_code;
                        $this->comment("型号存在：$old_category_name $old_entire_model_name $old_sub_model_name");
                    } else {
                        // 型号不存在
                        DB::connection($paragraph_center_conn_name)
                            ->table("equipment_sub_models")
                            ->insert([
                                "created_at" => now(),
                                "updated_at" => now(),
                                "equipment_model_unique_code" => $new_entire_model_unique_code,
                                "unique_code" => $new_sub_model_unique_code = $this->__generateSubModelUniqueCode($new_entire_model_unique_code),
                                "name" => $old_sub_model_name,
                            ]);
                        $this->info("型号不存在：$old_category_name $old_entire_model_name $old_sub_model_name >> $new_sub_model_unique_code");
                    }
                    $ret = $__getDB($old_conn_code)
                        ->where("c.name", $old_category_name)
                        ->where("em.name", $old_entire_model_name)
                        ->where("sm.name", $old_sub_model_name)
                        ->update([
                            "ei.new_sub_model_unique_code" => $new_sub_model_unique_code,
                            "ei.new_entire_model_unique_code" => $new_entire_model_unique_code,
                            "ei.new_category_unique_code" => $new_category_unique_code,
                        ]);
                    $this->info("修改型号归属关系：$old_category_name $old_entire_model_name $old_sub_model_name $old_sub_model_unique_code >> $new_sub_model_unique_code 共：$ret");
                });
            });
    }

    final private function __generateCategoryUniqueCode(): string
    {
        $last = DB::connection('paragraph_center')
            ->table('equipment_categories')
            ->orderByDesc('unique_code')
            ->first();

        // 36进制
        $max = $last ? intval(TextFacade::from36(substr($last->unique_code, -2))) : 0;  // 36进制
        return 'Q' . str_pad(TextFacade::to36(strval($max + 1)), 2, '0', STR_PAD_LEFT);
    }

    final private function __generateEntireModelUniqueCode(string $category_unique_code): string
    {
        $last = DB::connection('paragraph_center')
            ->table('equipment_models')
            ->where('equipment_category_unique_code', $category_unique_code)
            ->orderByDesc('unique_code')
            ->first();


        $max = $last ? intval(TextFacade::from36(substr($last->unique_code, -2))) : 0;
        return $category_unique_code . str_pad(TextFacade::to36($max + 1), 2, '0', STR_PAD_LEFT);
    }

    final private function __generateSubModelUniqueCode(string $parent_unique_code): string
    {
        $last = DB::connection('paragraph_center')
            ->table('equipment_sub_models')
            ->where('equipment_model_unique_code', $parent_unique_code)
            ->orderByDesc('unique_code')
            ->first();

        $max = $last ? intval(TextFacade::from36(substr($last->unique_code, -2))) : 0;
        return $parent_unique_code . str_pad(TextFacade::to36($max + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * 段中心 → 车间
     */
    final private function syncP2W()
    {
        $this->comment("P2W：开始");
        $old_conn_code = $this->argument("old_conn_code") ?: strtolower(env("ORGANIZATION_CODE"));
        $paragraph_center_conn_name = "paragraph_center";

        $sub_model_count = DB::connection($paragraph_center_conn_name)->table("equipment_sub_models")->count();
        $exec_sub_model_count = 0;

        DB::connection($paragraph_center_conn_name)
            ->table("equipment_categories")
            ->orderBy("unique_code")
            ->each(function ($equipment_category)
            use (
                $paragraph_center_conn_name,
                &$categories,
                &$entire_models,
                &$sub_models,
                $old_conn_code,
                $sub_model_count,
                &$exec_sub_model_count
            ) {
                $old_category = DB::connection($old_conn_code)->table("categories")->where("name", $equipment_category->name)->first();
                DB::connection($old_conn_code)
                    ->table("new_categories")
                    ->insert([
                        "created_at" => $equipment_category->created_at,
                        "updated_at" => now(),
                        "unique_code" => $equipment_category->unique_code,
                        "name" => $equipment_category->name,
                        "is_show" => false,
                        "nickname" => @$old_category->nickname ?: "",
                    ]);
                $this->comment("新增种类：{$equipment_category->name} {$equipment_category->unique_code}");

                DB::connection($paragraph_center_conn_name)
                    ->table("equipment_models")
                    ->where("equipment_category_unique_code", $equipment_category->unique_code)
                    ->orderBy("unique_code")
                    ->each(function ($equipment_model)
                    use (
                        $equipment_category,
                        $paragraph_center_conn_name,
                        &$entire_models,
                        &$sub_models,
                        $old_conn_code,
                        $sub_model_count,
                        &$exec_sub_model_count
                    ) {
                        $old_entire_model = DB::connection($old_conn_code)
                            ->table("entire_models as em")
                            ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                            ->where("c.name", $equipment_category->name)
                            ->where("em.name", $equipment_model->name)
                            ->where("em.is_sub_model", false)
                            ->first();
                        DB::connection($old_conn_code)
                            ->table("new_entire_models")
                            ->insert([
                                "created_at" => $equipment_model->created_at,
                                "updated_at" => now(),
                                "unique_code" => $equipment_model->unique_code,
                                "category_unique_code" => $equipment_category->unique_code,
                                "is_sub_model" => false,
                                "name" => $equipment_model->name,
                                "nickname" => @$old_entire_model->nickname ?: "",
                                "fix_cycle_value" => @$old_entire_model->fix_cycle_value ?: 0,
                                "fix_cycle_unit" => "YEAR",
                                "custom_fix_cycle" => @$old_entire_model->custom_fix_cycle ?: false,
                            ]);
                        $this->info("新增类型：{$equipment_model->name} {$equipment_model->unique_code}");

                        DB::connection($paragraph_center_conn_name)
                            ->table("equipment_sub_models")
                            ->where("equipment_model_unique_code", $equipment_model->unique_code)
                            ->orderBy("unique_code")
                            ->each(function ($equipment_sub_model)
                            use (
                                $equipment_category,
                                $equipment_model,
                                &$sub_models,
                                $old_conn_code,
                                $sub_model_count,
                                &$exec_sub_model_count
                            ) {
                                $old_sub_model = DB::connection($old_conn_code)
                                    ->table("entire_models as sm")
                                    ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                                    ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                                    ->where("c.name", $equipment_category->name)
                                    ->where("em.name", $equipment_model->name)
                                    ->where("sm.name", $equipment_sub_model->name)
                                    ->where("em.is_sub_model", false)
                                    ->where("sm.is_sub_model", true)
                                    ->first();
                                $exec_sub_model_count++;
                                $processed = number_format(($exec_sub_model_count / $sub_model_count) * 100, 2);
                                DB::connection($old_conn_code)
                                    ->table("new_entire_models")
                                    ->insert([
                                        "created_at" => $equipment_sub_model->created_at,
                                        "updated_at" => now(),
                                        "unique_code" => $equipment_sub_model->unique_code,
                                        "category_unique_code" => $equipment_category->unique_code,
                                        "parent_unique_code" => $equipment_model->unique_code,
                                        "is_sub_model" => true,
                                        "name" => $equipment_sub_model->name,
                                        "nickname" => @$old_sub_model->nickname ?: "",
                                        "fix_cycle_value" => @$old_sub_model->fix_cycle_value ?: 0,
                                        "fix_cycle_unit" => "YEAR",
                                        "custom_fix_cycle" => @$old_sub_model->custom_fix_cycle ?: false,
                                    ]);
                                $this->info("{$processed}% 新增型号：{$equipment_sub_model->name} {$equipment_sub_model->unique_code}");
                            });
                    });
            });
        $this->info("P2W：完成");
    }

    /**
     * 记录旧种类型名称
     */
    private function saveOld(?string $conn_code)
    {
        $this->comment("删除无法对应上型号的器材");
        $conn_code = $conn_code ?? strtolower(env("ORGANIZATION_CODE"));

        $model_unique_codes = DB::connection($conn_code)
            ->table("entire_instances")
            ->select("model_unique_code")
            ->where("category_unique_code", "like", "Q%")
            ->groupBy(["model_unique_code"])
            ->get()
            ->pluck("model_unique_code")
            ->unique()
            ->values();
        $exists_model_unique_codes = DB::connection($conn_code)
            ->table("entire_models as em")
            ->select("unique_code")
            ->whereIn("unique_code", $model_unique_codes)
            ->where("unique_code", "like", "Q%")
            ->where("is_sub_model", true)
            ->get()
            ->pluck("unique_code")
            ->unique()
            ->values();
        $diff = $model_unique_codes->diff($exists_model_unique_codes)->toArray();
        DB::connection($conn_code)
            ->table("entire_instances")
            ->selectRaw("count(model_unique_code) as aggregate, model_unique_code, model_name")
            ->where("category_unique_code", "like", "Q%")
            ->whereIn("model_unique_code", $diff)
            ->groupBy(["model_unique_code", "model_name",])
            ->update(["delete_description" => "合并种类型：因没有找到对应型号，删除处理。", "deleted_at" => now(),]);
        $this->info("删除无法对应上型号的器材");

        $this->comment("记录原始种类型名称");
        DB::connection($conn_code)
            ->table("entire_instances as ei")
            ->select("c.name as category_name", "c.unique_code as category_unique_code", "em.name as entire_model_name", "em.unique_code as entire_model_unique_code", "sm.name as sub_model_name", "sm.unique_code as sub_model_unique_code")
            ->join(DB::raw("entire_models sm"), "ei.model_unique_code", "=", "sm.unique_code")
            ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
            ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
            ->where("c.unique_code", "like", "Q%")
            ->whereNull("sm.deleted_at")
            ->whereNull("em.deleted_at")
            ->whereNull("c.deleted_at")
            ->groupBy(["sm.unique_code"])
            ->orderBy("sm.id")
            ->chunk(500, function ($data) use ($conn_code) {
                $data->each(function ($datum) use ($conn_code) {
                    DB::connection($conn_code)
                        ->table("entire_instances as ei")
                        ->where("ei.model_unique_code", $datum->sub_model_unique_code)
                        ->update([
                            "old_category_unique_code" => $datum->category_unique_code,
                            "old_entire_model_unique_code" => $datum->entire_model_unique_code,
                            "old_sub_model_unique_code" => $datum->sub_model_unique_code,
                            "old_category_name" => $datum->category_name,
                            "old_entire_model_name" => $datum->entire_model_name,
                            "old_sub_model_name" => $datum->sub_model_name,
                        ]);
                    $this->comment("修改：{$datum->sub_model_unique_code} {$datum->sub_model_name}");
                });
            });
        $this->info("记录原始种类型名称");
    }

    /**
     * 用新代码替换老代码
     */
    private function newToOld()
    {
        $old_conn_code = $this->argument("old_conn_code");
        DB::connection($old_conn_code)->statement("rename table categories to old_categories");
        DB::connection($old_conn_code)->statement("rename table new_categories to categories");
        DB::connection($old_conn_code)->statement("rename table entire_models to old_entire_models");
        DB::connection($old_conn_code)->statement("rename table new_entire_models to entire_models");
        // DB::connection($old_conn_code)->select("update categories set deleted_at = now() where unique_code like '~~~%'");
        // DB::connection($old_conn_code)->select("update entire_models set deleted_at = now() where unique_code like '~~~%'");
        DB::connection($old_conn_code)->select("update entire_instances set category_unique_code = new_category_unique_code, entire_model_unique_code = new_sub_model_unique_code, model_unique_code = new_sub_model_unique_code where deleted_at is not null");
        $this->info("new to old OK");
    }
}
