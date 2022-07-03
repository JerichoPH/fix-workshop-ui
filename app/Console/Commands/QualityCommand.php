<?php

namespace App\Console\Commands;

use App\Facades\QualityFacade;
use Carbon\Carbon;
use Illuminate\Console\Command;

class QualityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quality {year?} {month?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '质量报告统计';

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
        $isTest = false;

        $qualityFacade = QualityFacade::init($lastYear, $lastMonth);
        $qualityFacade->getBasicInfo($lastYear, $lastMonth);
        $qualityFacade->getFactories();
        $qualityFacade->getDeviceCountWithFactory();
        $qualityFacade->getNotCycleFixWithFactory();
//        $qualityFacade->getDeviceCountWithCategory($isTest);
//        $ret = $qualityFacade->getFixedCountWithoutCycleWithCategory($isTest);
//        dd($ret);
//        $qualityFacade->getRateWithoutCycleWithCategory($isTest);

        $this->line("返修率：{$lastYear}-{$lastMonth}");
    }
}
