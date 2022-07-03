<?php

namespace App\Console;

use App\Console\Commands\AutoCollect;
use App\Console\Commands\CycleFixCommand;
use App\Console\Commands\DeviceInputCommand;
use App\Console\Commands\EveryMonthExcelCommand;
use App\Console\Commands\FixWorkflowCycleCommand;
use App\Console\Commands\FixWorkflowInputCommand;
use App\Console\Commands\FixWorkflowOnlyOnceCommand;
use App\Console\Commands\GatherMsSqlCommand;
use App\Console\Commands\MaintainBatchInputCommand;
use App\Console\Commands\OutputExcelCommand;
use App\Console\Commands\QualityCommand;
use App\Console\Commands\SqlBackupCommand;
use App\Console\Commands\StatisticsIndexCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        FixWorkflowCycleCommand::class,  # 每月检修单结算
        FixWorkflowOnlyOnceCommand::class,  # 每月一次过检结算
        AutoCollect::class,  # 自动采集数据
        GatherMsSqlCommand::class,  # 获取MsSql中人昊最大参数
        EveryMonthExcelCommand::class,  # 每月统计
        QualityCommand::class,  # 质量报告统计
        DeviceInputCommand::class,  # 设备器材（Excel）导入
        MaintainBatchInputCommand::class,  # 台账批量录入
        FixWorkflowInputCommand::class,  # 历史检修记录导入
        OutputExcelCommand::class,  # 导出Excel（临时）
        SqlBackupCommand::class,  # MySQL备份
        CycleFixCommand::class,  # 周期修
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//        $schedule->command('inspire')
//            ->hourly();
//        $schedule->command('fixWorkflowCycle')->monthlyOn(1);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
