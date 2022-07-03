<?php

namespace App\Console\Commands;

use App\Facades\FixWorkflowCycleFacade;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixWorkflowCycleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixWorkflow:cycle {year?} {month?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动总结检修单';

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
        $currentYear = $this->argument('year') ? $this->argument('year') : date('Y');
        $currentMonth = $this->argument('month') ? $this->argument('month') : date('m');
        $lastTime = Carbon::createFromDate($currentYear, $currentMonth, 1)->subMonth()->firstOfMonth();
        $lastYear = $lastTime->year;
        $lastMonth = $lastTime->month;
        $isTest = env('FIX_WORKFLOW_CYCLE_IS_TEST',false);

        try {
            FixWorkflowCycleFacade::getBasicInfo($lastYear, $lastMonth);  # 获取基础信息：获取设备种类、种类对应的类型
            FixWorkflowCycleFacade::getCurrentMonthFixedCountWithCategory($lastYear, $lastMonth, $isTest);  # 获取本月已经完成（种类）
            FixWorkflowCycleFacade::getCurrentMonthAllFixCountWithCategory($lastYear, $lastMonth, $isTest);  # 获取本月全部检修单（种类）
            FixWorkflowCycleFacade::getCurrentMonthFixedRateCountWithCategory($lastYear, $lastMonth);  # 获取本月检修单完成率（种类）
            FixWorkflowCycleFacade::getCurrentMonthFixedCountWithSub($lastYear, $lastMonth, $isTest);  # 获取本月已经完成（型号、子类）
            FixWorkflowCycleFacade::getCurrentMonthAllFixCountWithSub($lastYear, $lastMonth, $isTest);  # 获取本月全部检修单（型号、子类）
            FixWorkflowCycleFacade::getCurrentMonthFixedRateCountWithSub($lastYear, $lastMonth);  # 获取本月检修单完成率（型号、子类）
            FixWorkflowCycleFacade::getCurrentMonthAllFixCount($lastYear, $lastMonth, $isTest);  # 获取本月已经完成
            FixWorkflowCycleFacade::getCurrentMonthFixedCount($lastYear, $lastMonth, $isTest);  # 获取本月全部检修单
            FixWorkflowCycleFacade::getCurrentMonthFixedRateCount($lastYear, $lastMonth);  # 获取本月检修单完成率

            $this->line("检修完成率统计：{$lastYear}-{$lastMonth}");
        } catch (\Exception $exception) {
            $this->line("错误：" . $exception->getMessage() . "\r\n" . $exception->getFile() . "\r\n" . $exception->getLine());
        }
    }
}
