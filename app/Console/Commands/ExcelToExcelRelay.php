<?php

namespace App\Console\Commands;

use App\Model\EntireModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use PHPExcel_Cell;
use PHPExcel_Reader_Exception;
use PHPExcel_Shared_Date;
use PHPExcel_Writer_Exception;

class ExcelToExcelRelay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ete:relay {control=false}';

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

    private function __generateExcel(string $filename, array $excel_data)
    {
        $control = $this->argument("control") == "true";

        collect($excel_data)->each(
        /**
         * @throws PHPExcel_Reader_Exception
         * @throws PHPExcel_Writer_Exception
         */
            function ($sheet_data, $sheet_name) use ($filename, $control) {
                $error_excel_data = [];
                $success_excel_data = [];
                $same_serial_number_excel_data = [];
                $error_messages = [];
                $same_serial_number_error_messages = [];

                // 填充数据（行）
                $row = 0;
                foreach ($sheet_data as $sheet_datum) {
                    $row++;
                    $row_1 = $row + 1;

                    try {
                        list(
                            $num,  // 序号
                            $station_name,  // 站名
                            $instance_S_type,  // 设备类型
                            $instance_S_name,  // 设备名称
                            $maintain_location_code,  // 器件安装位置
                            $entire_model_name,  // 器件名称
                            $sub_model_name,  // 器件型号
                            $serial_number, // 器件出所编号
                            $factory_device_code,  // 出厂编号
                            $factory_name,  // 供应商（厂家）
                            $made_at,  // 出厂日期或首次入所日期
                            $last_out_at,  // 上次检修时间或最新出所时间
                            $installed_at,  // 安装日期
                            $fix_cycle_value,  // 检修周期（年）
                            $next_fixing_at,  // 下次周期修时间
                            $life_year,  // 使用寿命(年)
                            $scarping_at,  // 报废日期
                            ) = $sheet_datum;
                    } catch (Exception $e) {
                        $pattern = '/Undefined offset: /';
                        $offset = preg_replace($pattern, '', $e->getMessage());
                        $column_name = ExcelWriteHelper::int2Excel($offset);
                        dd("读取：{$column_name}列失败。");
                    }


                    $is_error_entire_model = false;
                    $entire_model = DB::table('entire_models')
                        ->whereNull('deleted_at')
                        ->where('is_sub_model', false)
                        ->where('name', $entire_model_name)
                        ->first();
                    // if (!$entire_model) {
                    //     $error_message = "{$row}行，类型不存在：{$entire_model_name}";
                    //     $error_messages[] = $error_message;
                    //     $this->error($error_message);
                    //     $is_error_entire_model = true;
                    // }
                    $is_error_sub_model = false;
                    $is_error_category = false;
                    $sub_model = null;
                    $category = null;
                    if ($entire_model) {
                        $sub_model = DB::table('entire_models')
                            ->whereNull('deleted_at')
                            ->where('is_sub_model', true)
                            ->where('name', $sub_model_name)
                            // ->where('parent_unique_code', $entire_model->unique_code)
                            ->first();
                        if (!$sub_model) {
                            $error_message = "型号不存在：{$entire_model_name} > {$sub_model_name}";
                            if (!array_key_exists($row_1, $error_messages)) $error_messages[$row_1] = [];
                            $error_messages[$row_1][] = $error_message;
                            $is_error_sub_model = true;
                            // @todo: 控制台输出
                            $this->error("{$row_1}行，$error_message");
                        }
                        $category = DB::table('categories')
                            ->whereNull('deleted_at')
                            ->where('unique_code', $entire_model->category_unique_code)
                            ->first();
                        // if (!$category) {
                        //     $error_message = "{$row}行，种类不存在：{$entire_model_name} > {$sub_model_name}";
                        //     $error_messages[] = $error_message;
                        //     $this->error($error_message);
                        //     $is_error_category = true;
                        // }
                    }
                    // $is_error_factory = false;
                    // if ($factory_name) {
                    //     $factory = DB::table('factories')
                    //         ->whereNull('deleted_at')
                    //         ->where('name', $factory_name)
                    //         ->first();
                    //     if (!$factory) {
                    //         $error_message = "{$row}行，厂家不存在：{$factory_name}";
                    //         $error_messages[] = $error_message;
                    //         $this->error($error_message);
                    //         $is_error_factory = true;
                    //     }
                    // }
                    $is_error_station = false;
                    $station = DB::table('maintains')
                        ->whereNull('deleted_at')
                        ->where('type', 'STATION')
                        ->where(function ($query) use ($station_name) {
                            $query->where('name', $station_name)
                                ->orWhere('name', rtrim($station_name, '站'))
                                ->orWhere('name', $station_name . '站');
                        })
                        ->first();
                    // if (!$station) {
                    //     $error_message = "车站不存在：{$station_name}";
                    //     $is_error_station = true;
                    //     if (!array_key_exists($row_1, $error_messages)) $error_messages[$row_1] = [];
                    //     $error_messages[$row_1][] = $error_message;
                    //     // @todo: 控制台输出
                    //     if ($control) $this->error("{$row_1}行，$error_message");
                    // }
                    $is_error_same_serial_number = false;
                    // if ($serial_number && $sub_model) {
                    //     if (DB::table('entire_instances')
                    //         ->whereNull('deleted_at')
                    //         ->where('model_name', $sub_model->name)
                    //         ->where('serial_number', $serial_number)
                    //         ->exists()) {
                    //         $error_message = "所编号重复：“{$serial_number}” 型号：“{$sub_model->name}($sub_model->unique_code)”";
                    //         $is_error_same_serial_number = true;
                    //         if (!array_key_exists($row_1, $error_messages)) $error_messages[$row_1] = [];
                    //         $error_messages[$row_1][] = $error_message;
                    //         // @todo: 控制台输出
                    //         if ($control) $this->error("{$row_1}行，$error_message");
                    //     }
                    // }
                    $is_error_made_at = false;
                    if (!$made_at) {
                        $error_message = "出厂日期未填写";
                        if (!array_key_exists($row_1, $error_messages)) $error_messages[$row_1] = [];
                        $error_messages[$row_1][] = $error_message;
                        $is_error_made_at = true;
                        // @todo: 控制台输出
                        if ($control) $this->error("{$row_1}行，$error_message");
                    }
                    $is_error_maintain_location_code = false;
                    if (!$maintain_location_code) {
                        $error_message = "安装位置未填写";
                        if (!array_key_exists($row_1, $error_messages)) $error_messages[$row_1] = [];
                        $error_messages[$row_1][] = $error_message;
                        // @todo: 控制台输出
                        if ($control) $this->error("{$row_1}行，$error_message");
                    }

                    $new_data = [
                        $instance_S_name,
                        $serial_number,  // 所编号 A
                        '',  // 种类 B
                        $entire_model_name,  // 类型 C
                        $sub_model_name,  // 型号 D
                        'INSTALLED',  // 状态 E
                        $factory_device_code,  // 厂编号 F
                        $factory_name,  // 厂家 G
                        $made_at,  // 生产日期 H
                        $station_name,  // 车站 I
                        $maintain_location_code,  // 组合位置 J
                        $last_out_at,  // 出所日期 K
                        $installed_at,  // 上道日期 L
                        '',  // 检修人 M
                        '',  // 检修时间 N
                        '',  // 验收人 O
                        '',  // 验收时间 P
                        '',  // 抽验人 Q
                        '',  // 抽验时间 R
                        '',  // 来源类型 S
                        '',  // 来源名称 T
                        '',  // 归属道岔编号 U
                        '',  // 道岔号 V
                        '',  // 开向 W
                        '',  // 线制 X
                        '',  // 备注 Y
                    ];

                    // if ($is_error_same_serial_number) {
                    //     // 记录所编号重复错误
                    //     $same_serial_number_excel_data[] = $new_data;
                    // }

                    if ($is_error_sub_model
                        || $is_error_made_at
                        || $is_error_maintain_location_code
                    ) {
                        // 如果存在错误，记录错误信息（型号不存在，车站不存在，安装位置未填写，出厂日期未填写）
                        $error_excel_data[] = $new_data;
                    } else {
                        // 如果不存在错误，生成新Excel
                        $success_excel_data[] = $new_data;
                    }
                }

                // if (!is_dir(storage_path("ExcelToExcel/{$sheet_name}"))) mkdir(storage_path("ExcelToExcel/{$sheet_name}"));
                // file_put_contents(storage_path("ExcelToExcel/{$sheet_name}/same_serial_number_excel_data.json"), json_encode($same_serial_number_excel_data, 256));
                // file_put_contents(storage_path("ExcelToExcel/{$sheet_name}/same_serial_number_error_messages.json"), json_encode($same_serial_number_error_messages, 256));
                // file_put_contents(storage_path("ExcelToExcel/{$sheet_name}/error_excel_data.json"), json_encode($error_excel_data, 256));
                // file_put_contents(storage_path("ExcelToExcel/{$sheet_name}/error_messages.json"), json_encode($error_messages, 256));
                // file_put_contents(storage_path("ExcelToExcel/{$sheet_name}/success_excel_data.json"), json_encode($success_excel_data, 256));

                // 创建错误Excel
                if (!empty($error_excel_data)) {
                    if (!is_dir(storage_path("ExcelToExcel/error/$filename"))) mkdir(storage_path("ExcelToExcel/error/$filename"));
                    ExcelWriteHelper::save(
                        function ($excel) use ($filename, $error_messages, &$error_excel_data) {
                            $excel->setActiveSheetIndex(0);
                            $current_sheet = $excel->getActiveSheet();
                            $current_sheet->setTitle("需要返回的数据");

                            // 表头
                            $first_row_data = [
                                ExcelWriteHelper::setStdCell("所编号"),
                                ExcelWriteHelper::setStdCell("种类*", "red"),
                                ExcelWriteHelper::setStdCell("类型*", "red"),
                                ExcelWriteHelper::setStdCell("型号(设备不填此项)*", "red"),
                                ExcelWriteHelper::setStdCell("状态*(上道使用、现场备品、所内备品、待修、入所在途、出所在途、报废)", "red", 100),
                                ExcelWriteHelper::setStdCell("厂编号"),
                                ExcelWriteHelper::setStdCell("厂家"),
                                ExcelWriteHelper::setStdCell("生产日期", "red"),
                                ExcelWriteHelper::setStdCell("车站", "red"),
                                ExcelWriteHelper::setStdCell("组合位置", "red"),
                                ExcelWriteHelper::setStdCell("出所日期"),
                                ExcelWriteHelper::setStdCell("上道日期"),
                                ExcelWriteHelper::setStdCell("检修人"),
                                ExcelWriteHelper::setStdCell("检修时间(YYYY-MM-DD格式)"),
                                ExcelWriteHelper::setStdCell("验收人"),
                                ExcelWriteHelper::setStdCell("验收时间(YYYY-MM-DD格式)"),
                                ExcelWriteHelper::setStdCell("抽验人"),
                                ExcelWriteHelper::setStdCell("抽验时间(YYYY-MM-DD格式)"),
                                ExcelWriteHelper::setStdCell("来源类型(新线建设、大修、更新改造、专项整治、材料计划、拆旧回收、外局调入、其他)"),
                                ExcelWriteHelper::setStdCell("来源名称"),
                                ExcelWriteHelper::setStdCell("归属道岔编号"),
                                ExcelWriteHelper::setStdCell("道岔号 (综合器材)"),
                                ExcelWriteHelper::setStdCell("开向:左、右 (综合器材)"),
                                ExcelWriteHelper::setStdCell("线制"),
                                ExcelWriteHelper::setStdCell("备注"),
                            ];
                            // 填充首行数据
                            foreach ($first_row_data as $col => $first_row_datum) {
                                ExcelWriteHelper::writeStdCell($current_sheet, $first_row_datum, $col, 1);
                            }

                            $row = 0;
                            $write_row = 2;
                            foreach ($error_excel_data as $error_excel_datum) {
                                if (!empty(array_filter($error_excel_datum, function ($new_datum) {
                                    return !empty($new_datum);
                                }))) {
                                    [
                                        $instance_S_name, // 设备名称
                                        $serial_number,  // 所编号 A
                                        $category,  // 种类 B
                                        $entire_model_name,  // 类型 C
                                        $sub_model_name,  // 型号 D
                                        $status,  // 状态 E
                                        $factory_device_code,  // 厂编号 F
                                        $factory_name,  // 厂家 G
                                        $made_at,  // 生产日期 H
                                        $station_name,  // 车站 I
                                        $maintain_location_code,  // 组合位置 J
                                        $last_out_at,  // 出所日期 K
                                        $installed_at,  // 上道日期 L
                                        // '',  // 检修人 M
                                        // '',  // 检修时间 N
                                        // '',  // 验收人 O
                                        // '',  // 验收时间 P
                                        // '',  // 抽验人 Q
                                        // '',  // 抽验时间 R
                                        // '',  // 来源类型 S
                                        // '',  // 来源名称 T
                                        // '',  // 归属道岔编号 U
                                        // '',  // 道岔号 V
                                        // '',  // 开向 W
                                        // '',  // 线制 X
                                        // '',  // 备注 Y
                                    ] = $error_excel_datum;

                                    $current_sheet->setCellValueExplicit("A$write_row", @$serial_number ?: '');
                                    $current_sheet->setCellValueExplicit("B$write_row", @$category->name ?: '');
                                    $current_sheet->setCellValueExplicit("C$write_row", @$entire_model_name ?: '');
                                    $current_sheet->setCellValueExplicit("D$write_row", @$sub_model_name ?: '');
                                    $current_sheet->setCellValueExplicit("E$write_row", '上道使用');
                                    $current_sheet->setCellValueExplicit("F$write_row", @$factory_device_code ?: '');
                                    $current_sheet->setCellValueExplicit("G$write_row", @$factory_name ?: '');
                                    $current_sheet->setCellValueExplicit("H$write_row", @$made_at ?: '');
                                    $current_sheet->setCellValueExplicit("I$write_row", @$station_name ?: '');
                                    $tmp_location = trim("$instance_S_name $maintain_location_code");
                                    $current_sheet->setCellValueExplicit("J$write_row", @$tmp_location ?: '');
                                    $current_sheet->setCellValueExplicit("K$write_row", @$last_out_at ?: '');
                                    $current_sheet->setCellValueExplicit("L$write_row", @$installed_at ?: '');
                                }
                                $write_row++;
                            }

                            $error_write_row = 2;
                            if (!empty($error_messages)) {
                                $error_message_sheet = $excel->createSheet();
                                $error_message_sheet->setTitle("错误说明");
                                // 表头
                                $first_row_data = [
                                    // ExcelWriteHelper::setStdCell('原文件行号'),
                                    ExcelWriteHelper::setStdCell("整理后行号"),
                                    ExcelWriteHelper::setStdCell("错误说明", "black", 200),
                                ];
                                // 填充表头
                                foreach ($first_row_data as $first_col => $first_row_datum) {
                                    ExcelWriteHelper::writeStdCell($error_message_sheet, $first_row_datum, $first_col, 1);
                                }

                                // 填充数据
                                $write_row = 2;
                                foreach ($error_messages as $source_row => $error_message) {
                                    // ExcelWriteHelper::writeStdCell($error_message_sheet, ExcelWriteHelper::setStdCell($source_row), 0, $write_row);
                                    ExcelWriteHelper::writeStdCell($error_message_sheet, ExcelWriteHelper::setStdCell($write_row), 0, $write_row);
                                    ExcelWriteHelper::writeStdCell($error_message_sheet, ExcelWriteHelper::setStdCell(join("；\n", $error_message), 'black', 200), 1, $write_row);
                                    $write_row++;
                                }
                            }
                            return $excel;
                        },
                        storage_path("ExcelToExcel/error/$filename/$sheet_name"),
                        ExcelWriteHelper::$VERSION_5);
                }

                // 创建所编号重复Excel
                // if (!empty($same_serial_number_excel_data)) {
                //     ExcelWriteHelper::save(function ($excel) use ($same_serial_number_excel_data) {
                //         $excel->setActiveSheetIndex(0);
                //         $same_serial_number_sheet = $excel->getActiveSheet();
                //         $same_serial_number_sheet->setTitle("所编号重复");
                //
                //         $error_same_serial_number_write_row = 2;
                //         // 表头
                //         $first_row_data = [
                //             ExcelWriteHelper::setStdDatum('所编号'),
                //             ExcelWriteHelper::setStdDatum('种类*', 'red'),
                //             ExcelWriteHelper::setStdDatum('类型*', 'red'),
                //             ExcelWriteHelper::setStdDatum('型号(设备不填此项)*', 'red'),
                //             ExcelWriteHelper::setStdDatum('状态*(上道使用、现场备品、所内备品、待修、入所在途、出所在途、报废)', 'red', 100),
                //             ExcelWriteHelper::setStdDatum('厂编号'),
                //             ExcelWriteHelper::setStdDatum('厂家'),
                //             ExcelWriteHelper::setStdDatum('生产日期'),
                //             ExcelWriteHelper::setStdDatum('车站'),
                //             ExcelWriteHelper::setStdDatum('组合位置'),
                //             ExcelWriteHelper::setStdDatum('出所日期'),
                //             ExcelWriteHelper::setStdDatum('上道日期'),
                //             ExcelWriteHelper::setStdDatum('检修人'),
                //             ExcelWriteHelper::setStdDatum('检修时间(YYYY-MM-DD格式)'),
                //             ExcelWriteHelper::setStdDatum('验收人'),
                //             ExcelWriteHelper::setStdDatum('验收时间(YYYY-MM-DD格式)'),
                //             ExcelWriteHelper::setStdDatum('抽验人'),
                //             ExcelWriteHelper::setStdDatum('抽验时间(YYYY-MM-DD格式)'),
                //             ExcelWriteHelper::setStdDatum('来源类型(新线建设、大修、更新改造、专项整治、材料计划、拆旧回收、外局调入、其他)'),
                //             ExcelWriteHelper::setStdDatum('来源名称'),
                //             ExcelWriteHelper::setStdDatum('归属道岔编号'),
                //             ExcelWriteHelper::setStdDatum('道岔号 (综合器材)'),
                //             ExcelWriteHelper::setStdDatum('开向:左、右 (综合器材)'),
                //             ExcelWriteHelper::setStdDatum('线制'),
                //             ExcelWriteHelper::setStdDatum('备注'),
                //         ];
                //         // 填充首行数据
                //         foreach ($first_row_data as $col => $first_row_datum) {
                //             ExcelWriteHelper::writeStdDatum($same_serial_number_sheet, $first_row_datum, $col, 1);
                //         }
                //
                //         // 填充数据
                //         foreach ($same_serial_number_excel_data as $same_serial_number_excel_datum) {
                //             if (!empty(array_filter($same_serial_number_excel_data, function ($new_datum) {
                //                 return !empty($new_datum);
                //             }))) {
                //                 [
                //                     $instance_S_name, // 设备名称
                //                     $serial_number,  // 所编号 A
                //                     $category,  // 种类 B
                //                     $entire_model_name,  // 类型 C
                //                     $sub_model_name,  // 型号 D
                //                     $status,  // 状态 E
                //                     $factory_device_code,  // 厂编号 F
                //                     $factory_name,  // 厂家 G
                //                     $made_at,  // 生产日期 H
                //                     $station_name,  // 车站 I
                //                     $maintain_location_code,  // 组合位置 J
                //                     $last_out_at,  // 出所日期 K
                //                     $installed_at,  // 上道日期 L
                //                     // '',  // 检修人 M
                //                     // '',  // 检修时间 N
                //                     // '',  // 验收人 O
                //                     // '',  // 验收时间 P
                //                     // '',  // 抽验人 Q
                //                     // '',  // 抽验时间 R
                //                     // '',  // 来源类型 S
                //                     // '',  // 来源名称 T
                //                     // '',  // 归属道岔编号 U
                //                     // '',  // 道岔号 V
                //                     // '',  // 开向 W
                //                     // '',  // 线制 X
                //                     // '',  // 备注 Y
                //                 ] = $same_serial_number_excel_datum;
                //
                //                 $same_serial_number_sheet->setCellValueExplicit("A{$error_same_serial_number_write_row}", @$serial_number ?: '');
                //                 $same_serial_number_sheet->setCellValueExplicit("B{$error_same_serial_number_write_row}", @$category->name ?: '');
                //                 $same_serial_number_sheet->setCellValueExplicit("C{$error_same_serial_number_write_row}", @$entire_model_name ?: '');
                //                 $same_serial_number_sheet->setCellValueExplicit("D{$error_same_serial_number_write_row}", @$sub_model_name ?: '');
                //                 $same_serial_number_sheet->setCellValueExplicit("E{$error_same_serial_number_write_row}", '上道使用');
                //                 $same_serial_number_sheet->setCellValueExplicit("F{$error_same_serial_number_write_row}", @$factory_device_code ?: '');
                //                 $same_serial_number_sheet->setCellValueExplicit("G{$error_same_serial_number_write_row}", @$factory_name ?: '');
                //                 $same_serial_number_sheet->setCellValueExplicit("H{$error_same_serial_number_write_row}", @$made_at ?: '');
                //                 $same_serial_number_sheet->setCellValueExplicit("I{$error_same_serial_number_write_row}", @$station_name ?: '');
                //                 $tmp_location = trim("{$instance_S_name} {$maintain_location_code}");
                //                 $same_serial_number_sheet->setCellValueExplicit("J{$error_same_serial_number_write_row}", @$tmp_location ?: '');
                //                 $same_serial_number_sheet->setCellValueExplicit("K{$error_same_serial_number_write_row}", @$last_out_at ?: '');
                //                 $same_serial_number_sheet->setCellValueExplicit("L{$error_same_serial_number_write_row}", @$installed_at ?: '');
                //             }
                //             $error_same_serial_number_write_row++;
                //         }
                //         return $excel;
                //     },
                //         storage_path("ExcelToExcel/error/{$filename}--{$sheet_name}所编号重复"),
                //         ExcelWriteHelper::$VERSION_5);
                // }

                // 创建正确Excel
                // if (!empty($success_excel_data)) {
                //     ExcelWriteHelper::save(
                //         function ($excel) use ($filename, $error_messages, &$success_excel_data) {
                //             $excel->setActiveSheetIndex(0);
                //             $current_sheet = $excel->getActiveSheet();
                //
                //             // 表头
                //             $first_row_data = [
                //                 ExcelWriteHelper::setStdDatum('所编号'),
                //                 ExcelWriteHelper::setStdDatum('种类*', 'red'),
                //                 ExcelWriteHelper::setStdDatum('类型*', 'red'),
                //                 ExcelWriteHelper::setStdDatum('型号(设备不填此项)*', 'red'),
                //                 ExcelWriteHelper::setStdDatum('状态*(上道使用、现场备品、所内备品、待修、入所在途、出所在途、报废)', 'red', 100),
                //                 ExcelWriteHelper::setStdDatum('厂编号'),
                //                 ExcelWriteHelper::setStdDatum('厂家'),
                //                 ExcelWriteHelper::setStdDatum('生产日期'),
                //                 ExcelWriteHelper::setStdDatum('车站'),
                //                 ExcelWriteHelper::setStdDatum('组合位置'),
                //                 ExcelWriteHelper::setStdDatum('出所日期'),
                //                 ExcelWriteHelper::setStdDatum('上道日期'),
                //                 ExcelWriteHelper::setStdDatum('检修人'),
                //                 ExcelWriteHelper::setStdDatum('检修时间(YYYY-MM-DD格式)'),
                //                 ExcelWriteHelper::setStdDatum('验收人'),
                //                 ExcelWriteHelper::setStdDatum('验收时间(YYYY-MM-DD格式)'),
                //                 ExcelWriteHelper::setStdDatum('抽验人'),
                //                 ExcelWriteHelper::setStdDatum('抽验时间(YYYY-MM-DD格式)'),
                //                 ExcelWriteHelper::setStdDatum('来源类型(新线建设、大修、更新改造、专项整治、材料计划、拆旧回收、外局调入、其他)'),
                //                 ExcelWriteHelper::setStdDatum('来源名称'),
                //                 ExcelWriteHelper::setStdDatum('归属道岔编号'),
                //                 ExcelWriteHelper::setStdDatum('道岔号 (综合器材)'),
                //                 ExcelWriteHelper::setStdDatum('开向:左、右 (综合器材)'),
                //                 ExcelWriteHelper::setStdDatum('线制'),
                //                 ExcelWriteHelper::setStdDatum('备注'),
                //             ];
                //             // 填充首行数据
                //             foreach ($first_row_data as $col => $first_row_datum) {
                //                 ExcelWriteHelper::writeStdDatum($current_sheet, $first_row_datum, $col, 1);
                //             }
                //
                //             $row = 0;
                //             $write_row = 2;
                //             foreach ($success_excel_data as $success_excel_datum) {
                //                 if (!empty(array_filter($success_excel_datum, function ($new_datum) {
                //                     return !empty($new_datum);
                //                 }))) {
                //                     [
                //                         $instance_S_name, // 设备名称
                //                         $serial_number,  // 所编号 A
                //                         $category,  // 种类 B
                //                         $entire_model_name,  // 类型 C
                //                         $sub_model_name,  // 型号 D
                //                         $status,  // 状态 E
                //                         $factory_device_code,  // 厂编号 F
                //                         $factory_name,  // 厂家 G
                //                         $made_at,  // 生产日期 H
                //                         $station_name,  // 车站 I
                //                         $maintain_location_code,  // 组合位置 J
                //                         $last_out_at,  // 出所日期 K
                //                         $installed_at,  // 上道日期 L
                //                         // '',  // 检修人 M
                //                         // '',  // 检修时间 N
                //                         // '',  // 验收人 O
                //                         // '',  // 验收时间 P
                //                         // '',  // 抽验人 Q
                //                         // '',  // 抽验时间 R
                //                         // '',  // 来源类型 S
                //                         // '',  // 来源名称 T
                //                         // '',  // 归属道岔编号 U
                //                         // '',  // 道岔号 V
                //                         // '',  // 开向 W
                //                         // '',  // 线制 X
                //                         // '',  // 备注 Y
                //                     ] = $success_excel_datum;
                //
                //                     $current_sheet->setCellValueExplicit("A{$write_row}", @$serial_number ?: '');
                //                     $current_sheet->setCellValueExplicit("B{$write_row}", @$category->name ?: '');
                //                     $current_sheet->setCellValueExplicit("C{$write_row}", @$entire_model_name ?: '');
                //                     $current_sheet->setCellValueExplicit("D{$write_row}", @$sub_model_name ?: '');
                //                     $current_sheet->setCellValueExplicit("E{$write_row}", '上道使用');
                //                     $current_sheet->setCellValueExplicit("F{$write_row}", @$factory_device_code ?: '');
                //                     $current_sheet->setCellValueExplicit("G{$write_row}", @$factory_name ?: '');
                //                     $current_sheet->setCellValueExplicit("H{$write_row}", @$made_at ?: '');
                //                     $current_sheet->setCellValueExplicit("I{$write_row}", @$station_name ?: '');
                //                     $tmp_location = trim("{$instance_S_name} {$maintain_location_code}");
                //                     $current_sheet->setCellValueExplicit("J{$write_row}", @$tmp_location ?: '');
                //                     $current_sheet->setCellValueExplicit("K{$write_row}", @$last_out_at ?: '');
                //                     $current_sheet->setCellValueExplicit("L{$write_row}", @$installed_at ?: '');
                //                 }
                //                 $write_row++;
                //             }
                //
                //             return $excel;
                //         },
                //         storage_path("ExcelToExcel/out/{$filename}--{$sheet_name}"),
                //         ExcelWriteHelper::$VERSION_5);
                // }

                // if (!empty($error_messages))
                //     file_put_contents(storage_path("ExcelToExcel/out/{$filename}--{$sheet_name}.json"), json_encode($error_messages, 256));
            });
    }

    private function __generateError(string $filename, array $excel_data)
    {
        $control = $this->argument("control") == "true";
        $out_excel_data = [];

        collect($excel_data)->each(
        /**
         * @throws PHPExcel_Reader_Exception
         * @throws PHPExcel_Writer_Exception
         */
            function ($sheet_data, $sheet_name) use ($filename, $control) {
                // 填充数据（行）
                $row = 0;
                foreach ($sheet_data as $sheet_datum) {
                    $row++;
                    $row_1 = $row + 1;
                    $current_row_error_messages = [];

                    try {
                        list(
                            $num,  // 序号
                            $station_name,  // 站名
                            $instance_S_type,  // 设备类型
                            $instance_S_name,  // 设备名称
                            $maintain_location_code,  // 器件安装位置
                            $entire_model_name,  // 器件名称
                            $sub_model_name,  // 器件型号
                            $serial_number, // 器件出所编号
                            $factory_device_code,  // 出厂编号
                            $factory_name,  // 供应商（厂家）
                            $made_at,  // 出厂日期或首次入所日期
                            $last_out_at,  // 上次检修时间或最新出所时间
                            $installed_at,  // 安装日期
                            $fix_cycle_value,  // 检修周期（年）
                            $next_fixing_at,  // 下次周期修时间
                            $life_year,  // 使用寿命(年)
                            $scarping_at,  // 报废日期
                            ) = $sheet_datum;
                    } catch (Exception $e) {
                        $pattern = '/Undefined offset: /';
                        $offset = preg_replace($pattern, '', $e->getMessage());
                        $column_name = ExcelWriteHelper::int2Excel($offset);
                        dd("读取：{$column_name}列失败。");
                    }


                    $is_error_entire_model = false;
                    $entire_model = DB::table('entire_models')
                        ->whereNull('deleted_at')
                        ->where('is_sub_model', false)
                        ->where('name', $entire_model_name)
                        ->first();
                    // if (!$entire_model) {
                    //     $error_message = "{$row}行，类型不存在：{$entire_model_name}";
                    //     $error_messages[] = $error_message;
                    //     $this->error($error_message);
                    //     $is_error_entire_model = true;
                    // }
                    $is_error_sub_model = false;
                    $is_error_category = false;
                    $sub_model = null;
                    $category = null;
                    if ($entire_model) {
                        $sub_model = DB::table('entire_models')
                            ->whereNull('deleted_at')
                            ->where('is_sub_model', true)
                            ->where('name', $sub_model_name)
                            // ->where('parent_unique_code', $entire_model->unique_code)
                            ->first();
                        if (!$sub_model) {
                            $error_message = "型号不存在：{$entire_model_name} > {$sub_model_name}";
                            $current_row_error_messages[] = $error_message;
                            $is_error_sub_model = true;
                            // @todo: 控制台输出
                            $this->error("{$row_1}行，$error_message");
                        }
                        $category = DB::table('categories')
                            ->whereNull('deleted_at')
                            ->where('unique_code', $entire_model->category_unique_code)
                            ->first();
                        // if (!$category) {
                        //     $error_message = "{$row}行，种类不存在：{$entire_model_name} > {$sub_model_name}";
                        //     $error_messages[] = $error_message;
                        //     $this->error($error_message);
                        //     $is_error_category = true;
                        // }
                    }
                    // $is_error_factory = false;
                    // if ($factory_name) {
                    //     $factory = DB::table('factories')
                    //         ->whereNull('deleted_at')
                    //         ->where('name', $factory_name)
                    //         ->first();
                    //     if (!$factory) {
                    //         $error_message = "{$row}行，厂家不存在：{$factory_name}";
                    //         $error_messages[] = $error_message;
                    //         $this->error($error_message);
                    //         $is_error_factory = true;
                    //     }
                    // }
                    $is_error_station = false;
                    $station = DB::table('maintains')
                        ->whereNull('deleted_at')
                        ->where('type', 'STATION')
                        ->where(function ($query) use ($station_name) {
                            $query->where('name', $station_name)
                                ->orWhere('name', rtrim($station_name, '站'))
                                ->orWhere('name', $station_name . '站');
                        })
                        ->first();
                    // if (!$station) {
                    //     $error_message = "车站不存在：{$station_name}";
                    //     $is_error_station = true;
                    //     if (!array_key_exists($row_1, $error_messages)) $error_messages[$row_1] = [];
                    //     $error_messages[$row_1][] = $error_message;
                    //     // @todo: 控制台输出
                    //     if ($control) $this->error("{$row_1}行，$error_message");
                    // }
                    $is_error_same_serial_number = false;
                    // if ($serial_number && $sub_model) {
                    //     if (DB::table('entire_instances')
                    //         ->whereNull('deleted_at')
                    //         ->where('model_name', $sub_model->name)
                    //         ->where('serial_number', $serial_number)
                    //         ->exists()) {
                    //         $error_message = "所编号重复：“{$serial_number}” 型号：“{$sub_model->name}($sub_model->unique_code)”";
                    //         $is_error_same_serial_number = true;
                    //         if (!array_key_exists($row_1, $error_messages)) $error_messages[$row_1] = [];
                    //         $error_messages[$row_1][] = $error_message;
                    //         // @todo: 控制台输出
                    //         if ($control) $this->error("{$row_1}行，$error_message");
                    //     }
                    // }
                    $is_error_made_at = false;
                    if (!$made_at) {
                        $error_message = "出厂日期未填写";
                        $current_row_error_messages[] = $error_message;
                        $is_error_made_at = true;
                        // @todo: 控制台输出
                        if ($control) $this->error("{$row_1}行，$error_message");
                    }
                    $is_error_maintain_location_code = false;
                    if (!$maintain_location_code) {
                        $error_message = "安装位置未填写";
                        if (!array_key_exists($row_1, $current_row_error_messages)) $current_row_error_messages[$row_1] = [];
                        $current_row_error_messages[$row_1][] = $error_message;
                        // @todo: 控制台输出
                        if ($control) $this->error("{$row_1}行，$error_message");
                    }


                }
            }
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function handle()
    {
        $this->comment("开始");
        $this->comment("解析文件组");
        $dist_dir = storage_path("ExcelToExcel/dist");

        $fs = FileSystem::init(storage_path("ExcelToExcel/dist"));
        $filenames = $fs->getFiles();

        $this->comment("文件组解析完毕");

        foreach ($filenames as $filename) {
            $this->comment("开始读取excel文件：$filename");
            $excel = (new ExcelReadHelper)->file(storage_path("ExcelToExcel/dist/$filename"));
            $php_excel = $excel->php_excel;
            $sheet_names = collect($php_excel->getSheetNames());
            $excel_data = [];

            $sheet_names->each(function ($sheet_name) use ($php_excel, $filename, &$excel_data) {
                $sheet = $php_excel->getSheetByName($sheet_name);
                $sheet_data = [];

                $max_row = $sheet->getHighestRow();
                $max_col = $sheet->getHighestColumn();

                $tmp_data = [];
                for ($i = 2; $i <= $max_row; $i++) {
                    $tmp_data = $sheet->rangeToArray('A' . $i . ':' . $max_col . $i, NULL, TRUE, FALSE)[0];
                    if (empty(array_filter($tmp_data, function ($tmp_datum) {
                        return !empty($tmp_datum);
                    }))) continue;
                    $sheet_data[] = $tmp_data;
                }

                $excel_data[$sheet_name] = $sheet_data;
            });
            $php_excel->disconnectWorksheets();
            $this->comment("excel文件读取完毕");

            $this->comment("开始导入新excel：$filename");
            // 写入excel
            $this->__generateExcel($filename, $excel_data);
            $this->comment("新文件导入完毕：$filename");
        }
    }
}
