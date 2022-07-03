<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Curl\Curl;
use Illuminate\Console\Command;

class SqlBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sqlBackup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取数据库备份文件，自动解压缩，自动恢复数据库，自动进行统计';

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
        $current_datetime = Carbon::now()->format("Y_m_d_H_i_s");
        $db_name = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        $url = env('SQL_BACKUP_URL');
        $now = Carbon::now();
        $year = $now->year;
        $organization_code = env('ORGANIZATION_CODE');
        $organization_name = env('ORGANIZATION_NAME');

        // 检查保存sql路径是否存在
        $root_path = "app/sqlBackup";
        if(!is_dir(storage_path($root_path))) mkdir(storage_path($root_path));

        $sql_filename = "$current_datetime.sql.gz";
        $sql_file_dir = storage_path("$root_path/$sql_filename");

        $this->comment("开始压缩数据库：{$organization_code}$organization_name");
        shell_exec("mysqldump -u$username -p'$password' $db_name | gzip > $sql_file_dir");

        $this->comment("开始发送数据库");

        # 检查SQL文件是否存在
        if (is_file($sql_file_dir)) {
            $curl = new Curl();
            $curl->setConnectTimeout(300);
            $curl->setTimeout(300);
            $curl->post("http://{$url}/api/sqlBackup", [
                'paragraph_code' => env("ORGANIZATION_CODE"),
                'paragraph_name' => env("ORGANIZATION_NAME"),
                'workshop_type' => "检修车间",
                'sql' => "@$sql_file_dir",
            ]);

            if ($curl->error) {
                $this->error("{$curl->errorMessage}：$curl->errorCode");
            } else {
                $this->info($curl->response->msg);
            }
            // 删除备份文件
            // unlink(public_path($sql_filename));
        } else {
            $this->error("意外错误");
        }
        return null;
    }
}
