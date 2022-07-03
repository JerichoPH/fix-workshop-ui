<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KindsNamesSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kinds-names:s';

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
        $old_conn_names = [
            'b048' => '广州',
            'b049' => '长沙',
            'b050' => '怀化',
            'b051' => '衡阳',
            'b052' => '惠州',
            'b053' => '肇庆',
            'b074' => '海口',
        ];

        collect($old_conn_names)->each(function ($paragraph_name, $old_conn_name) {
            $this->comment("开始整理：$old_conn_name");
            $new_conn_name = 'fix_kind';

            DB::connection($old_conn_name)
                ->table('entire_instances as ei')
                ->selectRaw(join(',', [
                    'c.name as category_name',
                    'c.unique_code as category_unique_code',
                    'em.name as entire_model_name',
                    'em.unique_code as entire_model_unique_code',
                    'count(em.name) as aggregate',
                    "'$paragraph_name' as paragraph_name"
                ]))
                ->join(DB::raw('entire_models em'), 'ei.entire_model_unique_code', '=', 'em.unique_code')
                ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                ->orderBy('c.unique_code')
                ->orderBy('em.unique_code')
                ->groupBy(['em.unique_code',])
                ->where('em.is_sub_model', false)
                ->whereNull('em.deleted_at')
                ->where('c.unique_code', 'like', 'S%')
                ->whereNull('c.deleted_at')
                ->chunk(500, function ($entire_instances) use ($new_conn_name) {
                    $entire_instances->each(function ($datum) use ($new_conn_name) {
                        [
                            'category_name' => $category_name,
                            'category_unique_code' => $category_unique_code,
                            'entire_model_name' => $entire_model_name,
                            'entire_model_unique_code' => $entire_model_unique_code,
                            'aggregate' => $aggregate,
                            'paragraph_name' => $paragraph_name,
                        ] = (array)$datum;

                        // 写入新数据库
                        if (!DB::connection($new_conn_name)
                            ->table('s')
                            ->where('category_name', $category_name)
                            ->where('category_unique_code', $category_unique_code)
                            ->where('entire_model_name', $entire_model_name)
                            ->where('paragraph_name', $paragraph_name)
                            ->exists()) {
                            DB::connection($new_conn_name)
                                ->table('s')
                                ->insert([
                                    'category_name' => $category_name,
                                    'category_unique_code' => $category_unique_code,
                                    'entire_model_name' => $entire_model_name,
                                    'entire_model_unique_code' => $entire_model_unique_code,
                                    'aggregate' => $aggregate,
                                    'paragraph_name' => $paragraph_name,
                                ]);
                            $this->info("添加种类：$category_name($category_unique_code) > 添加类型：$entire_model_name > 数量：$aggregate");
                        }
                    });
                });
        });

        $this->comment('执行完毕');
    }
}
