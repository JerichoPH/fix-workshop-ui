<?php

namespace App\Console\Commands;

use App\Facades\MaintainBatchInputFacade;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MaintainBatchInputCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintainBatchInput';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '批量导入站场';

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
        $maintains = MaintainBatchInputFacade::FROM_STORAGE('maintainInput', '站场.xlsx')->withSheetIndex(1);
        $ret = DB::table('maintains')->insert($maintains['success']);
        $this->line("导入现场车间，站场：{$ret}");
    }
}
