<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PHPExcel_Exception;
use PHPExcel_Reader_Exception;

class KindCheckQCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kinds-check:q {conn_code} {prefix?}';

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
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function handle()
    {
        $conn_code = $this->argument('conn_code');
        $prefix = $this->argument('prefix') ?? "";

        $this->comment('检查器材种类型合并');

        $new = DB::connection($conn_code)
            ->table("entire_instances as ei")
            ->selectRaw("count(sm.name) as aggregate, concat(c.name, ' >> ', em.name, ' >> ', sm.name) as name")
            ->join(DB::raw("{$prefix}entire_models sm"), "ei.new_sub_model_unique_code", "=", "sm.unique_code")
            ->join(DB::raw("{$prefix}entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
            ->join(DB::raw("{$prefix}categories c"), "em.category_unique_code", "=", "c.unique_code")
            ->whereNull("ei.deleted_at")
            ->where("ei.new_category_unique_code", "like", "Q%")
            ->groupBy(["c.unique_code", "em.unique_code", "sm.unique_code"])
            ->get()
            ->pluck("aggregate", "name")
            ->toArray();

        $old = DB::connection($conn_code)
            ->table("entire_instances")
            ->selectRaw("count(old_sub_model_name) aggregate, concat(old_category_name, ' >> ', old_entire_model_name, ' >> ', old_sub_model_name) as name")
            ->whereNull("deleted_at")
            ->where("old_category_unique_code", "like", "Q%")
            ->groupBy(["old_category_name", "old_entire_model_name", "old_sub_model_name"])
            ->get()
            ->pluck("aggregate", "name")
            ->each(function ($aggregate, $name) use ($new) {
                if (!array_key_exists($name, $new)) {
                    $this->error("{$name} 不存在");
                } else {
                    if ($new[$name] != $aggregate) {
                        $this->error("{$name} 数量不正确");
                    } else {
                        $this->info("{$name} 数量正确");
                    }
                }
            });

        // DB::connection($conn_code)
        //     ->table("entire_instances as ei")
        //     ->selectRaw("count(sm.name) as aggregate, concat(c.name, ' >> ', em.name, ' >> ', sm.name) as name")
        //     ->join(DB::raw("new_entire_models sm"), "ei.new_sub_model_unique_code", "=", "sm.unique_code")
        //     ->join(DB::raw("new_entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
        //     ->join(DB::raw("new_categories c"), "em.category_unique_code", "=", "c.unique_code")
        //     ->whereNull("ei.deleted_at")
        //     ->where("ei.new_category_unique_code", "like", "Q%")
        //     ->groupBy(["c.unique_code", "em.unique_code", "sm.unique_code"])
        //     ->get()
        //     ->pluck("aggregate", "name")
        //     ->each(function ($aggregate, $name) use ($old) {
        //         if (!array_key_exists($name, $old)) {
        //             $this->error("{$name} 不存在");
        //         } else {
        //             if ($old[$name] != $aggregate) {
        //                 $this->error("{$name} 数量不正确");
        //             } else {
        //                 $this->info("{$name} 数量正确");
        //             }
        //         }
        //     });

        $this->comment('检查器材种类型合并：结束');
    }
}
