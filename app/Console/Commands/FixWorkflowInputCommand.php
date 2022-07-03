<?php

namespace App\Console\Commands;

use App\Facades\FixWorkflowInputFacade;
use Illuminate\Console\Command;

class FixWorkflowInputCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixWorkflow:input';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '历史检修记录导入';

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
        try {
//            FixWorkflowInputFacade::input_66345('66345.json');
//            FixWorkflowInputFacade::input_70240('70240.json');
            FixWorkflowInputFacade::input_k4railroad('K4道口.json');
            FixWorkflowInputFacade::input_baiMaLong('白马垅.json');
        } catch (\Exception $exception) {
            dd($exception->getMessage());
        }
    }
}
