<?php

namespace App\Console\Commands;

use App\Facades\MockDataFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MockDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mockData {mode} {tag?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成模拟数据';

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
        switch ($this->argument('mode')) {
            default:
                $this->error("模式错误：" . $this->argument('mode'));
                break;
            case 'fixWorkflow':
                switch ($this->argument('tag')) {
                    default:
                        # 模拟检修单（默认）
                        $ret = MockDataFacade::runMockFixWorkflow();  # 模拟检修单
                        $this->info($ret);
                        break;
                    case 'accounts':
                        # 生成人员
                        $fixers = Account::with([])->where('work_area', 2)->get()->pluck('nickname')->toJson(256);
                        $checkers = Account::with([])->where('work_area', 2)->where('supervision', 1)->get()->pluck('nickname')->toJson(256);
                        file_put_contents(storage_path("mockData/fixWorkflow/fixers.json"), $fixers);
                        file_put_contents(storage_path("mockData/fixWorkflow/checkers.json"), $checkers);
                        $this->info("生成人员完成");
                        break;
                }
                break;
            case 'madeAtForLogs':
                # 补充生产日期日志
                DB::transaction(function () {
                    $originTime = time();
                    $this->info('补充出厂日志：开始');
                    $ret = MockDataFacade::runReplenishMadeAtForLogs();
                    $finishTime = time();
                    $time = $finishTime - $originTime;
                    $this->info($ret);
                    $this->info("补充出厂日志：完成。用时：{$time}秒");
                });
                break;
            case 'tmpPartInstances':
                list($message, $partInstances) = MockDataFacade::tmpReplenishPartInstances();
                dump($partInstances);
                $this->info($message);
                break;
            case 'replenish':
                EntireInstance::with([])->whereIn('serial_number', [
                    '10197000151',
                    '10197000158',
                ])
                    ->update([
                        'created_at' => '2013-06-01',
                        'last_out_at' => '2013-08-01',
                        'installed_at' => '2018-08-21',
                    ]);
                $this->info('ok');
                break;
            case 'entireLog':
                # 补充完整日志（包括出入所单、入库单，不包括检修单、检测值）
                DB::table('entire_instances as ei')
                    ->where('ei.deleted_at', null)
                    ->where('ei.maintain_station_name', '湘潭')  # 湘潭车站
                    ->where('ei.status', 'INSTALLED')  # 上道设备
                    ->where('ei.category_unique_code', 'Q01')  # 继电器
                    ->limit(1)
                    ->orderBy('ei.id')
                    ->get()
                    ->each(function ($item) {
                        # 出厂日志（计算出厂时间、报废时间）
                        # 入所单（经办人：圆满11，上道日期-2月）
                        # 
                        dump($item);
                    });

                dd('ok');
                break;
        }
        return 0;
    }
}
