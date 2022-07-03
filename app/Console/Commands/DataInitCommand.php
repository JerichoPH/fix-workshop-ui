<?php

namespace App\Console\Commands;

use App\Facades\TextFacade;
use App\Model\Account;
use Illuminate\Console\Command;

class DataInitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成AccessKey和SecretKey';

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
     * @throws \Throwable
     */
    public function handle()
    {
        foreach (Account::with([])->get() as $account) {
            $accessKey = strtoupper(md5(env('ORGANIZATION_CODE') . $account->id . date('YmdHis') . TextFacade::rand('Admix', 32)));
            $secretKey = strtoupper(md5($accessKey));
            $account->fill([
                'access_key' => $accessKey,
                'secret_key' => $secretKey,
            ])
                ->saveOrFail();
        }
    }
}
