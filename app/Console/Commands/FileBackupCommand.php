<?php

namespace App\Console\Commands;

use App\Facades\StatisticsFacade;
use Carbon\Carbon;
use Hprose\Http\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class FileBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fileBackup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '备份必要缓存文件';

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
        $url = env('SQL_BACKUP_URL');
        $now = Carbon::now();
        $year = $now->year;
        $organization_code = env('ORGANIZATION_CODE');
        $organization_name = env('ORGANIZATION_NAME');
        $client = new Client("http://{$url}/rpc/sqlBackup", false);

        # 将出入所统计文件发给电务部（年）
        $today = $now->format('Y-m-d');
        $statistics_for_warehouse = function () use ($client, $organization_code, $organization_name, $year, $today) {
            $date_list = [];

            for ($i = 6; $i >= 0; $i--) {
                $time = Carbon::today()->subDay($i);
//                if ($time->dayOfWeek == 0 || $time->dayOfWeek == 6) continue;
                $time = $time->format('Y-m-d');
                $date_list[] = $time;  # 当前时间标记
            }

            $origin_time = array_first($date_list) . ' 00:00:00';
            $finish_time = array_last($date_list) . ' 23:59:59';

            $statistics = DB::table('warehouse_report_entire_instances as wrei')
                ->selectRaw("count(c.name) as t,c.name as c,wr.direction as d,DATE_FORMAT(wr.created_at, '%Y-%m-%d') as time")
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'wrei.entire_instance_identity_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                ->join(DB::raw('warehouse_reports wr'), 'wr.serial_number', '=', 'wrei.warehouse_report_serial_number')
                ->whereBetween('wr.updated_at', [$origin_time, $finish_time])
                ->groupBy(DB::raw('c,d,time'))
//                ->toSql();
//            dd($statistics);
                ->get();
            $statistics2 = $statistics->groupBy('time')->all();

            # 空数据
            $statistics_for_warehouse = [];
            foreach ($date_list as $date) {
                $statistics_for_warehouse["转辙机(入所)"][$date] = 0;
                $statistics_for_warehouse["转辙机(出所)"][$date] = 0;
                $statistics_for_warehouse["继电器(入所)"][$date] = 0;
                $statistics_for_warehouse["继电器(出所)"][$date] = 0;
                $statistics_for_warehouse["综合(入所)"][$date] = 0;
                $statistics_for_warehouse["综合(出所)"][$date] = 0;
            }

            foreach ($date_list as $date) {
                if (!array_key_exists($date, $statistics2)) continue;
                foreach ($statistics2[$date] as $val) {
                    if ($val) {
                        if ($val->c == '转辙机' || $val->c == '继电器') {
                            if ($val->d == 'IN') {
                                $statistics_for_warehouse["{$val->c}(入所)"][$val->time] += $val->t;
                            } else {
                                $statistics_for_warehouse["{$val->c}(出所)"][$val->time] += $val->t;
                            }
                        } else {
                            if ($val->d == 'IN') {
                                $statistics_for_warehouse["综合(入所)"][$val->time] += $val->t;
                            } else {
                                $statistics_for_warehouse["综合(出所)"][$val->time] += $val->t;
                            }
                        }
                    }
                }
            }

            $res = $client->warehouseByDay(
                $organization_code,
                $organization_name,
                "出入所列表专用",
                $year,
                "{$today}.json",
                TextHelper::toJson(['date_list' => $date_list, 'statistics_for_warehouse' => $statistics_for_warehouse])
            );
            dump("备份：出入所{$today}" . $res);
        };
        $statistics_for_warehouse();

        # 将周期修计划（月）文件发给maintain_group
        $root_dir = storage_path("app/周期修");
        for ($i = 1; $i <= 12; $i++) {
            $month = str_pad($i, 2, '0', STR_PAD_LEFT);
            $dir_name = "{$year}/{$year}-{$month}";
            $file_content = file_get_contents("{$root_dir}/{$dir_name}/计划.json");
            $res = $client->cycleFixPlan(
                $organization_code,
                $organization_name,
                $dir_name,
                "计划.json",
                $file_content
            );
            dump("备份：{$year}-{$month} 月计划：" . $res);
        }

        # 将周期修计划（列-计划）文件发给maintain_group
        $dir_name = "{$year}";
        $file_content = file_get_contents("{$root_dir}/{$dir_name}/列-计划.json");
        $res = $client->cycleFixPlan(
            $organization_code,
            $organization_name,
            $dir_name,
            "列-计划.json",
            $file_content
        );
        dump("备份：{$year} 列-计划：" . $res);

        # 将周期修计划（行-计划）文件发给maintain_group
        $dir_name = "{$year}";
        $file_content = file_get_contents("{$root_dir}/{$dir_name}/行-计划.json");
        $res = $client->cycleFixPlan(
            $organization_code,
            $organization_name,
            $dir_name,
            "行-计划.json",
            $file_content
        );
        dump("备份：{$year} 行-计划：" . $res);

        # 备份车站
        $res = $client->station(
            $organization_code,
            $organization_name,
            StatisticsFacade::makeStation()
        );
        dump("备份：{$year} 车站：" . $res);

        # 备份类型-种类
        $res = $client->categoryAndEntireModel(
            $organization_code,
            $organization_name,
            StatisticsFacade::makeCategoryAndEntireModel()
        );
        dump("备份：{$year} 类型-种类：" . $res);

        # 备份种类
        $res = $client->category(
            $organization_code,
            $organization_name,
            StatisticsFacade::makeCategories()
        );
        dump("备份：{$year} 种类：" . $res);

        $res = $client->subModel(
            $organization_code,
            $organization_name,
            StatisticsFacade::makeSubModel()
        );
        dump("备份：{$year} 型号：" . $res);
        dump("完成备份");
        return null;
    }
}
