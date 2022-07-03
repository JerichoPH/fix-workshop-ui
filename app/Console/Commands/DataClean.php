<?php

namespace App\Console\Commands;

use App\Model\EntireInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelReadHelper;

class DataClean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DataClean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清洗数据';

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
     * @return int
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');

        $path = public_path("惠州电务段检修车间数据整理.xls");

        $originRow = 2;
        $excel = ExcelReadHelper::FROM_STORAGE($path)->originRow($originRow)->withSheetIndex(0);

        $created_at = date('Y-m-d H:i:s');
        // new
        // DB::transaction(function () use ($created_at, $excel) {
        //
        // DB::table('lines')->truncate();
        // DB::table('workshops')->truncate();
        // DB::table('stations')->truncate();
        // DB::table('lines_stations')->truncate();
        // foreach ($excel['success'] as $row) {
        // if ($row[22] == 'SCENE_WORKSHOP') {
        // 线别导入
        // $lineName = $row[0];
        // $lineUniqueCode = $row[21];
        // if (!DB::table('lines')->where('name', $lineName)->exists()) {
        // DB::table('lines')->insert([
        // 'created_at' => $created_at,
        // 'name' => $lineName,
        // 'unique_code' => $lineUniqueCode
        // ]);
        // }
        //
        // 现场车间导入
        // $workshopName = $row[2];
        // $workshopUniqueCode = $row[5];
        // $workshopLon = $row[15];
        // $workshopLat = $row[16];
        // $workshopContact = $row[18];
        // $workshopContactPhone = $row[17];
        // if (!DB::table('workshops')->where('name', $workshopName)->exists()) {
        // DB::table('workshops')->insert([
        // 'created_at' => $created_at,
        // 'unique_code' => $workshopUniqueCode,
        // 'name' => $workshopName,
        // 'lon' => $workshopLon,
        // 'lat' => $workshopLat,
        // 'contact' => $workshopContact,
        // 'contact_phone' => $workshopContactPhone,
        // 'type' => 'SCENE_WORKSHOP',
        // 'is_show' => '1'
        // ]);
        // }
        //
        // 车站导入
        // $stationUniqueCode = $row[10];
        // $stationName = $row[7];
        // $stationLon = $row[11];
        // $stationLat = $row[12];
        // $stationContact = $row[13];
        // $stationContactPhone = $row[14];
        // if (!DB::table('stations')->where('name', $stationName)->exists()) {
        // DB::table('stations')->insert([
        // 'created_at' => $created_at,
        // 'workshop_unique_code' => $workshopUniqueCode,
        // 'unique_code' => $stationUniqueCode,
        // 'name' => $stationName,
        // 'lon' => $stationLon,
        // 'lat' => $stationLat,
        // 'contact' => $stationContact,
        // 'contact_phone' => $stationContactPhone,
        // 'is_show' => '1'
        // ]);
        // DB::table('lines_stations')->insert([
        // 'line_unique_code' => $lineUniqueCode,
        // 'station_code' => $stationUniqueCode
        // ]);
        // }
        // }else {
        // 车间导入
        // $workshopName = $row[2];
        // $workshopUniqueCode = $row[5];
        // $workshopLon = $row[15];
        // $workshopLat = $row[16];
        // $workshopContact = $row[18];
        // $workshopContactPhone = $row[17];
        // if (!DB::table('workshops')->where('name', $workshopName)->exists()) {
        // DB::table('workshops')->insert([
        // 'created_at' => $created_at,
        // 'unique_code' => $workshopUniqueCode,
        // 'name' => $workshopName,
        // 'lon' => $workshopLon,
        // 'lat' => $workshopLat,
        // 'contact' => $workshopContact,
        // 'contact_phone' => $workshopContactPhone,
        // 'type' => 'WORKSHOP',
        // 'is_show' => '1'
        // ]);
        // }
        // }
        //
        // breakdown_logs
        // DB::table('breakdown_logs')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // DB::table('breakdown_logs')->where('scene_workshop_name', $row[1])->update(['maintain_station_name' => $row[2]]);
        // // collect_equipment_order_entire_instances
        // DB::table('collect_device_order_entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // DB::table('collect_device_order_entire_instances')->where('maintain_station_unique_code', $row[8])->update(['maintain_station_unique_code' => $row[10]]);
        // DB::table('collect_device_order_entire_instances')->where('maintain_workshop_name', $row[1])->update(['maintain_workshop_name' => $row[2]]);
        // DB::table('collect_device_order_entire_instances')->where('maintain_workshop_unique_code', $row[3])->update(['maintain_workshop_unique_code' => $row[5]]);
        // // entire_instances
        // DB::table('entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // DB::table('entire_instances')->where('maintain_workshop_name', $row[1])->update(['maintain_workshop_name' => $row[2]]);
        // // fix_workflows
        // DB::table('fix_workflows')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // // repair_base_breakdown_order_entire_instances
        // DB::table('repair_base_breakdown_order_entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // DB::table('repair_base_breakdown_order_entire_instances')->where('scene_workshop_name', $row[1])->update(['scene_workshop_name' => $row[2]]);
        // // station_locations
        // DB::table('station_locations')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // DB::table('station_locations')->where('maintain_station_unique_code', $row[8])->update(['maintain_station_unique_code' => $row[10]]);
        // DB::table('station_locations')->where('scene_workshop_name', $row[1])->update(['scene_workshop_name' => $row[2]]);
        // DB::table('station_locations')->where('scene_workshop_unique_code', $row[3])->update(['scene_workshop_unique_code' => $row[5]]);
        // // temp_station_eis
        // DB::table('temp_station_eis')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // // temp_station_position
        // DB::table('temp_station_position')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // DB::table('temp_station_position')->where('maintain_station_unique_code', $row[8])->update(['maintain_station_unique_code' => $row[10]]);
        // DB::table('temp_station_position')->where('scene_workshop_name', $row[1])->update(['scene_workshop_name' => $row[2]]);
        // DB::table('temp_station_position')->where('scene_workshop_unique_code', $row[3])->update(['scene_workshop_unique_code' => $row[5]]);
        // // warehouse_in_batch_reports
        // DB::table('warehouse_in_batch_reports')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // // warehouse_report_entire_instances
        // DB::table('warehouse_report_entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // // warehouse_storage_batch_reports
        // DB::table('warehouse_storage_batch_reports')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
        // }
        // });

        // old
        DB::transaction(function () use ($created_at, $excel) {

            DB::table('lines')->truncate();
            DB::table('lines_maintains')->truncate();
            DB::table('maintains')->truncate();
            // DB::table('workshops')->truncate();
            // DB::table('stations')->truncate();
            // DB::table('lines_stations')->truncate();
            foreach ($excel['success'] as $row) {
                if (empty(array_filter($row, function ($val) {
                    return !empty($val) && !is_null($val);
                }))) continue;

                if ($row[22] == 'SCENE_WORKSHOP') {
                    // 线别导入
                    $lineName = $row[0];
                    $lineUniqueCode = $row[21];
                    if (!DB::table('lines')->where('name', $lineName)->exists()) {
                        $lineId = DB::table('lines')->insertGetId([
                            'created_at' => $created_at,
                            'name' => $lineName,
                            'unique_code' => $lineUniqueCode
                        ]);
                    }
                    // 现场车间导入
                    $workshopName = $row[2];
                    $workshopUniqueCode = $row[5];
                    $workshopLon = $row[15];
                    $workshopLat = $row[16];
                    $workshopContact = $row[18];
                    $workshopContactPhone = $row[17];
                    if (!DB::table('maintains')->where('name', $workshopName)->exists()) {
                        DB::table('maintains')->insertGetId([
                            'created_at' => $created_at,
                            'unique_code' => $workshopUniqueCode,
                            'name' => $workshopName,
                            'parent_unique_code' => env('ORGANIZATION_CODE'),
                            'type' => 'SCENE_WORKSHOP',
                            'lon' => $workshopLon,
                            'lat' => $workshopLat,
                            'contact' => $workshopContact,
                            'contact_phone' => $workshopContactPhone,
                            'is_show' => '1',
                        ]);
                    }
                    // 车站导入
                    $stationUniqueCode = $row[10];
                    $stationName = $row[7];
                    $stationLon = $row[11];
                    $stationLat = $row[12];
                    $stationContact = $row[13];
                    $stationContactPhone = $row[14];
                    if (!DB::table('maintains')->where('name', $stationName)->exists()) {
                        $stationId = DB::table('maintains')->insertGetId([
                            'created_at' => $created_at,
                            'unique_code' => $stationUniqueCode,
                            'name' => $stationName,
                            'parent_unique_code' => $workshopUniqueCode,
                            'type' => 'STATION',
                            'lon' => $stationLon,
                            'lat' => $stationLat,
                            'contact' => $stationContact,
                            'contact_phone' => $stationContactPhone,
                            'is_show' => '1'
                        ]);
                        // 车站线别关联关系
                        DB::table('lines_maintains')->insert([
                            'lines_id' => $lineId,
                            'maintains_id' => $stationId
                        ]);
                    } else {
                        // 车站线别关联关系
                        DB::table('lines_maintains')->insert([
                            'lines_id' => $lineId,
                            'maintains_id' => DB::table('maintains')->where('name', $stationName)->value('id')
                        ]);
                    }
                } else {
                    // 车间导入
                    $workshopName = $row[2];
                    $workshopUniqueCode  = $row[5];
                    $workshopLon = $row[15];
                    $workshopLat = $row[16];
                    $workshopContact = $row[18];
                    $workshopContactPhone = $row[17];
                    if (!DB::table('workshops')->where('name', $workshopName)->exists()) {
                        if (!DB::table('maintains')->where('name', $workshopName)->exists()) {
                            DB::table('maintains')->insertGetId([
                                'created_at' => $created_at,
                                'unique_code' => $workshopUniqueCode,
                                'name' => $workshopName,
                                'parent_unique_code' => env('ORGANIZATION_CODE'),
                                'type' => 'WORKSHOP',
                                'lon' => $workshopLon,
                                'lat' => $workshopLat,
                                'contact' => $workshopContact,
                                'contact_phone' => $workshopContactPhone,
                                'is_show' => '1',
                            ]);
                        }
                    }
                }

                // breakdown_logs
                DB::table('breakdown_logs')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                DB::table('breakdown_logs')->where('scene_workshop_name', $row[1])->update(['maintain_station_name' => $row[2]]);
                // collect_equipment_order_entire_instances
                DB::table('collect_device_order_entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                DB::table('collect_device_order_entire_instances')->where('maintain_station_unique_code', $row[8])->update(['maintain_station_unique_code' => $row[10]]);
                DB::table('collect_device_order_entire_instances')->where('maintain_workshop_name', $row[1])->update(['maintain_workshop_name' => $row[2]]);
                DB::table('collect_device_order_entire_instances')->where('maintain_workshop_unique_code', $row[3])->update(['maintain_workshop_unique_code' => $row[5]]);
                // entire_instances
                DB::table('entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                DB::table('entire_instances')->where('maintain_workshop_name', $row[1])->update(['maintain_workshop_name' => $row[2]]);
                // fix_workflows
                DB::table('fix_workflows')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                // repair_base_breakdown_order_entire_instances
                DB::table('repair_base_breakdown_order_entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                DB::table('repair_base_breakdown_order_entire_instances')->where('scene_workshop_name', $row[1])->update(['scene_workshop_name' => $row[2]]);
                // station_locations
                DB::table('station_locations')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                DB::table('station_locations')->where('maintain_station_unique_code', $row[8])->update(['maintain_station_unique_code' => $row[10]]);
                DB::table('station_locations')->where('scene_workshop_name', $row[1])->update(['scene_workshop_name' => $row[2]]);
                DB::table('station_locations')->where('scene_workshop_unique_code', $row[3])->update(['scene_workshop_unique_code' => $row[5]]);
                // temp_station_eis
                DB::table('temp_station_eis')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                // temp_station_position
                DB::table('temp_station_position')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                DB::table('temp_station_position')->where('maintain_station_unique_code', $row[8])->update(['maintain_station_unique_code' => $row[10]]);
                DB::table('temp_station_position')->where('scene_workshop_name', $row[1])->update(['scene_workshop_name' => $row[2]]);
                DB::table('temp_station_position')->where('scene_workshop_unique_code', $row[3])->update(['scene_workshop_unique_code' => $row[5]]);
                // warehouse_in_batch_reports
                DB::table('warehouse_in_batch_reports')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                // warehouse_report_entire_instances
                DB::table('warehouse_report_entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
                // warehouse_storage_batch_reports
                DB::table('warehouse_storage_batch_reports')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            }
        });


        $this->info('清洗完成');
        return 0;
    }
}
