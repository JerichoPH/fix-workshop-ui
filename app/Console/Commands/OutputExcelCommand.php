<?php

namespace App\Console\Commands;

use App\Facades\OutputExcelFacade;
use Illuminate\Console\Command;

class OutputExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'output:excel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出Excel（临时）';

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
        OutputExcelFacade::do_q01();
    }
}
