<?php

namespace App\Console\Commands;

use Curl\Curl;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $__curl = null;
    protected $__ue_url_root = "";
    protected $__ue_api_version = "";
    protected $__ue_api_url = "";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

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
        $this->__curl = new Curl();
        $this->__ue_url_root = env("UE_URL_ROOT");
        $this->__ue_api_version = env("UE_API_VERSION");
        $this->__ue_api_url = "{$this->__ue_url_root}/{$this->__ue_api_version}";
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->__curl->post("{$this->__ue_api_url}/authorization/login",[
            "username"=>"admin",
            "password"=>"123123",
        ]);
        dump($this->__curl->getHttpStatusCode());
        $this->__curl->close();
    }
}
