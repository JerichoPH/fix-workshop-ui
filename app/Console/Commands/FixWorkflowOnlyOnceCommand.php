<?php

namespace App\Console\Commands;

use App\Facades\FixWorkflowOnlyOnceFacade;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixWorkflowOnlyOnceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixWorkflow:onlyOnce {year?} {month?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '一次过检结算';

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
        $isTest = env('FIX_WORKFLOW_ONLY_ONCE_IS_TEST', false);

        $fixWorkflowOnlyOnceFacade = FixWorkflowOnlyOnceFacade::init($lastYear, $lastMonth);
        $fixWorkflowOnlyOnceFacade->getBasicInfo($lastYear, $lastMonth);
        $fixWorkflowOnlyOnceFacade->getCurrentMonthAllCountWithAccounts();
        $fixWorkflowOnlyOnceFacade->getCurrentMonthOnlyOnceCountWithAccounts();
        $fixWorkflowOnlyOnceFacade->getCurrentMonthRateWithAccounts();

//        $fixWorkflowOnlyOnceFacade->getCurrentMonthOnlyOnceCountWithCategory($isTest);
//        $fixWorkflowOnlyOnceFacade->getCurrentMonthOnlyOnceCountWithEntireModel($isTest);
//        $fixWorkflowOnlyOnceFacade->getCurrentMonthOnlyOnceCountWithSub($isTest);
//        $fixWorkflowOnlyOnceFacade->getCurrentMonthEntireInstanceCountWithCategory($isTest);
//        $fixWorkflowOnlyOnceFacade->getCurrentMonthEntireInstanceCountWithEntireModel($isTest);
//        $fixWorkflowOnlyOnceFacade->getCurrentMonthEntireInstanceCountWithSub($isTest);
//        $fixWorkflowOnlyOnceFacade->getCurrentMonthRateCountWithCategory();
//        $fixWorkflowOnlyOnceFacade->getCurrentMonthRateCountWithEntireModel();
//        $fixWorkflowOnlyOnceFacade->getCurrentMonthRateCountWithSub();

        $this->line("一次过检统计：{$lastYear}-{$lastMonth}");
    }
}
