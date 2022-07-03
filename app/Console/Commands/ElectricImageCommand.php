<?php

namespace App\Console\Commands;

use App\Libraries\Super\Excel\ExcelReadHelper;
use App\Model\Maintain;
use App\Model\StationElectricImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ElectricImageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ei:ife';

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
     *
     * @return mixed
     */
    public function handle()
    {
        DB::beginTransaction();
        $excel = ExcelReadHelper::FROM_STORAGE(storage_path('电子图纸.xls'))
            ->originRow(2)
            ->all();

        foreach ($excel as $sheet_name => $item) {
            foreach ($item['success'] as $row_data) {
                list($station_name, $filename, $original_filename) = $row_data;
                $station = Maintain::with([])->where('name', $station_name)->get();
                if ($station->isEmpty()) {
                    $this->error("【{$sheet_name}】station： {$station_name} is doesn't exists");
                    continue;
                }
                if ($station->count() > 1) {
                    $this->error("【{$sheet_name}】station： {$station_name} has multiple instance");
                    return 1;
                }

                StationElectricImage::with([])->create([
                    'original_filename' => $original_filename,
                    'original_extension' => 'pdf',
                    'filename' => "/storage/stationWiki/electricImages/{$filename}",
                    'station_unique_code' => @$station->first()->unique_code ?? $station_name,
                ]);
            }
        }

        DB::commit();
        $this->info("导入车站电子图纸成功");
    }
}
