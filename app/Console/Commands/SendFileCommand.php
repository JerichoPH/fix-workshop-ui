<?php

namespace App\Console\Commands;

use Hprose\Http\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Jericho\CurlHelper;
use Jericho\FileSystem;

class SendFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendFile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发送缓存数据给电务部';

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
     * @throws \Exception
     */
    public function handle()
    {
        dump('发送统计数据开始');
        $url = env('SQL_BACKUP_URL');
        $organization_code = env('ORGANIZATION_CODE');
        $client = new Client("http://{$url}/rpc/receiveFile", false);
        $client->setTimeout(300000);

        $dir_path = storage_path('app');
        if (is_dir($dir_path)) {
            $file_name = "{$organization_code}-app.zip";
            shell_exec("cd {$dir_path} && zip -r -q {$file_name} *");
            $file_path = "{$dir_path}/{$file_name}";
            if (is_file($file_path)) {
                $res = $client->statisticFile(
                    file_get_contents($file_path),
                    $file_name,
                    $organization_code
                );
                if ($res['status'] == 200) {
                    FileSystem::init($file_path)->deleteFile();
                    dump($res['message']);
                } else {
                    dump($res['message']);
                }
            } else {
                dump("{$file_path}，文件不存在");
            }
        } else {
            dump("{$dir_path}，文件夹不存在");
        }

        return true;
    }
}
