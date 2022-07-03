<?php

namespace App\Http\Controllers\Entire;

use App\Exceptions\EmptyException;
use App\Facades\CodeFacade;
use App\Facades\FixWorkflowFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\EntireInstanceExcelTaggingReport;
use App\Model\EntireInstanceUpdateFixerCheckerOrder;
use App\Model\EntireInstanceUpdateFixerCheckerOrderItem;
use App\Services\ExcelCellService;
use App\Services\ExcelReaderService;
use App\Services\ExcelRowService;
use App\Services\ExcelWriterService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PHPExcel_Exception;
use PHPExcel_Reader_Exception;
use Throwable;

class UpdateFixerCheckerOrderController extends Controller
{
    private $__errors = [];

    /**
     * 批量添加错误单元格
     * @param array $cell_rows
     * @param string $cell_text
     * @param null|string $reason
     */
    final private function appendErrorCells(array $cell_rows, string $cell_text, ?string $reason): void
    {
        collect($cell_rows)
            ->each(function ($row_number) use ($cell_text, $reason) {
                if (!array_key_exists(strval($row_number), $this->__errors)) $this->__errors[$row_number] = [];
                $this->__errors[$row_number][$cell_text] = $reason;
                // $this->__errors[$row_number] = array_values(array_unique($this->__errors[$row_number]));
            });
    }

    /**
     * 批量添加错误行
     * @param array $row_numbers
     */
    final private function appendErrorRows(array $row_numbers): void
    {
        collect($row_numbers)
            ->each(function ($row_number) {
                if (!array_key_exists(strval($row_number), $this->__errors)) {
                    $this->__errors[$row_number] = [];
                }
            });
    }

    /**
     * 列表
     * @return Factory|Application|View
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    final public function Index()
    {
        if (request("download") == 1) {
            $excel_writer = ExcelWriterService::Init();
            $excel_writer_sheet = $excel_writer->GetSheet();
            $excel_writer_row = 1;

            // 制作表头
            ExcelRowService::Init()
                ->SetRow($excel_writer_row)
                ->SetExcelCells([
                    ExcelCellService::Init("唯一编号/所编号*")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                    ExcelCellService::Init("种类*(使用所编号时该项必填)")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                    ExcelCellService::Init("类型*(使用所编号时该项必填)")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                    ExcelCellService::Init("型号*(使用所编号时该项必填)")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                    ExcelCellService::Init("检修人*")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                    ExcelCellService::Init("检修时间*")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                    ExcelCellService::Init("验收人"),
                    ExcelCellService::Init("验收时间"),
                    ExcelCellService::Init("抽验人"),
                    ExcelCellService::Init("抽验时间"),
                ])
                ->Write($excel_writer_sheet);

            $excel_writer
                ->SetWidthByColText("A", 25)
                ->SetWidthByColText("B", 40)
                ->SetWidthByColText("C", 40)
                ->SetWidthByColText("D", 40)
                ->SetWidthByColText("E", 25)
                ->SetWidthByColText("F", 25)
                ->SetWidthByColText("G", 25)
                ->SetWidthByColText("H", 25)
                ->SetWidthByColText("I", 25)
                ->SetWidthByColText("J", 25)
                ->Download("补录检修人、验收人模板");
        }

        if (request()->ajax()) {
            $entire_instance_update_fixer_checker_orders = (new EntireInstanceUpdateFixerCheckerOrder)
                ->ReadMany(["created_at"])
                ->with(["Operator", "WorkArea"])
                ->when(
                    request("created_at"),
                    function ($query, $created_at) {
                        list($original_at, $finished_at) = explode("~", $created_at);
                        $original_at = Carbon::parse($original_at)->startOfDay()->format("Y-m-d H:i:s");
                        $finished_at = Carbon::parse($finished_at)->endOfDay()->format("Y-m-d H:i:s");
                        $query->whereBetween("created_at", [$original_at, $finished_at]);
                    }
                );
            return JsonResponseFacade::dict(["entire_instance_update_fixer_checker_orders" => $entire_instance_update_fixer_checker_orders->get(),]);
        } else {
            return view("Entire.UpdateFixerCheckerOrder.index");
        }
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws Exception
     * @throws Throwable
     */
    final public function Store(Request $request)
    {
        $excel_reader = ExcelReaderService::Request($request, "file")
            ->SetOriginRow(2)
            ->SetFinishColText("J")
            ->ReadBySheetIndex()
            ->Close();

        $excel_data = $excel_reader->GetData([
            "code",
            "category_name",
            "entire_model_name",
            "sub_model_name",
            "fixer_name",
            "fixed_at",
            "checker_name",
            "checked_at",
            "spot_checker_name",
            "spot_checked_at",
        ]);

        $success_count = 0;

        // 拆分成唯一编号组和所编号组
        $identity_code_data = collect([]);
        $serial_number_data = collect([]);
        if ($excel_data->isNotEmpty()) {
            $excel_data->each(function ($excel_datum, $excel_row) use (&$identity_code_data, &$serial_number_data) {
                if (!$excel_datum["code"]) return null;
                $is_identity_code = CodeFacade::isIdentityCode($excel_datum["code"]);
                if ($is_identity_code) {
                    $identity_code_data[$excel_row] = $excel_datum;
                } else {
                    $serial_number_data[$excel_row] = $excel_datum;
                }
            });
        }

        // 检查唯一编号组
        $identity_code_data = $this->checkIdentityCodeData($identity_code_data);
        // 检查所编号组
        ["tagging_data" => $tagging_data, "un_tagging_data" => $un_tagging_data] = $this->checkSerialNumberData($serial_number_data);

        // 将没有赋码的进行赋码
        $un_tagging_data = $this->handleUnTagging($un_tagging_data);

        // 创建上传检修人、验收人单
        $entire_instance_update_fixer_checker_order = EntireInstanceUpdateFixerCheckerOrder::with([])
            ->create([
                "uuid" => Str::uuid(),
                "operator_id" => session("account.id"),
                "work_area_unique_code" => session("account.work_area_unique_code"),
                "error_filename" => "",
            ]);
        $entire_instance_update_fixer_checker_order_items = [];

        // 组合修改检修人验收人数据
        $data = [];
        if ($identity_code_data->isNotEmpty()) {
            $identity_code_data->each(function ($identity_code_datum) use (&$data, &$entire_instance_update_fixer_checker_order_items, $entire_instance_update_fixer_checker_order) {
                $data[] = [
                    "entire_instance_identity_code" => $identity_code_datum["identity_code"],
                    "fixed_at" => $identity_code_datum["fixed_at"],
                    "fixer_name" => $identity_code_datum["fixer_name"],
                    "checked_at" => $identity_code_datum["checked_at"],
                    "checker_name" => $identity_code_datum["checker_name"],
                    "spot_checked_at" => $identity_code_datum["spot_checked_at"],
                    "spot_checker_name" => $identity_code_datum["spot_checker_name"],
                ];
                $entire_instance_update_fixer_checker_order_items[] = [
                    "entire_instance_identity_code" => $identity_code_datum["identity_code"],
                    "serial_number" => $identity_code_datum["serial_number"],
                    "fixer_name" => $identity_code_datum["fixer_name"],
                    "fixed_at" => @$identity_code_datum["fixed_at"] ? Carbon::parse($identity_code_datum["fixed_at"]) : null,
                    "checker_name" => $identity_code_datum["checker_name"],
                    "checked_at" => @$identity_code_datum["checked_at"] ? Carbon::parse($identity_code_datum["checked_at"]) : null,
                    "spot_checker_name" => $identity_code_datum["spot_checker_name"],
                    "spot_checked_at" => @$identity_code_datum["spot_checked_at"] ? Carbon::parse($identity_code_datum["spot_checked_at"]) : null,
                    "entire_instance_update_fixer_checker_order_uuid" => $entire_instance_update_fixer_checker_order->uuid,
                ];
            });
        }
        if ($tagging_data->isNotEmpty()) {
            $tagging_data->each(function ($tagging_datum) use (&$data, &$entire_instance_update_fixer_checker_order_items, $entire_instance_update_fixer_checker_order) {
                $data[] = [
                    "entire_instance_identity_code" => $tagging_datum["identity_code"],
                    "fixed_at" => $tagging_datum["fixed_at"],
                    "fixer_name" => $tagging_datum["fixer_name"],
                    "checked_at" => $tagging_datum["checked_at"],
                    "checker_name" => $tagging_datum["checker_name"],
                    "spot_checked_at" => $tagging_datum["spot_checked_at"],
                    "spot_checker_name" => $tagging_datum["spot_checker_name"],
                ];
                $entire_instance_update_fixer_checker_order_items[] = [
                    "entire_instance_identity_code" => $tagging_datum["identity_code"],
                    "serial_number" => $tagging_datum["serial_number"],
                    "fixer_name" => $tagging_datum["fixer_name"],
                    "fixed_at" => @$tagging_datum["fixed_at"] ? Carbon::parse($tagging_datum["fixed_at"]) : null,
                    "checker_name" => $tagging_datum["checker_name"],
                    "checked_at" => @$tagging_datum["checked_at"] ? Carbon::parse($tagging_datum["checked_at"]) : null,
                    "spot_checker_name" => $tagging_datum["spot_checker_name"],
                    "spot_checked_at" => @$tagging_datum["spot_checked_at"] ? Carbon::parse($tagging_datum["spot_checked_at"]) : null,
                    "entire_instance_update_fixer_checker_order_uuid" => $entire_instance_update_fixer_checker_order->uuid,
                ];
            });
        }
        if ($un_tagging_data->isNotEmpty()) {
            $un_tagging_data->each(function ($un_tagging_datum) use (&$data, &$entire_instance_update_fixer_checker_order_items, $entire_instance_update_fixer_checker_order) {
                $data[] = [
                    'entire_instance_identity_code' => $un_tagging_datum["identity_code"],
                    'fixed_at' => $un_tagging_datum["fixed_at"],
                    'fixer_name' => $un_tagging_datum["fixer_name"],
                    'checked_at' => $un_tagging_datum["checked_at"],
                    'checker_name' => $un_tagging_datum["checker_name"],
                    'spot_checked_at' => $un_tagging_datum["spot_checked_at"],
                    'spot_checker_name' => $un_tagging_datum["spot_checker_name"],
                ];
                $entire_instance_update_fixer_checker_order_items[] = [
                    "entire_instance_identity_code" => $un_tagging_datum["identity_code"],
                    "serial_number" => $un_tagging_datum["serial_number"],
                    "fixer_name" => $un_tagging_datum["fixer_name"],
                    "fixed_at" => @$un_tagging_datum["fixed_at"] ? Carbon::parse($un_tagging_datum["fixed_at"]) : null,
                    "checker_name" => $un_tagging_datum["checker_name"],
                    "checked_at" => @$un_tagging_datum["checked_at"] ? Carbon::parse($un_tagging_datum["checked_at"]) : null,
                    "spot_checker_name" => $un_tagging_datum["spot_checker_name"],
                    "spot_checked_at" => @$un_tagging_datum["spot_checked_at"] ? Carbon::parse($un_tagging_datum["spot_checked_at"]) : null,
                    "entire_instance_update_fixer_checker_order_uuid" => $entire_instance_update_fixer_checker_order->uuid,
                    "be_new_tagging" => true,
                ];
            });
        }

        // 记录上传检修人、验收人器材记录
        collect($data)
            ->each(function ($datum) use ($entire_instance_update_fixer_checker_order) {
                EntireInstanceUpdateFixerCheckerOrderItem::with([])->create([
                    "entire_instance_identity_code" => $datum["entire_instance_identity_code"],
                    "fixer_name" => $datum["fixer_name"],
                    "fixed_at" => @$datum["fixed_at"] ? Carbon::parse($datum["fixed_at"]) : null,
                    "checker_name" => $datum["checker_name"],
                    "checked_at" => @$datum["checked_at"] ? Carbon::parse($datum["checked_at"]) : null,
                    "spot_checker_name" => $datum["spot_checker_name"],
                    "spot_checked_at" => @$datum["spot_checked_at"] ? Carbon::parse($datum["spot_checked_at"]) : null,
                    "entire_instance_update_fixer_checker_order_uuid" => $entire_instance_update_fixer_checker_order->uuid,
                ]);
            });

        // 处理错误excel
        if (!empty($this->__errors)) $this->handleErrorExcel($entire_instance_update_fixer_checker_order, $excel_data);

        // 修改检修人验收人
        if (!empty($data)) $success_count = FixWorkflowFacade::mockEmpties($data);

        // 保存成功、失败、新赋码数量
        $entire_instance_update_fixer_checker_order
            ->fill([
                "new_tagging_count" => $un_tagging_data->count(),
                "correct_count" => $success_count,
                "fail_count" => count($this->__errors),
            ])
            ->saveOrFail();

        return redirect("/entire/updateFixerCheckerOrder/$entire_instance_update_fixer_checker_order->uuid");
    }

    /**
     * 详情页
     * @param string $uuid
     * @return Factory|Application|View
     */
    final public function Show(string $uuid)
    {
        if (request()->ajax()) {
            $entire_instance_update_fixer_checker_order_items = (new EntireInstanceUpdateFixerCheckerOrderItem)
                ->with(["EntireInstance",])
                ->where("entire_instance_update_fixer_checker_order_uuid", $uuid)
                ->get()
                ->map(function ($entire_instance_update_fixer_checker_order_item) {
                    $entire_instance_update_fixer_checker_order_item->EntireInstance["full_kind_name"] = $entire_instance_update_fixer_checker_order_item->EntireInstance->full_kind_name;
                    return $entire_instance_update_fixer_checker_order_item;
                });
            return JsonResponseFacade::dict(["entire_instance_update_fixer_checker_order_items" => $entire_instance_update_fixer_checker_order_items,]);
        } else {
            $factories = \App\Model\Factory::with([])->get();
            $entire_instance_update_fixer_checker_order = (new EntireInstanceUpdateFixerCheckerOrder)->with([])->where("uuid", $uuid)->firstOrFail();
            return view("Entire.UpdateFixerCheckerOrder.show", [
                "factories_as_json" => $factories,
                "entire_instance_update_fixer_checker_order" => $entire_instance_update_fixer_checker_order,
            ]);
        }
    }

    /**
     * 处理错误Excel
     * @throws Throwable
     */
    private function handleErrorExcel(EntireInstanceUpdateFixerCheckerOrder $entire_instance_update_fixer_checker_order, Collection $excel_data)
    {
        if (!is_dir(storage_path("app/public/updateFixerCheckerExcel")))
            mkdir(storage_path("app/public/updateFixerCheckerExcel"));

        // 错误Excel
        $excel_writer_error = ExcelWriterService::Init();
        $excel_writer_sheet_error = $excel_writer_error->GetSheet();
        $excel_writer_row_error = 1;

        ExcelRowService::Init()
            ->SetRow($excel_writer_row_error++)
            ->SetExcelCells([
                ExcelCellService::Init("唯一编号/所编号*")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                ExcelCellService::Init("种类*(使用所编号时该项必填)")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                ExcelCellService::Init("类型*(使用所编号时该项必填)")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                ExcelCellService::Init("型号*(使用所编号时该项必填)")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                ExcelCellService::Init("检修人")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                ExcelCellService::Init("检修时间")->SetFontColor(ExcelCellService::$FONT_COLOR_RED),
                ExcelCellService::Init("验收人"),
                ExcelCellService::Init("验收时间"),
                ExcelCellService::Init("抽验人"),
                ExcelCellService::Init("抽验时间"),
            ])
            ->Write($excel_writer_sheet_error);
        $excel_writer_error
            ->SetWidthByColText("A", 25)
            ->SetWidthByColText("B", 40)
            ->SetWidthByColText("C", 40)
            ->SetWidthByColText("D", 40)
            ->SetWidthByColText("E", 25)
            ->SetWidthByColText("F", 25)
            ->SetWidthByColText("G", 25)
            ->SetWidthByColText("H", 25)
            ->SetWidthByColText("I", 25)
            ->SetWidthByColText("J", 25);

        // 填充个错误数据
        $current_row = 2;
        collect($this->__errors)
            ->each(function ($datum, $row) use (&$current_row, &$excel_writer_sheet_error, $excel_data) {
                $code = ExcelCellService::Init($excel_data->get($row)["code"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("A", array_keys(@$this->__errors[$row])));
                $category_name = ExcelCellService::Init($excel_data->get($row)["category_name"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("B", array_keys(@$this->__errors[$row])));
                $entire_model_name = ExcelCellService::Init($excel_data->get($row)["entire_model_name"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("C", array_keys(@$this->__errors[$row])));
                $sub_model_name = ExcelCellService::Init($excel_data->get($row)["sub_model_name"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("D", array_keys(@$this->__errors[$row])));
                $fixer_name = ExcelCellService::Init($excel_data->get($row)["fixer_name"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("E", array_keys(@$this->__errors[$row])));
                $fixed_at = ExcelCellService::Init($excel_data->get($row)["fixed_at"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("F", array_keys(@$this->__errors[$row])));
                $checker_name = ExcelCellService::Init($excel_data->get($row)["checker_name"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("G", array_keys(@$this->__errors[$row])));
                $checked_at = ExcelCellService::Init($excel_data->get($row)["checked_at"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("H", array_keys(@$this->__errors[$row])));
                $spot_checker_name = ExcelCellService::Init($excel_data->get($row)["spot_checker_name"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("I", array_keys(@$this->__errors[$row])));
                $spot_checked_at = ExcelCellService::Init($excel_data->get($row)["spot_checked_at"])->SetFontColorWhen(ExcelCellService::$FONT_COLOR_RED, in_array("J", array_keys(@$this->__errors[$row])));

                ExcelRowService::Init()
                    ->SetRow($current_row++)
                    ->SetExcelCells([
                        $code,
                        $category_name,
                        $entire_model_name,
                        $sub_model_name,
                        $fixer_name,
                        $fixed_at,
                        $checker_name,
                        $checked_at,
                        $spot_checker_name,
                        $spot_checked_at,
                    ])
                    ->Write($excel_writer_sheet_error);
            });

        $error_excel_filename = "【上传检修人、验收人错误表】" . now()->format("Y年m月d日 H时i分s秒") . "-" . session("account.nickname");
        $excel_writer_error->save(storage_path("app/public/updateFixerCheckerExcel/$error_excel_filename"));
        $entire_instance_update_fixer_checker_order->error_filename = "updateFixerCheckerExcel/$error_excel_filename.xls";
        $entire_instance_update_fixer_checker_order->saveOrFail();
    }

    /**
     * 处理未赋码Excel
     * @param Collection $un_tagging
     * @return Collection
     * @throws Throwable
     */
    private function handleUnTagging(Collection $un_tagging): Collection
    {
        $kinds = [];
        $new_entire_instances = [];  // 赋码器材
        $new_entire_instance_logs = [];  // 赋码器材日志
        $entire_instance_excel_tagging_identity_codes = [];  // 赋码记录器材
        if ($un_tagging->isNotEmpty()) {
            // 生成赋码记录单
            $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([])
                ->create([
                    'serial_number' => EntireInstanceExcelTaggingReport::generateSerialNumber(),
                    'is_upload_create_device_excel_error' => false,
                    'work_area_type' => session('account.work_area_by_unique_code.type'),
                    'processor_id' => session('account.id'),
                    'work_area_unique_code' => session('account.work_area_by_unique_code.unique_code'),
                    'correct_count' => 0,
                    'fail_count' => 0,
                ]);

            $un_tagging->map(function ($un_tagging_datum)
            use (
                &$un_tagging_by_kinds,
                &$kinds,
                &$new_entire_instances,
                &$new_entire_instance_logs,
                &$entire_instance_excel_tagging_identity_codes,
                $entire_instance_excel_tagging_report
            ) {
                $kind_name = "{$un_tagging_datum["category_name"]}~~~{$un_tagging_datum["entire_model_name"]}~~~{$un_tagging_datum["sub_model_name"]}";
                if (!array_key_exists($kind_name, $kinds)) {
                    $model = DB::table("entire_models as sm")
                        ->selectRaw(join(",", [
                            "c.unique_code as category_unique_code",
                            "c.name as category_name",
                            "sm.unique_code as sub_model_unique_code",
                            "sm.name as sub_model_name",
                        ]))
                        ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                        ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                        ->whereNull("sm.deleted_at")
                        ->whereNull("em.deleted_at")
                        ->whereNull("c.deleted_at")
                        ->where("sm.name", $un_tagging_datum["sub_model_name"])
                        ->where("em.name", $un_tagging_datum["entire_model_name"])
                        ->where("c.name", $un_tagging_datum["category_name"])
                        ->first();

                    if (!$model) throw new EmptyException("型号没有找到：{$kind_name}");
                    $kinds[$kind_name] = $model;
                }

                $new_identity_code = CodeFacade::makeEntireInstanceIdentityCode($kinds[$kind_name]->sub_model_unique_code);

                // 生成赋码器材
                $new_entire_instances[] = [
                    "created_at" => now(),
                    "updated_at" => now(),
                    "identity_code" => $new_identity_code,
                    "serial_number" => $un_tagging_datum["code"],
                    "category_unique_code" => $kinds[$kind_name]->category_unique_code,
                    "category_name" => $kinds[$kind_name]->category_name,
                    "entire_model_unique_code" => $kinds[$kind_name]->sub_model_unique_code,
                    "model_unique_code" => $kinds[$kind_name]->sub_model_unique_code,
                    "model_name" => $kinds[$kind_name]->sub_model_name,
                ];

                // 生成赋码日志
                $new_entire_instance_logs[] = [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'name' => '赋码',
                    'description' => "操作人：" . session("account.nickname"),
                    'entire_instance_identity_code' => $new_identity_code,
                    'type' => 0,
                    'url' => '',
                    'material_type' => 'ENTIRE',
                    'operator_id' => session('account.id'),
                    'station_unique_code' => '',
                ];

                // 生成赋码记录单
                $entire_instance_excel_tagging_identity_codes[] = [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'entire_instance_excel_tagging_report_sn' => $entire_instance_excel_tagging_report->serial_number,
                    'entire_instance_identity_code' => $new_identity_code,
                ];

                $un_tagging_datum["serial_number"] = $un_tagging_datum["code"];
                $un_tagging_datum["identity_code"] = $new_identity_code;
                return $un_tagging_datum;
            });

            // 保存器材
            DB::table("entire_instances")->insert($new_entire_instances);
            // 保存器材日志
            DB::table("entire_instance_logs")->insert($new_entire_instance_logs);
            // 保存器材赋码记录
            DB::table("entire_instance_excel_tagging_identity_codes")->insert($entire_instance_excel_tagging_identity_codes);
            // 修改成功数量
            $entire_instance_excel_tagging_report->fill(["correct_count" => count($new_entire_instances),])->saveOrFail();

            return $un_tagging;
        }

        return collect([]);
    }

    /**
     * 检查唯一编号组正确性并返回正确数据
     * @param Collection $identity_code_data
     * @return Collection
     */
    final private function checkIdentityCodeData(Collection $identity_code_data): Collection
    {
        // 排除不存在的唯一编号
        $no_exists = [];
        if ($identity_code_data->isNotEmpty()) {
            $identity_code_data = $identity_code_data
                ->map(function ($identity_code_datum, $row) use (&$no_exists) {
                    $entire_instance = DB::table("entire_instances")->selectRaw("identity_code, serial_number")->whereNull("deleted_at")->where("identity_code", $identity_code_datum["code"])->first();
                    if ($entire_instance) {
                        $identity_code_datum["identity_code"] = $entire_instance->identity_code;
                        $identity_code_datum["serial_number"] = $entire_instance->serial_number ?: "";
                        return $identity_code_datum;
                    } else {
                        $no_exists[] = $row;
                        return null;
                    }
                })
                ->filter(function ($identity_code_datum) {
                    return !empty($identity_code_datum);
                });
        }
        if (!empty($no_exists)) $this->appendErrorCells(array_values($no_exists), "A", "唯一编号不存在");

        $accounts = Account::with([])->get()->pluck("nickname");
        // 排除未填写检修人
        $this->appendErrorCells($identity_code_data->where("fixer_name", "")->keys()->toArray(), "E", "未填写检修人");
        // 排除错误的检修人
        $diff = $identity_code_data->where("fixer_name", "<>", "")->pluck("fixer_name")->diff($accounts);
        if ($diff) $this->appendErrorCells($identity_code_data->whereIn("fixer_name", $diff->values()->toArray())->keys()->toArray(), "E", "错误的检修人");
        // 排除错误的验收人
        $diff = $identity_code_data->where("checker_name", "<>", "")->pluck("checker_name")->diff($accounts);
        if ($diff) $this->appendErrorCells($identity_code_data->whereIn("checker_name", $diff->values()->toArray())->keys()->toArray(), "G", "错误的验收人");
        // 排除错误的抽验人
        $diff = $identity_code_data->where("spot_checker_name", "<>", "")->pluck("spot_checker_name")->diff($accounts);
        if ($diff) $this->appendErrorCells($identity_code_data->whereIn("checker_name", $diff->values()->toArray())->keys()->toArray(), "I", "错误的抽验人");
        // 排除填写验收时间没写验收人的
        $this->appendErrorCells($identity_code_data->where("checker_name", "")->where("checked_at", "<>", "")->keys()->toArray(), "G", "填写验收时间没写验收人");
        // 排除填写验收人没写验收时间的
        $this->appendErrorCells($identity_code_data->where("checker_name", "<>", "")->where("checked_at", "")->keys()->toArray(), "H", "填写验收人没写验收时间");
        // 排除填写抽验时间没写抽验人的
        $this->appendErrorCells($identity_code_data->where("spot_checker_name", "")->where("spot_checked_at", "<>", "")->keys()->toArray(), "I", "填写抽验时间没写抽验人");
        // 排除填写抽验人没写抽验时间的
        $this->appendErrorCells($identity_code_data->where("spot_checker_name", "<>", "")->where("spot_checked_at", "")->keys()->toArray(), "H", "填写抽验人没写抽验时间");

        // 排除不存在检修时间
        $this->appendErrorCells($identity_code_data->where("fixed_at", "")->keys()->toArray(), "F", "检修时间未填写");
        // 排除错误的检修时间
        $identity_code_data->where("fixed_at", "<>", "")->pluck("fixed_at")->each(function ($fixed_at) use ($identity_code_data) {
            try {
                Carbon::parse($fixed_at);
            } catch (Exception $e) {
                $this->appendErrorCells($identity_code_data->where("fixed_at", $fixed_at)->keys()->toArray(), "F", "时间格式错误");
            }
        });
        // 排除错误的验收时间
        $identity_code_data->where("checked_at", "<>", "")->pluck("checked_at")->each(function ($checked_at) use ($identity_code_data) {
            try {
                Carbon::parse($checked_at);
            } catch (Exception $e) {
                $this->appendErrorCells($identity_code_data->where("checked_at", $checked_at)->keys()->toArray(), "H", "时间格式错误");
            }
        });
        // 排除错误的抽验时间
        $identity_code_data->where("checked_at", "<>", "")->pluck("spot_checked_at")->each(function ($spot_checked_at) use ($identity_code_data) {
            try {
                Carbon::parse($spot_checked_at);
            } catch (Exception $e) {
                $this->appendErrorCells($identity_code_data->where("spot_checked_at", $spot_checked_at)->keys()->toArray(), "J", "时间格式错误");
            }
        });

        return $identity_code_data->except(array_keys($this->__errors));
    }

    /**
     * 检查所编号组正确性并返回正确数据
     * @param Collection $serial_number_data
     * @return array
     */
    final private function checkSerialNumberData(collection $serial_number_data): array
    {
        // 排除未填写种类型
        $this->appendErrorRows($serial_number_data->where("category_name", "")->keys()->toArray());
        $this->appendErrorRows($serial_number_data->where("entire_model_name", "")->keys()->toArray());
        $this->appendErrorRows($serial_number_data->where("sub_model_name", "")->keys()->toArray());

        // 排除种类错误
        $category_names = DB::table("categories")->whereIn("name", $serial_number_data->pluck("category_name")->unique()->values()->toArray())->get();
        $diff = $serial_number_data->pluck("category_name")->unique()->values()->diff($category_names);
        if ($diff) $this->appendErrorCells($serial_number_data->whereIn("category_name", $diff->values()->toArray())->keys()->toArray(), "B", "种类不存在");

        // 排除类型错误
        $serial_number_data
            ->groupBy("category_name")
            ->each(function (Collection $serial_number_entire_models, string $category_name) {
                $entire_model_names = DB::table("entire_models as em")
                    ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                    ->where("em.is_sub_model", false)
                    ->whereNull("em.deleted_at")
                    ->where("c.name", $category_name)
                    ->whereNull("c.deleted_at")
                    ->whereIn("name", $serial_number_entire_models->pluck("entire_model_name")->unique()->values()->toArray())
                    ->get();
                $diff = $serial_number_entire_models->pluck("entire_model_name")->unique()->diff($entire_model_names);
                if ($diff) $this->appendErrorCells($serial_number_entire_models->whereIn("entire_model_name", $diff->values()->toArray())->keys()->toArray(), "C", "类型不存在");

                $serial_number_entire_models
                    ->groupBy("entire_model_name")
                    ->each(function (Collection $serial_number_sub_models, string $entire_model_name) use ($category_name) {
                        $sub_model_names = DB::table("entire_models as sm")
                            ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                            ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                            ->where("sm.is_sub_model", true)
                            ->whereNull("sm.deleted_at")
                            ->where("em.is_sub_model", false)
                            ->whereNull("em.deleted_at")
                            ->whereNull("c.deleted_at")
                            ->where("em.name", $entire_model_name)
                            ->where("c.name", $category_name)
                            ->whereIn("sm.name", $serial_number_sub_models->pluck("sub_model_name")->unique()->values()->toArray())
                            ->get();
                        $diff = $serial_number_sub_models->pluck("sub_model_name")->unique()->diff($sub_model_names);
                        if ($diff) $this->appendErrorCells($serial_number_sub_models->whereIn("sub_model_name", $diff->values()->toArray())->keys()->toArray(), "D", "型号不存在");
                    });
            });

        $tagging_data = [];
        $un_tagging_data = [];
        // 排除不存在的所编号
        $serial_number_data
            ->where("category_name", "<>", "")
            ->where("entire_model_name", "<>", "")
            ->where("sub_model_name", "<>", "")
            ->each(function ($serial_number_datum) use (&$tagging_data, &$un_tagging_data) {
                $entire_instance = DB::table("entire_instances as ei")
                    ->join(DB::raw("entire_models sm"), "ei.model_unique_code", "=", "sm.unique_code")
                    ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                    ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                    ->whereNull("ei.deleted_at")
                    ->whereNull("sm.deleted_at")
                    ->whereNull("em.deleted_at")
                    ->whereNull("c.deleted_at")
                    ->first();
                if (!$entire_instance) {
                    $un_tagging_data[] = $serial_number_datum;
                } else {
                    $serial_number_datum["serial_number"] = $serial_number_datum["code"];
                    $serial_number_datum["identity_code"] = $entire_instance->identity_code;
                    $tagging_data[] = $serial_number_datum;
                }
            });

        $accounts = Account::with([])->get()->pluck("nickname");
        // 排除未填写检修人
        $this->appendErrorCells($serial_number_data->where("fixer_name", "")->keys()->toArray(), "E", "未填写检修人");
        // 排除错误的检修人
        $diff = $serial_number_data->where("fixer_name", "<>", "")->pluck("fixer_name")->diff($accounts);
        if ($diff) $this->appendErrorCells($serial_number_data->whereIn("fixer_name", $diff->values()->toArray())->keys()->toArray(), "E", "错误的检修人");
        // 排除错误的验收人
        $diff = $serial_number_data->where("checker_name", "<>", "")->pluck("checker_name")->diff($accounts);
        if ($diff) $this->appendErrorCells($serial_number_data->whereIn("checker_name", $diff->values()->toArray())->keys()->toArray(), "G", "错误的验收人");
        // 排除错误的抽验人
        $diff = $serial_number_data->where("spot_checker_name", "<>", "")->pluck("spot_checker_name")->diff($accounts);
        if ($diff) $this->appendErrorCells($serial_number_data->whereIn("checker_name", $diff->values()->toArray())->keys()->toArray(), "I", "错误的抽验人");
        // 排除填写验收时间没写验收人的
        $this->appendErrorCells($serial_number_data->where("checker_name", "")->where("checked_at", "<>", "")->keys()->toArray(), "G", "填写验收时间没写验收人");
        // 排除填写验收人没写验收时间的
        $this->appendErrorCells($serial_number_data->where("checker_name", "<>", "")->where("checked_at", "")->keys()->toArray(), "H", "填写验收人没写验收时间");
        // 排除填写抽验时间没写抽验人的
        $this->appendErrorCells($serial_number_data->where("spot_checker_name", "")->where("spot_checked_at", "<>", "")->keys()->toArray(), "I", "填写抽验时间没写抽验人");
        // 排除填写抽验人没写抽验时间的
        $this->appendErrorCells($serial_number_data->where("spot_checker_name", "<>", "")->where("spot_checked_at", "")->keys()->toArray(), "H", "填写抽验人没写抽验时间");

        // 排除不存在检修时间
        $this->appendErrorCells($serial_number_data->where("fixed_at", "")->keys()->toArray(), "F", "未填写检修时间");
        // 排除错误的检修时间
        $serial_number_data->where("fixed_at", "<>", "")->pluck("fixed_at")->each(function ($fixed_at) use ($serial_number_data) {
            try {
                Carbon::parse($fixed_at);
            } catch (Exception $e) {
                $this->appendErrorCells($serial_number_data->where("fixed_at", $fixed_at)->keys()->toArray(), "F", "时间格式错误");
            }
        });
        // 排除错误的验收时间
        $serial_number_data->where("checked_at", "<>", "")->pluck("checked_at")->each(function ($checked_at) use ($serial_number_data) {
            try {
                Carbon::parse($checked_at);
            } catch (Exception $e) {
                $this->appendErrorCells($serial_number_data->where("checked_at", $checked_at)->keys()->toArray(), "H", "时间格式错误");
            }
        });
        // 排除错误的抽验时间
        $serial_number_data->where("checked_at", "<>", "")->pluck("spot_checked_at")->each(function ($spot_checked_at) use ($serial_number_data) {
            try {
                Carbon::parse($spot_checked_at);
            } catch (Exception $e) {
                $this->appendErrorCells($serial_number_data->where("spot_checked_at", $spot_checked_at)->keys()->toArray(), "J", "时间格式错误");
            }
        });

        return [
            "tagging_data" => collect($tagging_data),
            "un_tagging_data" => collect($un_tagging_data),
        ];
    }
}
