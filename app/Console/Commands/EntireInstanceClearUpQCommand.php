<?php

namespace App\Console\Commands;

use App\Services\ExcelReaderService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PHPExcel_Exception;
use PHPExcel_Reader_Exception;

class EntireInstanceClearUpQCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'entire-instance-clear-up:q {operator} {arg1?}';

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
     * @return int
     * @throws PHPExcel_Reader_Exception
     * @throws Exception
     */
    public function handle(): int
    {
        $operator = $this->argument('operator');
        if (!$operator) {
            $this->error("请填写方法名");
            return -1;
        }
        switch ($operator) {
            case "excelToJson":
                $filename = $this->argument("arg1");
                if (!$filename) {
                    $this->error("请填写文件名");
                    return -1;
                }
                return $this->excelToJson($filename);
            case "gz_zxq1":
                $filename = $this->argument("arg1");
                if (!$filename) {
                    $this->error("请填写文件名");
                    return -1;
                }
                $deleted_at = $this->gz_zxq1($filename);
                $this->info("删除：{$deleted_at}条。");
                return 0;
        }
        return 0;
    }

    /**
     * @param string $filename
     * @return int
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    private function excelToJson(string $filename): int
    {
        $excel = $this->readExcel($filename);
        $excel_data = $excel->GetData(["serial_number", "factory_device_code", "sub_model_name", "status", "maintain_station_name", "maintain_location_code"])
            ->map(function ($datum) {
                return array_map(function ($item) {
                    return trim($item);
                }, $datum);
            });
        file_put_contents(storage_path("a.xlsx.json"), $excel_data->toJson(256));
        $excel->Close();

        $this->info("FINISHED");
        return 0;
    }

    /**
     * @param string $filename
     * @return ExcelReaderService
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws Exception
     */
    private function readExcel(string $filename): ExcelReaderService
    {
        return ExcelReaderService::File(storage_path($filename))
            ->SetOriginRow(3)
            ->SetOriginCol(1)
            ->SetFinishColText("F")
            ->ReadBySheetIndex();
    }

    private function gz_zxq1(string $filename): int
    {
        $data = collect(json_decode(file_get_contents(storage_path("{$filename}.json")), true));
        $model_names = $data->where("sub_model_name", "<>", "")->pluck("sub_model_name")->unique()->values()->all();
        $maintain_station_names = $data->where("maintain_station_name", "<>", "")->pluck("maintain_station_name")->unique()->values()->all();
        $serial_numbers = $data->where("serial_number", "<>", "")->pluck("serial_number")->unique()->values()->all();

        return DB::table("entire_instances as ei")
            ->join(DB::raw("entire_models sm"), "ei.model_unique_code", "=", "sm.unique_code")
            ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
            ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
            ->whereIn("ei.serial_number", $serial_numbers)
            ->whereIn("ei.maintain_station_name", $maintain_station_names)
            ->whereIn("sm.name", $model_names)
            ->update([
                "ei.deleted_at"=>now()->toDateTimeString(),
                "ei.note"=>"广州-邓晓倩-申请删除",
            ]);
    }
}
