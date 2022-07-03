<?php

namespace App\Console\Commands;

use App\Facades\DeviceInput_Q01Facade;
use App\Facades\DeviceInput_S03Facade;
use Illuminate\Console\Command;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\TextHelper;

class DeviceInputCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deviceInput:excel {name} {dir?} {type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '设备导入';

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
    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $name = $this->argument('name');
        $dir = $this->argument('dir');
        $type = $this->argument('type');
        if (!$name) $this->error('导入文件不存在');

        switch ($type) {
            case 'Q01':
                //        DeviceInputFacade::b051_1($name);  # 衡阳
//        DeviceInputFacade::b049_38($name, $dir);  # 株洲
                try {
                    DeviceInput_Q01Facade::b049_38_in_workshop();
                } catch (\Exception $exception) {
                    $this->error($exception->getMessage());
                }
                break;
            case 'S03':
                try {
//                    DeviceInput_S03Facade::excelToJson();
                    $ret = DeviceInput_S03Facade::b049_1();
                    dd($ret);
                } catch (\Exception $exception) {
                    $this->error($exception->getMessage() . ' ' . $exception->getFile() . ' ' . $exception->getLine());
                }
                break;
        }
    }
}
