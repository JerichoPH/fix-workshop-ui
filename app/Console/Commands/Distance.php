<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Distance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'distance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算全部车间车站距离';

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
        // 计算全部车间车站距离
        echo '正在计算全部车间车站距离';
        DB::table('distance')->truncate();
        $maintains = DB::table('maintains')->where('lon', '<>', '')->where('lat', '<>', '')->get()->toArray();
        foreach ($maintains as $from_maintain) {
            $radLat1 = deg2rad($from_maintain->lat); // 纬度
            $radLng1 = deg2rad($from_maintain->lon); // 经度
            foreach ($maintains as $to_maintain) {
                $radLat2 = deg2rad($to_maintain->lat); // 纬度
                $radLng2 = deg2rad($to_maintain->lon); // 经度
                $a = $radLat1 - $radLat2;
                $b = $radLng1 - $radLng2;
                $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
                DB::table('distance')->insert([
                    'maintains_id' => $from_maintain->id,
                    'maintains_name' => $to_maintain->name,
                    'distance' => $s,
                    'from_unique_code' => $from_maintain->unique_code,
                    'to_unique_code' => $to_maintain->unique_code,
                    'from_type' => $from_maintain->type,
                    'to_type' => $to_maintain->type,
                ]);
            }
        }
        $this->info('执行成功');
        return 0;
    }
}
