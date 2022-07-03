<?php

namespace App\Console\Commands;

use App\Facades\CycleFixFacade;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CycleFixCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cycleFix:refreshPlan';

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
        $year = Carbon::now()->year;
        CycleFixFacade::refreshPlanRow($year);
        CycleFixFacade::refreshPlanColumn($year);
    }
}
