<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class PushQCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:Q';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '器材数据推送到段中心';

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

        } catch (Throwable $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
}
