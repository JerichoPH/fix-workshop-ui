<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FactoryClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'factory-clear-up {operator}';

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

    final public function p0482()
    {
        DB::table("factories")->where("unique_code","P0482")->update(["name"=>"西安华特铁路器材有限责任公司",]);
        $this->info("OK");
    }

    /**
     * Execute the console command.
     */
    final public function handle():void
    {
        $this->{$this->argument("operator")}();
    }
}
