<?php

namespace App\Console\Commands;

use App\Facades\EveryMonthExcelFacade;
use App\Facades\FixWorkflowCycleFacade;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EveryMonthExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'everyMonth:excel {year?} {month?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每月自动统计';

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
        $isTest = true;

        # 当月入所
        $everyMonthExcelFacade = EveryMonthExcelFacade::init($lastYear, $lastMonth);
        $everyMonthExcelFacade->getBasicInfo($lastYear, $lastMonth);
        $everyMonthExcelFacade->getEntireInstanceIdentityCodesWithCategory();
        $everyMonthExcelFacade->getEntireInstanceIdentityCodesWithEntireModel();
        $everyMonthExcelFacade->getEntireInstanceIdentityCodesWithSub();

        # 次月待检修统计
        $goingToFixEntireInstances = FixWorkflowCycleFacade::getEntireInstanceIdentityCodesForGoingToAutoMakeFixWorkflow($lastYear, $lastMonth);
        FixWorkflowCycleFacade::autoMakeFixWorkflow($goingToFixEntireInstances);

        $this->line("每月数据生成Excel{$lastYear}-{$lastMonth}");
    }
}
