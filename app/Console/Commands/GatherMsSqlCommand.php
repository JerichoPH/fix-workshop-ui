<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GatherMsSqlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:GatherSqlsrvCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集Sqlsrv中人昊的数据';

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
        $this->line('ok');
//        $a = DB::connection('sqlsrv')->select('SELECT TOP 1 TYPE_ID FROM tTYPE_INDEX ORDER BY TYPE_ID DESC');
//        $this->line($a);
    }
}
