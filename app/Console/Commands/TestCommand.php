<?php

namespace App\Console\Commands;

use Curl\Curl;
use Curl\MultiCurl;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $__curl = NULL;
    protected $_ueUrlRoot = "";
    protected $_ueApiVersion = "";
    protected $_ueApiUrl = "";
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test {operation}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    
    /**
     * @throws \ErrorException
     */
    private function _curl()
    {
        $multiCurl = new MultiCurl();
        
        $multiCurl->success(function ($instance) {
            dump($instance->response);
        });
        $multiCurl->error(function ($instance) {
            // ...
        });
        $multiCurl->complete(function ($instance) {
            // ...
        });
        
        $curl1 = new Curl();
        $curl1->setHeader('Content-Type', 'application/json');
        $curl1->setUrl('http://127.0.0.1:8080/api/v1/test');
        $curl1->setOpts([
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => $curl1->buildPostData(['a' => 'A', 'b' => 'B', 'c' => 'C',]),
        ]);
        $multiCurl->addCurl($curl1);
        
        $curl2 = new Curl();
        $curl2->setHeader('Content-Type', 'application/json');
        $curl2->setUrl('http://127.0.0.1:8080/api/v1/test');
        $curl2->setOpts([
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => $curl2->buildPostData(['a' => 1, 'b' => 2, 'c' => 3,]),
        ]);
        $multiCurl->addCurl($curl2);
        
        $multiCurl->start();
        $multiCurl->close();
    }
    
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
     * @return void
     */
    public function handle(): void
    {
        $this->{$this->argument('operation')}();
    }
}
