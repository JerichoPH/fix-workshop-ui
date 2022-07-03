<?php

namespace App\Console\Commands;

use App\Facades\FixWorkflowExcelFacade;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixWorkflowExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixWorkflow:excel {year?} {month?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将检修单统计结果生成Excel';

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
        $lastYear = $lastTime->format('Y');
        $lastMonth = $lastTime->format('m');
        $isTest = true;

        $fixWorkflowExcelFacade = FixWorkflowExcelFacade::init($lastYear, $lastMonth);
        $fixWorkflowExcelFacade->onlyOnceFixedToExcel();
        $fixWorkflowExcelFacade->fixWorkflowCycleToExcel();

        $this->line("检修单统计合并Excel：{$lastYear}-{$lastMonth}");
    }
}
