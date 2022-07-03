<?php

namespace App\Console\Commands;

use App\Facades\TextFacade;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class KindClearUpSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kinds-clear-up:s {operator} {old_conn_code?}';

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

    final private function __generateCategoryUniqueCode(): string
    {
        $last = DB::connection('paragraph_center')
            ->table('facility_categories')
            ->orderByDesc('unique_code')
            ->first();

        // 36进制
        $max = $last ? intval(TextFacade::from36(substr($last->unique_code, -2))) : 0;  // 36进制
        return 'S' . str_pad(TextFacade::to36(strval($max + 1)), 2, '0', STR_PAD_LEFT);
    }

    final private function __generateEntireModelUniqueCode(string $category_unique_code): string
    {
        $last = DB::connection('paragraph_center')
            ->table('facility_models')
            ->where('facility_category_unique_code', $category_unique_code)
            ->orderByDesc('unique_code')
            ->first();


        $max = $last ? intval(TextFacade::from36(substr($last->unique_code, -2))) : 0;
        return $category_unique_code . str_pad(TextFacade::to36($max + 1), 2, '0', STR_PAD_LEFT);
    }

    final private function __generateSubModelUniqueCode(string $parent_unique_code): string
    {
        $last = DB::connection('paragraph_center')
            ->table('facility_sub_models')
            ->where('facility_model_unique_code', $parent_unique_code)
            ->orderByDesc('unique_code')
            ->first();

        $max = $last ? intval(TextFacade::from36(substr($last->unique_code, -2))) : 0;
        return $parent_unique_code . str_pad(TextFacade::to36($max + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * 车间 → 段中心
     */
    private function syncW2P()
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
                    "em.life_year as entire_model_fix_cycle_unit",
                    "em.fix_cycle_unit as entire_model_fix_cycle_unit",
                    "em.fix_cycle_value as entire_model_fix_cycle_value",
                    "em.custom_fix_cycle as entire_model_custom_fix_cycle",
                ]))
                ->join(DB::raw("entire_models em"), "ei.entire_model_unique_code", "=", "em.unique_code")
                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                ->where("em.is_sub_model", false)
                ->whereNull("em.deleted_at")
                ->where("c.unique_code", "like", "S%")
                ->whereNull("c.deleted_at");
        };

        $__getDB($old_conn_code)
            ->orderBy("c.unique_code")
            ->orderBy("em.unique_code")
            ->groupBy(["em.unique_code",])
            // ->where("ei.identity_code", "Q02070KB0740000001H")
            ->chunk(500, function ($entire_instances) use ($__getDB, $old_conn_code, $paragraph_center_conn_name) {
                $entire_instances->each(function ($datum) use ($__getDB, $old_conn_code, $paragraph_center_conn_name) {
                    [
                        "category_name" => $old_category_name,
                        "category_unique_code" => $old_category_unique_code,
                        "category_nickname" => $old_category_nickname,
                        "entire_model_name" => $old_entire_model_name,
                        "entire_model_unique_code" => $old_entire_model_unique_code,
                        "entire_model_nickname" => $old_entire_model_nickname,
                    ] = (array)$datum;

                    // 比对种类
                    $new_category = DB::connection($paragraph_center_conn_name)
                        ->table("facility_categories")
                        ->where("name", $old_category_name)
                        ->first();
                    if ($new_category) {
                        // 种类存在
                        $new_category_unique_code = $new_category->unique_code;
                        $this->comment("种类存在：$old_category_name");
                    } else {
                        // 种类不存在
                        DB::connection($paragraph_center_conn_name)
                            ->table("facility_categories")
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
                        ->update(["ei.new_category_unique_code" => $new_category_unique_code,]);
                    $this->info("修改种类归属关系：$old_category_name $old_category_unique_code >> $new_category_unique_code 共：$ret");

                    // 比对类型
                    $new_entire_model = DB::connection($paragraph_center_conn_name)
                        ->table("facility_models as fm")
                        ->selectRaw("fm.*")
                        ->join(DB::raw("facility_categories fc"), "fc.unique_code", "=", "fm.facility_category_unique_code")
                        ->where("fc.name", $old_category_name)
                        ->where("fc.unique_code", $new_category_unique_code)
                        ->where("fm.name", $old_entire_model_name)
                        ->first();
                    if ($new_entire_model) {
                        // 类型存在
                        $new_entire_model_unique_code = $new_entire_model->unique_code;
                        $this->comment("类型存在：$old_category_name $old_entire_model_name");
                    } else {
                        // 类型不存在
                        DB::connection($paragraph_center_conn_name)
                            ->table("facility_models")
                            ->insert([
                                "created_at" => now(),
                                "updated_at" => now(),
                                "facility_category_unique_code" => $new_category_unique_code,
                                "unique_code" => $new_entire_model_unique_code = $this->__generateEntireModelUniqueCode($new_category_unique_code),
                                "name" => $old_entire_model_name,
                            ]);
                        $this->info("类型不存在：$old_category_name $old_entire_model_name >> $new_entire_model_unique_code");
                    }
                    $ret = $__getDB($old_conn_code)
                        ->where("c.name", $old_category_name)
                        ->where("em.name", $old_entire_model_name)
                        ->update(["ei.new_entire_model_unique_code" => $new_entire_model_unique_code,]);
                    $this->info("修改类型归属关系：$old_entire_model_name $old_entire_model_unique_code >> $new_entire_model_unique_code 共：$ret");
                });
            });
    }

    /**
     * 段中心种类型同步到车间
     */
    final private function syncP2W()
    {
        $this->comment("P2W：开始");
        $old_conn_code = $this->argument("old_conn_code") ?: strtolower(env("ORGANIZATION_CODE"));
        $paragraph_center_conn_name = "paragraph_center";

        // DB::connection($old_conn_code)->select("update categories set unique_code = concat('~~~',unique_code) where unique_code like 'S%'");
        // DB::connection($old_conn_code)->select("update entire_models set unique_code = concat('~~~',unique_code) where unique_code like 'S%'");

        DB::connection($paragraph_center_conn_name)
            ->table("facility_categories")
            ->orderBy("unique_code")
            ->each(function ($facility_category)
            use (
                $paragraph_center_conn_name,
                &$categories,
                &$entire_models,
                &$sub_models,
                $old_conn_code
            ) {
                $old_category = DB::connection($old_conn_code)->table("categories")->where("name", $facility_category->name)->first();
                if ($old_category) {
                    DB::connection($old_conn_code)->table("categories")->where("id", $old_category->id)->update(["unique_code" => $facility_category->unique_code]);
                    $this->comment("同步种类：{$facility_category->name} {$facility_category->unique_code}");
                } else {
                    DB::connection($old_conn_code)
                        ->table("new_categories")
                        ->insert([
                            "created_at" => $facility_category->created_at,
                            "updated_at" => now(),
                            "unique_code" => $facility_category->unique_code,
                            "name" => $facility_category->name,
                            "is_show" => false,
                            "nickname" => @$old_category->nickname ?: "",
                        ]);
                    $this->info("新增种类：{$facility_category->name} {$facility_category->unique_code}");
                }

                DB::connection($paragraph_center_conn_name)
                    ->table("facility_models")
                    ->where("facility_category_unique_code", $facility_category->unique_code)
                    ->orderBy("unique_code")
                    ->each(function ($facility_model)
                    use (
                        $facility_category,
                        $paragraph_center_conn_name,
                        &$entire_models,
                        &$sub_models,
                        $old_conn_code
                    ) {
                        $old_entire_model = DB::connection($old_conn_code)
                            ->table("entire_models as em")
                            ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                            ->where("c.name", $facility_category->name)
                            ->where("em.name", $facility_model->name)
                            ->where("em.is_sub_model", false)
                            ->first();
                        // if ($old_entire_model) {
                        //     DB::connection($old_conn_code)->table("entire_models")->where("id", $old_entire_model->id)->update(["unique_code" => $facility_model->unique_code]);
                        //     $this->comment("同步类型：{$facility_model->name} {$facility_model->unique_code}");
                        // } else {
                        //     DB::connection($old_conn_code)
                        //         ->table("entire_models")
                        //         ->insert([
                        //             "created_at" => $facility_model->created_at,
                        //             "updated_at" => now(),
                        //             "unique_code" => $facility_model->unique_code,
                        //             "category_unique_code" => $facility_category->unique_code,
                        //             "is_sub_model" => false,
                        //             "name" => $facility_model->name,
                        //             "nickname" => @$old_entire_model->nickname ?: "",
                        //             "fix_cycle_value" => @$old_entire_model->fix_cycle_value ?: 0,
                        //             "fix_cycle_unit" => "YEAR",
                        //             "custom_fix_cycle" => @$old_entire_model->custom_fix_cycle ?: false,
                        //         ]);
                        //     $this->info("新增类型：{$facility_model->name} {$facility_model->unique_code}");
                        // }
                        DB::connection($old_conn_code)
                            ->table("new_entire_models")
                            ->insert([
                                "created_at" => $facility_model->created_at,
                                "updated_at" => now(),
                                "unique_code" => $facility_model->unique_code,
                                "category_unique_code" => $facility_category->unique_code,
                                "is_sub_model" => false,
                                "name" => $facility_model->name,
                                "nickname" => @$old_entire_model->nickname ?: "",
                                "fix_cycle_value" => @$old_entire_model->fix_cycle_value ?: 0,
                                "fix_cycle_unit" => "YEAR",
                                "custom_fix_cycle" => @$old_entire_model->custom_fix_cycle ?: false,
                            ]);
                        $this->info("新增类型：{$facility_model->name} {$facility_model->unique_code}");
                    });
            });
        $this->info("P2W：完成");
    }

    private function saveOld(): void
    {
        $this->comment("删除无法对应上型号的器材");
        $conn_code = $this->argument("old_conn_code") ?? strtolower(env("ORGANIZATION_CODE"));

        $model_unique_codes = DB::connection($conn_code)
            ->table("entire_instances")
            ->select("entire_model_unique_code")
            ->where("category_unique_code", "like", "S%")
            ->groupBy(["entire_model_unique_code"])
            ->get()
            ->pluck("entire_model_unique_code")
            ->unique()
            ->values();
        $exists_model_unique_codes = DB::connection($conn_code)
            ->table("entire_models as em")
            ->select("unique_code")
            ->whereIn("unique_code", $model_unique_codes)
            ->where("is_sub_model", false)
            ->where("category_unique_code", "like", "S%")
            ->get()
            ->pluck("unique_code")
            ->unique()
            ->values();
        $diff = $model_unique_codes->diff($exists_model_unique_codes)->toArray();
        DB::connection($conn_code)
            ->table("entire_instances")
            ->selectRaw("count(entire_model_unique_code) as aggregate, entire_model_unique_code")
            ->whereIn("entire_model_unique_code", $diff)
            ->where("category_unique_code", "like", "S%")
            ->groupBy(["entire_model_unique_code"])
            ->update(["delete_description" => "合并种类型：因没有找到对应型号，删除处理。", "deleted_at" => now(),]);
        $this->info("删除无法对应上型号的设备");

        $this->comment("记录原始种类型名称");
        DB::connection($conn_code)
            ->table("entire_instances as ei")
            ->select("c.name as category_name", "c.unique_code as category_unique_code", "em.name as entire_model_name", "em.unique_code as entire_model_unique_code")
            ->join(DB::raw("entire_models em"), "ei.entire_model_unique_code", "=", "em.unique_code")
            ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
            ->whereNull("em.deleted_at")
            ->whereNull("c.deleted_at")
            ->where("c.unique_code", "like", "S%")
            ->groupBy(["em.unique_code"])
            ->orderBy("em.id")
            ->chunk(500, function ($data) use ($conn_code) {
                $data->each(function ($datum) use ($conn_code) {
                    DB::connection($conn_code)
                        ->table("entire_instances as ei")
                        ->where("ei.entire_model_unique_code", $datum->entire_model_unique_code)
                        ->update([
                            "old_category_unique_code" => $datum->category_unique_code,
                            "old_entire_model_unique_code" => $datum->entire_model_unique_code,
                            "old_category_name" => $datum->category_name,
                            "old_entire_model_name" => $datum->entire_model_name,
                        ]);
                    $this->comment("修改：{$datum->entire_model_unique_code} {$datum->entire_model_name}");
                });
            });
        $this->info("记录原始种类型名称");
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $operator = $this->argument('operator');
        $this->$operator();
    }
}
