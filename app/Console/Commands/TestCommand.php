<?php

namespace App\Console\Commands;

use Curl\Curl;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $__curl = null;
    protected $_ueUrlRoot = "";
    protected $_ueApiVersion = "";
    protected $_ueApiUrl = "";

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
        $this->_ueUrlRoot = env("UE_URL_ROOT");
        $this->_ueApiVersion = env("UE_API_VERSION");
        $this->_ueApiUrl = "{$this->_ueUrlRoot}/{$this->_ueApiVersion}";
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->__curl->post("{$this->_ueApiUrl}/authorization/login", ["username" => "admin", "password" => "123123"]);
        dump($this->__curl->getHttpStatusCode());
        $this->__curl->close();
    }
}
