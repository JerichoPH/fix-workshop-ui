<?php

namespace App\Console\Commands;

use App\Libraries\Super\Excel\ExcelReadHelper;
use App\Model\Category;
use App\Model\EntireModel;
use App\Model\PartCategory;
use App\Model\PartModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KindInputSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kindInput:S';

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
        DB::beginTransaction();
        $filename = storage_path('检修车间种类型导入.xls');
        if (!file_exists($filename)) {
            $this->error("{$filename} is doesn't exist");
            return 1;
        }

        $excel = ExcelReadHelper::FROM_STORAGE($filename)
            ->originRow(2)
            ->withSheetIndex(1);

        $current_row = 1;
        foreach ($excel['success'] as $row_data) {
            $current_row++;
            if (empty(array_filter($row_data, function ($val) use ($current_row) {
                return !empty($val);
            }))) {
                $this->error("continue empty row：{$current_row}");
                continue;
            }
            $this->info("[设备] row:{$current_row} beginning");

            list($category_name, $category_unique_code, $entire_model_name, $entire_model_unique_code, $part_model_name, $part_category_name, $fix_cycle_value) = $row_data;
            $S_category = Category::with([])->where('unique_code', $category_unique_code)->first();
            if (!$S_category) Category::with([])->create(['name' => $category_name, 'unique_code' => $category_unique_code]);

            $S_entire_model = EntireModel::with([])->where('is_sub_model', false)->where('unique_code', $entire_model_unique_code)->first();
            if (!$S_entire_model) EntireModel::with([])->create([
                'name' => $entire_model_name,
                'unique_code' => $entire_model_unique_code,
                'category_unique_code' => $category_unique_code,
                'fix_cycle_value' => intval($fix_cycle_value),
                'is_sub_model' => false,
                'parent_unique_code' => null,
            ]);

            $S_part_category = PartCategory::with([])
                ->where('name', $part_category_name)
                ->where('category_unique_code', $category_unique_code)
                ->first();
            if (!$S_part_category) {
                $this->error("{$part_category_name}({$category_name}) is doesn't exist");
                continue;
            }

            $S_part_model = PartModel::with([])->where('name', $part_model_name)->where('entire_model_unique_code',$entire_model_unique_code)->first();
            if ($S_part_model) {
                $this->error("{$part_model_name} is already exist");
                continue;
            }

            $next_part_model_unique_code = PartModel::generateUniqueCode($entire_model_unique_code);

            $S_part_model = PartModel::with([])->create([
                'name' => $part_model_name,
                'unique_code' => $next_part_model_unique_code,
                'category_unique_code' => $category_unique_code,
                'entire_model_unique_code' => $entire_model_unique_code,
                'part_category_id' => $S_part_category->id,
                'fix_cycle_value' => $fix_cycle_value,
            ]);
            $this->info("{$S_part_model->name}({$S_part_model->unique_code}) is correct input");
        }
        DB::commit();
        return 0;
    }
}
