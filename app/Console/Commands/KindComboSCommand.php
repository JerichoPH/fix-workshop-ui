<?php

namespace App\Console\Commands;

use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireModel;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KindComboSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kinds-combo:s {old_conn_name} {new_conn_name} {sync_entire_instance_model_unique_code=false}';

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
     * @throws Exception
     */
    public function handle()
    {
        $old_conn_name = $this->argument('old_conn_name');
        $new_conn_name = $this->argument('new_conn_name');
        $sync_entire_instance_model_unique_code = $this->argument('sync_entire_instance_model_unique_code') === 'true';

        $this->comment('合并设备种类型');
        DB::connection($old_conn_name)
            ->table('entire_instances as ei')
            ->selectRaw(join(',', [
                'c.name as category_name',
                'c.unique_code as category_unique_code',
                'em.name as entire_model_name',
                'em.unique_code as entire_model_unique_code',
            ]))
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
            ->orderBy('c.unique_code')
            ->orderBy('em.unique_code')
            ->groupBy(['em.unique_code',])
            ->where('em.is_sub_model', false)
            ->whereNull('em.deleted_at')
            ->where('c.unique_code', 'like', 'S%')
            ->whereNull('c.deleted_at')
            ->chunk(50, function ($entire_instances) use ($new_conn_name, $old_conn_name, $sync_entire_instance_model_unique_code) {
                $entire_instances->each(function ($datum) use ($new_conn_name, $old_conn_name, $sync_entire_instance_model_unique_code) {
                    [
                        'category_name' => $old_category_name,
                        'category_unique_code' => $old_category_unique_code,
                        'entire_model_name' => $old_entire_model_name,
                        'entire_model_unique_code' => $old_entire_model_unique_code,
                    ] = (array)$datum;

                    // 寻找种类
                    $new_category = Category::on($new_conn_name)->where('name', $old_category_name)->first();
                    if (!$new_category) {
                        $new_category = Category::on($new_conn_name)
                            ->with([])
                            ->create([
                                'unique_code' => Category::generateUniqueCode('S', $new_conn_name),
                                'name' => $old_category_name,
                            ]);
                        $this->info("种类（设备）：{$old_category_name}不存在，创建新种类");
                    }
                    if ($sync_entire_instance_model_unique_code) {
                        DB::connection($old_conn_name)->table('entire_instances')->where('category_unique_code', $old_category_unique_code)->update(['category_unique_code' => "_$new_category->unique_code",]);
                        $this->info("修改种类对应关系（设备）：{$old_category_name} {$old_category_unique_code} => {$new_category->name} {$new_category->unique_code}");
                    }

                    // 寻找类型
                    $new_entire_model = EntireModel::on($new_conn_name)
                        ->where('name', $old_entire_model_name)
                        ->where('category_unique_code', $new_category->unique_code)
                        ->where('is_sub_model', false)
                        ->first();
                    if (!$new_entire_model) {
                        $new_entire_model = EntireModel::on($new_conn_name)->create([
                            'unique_code' => EntireModel::generateEntireModelUniqueCode($new_category->unique_code, $new_conn_name),
                            'name' => $old_entire_model_name,
                            'category_unique_code' => $new_category->unique_code,
                            'is_sub_model' => false,
                        ]);

                        $this->info("类型（设备）：{$old_category_name} {$old_entire_model_name}不存在，创建新类型");
                    }
                    if ($sync_entire_instance_model_unique_code) {
                        DB::connection($old_conn_name)
                            ->table('entire_instances')
                            ->where('entire_model_unique_code', $old_entire_model_unique_code)
                            ->update([
                                'entire_model_unique_code' => "_$new_entire_model->unique_code",
                                'model_unique_code' => '',
                            ]);
                        $this->info("修改类型对应关系（设备）：{$old_category_name} {$old_category_unique_code} => {$new_category->name} {$new_category->unique_code}");
                    }
                });
            });

        // 清除器材种类型代码前的_
        DB::connection($old_conn_name)->select("update entire_instances set model_unique_code = replace(model_unique_code, '_', ''), entire_model_unique_code = replace(entire_model_unique_code,'_',''), category_unique_code = replace(category_unique_code,'_','') where true");
        // 清除种类代码前的_
        DB::connection($old_conn_name)->select("update categories set unique_code = replace(unique_code,'_','') where true");
        // 清除类型代码前的_
        DB::connection($old_conn_name)->select("update entire_models set parent_unique_code = replace(unique_code,'_',''), unique_code = replace(unique_code,'_',''), category_unique_code = replace('category_unique_code','_','') where true");

        $this->comment('合并设备种类型：完成');
    }
}
