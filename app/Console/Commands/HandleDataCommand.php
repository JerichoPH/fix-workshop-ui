<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HandleDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handleData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '处理数据';

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
     * @return bool
     * @throws \Exception
     */
    public function handle()
    {
        $bills = DB::table('repair_base_plan_out_cycle_fix_bills')->get();
        foreach ($bills as $bill) {
            $station = DB::table('maintains')->where('type', 'STATION')->where('name', $bill->station_name)->first();
            if (!empty($station)) {
                $stationUniqueCode = $station->unique_code;
                $work_area_id = $bill->work_area_id;
                $year = $bill->year;
                $month = str_pad($bill->month, 2, '0', STR_PAD_LEFT);
                $serial_number = "{$work_area_id}-{$stationUniqueCode}-{$year}-{$month}";
                DB::table('repair_base_plan_out_cycle_fix_bills')->where('id', $bill->id)->update([
                    'serial_number' => $serial_number,
                    'station_unique_code' => $stationUniqueCode
                ]);
                DB::table('repair_base_plan_out_cycle_fix_entire_instances')->where('bill_id', $bill->id)->update([
                    'station_unique_code' => $stationUniqueCode
                ]);
            }
        }

        return 'ok';
    }
}
