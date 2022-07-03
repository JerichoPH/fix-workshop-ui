<?php

namespace App\Console\Commands;

use App\Facades\TextFacade;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireModel;
use App\Model\PartCategory;
use App\Model\PartInstance;
use App\Model\PartModel;
use Illuminate\Console\Command;

class UpgradeCategoryTo36Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade-category-to-36 {db_conn}';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $db_conn = $this->argument('db_conn');

        $this->comment('刷新器材种 => 36进制');
        Category::on($db_conn)
            ->where('unique_code', 'like', 'Q%')
            ->get()
            ->each(function ($category) use ($db_conn) {
                $old_category_unique_code = $category->unique_code;
                $first_unique_code = substr($old_category_unique_code, 0, 1);
                $last_unique_code = intval(substr($old_category_unique_code, -2));
                if ($last_unique_code < 10) return null;  // 如果序号不超过10则调过

                $last_unique_code = str_pad(TextFacade::to36($last_unique_code), 2, '0', 0);
                $new_category_unique_code = "{$first_unique_code}{$last_unique_code}";
                $category->fill(['unique_code' => $new_category_unique_code,])->saveOrFail();

                EntireInstance::with([])->where('category_unique_code', $old_category_unique_code)->update(['category_unique_code' => $new_category_unique_code,]);
                $this->info("更新种类（器材）：{$old_category_unique_code} => {$new_category_unique_code}");

                EntireModel::on($db_conn)
                    ->where('category_unique_code', $old_category_unique_code)
                    ->where('is_sub_model', false)
                    ->get()
                    ->each(function ($entire_model) use ($db_conn, $new_category_unique_code) {
                        $old_entire_model_unique_code = $entire_model->unique_code;
                        $last_unique_code = substr($old_entire_model_unique_code, -2);
                        $new_entire_model_unique_code = "{$new_category_unique_code}{$last_unique_code}";

                        $entire_model->fill(['category_unique_code' => $new_category_unique_code, 'unique_code' => $new_entire_model_unique_code,])->saveOrFail();

                        EntireInstance::with([])->where('entire_model_unique_code', $old_entire_model_unique_code)->update(['entire_model_unique_code' => $new_entire_model_unique_code,]);
                        $this->info("更新类型（器材）：{$old_entire_model_unique_code} => {$new_entire_model_unique_code}");

                        EntireModel::on($db_conn)
                            ->where('parent_unique_code', $old_entire_model_unique_code)
                            ->where('is_sub_model', true)
                            ->get()
                            ->each(function ($sub_model) use ($db_conn, $new_category_unique_code, $new_entire_model_unique_code) {
                                $old_sub_model_unique_code = $sub_model->unique_code;
                                $last_unique_code = substr($old_sub_model_unique_code, -2);
                                $new_sub_model_unique_code = "{$new_entire_model_unique_code}{$last_unique_code}";

                                $sub_model->fill(['category_unique_code' => $new_category_unique_code, 'parent_unique_code' => $new_entire_model_unique_code, 'unique_code' => $new_sub_model_unique_code,])->saveOrFail();

                                EntireInstance::with([])->where('model_unique_code', $old_sub_model_unique_code)->update(['model_unique_code' => $new_sub_model_unique_code,]);
                                $this->info("更新型号（器材）：{$old_sub_model_unique_code} => {$new_sub_model_unique_code}");
                            });
                    });
            });
        $this->comment('刷新器材种 => 36进制：结束');

        $this->comment('刷新设备种 => 36进制');
        Category::on($db_conn)
            ->where('unique_code', 'like', 'S%')
            ->get()
            ->each(function ($category) use ($db_conn) {
                $old_category_unique_code = $category->unique_code;
                $first_unique_code = substr($old_category_unique_code, 0, 1);
                $last_unique_code = intval(substr($old_category_unique_code, -2));
                if ($last_unique_code < 10) return null;  // 如果序号不超过10则调过

                $last_unique_code = str_pad(TextFacade::to36($last_unique_code), 2, '0', 0);
                $new_category_unique_code = "{$first_unique_code}{$last_unique_code}";
                $category->fill(['unique_code' => $new_category_unique_code,])->saveOrFail();

                EntireInstance::with([])->where('category_unique_code', $old_category_unique_code)->update(['category_unique_code' => $new_category_unique_code,]);
                $this->info("更新种类（设备）：{$old_category_unique_code} => {$new_category_unique_code}");

                PartCategory::with([])->where('category_unique_code', $old_category_unique_code)->update(['category_unique_code' => $new_category_unique_code,]);
                $this->info("更新部件种类（设备）：{$old_category_unique_code} => {$new_category_unique_code}");

                PartModel::with([])->where('category_unique_code', $old_category_unique_code)->update(['category_unique_code' => $new_category_unique_code,]);
                $this->info("更新部件型号（设备）：{$old_category_unique_code} => {$new_category_unique_code}");

                PartInstance::with([])->where('category_unique_code', $old_category_unique_code)->update(['category_unique_code' => $new_category_unique_code,]);
                $this->info("更新部件（设备）：{$old_category_unique_code} => {$new_category_unique_code}");

                EntireModel::on($db_conn)
                    ->where('category_unique_code', $old_category_unique_code)
                    ->where('is_sub_model', false)
                    ->get()
                    ->each(function ($entire_model) use ($db_conn, $new_category_unique_code) {
                        $old_entire_model_unique_code = $entire_model->unique_code;
                        $last_unique_code = substr($old_entire_model_unique_code, -2);
                        $new_entire_model_unique_code = "{$new_category_unique_code}{$last_unique_code}";

                        $entire_model->fill(['category_unique_code' => $new_category_unique_code, 'unique_code' => $new_entire_model_unique_code,])->saveOrFail();

                        EntireInstance::with([])->where('entire_model_unique_code', $old_entire_model_unique_code)->update(['entire_model_unique_code' => $new_entire_model_unique_code, 'model_unique_code' => $new_entire_model_unique_code,]);
                        $this->info("更新类型（设备）：{$old_entire_model_unique_code} => {$new_entire_model_unique_code}");

                        PartCategory::with([])->where('entire_model_unique_code', $old_entire_model_unique_code)->update(['entire_model_unique_code' => $new_entire_model_unique_code,]);
                        $this->info("更新部件种类（设备）：{$old_entire_model_unique_code} => {$new_entire_model_unique_code}");

                        PartModel::with([])->where('entire_model_unique_code', $old_entire_model_unique_code)->update(['entire_model_unique_code' => $new_entire_model_unique_code,]);
                        $this->info("更新部件型号（设备）：{$old_entire_model_unique_code} => {$new_entire_model_unique_code}");

                        PartInstance::with([])->where('entire_model_unique_code', $old_entire_model_unique_code)->update(['entire_model_unique_code' => $new_entire_model_unique_code,]);
                        $this->info("更新部件（设备）：{$old_entire_model_unique_code} => {$new_entire_model_unique_code}");
                    });
            });
        $this->comment('刷新设备种 => 36进制：结束');
    }
}
