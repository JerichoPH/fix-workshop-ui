<?php

namespace App\Console\Commands;

use App\Model\Category;
use App\Model\EntireModel;
use Illuminate\Console\Command;
use Throwable;

class KindsFixCycleSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kinds-fix-cycle:s {old_conn_name} {new_conn_name}';

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
     * @throws Throwable
     */
    public function handle()
    {
        $old_conn_name = $this->argument('old_conn_name');
        $new_conn_name = $this->argument('new_conn_name');

        $this->comment('同步类型周期修时间');
        EntireModel::on($old_conn_name)
            ->where('unique_code', 'like', 'S%')
            ->where('is_sub_model', false)
            ->with(['Category',])
            ->get()
            ->each(function ($old_entire_model) use ($new_conn_name) {
                $old_entire_model_name = @$old_entire_model->name ?: '';
                $old_category_name = @$old_entire_model->Category->name ?: '';
                $old_fix_cycle_value = @$old_entire_model->fix_cycle_value ?: 0;
                if (!($old_entire_model_name && $old_category_name)) return null;

                $new_category = Category::on($new_conn_name)->where('name', $old_category_name)->first();
                if (!$new_category) {
                    $this->error("种类不存在：{$old_category_name}");
                }

                $new_entire_model = EntireModel::on($new_conn_name)
                    ->where('is_sub_model', false)
                    ->where('category_unique_code', $new_category->unique_code)
                    ->where('name', $old_entire_model_name)
                    ->first();
                if (!$new_entire_model) {
                    $this->error("类型不存在：{$old_entire_model_name}");
                }

                $new_entire_model->fill(['fix_cycle_value' => $old_fix_cycle_value,])->saveOrFail();
                $this->info("类型修改成功：{$old_entire_model_name} {$old_fix_cycle_value}年");
            });
        $this->comment('同步类型周期修时间：完成');
    }
}
