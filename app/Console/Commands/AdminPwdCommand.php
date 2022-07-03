<?php

namespace App\Console\Commands;

use App\Facades\TextFacade;
use App\Model\Account;
use Illuminate\Console\Command;

class AdminPwdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changePwd {account} {new_pwd?}';

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
     * @throws \Throwable
     */
    public function handle()
    {
        $account = Account::with([])->where('account', $this->argument('account'))->firstOrFail();
        $new_pwd = $this->argument('new_pwd') ?: TextFacade::rand('num', 6);
        $account->password = bcrypt($new_pwd);
        $account->saveOrFail();
        $this->info("账号：{$account->account} 昵称：{$account->nickname} 新密码：{$new_pwd}");
    }
}
