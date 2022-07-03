<?php

namespace App\Console\Commands;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Facades\OrganizationFacade;
use App\Facades\SyncFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\Line;
use App\Model\Maintain;
use App\Model\WarehouseReport;
use App\Model\WarehouseReportEntireInstance;
use App\Model\WorkArea;
use App\Services\ExcelCellService;
use App\Services\ExcelReaderService;
use App\Services\ExcelRowService;
use App\Services\ExcelWriterService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPExcel_Exception;
use PHPExcel_Reader_Exception;
use PHPExcel_Writer_Exception;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync {operation} {arg1?}';

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
     * @param string|null $work_area_unique_code
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final private function entireInstanceToExcel(?string $work_area_unique_code): void
    {
        $excel = ExcelWriterService::Init();
        $sheet = $excel->GetSheet();
        $current_row = 2;

        // created_at 创建时间
        // updated_at 更新时间
        // full_kind 完整种类型
        // entire_model_unique_code 类型名称
        // entire_model_id_code ×
        // serial_number 所编号
        // status 状态
        // maintain_station_name 车站名称
        // maintain_location_code 上道位置代码
        // work_area ×
        // is_main ×
        // factory_name 供应商名称
        // factory_device_code 出厂编号
        // identity_code 唯一编号
        // last_installed_time 上道时间
        // in_warehouse_time 入库时间
        // category_unique_code ×
        // category_name ×
        // fix_workflow_serial_number ×
        // last_warehouse_report_serial_number_by_out ×
        // is_flush_serial_number ×
        // next_auto_making_fix_workflow_time ×
        // next_fixing_time 下次周期修时间
        // next_auto_making_fix_workflow_at ×
        // next_fixing_month ×
        // next_fixing_day ×
        // fix_cycle_unit ×
        // fix_cycle_value ×
        // cycle_fix_count ×
        // un_cycle_fix_count ×
        // made_at 出厂日期
        // scarping_at 到期日期
        // residue_use_year ×
        // old_number ×
        // purpose ×
        // warehouse_name ×
        // location_unique_code ×
        // to_direction ×
        // crossroad_number 道岔号
        // traction ×
        // source ×
        // source_crossroad_number ×
        // source_traction ×
        // forecast_install_at ×
        // line_unique_code 线别代码
        // line_name 线制
        // open_direction 开向
        // said_rod 表示干特征
        // scarped_note ×
        // railway_name ×
        // section_name ×
        // base_name ×
        // rfid_code ×
        // scene_workshop_status ×
        // rfid_epc ×
        // note 备注
        // before_fixed_at ×
        // before_fixer_name ×
        // useable_year ×
        // crossroad_type 道岔类型
        // allocated_to ×
        // allocated_at ×
        // point_switch_group_type ×
        // extrusion_protect 防挤压装置
        // model_unique_code ×
        // model_name ×
        // in_warehouse ×
        // in_warehouse_breakdown_explain ×
        // last_fix_workflow_at ×
        // is_rent ×
        // emergency_identity_code ×
        // bind_device_code ×
        // bind_device_type_code ×
        // bind_device_type_name ×
        // bind_crossroad_number ×
        // bind_crossroad_id ×
        // bind_station_name ×
        // bind_station_code ×
        // last_out_at 出所时间
        // is_bind_location ×
        // maintain_workshop_name 现场车间名称
        // last_take_stock_at ×
        // last_repair_material_id ×
        // warehousein_at ×
        // v250_task_order_sn ×
        // work_area_unique_code 工区代码
        // is_overhaul ×
        // fixer_name 检修人姓名
        // fixed_at 检修时间
        // checker_name 验收人姓名
        // checked_at 验收时间
        // spot_checker_name 抽验人姓名
        // spot_checked_at 抽验时间
        // life_year ×
        // last_maintain_location_code ×
        // last_crossroad_number ×
        // last_open_direction ×
        // last_maintain_station_name ×
        // last_maintain_workshop_name ×
        // asset_code 物资编号
        // fixed_asset_code 固资编号
        // entire_instance_identity_code ×
        // is_part ×
        // part_model_unique_code ×
        // part_model_name ×
        // part_category_id ×
        // last_installed_at ×
        // is_emergency ×
        // source_type 来源类型
        // source_name 来源名称
        // old_model_unique_code ×
        // old_entire_model_unique_code ×
        // old_category_unique_code ×
        // maintain_section_name 区间名称
        // old_model_name ×
        // old_category_name ×
        // railroad_grade_cross_unique_code ×
        // centre_unique_code ×
        // maintain_work_area_unique_code 现场工区代码
        // last_scene_breakdown_description ×
        // last_line_unique_code ×
        // lock_type ×
        // lock_description ×
        // deleter_id ×
        // last_maintain_section_name ×
        // maintain_send_or_receive 送/受端
        // last_maintain_send_or_receive ×

        // 制作表头
        ExcelRowService::Init()
            ->SetRow(1)
            ->SetExcelCells([
                ExcelCellService::Init("创建时间"),
                ExcelCellService::Init("更新时间"),
                ExcelCellService::Init("种类名称"),
                ExcelCellService::Init("种类代码"),
                ExcelCellService::Init("类型名称"),
                ExcelCellService::Init("类型代码"),
                ExcelCellService::Init("型号名称"),
                ExcelCellService::Init("型号代码"),
                ExcelCellService::Init("唯一编号"),
                ExcelCellService::Init("所编号"),
                ExcelCellService::Init("状态"),
                ExcelCellService::Init("车间名称"),
                ExcelCellService::Init("车间代码"),
                ExcelCellService::Init("车站名称"),
                ExcelCellService::Init("车站代码"),
                ExcelCellService::Init("上道位置_车站名称"),
                ExcelCellService::Init("上道位置_车站代码"),
                ExcelCellService::Init("上道位置_机房类型名称"),
                ExcelCellService::Init("上道位置_机房类型代码"),
                ExcelCellService::Init("上道位置_排名称"),
                ExcelCellService::Init("上道位置_排代码"),
                ExcelCellService::Init("上道位置_架名称"),
                ExcelCellService::Init("上道位置_架代码"),
                ExcelCellService::Init("上道位置_层名称"),
                ExcelCellService::Init("上道位置_层代码"),
                ExcelCellService::Init("上道位置_位名称"),
                ExcelCellService::Init("上道位置_位代码"),
                ExcelCellService::Init("供应商名称"),
                ExcelCellService::Init("供应商名代码"),
                ExcelCellService::Init("出厂编号"),
                ExcelCellService::Init("上道时间"),
                ExcelCellService::Init("入库时间"),
                ExcelCellService::Init("下次周期修时间"),
                ExcelCellService::Init("出厂日期"),
                ExcelCellService::Init("到期日期"),
                ExcelCellService::Init("道岔号"),
                ExcelCellService::Init("线别名称"),
                ExcelCellService::Init("线别代码"),
                ExcelCellService::Init("线制"),
                ExcelCellService::Init("开向"),
                ExcelCellService::Init("表示干特征"),
                ExcelCellService::Init("备注"),
                ExcelCellService::Init("道岔类型"),
                ExcelCellService::Init("防挤压装置"),
                ExcelCellService::Init("出所时间"),
                ExcelCellService::Init("所属专业工区名称"),
                ExcelCellService::Init("所属专业工区代码"),
                ExcelCellService::Init("检修人姓名"),
                ExcelCellService::Init("检修时间"),
                ExcelCellService::Init("验收人姓名"),
                ExcelCellService::Init("验收时间"),
                ExcelCellService::Init("抽验人姓名"),
                ExcelCellService::Init("抽验时间"),
                ExcelCellService::Init("物资编号"),
                ExcelCellService::Init("固资编号"),
                ExcelCellService::Init("来源类型"),
                ExcelCellService::Init("来源名称"),
                ExcelCellService::Init("寿命"),
                ExcelCellService::Init("现场备注"),
            ])
            ->Write($sheet);

        EntireInstance::with([
            "Category",
            "SubModel",
            "SubModel.Parent",
            "Factory",
            "WorkArea",
            "InstallPosition",
            "InstallPosition.WithInstallTier",
            "InstallPosition.WithInstallTier.WithInstallShelf",
            "InstallPosition.WithInstallTier.WithInstallShelf.WithInstallPlatoon",
            "InstallPosition.WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom",
            "InstallPosition.WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation",
        ])
            ->where("category_unique_code", "Q07")
            ->where("work_area_unique_code", "<>", $work_area_unique_code)
            ->chunk(500, function ($entire_instances) use (&$excel, &$sheet, &$current_row) {
                $entire_instances->each(function ($entire_instance) use (&$sheet, &$current_row) {
                    dump($current_row);
                    // 填充数据
                    ExcelRowService::Init()
                        ->SetRow($current_row++)
                        ->SetExcelCells([
                            ExcelCellService::Init(@$entire_instance->created_at),  // 创建时间
                            ExcelCellService::Init(@$entire_instance->updated_at),  // 更新时间
                            ExcelCellService::Init(@$entire_instance->full_kind->category_name ?: ""),  // 种类名称
                            ExcelCellService::Init(@$entire_instance->full_kind->category_unique_code ?: ""),  // 种类代码
                            ExcelCellService::Init(@$entire_instance->full_kind->entire_model_name ?: ""),  // 类型名称
                            ExcelCellService::Init(@$entire_instance->full_kind->entire_model_unique_code ?: ""),  // 类型代码
                            ExcelCellService::Init(@$entire_instance->full_kind->sub_model_name ?: ""),  // 型号名称
                            ExcelCellService::Init(@$entire_instance->full_kind->sub_model_unique_code ?: ""),  // 型号代码
                            ExcelCellService::Init(@$entire_instance->identity_code),  // 唯一编号
                            ExcelCellService::Init(@$entire_instance->serial_number),  // 所编号
                            ExcelCellService::Init(@$entire_instance->status),  // 状态
                            ExcelCellService::Init(@$entire_instance->SceneWorkshop->name ?: ""),  // 车间名称
                            ExcelCellService::Init(@$entire_instance->SceneWorkshop->unique_code ?: ""),  // 车间代码
                            ExcelCellService::Init(@$entire_instance->Station->name ?: ""),  // 车站名称
                            ExcelCellService::Init(@$entire_instance->Station->unique_code ?: ""),  // 车站代码
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name ?: ""),  // 上道位置_车站名称
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->unique_code ?: ""),  // 上道位置_车站代码
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text ?: ""),  // 上道位置_机房类型名称
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->value ?: ""),  // 上道位置_机房类型代码
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->name ?: ""),  // 上道位置_排名称
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->unique_code ?: ""),  // 上道位置_排代码
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->name ?: ""),  // 上道位置_架名称
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->unique_code ?: ""),  // 上道位置_架代码
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->name ?: ""),  // 上道位置_层名称
                            ExcelCellService::Init(@$entire_instance->InstallPosition->WithInstallTier->unique_code ?: ""),  // 上道位置_层代码
                            ExcelCellService::Init(@$entire_instance->InstallPosition->name ?: ""),  // 上道位置_位名称
                            ExcelCellService::Init(@$entire_instance->InstallPosition->unique_code ?: ""),  // 上道位置_位代码
                            ExcelCellService::Init(@$entire_instance->Factory->name),  // 供应商名称
                            ExcelCellService::Init(@$entire_instance->Factory->unique_code),  // 供应商名代码
                            ExcelCellService::Init(@$entire_instance->factory_device_code),  // 出厂编号
                            ExcelCellService::Init(@$entire_instance->installed_at),  // 上道时间
                            ExcelCellService::Init(@$entire_instance->warehousein_at),  // 入库时间
                            ExcelCellService::Init(@$entire_instance->next_fixing_day),  // 下次周期修时间
                            ExcelCellService::Init(@$entire_instance->made_at),  // 出厂日期
                            ExcelCellService::Init(@$entire_instance->scarping_at),  // 到期日期
                            ExcelCellService::Init(@$entire_instance->crossroad_number),  // 道岔号
                            ExcelCellService::Init(@$entire_instance->Line->name ?: ""),  // 线别名称
                            ExcelCellService::Init(@$entire_instance->Line->unique_code ?: ""),  // 线别代码
                            ExcelCellService::Init(@$entire_instance->line_name),  // 线制
                            ExcelCellService::Init(@$entire_instance->open_direction),  // 开向
                            ExcelCellService::Init(@$entire_instance->said_rod),  // 表示干特征
                            ExcelCellService::Init(@$entire_instance->note),  // 备注
                            ExcelCellService::Init(@$entire_instance->crossroad_type),  // 道岔类型
                            ExcelCellService::Init(@$entire_instance->extrusion_protect),  // 防挤压装置
                            ExcelCellService::Init(@$entire_instance->last_out_at),  // 出所时间
                            ExcelCellService::Init(@$entire_instance->WorkArea->name),  // 所属专业工区名称
                            ExcelCellService::Init(@$entire_instance->WorkArea->unique_code),  // 所属专业工区代码
                            ExcelCellService::Init(@$entire_instance->fixer_name),  // 检修人姓名
                            ExcelCellService::Init(@$entire_instance->fixed_at),  // 检修时间
                            ExcelCellService::Init(@$entire_instance->checker_name),  // 验收人姓名
                            ExcelCellService::Init(@$entire_instance->checked_at),  // 验收时间
                            ExcelCellService::Init(@$entire_instance->spot_checker_name),  // 抽验人姓名
                            ExcelCellService::Init(@$entire_instance->spot_checked_at),  // 抽验时间
                            ExcelCellService::Init(@$entire_instance->asset_code),  // 物资编号
                            ExcelCellService::Init(@$entire_instance->fixed_asset_code),  // 固资编号
                            ExcelCellService::Init(@$entire_instance->source_type),  // 来源类型
                            ExcelCellService::Init(@$entire_instance->source_name),  // 来源名称
                            ExcelCellService::Init(@$entire_instance->SubModel->life_year),  // 寿命
                            ExcelCellService::Init(@$entire_instance->maintain_location_code),  // 现场备注
                        ])
                        ->Write($sheet);
                });
            });

        $work_area = WorkArea::with([])->where("unique_code", $work_area_unique_code)->first();
        $organization_name = env("ORGANIZATION_NAME");
        $excel->Save(public_path("{$organization_name}-{$work_area->name}-电源屏器材"));
        $this->info("导出：{$organization_name}-{$work_area->name}-电源屏器材 完成。");
    }

    /**
     * @param string|null $work_area_unique_code
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final private function entireInstanceLogToExcel(?string $work_area_unique_code)
    {
        $excel = ExcelWriterService::Init();
        $sheet = $excel->GetSheet();
        $current_row = 2;

        // created_at
        // updated_at
        // name
        // description
        // entire_instance_identity_code
        // type
        // url
        // material_type
        // operator_id
        // station_unique_code

        // 制作表头
        ExcelRowService::Init()
            ->SetRow(1)
            ->SetExcelCells([
                ExcelCellService::Init("创建时间"),
                ExcelCellService::Init("更新时间"),
                ExcelCellService::Init("日志名称"),
                ExcelCellService::Init("日志描述"),
                ExcelCellService::Init("所属器材编号"),
                ExcelCellService::Init("类型"),
                ExcelCellService::Init("操作人_账号"),
                ExcelCellService::Init("操作人_昵称"),
                ExcelCellService::Init("车站名称"),
                ExcelCellService::Init("车站代码"),
            ])
            ->Write($sheet);

        $identity_codes = EntireInstance::with([])
            ->where("category_unique_code", "Q07")
            ->where("work_area_unique_code", "<>", $work_area_unique_code)
            ->pluck("identity_code")
            ->toArray();

        EntireInstanceLog::with(["EntireInstance"])
            ->whereIn("entire_instance_identity_code", $identity_codes)
            ->chunk(500, function ($entire_instance_logs) use (&$sheet, &$current_row) {
                $entire_instance_logs->each(function ($entire_instance_log) use (&$sheet, &$current_row) {
                    dump($current_row);
                    ExcelRowService::Init()
                        ->SetRow($current_row++)
                        ->SetExcelCells([
                            ExcelCellService::Init(@$entire_instance_log->created_at),  // 创建时间
                            ExcelCellService::Init(@$entire_instance_log->updated_at),  // 更新时间
                            ExcelCellService::Init(@$entire_instance_log->name),  // 日志名称
                            ExcelCellService::Init(@$entire_instance_log->description),  // 日志描述
                            ExcelCellService::Init(@$entire_instance_log->entire_instance_identity_code),  // 所属器材编号
                            ExcelCellService::Init(@$entire_instance_log->type),  // 类型
                            ExcelCellService::Init(@$entire_instance_log->Operator->account),  // 操作人_账号
                            ExcelCellService::Init(@$entire_instance_log->Operator->nickname),  // 操作人_昵称
                            ExcelCellService::Init(@$entire_instance_log->Station->name),  // 车站名称
                            ExcelCellService::Init(@$entire_instance_log->Station->unique_code),  // 车站代码
                        ])
                        ->Write($sheet);
                });
            });

        $work_area = WorkArea::with([])->where("unique_code", $work_area_unique_code)->first();
        $organization_name = env("ORGANIZATION_NAME");
        $excel->Save(public_path("{$organization_name}-{$work_area->name}-电源屏日志"));
        $this->info("导出：{$organization_name}-{$work_area->name}-电源屏日志 完成。");
    }

    /**
     * @param string|null $work_area_unique_code
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final public function warehouseReportToExcel(?string $work_area_unique_code)
    {
        $excel = ExcelWriterService::Init();
        $sheet = $excel->GetSheet();
        $current_row = 2;

        // 制作表头
        ExcelRowService::Init()
            ->SetRow(1)
            ->SetExcelCells([
                ExcelCellService::Init("ID"),
                ExcelCellService::Init("创建时间"),
                ExcelCellService::Init("更新时间"),
                ExcelCellService::Init("处理人_账号"),
                ExcelCellService::Init("处理人_昵称"),
                ExcelCellService::Init("联系人"),
                ExcelCellService::Init("联系电话"),
                ExcelCellService::Init("出入所单类型"),
                ExcelCellService::Init("方向"),
                ExcelCellService::Init("出入所单号"),
                ExcelCellService::Init("车间名称"),
                ExcelCellService::Init("车间代码"),
                ExcelCellService::Init("车站抿成"),
                ExcelCellService::Init("车站抿成"),
                ExcelCellService::Init("状态"),
                ExcelCellService::Init("所属专业工区名称"),
                ExcelCellService::Init("所属专业工区代码"),
            ])
            ->Write($sheet);

        // 制作表头
        ExcelRowService::Init()
            ->SetRow(1)
            ->SetExcelCells([])
            ->Write($sheet);

        $warehouse_report_serial_numbers = DB::table("warehouse_reports as wr")
            ->selectRaw("wr.serial_number")
            ->join(DB::raw("warehouse_report_entire_instances wrei"), "wr.serial_number", "=", "wrei.warehouse_report_serial_number")
            ->join(DB::raw("entire_instances ei"), "wrei.entire_instance_identity_code", "=", "ei.identity_code")
            ->where("ei.work_area_unique_code", $work_area_unique_code)
            ->pluck("serial_number")
            ->toArray();
        $warehouse_report_serial_numbers = array_values(array_unique($warehouse_report_serial_numbers));

        WarehouseReport::with([])
            ->whereIn("serial_number", $warehouse_report_serial_numbers)
            ->chunk(50, function ($warehouse_reports) use (&$sheet, &$current_row) {
                $warehouse_reports->each(function ($warehouse_report) use (&$sheet, &$current_row) {
                    ExcelRowService::Init()
                        ->SetRow($current_row++)
                        ->SetExcelCells([
                            ExcelCellService::Init(@$warehouse_report->id),  // 创建时间
                            ExcelCellService::Init(@$warehouse_report->created_at),  // 创建时间
                            ExcelCellService::Init(@$warehouse_report->updated_at),  // 更新时间
                            ExcelCellService::Init(@$warehouse_report->Processor->account),  // 处理人_账号
                            ExcelCellService::Init(@$warehouse_report->Processor->nickname),  // 处理人_昵称
                            ExcelCellService::Init(@$warehouse_report->connection_name),  // 联系人
                            ExcelCellService::Init(@$warehouse_report->connection_phone),  // 联系电话
                            ExcelCellService::Init(@$warehouse_report->type),  // 出入所单类型
                            ExcelCellService::Init(@$warehouse_report->direction),  // 方向
                            ExcelCellService::Init(@$warehouse_report->serial_number),  // 出入所单号
                            ExcelCellService::Init(@$warehouse_report->Workshop->name),  // 车间名称
                            ExcelCellService::Init(@$warehouse_report->Workshop->unique_code),  // 车间代码
                            ExcelCellService::Init(@$warehouse_report->Station->name),  // 车站抿成
                            ExcelCellService::Init(@$warehouse_report->Station->unique_code),  // 车站抿成
                            ExcelCellService::Init(@$warehouse_report->status),  // 状态
                            ExcelCellService::Init(@$warehouse_report->WorkArea->name),  // 所属专业工区名称
                            ExcelCellService::Init(@$warehouse_report->WorkArea->unique_code),  // 所属专业工区代码
                        ])
                        ->Write($sheet);
                });
            });

        $excel->Save(public_path("怀化-出入所单"));
        $this->info("导出：怀化-出入所单 完成。");
    }

    /**
     * @param string|null $work_area_unique_code
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final public function warehouseReportEntireInstanceToExcel(?string $work_area_unique_code)
    {
        $excel = ExcelWriterService::Init();
        $sheet = $excel->GetSheet();
        $current_row = 2;

        // 制作表头
        ExcelRowService::Init()
            ->SetRow(1)
            ->SetExcelCells([
                ExcelCellService::init("创建时间"),
                ExcelCellService::init("更新时间"),
                ExcelCellService::init("出入所单编号"),
                ExcelCellService::init("器材编号"),
                ExcelCellService::init("车间名称"),
                ExcelCellService::init("车间代码"),
                ExcelCellService::init("车站名称"),
                ExcelCellService::init("车站代码"),
                ExcelCellService::init("线别名称"),
                ExcelCellService::init("线别代码"),
                ExcelCellService::init("上道位置_车站名称"),
                ExcelCellService::init("上道位置_车站代码"),
                ExcelCellService::init("上道位置_机房类型名称"),
                ExcelCellService::init("上道位置_机房类型代码"),
                ExcelCellService::init("上道位置_排名称"),
                ExcelCellService::init("上道位置_排代码"),
                ExcelCellService::init("上道位置_架名称"),
                ExcelCellService::init("上道位置_架代码"),
                ExcelCellService::init("上道位置_层名称"),
                ExcelCellService::init("上道位置_层代码"),
                ExcelCellService::init("上道位置_位名称"),
                ExcelCellService::init("上道位置_位代码"),
                ExcelCellService::init("道岔号"),
                ExcelCellService::init("线制"),
                ExcelCellService::init("道岔类型"),
                ExcelCellService::init("防挤压装置"),
                ExcelCellService::init("开向"),
                ExcelCellService::init("表示干特征"),
            ])
            ->Write($sheet);

        $warehouse_report_entire_instance_ids = DB::table("warehouse_report_entire_instances as wrei")
            ->selectRaw("wrei.id")
            ->join(DB::raw("entire_instances ei"), "wrei.entire_instance_identity_code", "=", "ei.identity_code")
            ->where("ei.work_area_unique_code", $work_area_unique_code)
            ->pluck("id")
            ->toArray();

        WarehouseReportEntireInstance::with([])
            ->whereIn("id", $warehouse_report_entire_instance_ids)
            ->chunk(500, function ($warehouse_report_entire_instances) use (&$sheet, &$current_row) {
                $warehouse_report_entire_instances->each(function ($warehouse_report_entire_instance) use (&$sheet, &$current_row) {
                    dump($current_row);
                    ExcelRowService::Init()
                        ->SetRow($current_row++)
                        ->SetExcelCells([
                            ExcelCellService::Init(@$warehouse_report_entire_instance->created_at),  // 创建时间
                            ExcelCellService::Init(@$warehouse_report_entire_instance->updated_at),  // 更新时间
                            ExcelCellService::Init(@$warehouse_report_entire_instance->warehouse_report_serial_number),  // 出入所单编号
                            ExcelCellService::Init(@$warehouse_report_entire_instance->entire_instance_identity_code),  // 器材编号
                            ExcelCellService::Init(@$warehouse_report_entire_instance->Workshop->name),  // 车间名称
                            ExcelCellService::Init(@$warehouse_report_entire_instance->Workshop->unique_code),  // 车间代码
                            ExcelCellService::Init(@$warehouse_report_entire_instance->Station->name),  // 车站名称
                            ExcelCellService::Init(@$warehouse_report_entire_instance->Station->unique_code),  // 车站代码
                            ExcelCellService::Init(@$warehouse_report_entire_instance->Line->name),  // 线别名称
                            ExcelCellService::Init(@$warehouse_report_entire_instance->Line->unique_code),  // 线别代码
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name ?: ""),  // 上道位置_车站名称
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->unique_code ?: ""),  // 上道位置_车站代码
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text ?: ""),  // 上道位置_机房类型名称
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->value ?: ""),  // 上道位置_机房类型代码
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->name ?: ""),  // 上道位置_排名称
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->WithInstallPlatoon->unique_code ?: ""),  // 上道位置_排代码
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->name ?: ""),  // 上道位置_架名称
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->WithInstallShelf->unique_code ?: ""),  // 上道位置_架代码
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->name ?: ""),  // 上道位置_层名称
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->WithInstallTier->unique_code ?: ""),  // 上道位置_层代码
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->name ?: ""),  // 上道位置_位名称
                            ExcelCellService::Init(@$warehouse_report_entire_instance->InstallPosition->unique_code ?: ""),  // 上道位置_位代码
                            ExcelCellService::Init(@$warehouse_report_entire_instance->crossroad_number),  // 道岔号
                            ExcelCellService::Init(@$warehouse_report_entire_instance->line_name),  // 线制
                            ExcelCellService::Init(@$warehouse_report_entire_instance->crossroad_type),  // 道岔类型
                            ExcelCellService::Init(@$warehouse_report_entire_instance->extrusion_protect),  // 防挤压装置
                            ExcelCellService::Init(@$warehouse_report_entire_instance->open_direction),  // 开向
                            ExcelCellService::Init(@$warehouse_report_entire_instance->said_rod),  // 表示干特征
                        ])
                        ->Write($sheet);
                });
            });

        $excel->Save(public_path("怀化-出入所器材"));
        $this->info("导出：怀化-出入所器材 完成。");
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final public function installPositionToExcel(): void
    {
        $excel = ExcelWriterService::Init();
        $sheet = $excel->GetSheet();
        $current_row = 2;

        ExcelRowService::Init()
            ->SetRow(1)
            ->SetExcelCells([
                ExcelCellService::Init("上道位置_车站名称"),
                ExcelCellService::Init("上道位置_车站代码"),
                ExcelCellService::Init("上道位置_机房类型代码"),
                ExcelCellService::Init("上道位置_机房类型"),
                ExcelCellService::Init("上道位置_排名称"),
                ExcelCellService::Init("上道位置_排代码"),
                ExcelCellService::Init("上道位置_架名称"),
                ExcelCellService::Init("上道位置_架代码"),
                ExcelCellService::Init("上道位置_层名称"),
                ExcelCellService::Init("上道位置_层代码"),
                ExcelCellService::Init("上道位置_位名称"),
                ExcelCellService::Init("上道位置_位代码"),
            ])
            ->Write($sheet);

        // $a = DB::table("install_positions as ip")
        //     ->selectRaw(implode(",", [
        //         "s.unique_code as su",
        //         "s.name as sn",
        //         "ir.unique_code as ru",
        //         "ir.type as rt",
        //         "ip2.unique_code as ip2u",
        //         "ip2.name as ip2n",
        //         "is.unique_code as isu",
        //         "is.name as isn",
        //         "it.unique_code as itu",
        //         "it.name as itn",
        //         "ip.unique_code as ipu",
        //         "ip.name as ipn",
        //     ]))
        //     ->join(DB::raw("install_tiers it"), "ip.install_tier_unique_code", "=", "it.unique_code")
        //     ->join(DB::raw("install_shelves `is`"), "it.install_shelf_unique_code", "=", "is.unique_code")
        //     ->join(DB::raw("install_platoons ip2"), "is.install_platoon_unique_code", "=", "ip2.unique_code")
        //     ->join(DB::raw("install_rooms ir"), "ip2.install_room_unique_code", "=", "ir.unique_code")
        //     ->join(DB::raw("maintains s"), "ir.station_unique_code", "=", "s.unique_code")
        //     ->where("ir.type", "12")
        //     ->first();
        // dd($a);

        DB::table("install_positions as ip")
            ->selectRaw(implode(",", [
                "s.unique_code as su",
                "s.name as sn",
                "ir.unique_code as ru",
                "ir.type as rt",
                "ip2.unique_code as ip2u",
                "ip2.name as ip2n",
                "is.unique_code as isu",
                "is.name as isn",
                "it.unique_code as itu",
                "it.name as itn",
                "ip.unique_code as ipu",
                "ip.name as ipn",
            ]))
            ->join(DB::raw("install_tiers it"), "ip.install_tier_unique_code", "=", "it.unique_code")
            ->join(DB::raw("install_shelves `is`"), "it.install_shelf_unique_code", "=", "is.unique_code")
            ->join(DB::raw("install_platoons ip2"), "is.install_platoon_unique_code", "=", "ip2.unique_code")
            ->join(DB::raw("install_rooms ir"), "ip2.install_room_unique_code", "=", "ir.unique_code")
            ->join(DB::raw("maintains s"), "ir.station_unique_code", "=", "s.unique_code")
            ->where("ir.type", "12")
            ->orderBy("s.unique_code")
            ->orderBy("ir.unique_code")
            ->orderBy("ip2.unique_code")
            ->orderBy("is.unique_code")
            ->orderBy("it.unique_code")
            ->orderBy("ip.unique_code")
            ->chunk(500, function ($data) use (&$current_row, &$sheet) {
                $data->each(function ($datum) use (&$current_row, &$sheet) {
                    dump($current_row);
                    ExcelRowService::Init()
                        ->SetRow($current_row++)
                        ->SetExcelCells([
                            ExcelCellService::Init($datum->sn),  // 上道位置_车站名称
                            ExcelCellService::Init($datum->su),  // 上道位置_车站代码
                            ExcelCellService::Init($datum->ru),  // 上道位置_机房代码
                            ExcelCellService::Init($datum->rt),  // 上道位置_机房类型
                            ExcelCellService::Init($datum->ip2n),  // 上道位置_排名称
                            ExcelCellService::Init($datum->ip2u),  // 上道位置_排代码
                            ExcelCellService::Init($datum->isn),  // 上道位置_架名称
                            ExcelCellService::Init($datum->isu),  // 上道位置_架代码
                            ExcelCellService::Init($datum->itn),  // 上道位置_层名称
                            ExcelCellService::Init($datum->itu),  // 上道位置_层代码
                            ExcelCellService::Init($datum->ipn),  // 上道位置_位名称
                            ExcelCellService::Init($datum->ipu),  // 上道位置_位代码
                        ])
                        ->Write($sheet);
                });
            });


        // InstallPosition::with([
        //     "WithInstallTier",
        //     "WithInstallTier.WithInstallShelf",
        //     "WithInstallTier.WithInstallShelf.WithInstallPlatoon",
        //     "WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom",
        //     "WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation",
        // ])
        //     ->whereHas("WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom", function ($WithInstallRoom) {
        //         $WithInstallRoom->where("type", "12");
        //     })
        //     ->chunk(500, function ($install_positions) use (&$current_row, &$sheet) {
        //         $install_positions->each(function ($install_position) use (&$current_row, &$sheet) {
        //             ExcelRowService::Init()
        //                 ->SetRow($current_row++)
        //                 ->SetExcelCells([
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name ?: ""),  // 上道位置_车站名称
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->unique_code ?: ""),  // 上道位置_车站代码
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text ?: ""),  // 上道位置_机房类型名称
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->unique_code ?: ""),  // 上道位置_机房代码
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->value ?: ""),  // 上道位置_机房类型代码
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->name ?: ""),  // 上道位置_排名称
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->unique_code ?: ""),  // 上道位置_排代码
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->WithInstallShelf->name ?: ""),  // 上道位置_架名称
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->WithInstallShelf->unique_code ?: ""),  // 上道位置_架代码
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->name ?: ""),  // 上道位置_层名称
        //                     ExcelCellService::Init(@$install_position->WithInstallTier->unique_code ?: ""),  // 上道位置_层代码
        //                     ExcelCellService::Init(@$install_position->name ?: ""),  // 上道位置_位名称
        //                     ExcelCellService::Init(@$install_position->unique_code ?: ""),  // 上道位置_位代码
        //                 ])
        //                 ->Write($sheet);
        //         });
        //     });

        $excel->Save(public_path("怀化-上道位置"));
        $this->info("导出：怀化-上道位置 完成。");
    }

    /**
     * 根据工区删除器材
     * @param $work_area_unique_code
     */
    final private function deleteEntireInstance($work_area_unique_code)
    {
        DB::table("entire_instances")
            ->where("work_area_unique_code", $work_area_unique_code)
            ->update(["deleted_at" => now(), "note" => "因电源屏工区移交电子车间删除",]);
    }

    /**
     * 来源名称同步到段中心
     */
    final private function sourceNameToParagraphCenter()
    {
        SyncFacade::SourceNamesToParagraphCenter();
    }

    /**
     * 同步供应商数据 车间 → 段中心
     * @param string|null $paragraph_code
     */
    final private function factoryToParagraphCenter(?string $paragraph_code): void
    {
        SyncFacade::FactoriesToParagraphCenter($paragraph_code);
        $this->info("供应商数据同步到段中心：完成");
    }

    /**
     * 同步供应商数据 excel → 车间
     * @param string $filename
     */
    final private function factoryFromExcel(string $filename): void
    {
        SyncFacade::FactoriesFromExcel(storage_path($filename));

        $this->info("同步供应商数据 excel → 车间：完成");
    }

    /**
     * 同步车站 车间 → excel
     * @param string $filename
     */
    final private function stationToExcel(string $filename): void
    {
        $organization_name = env("ORGANIZATION_NAME");
        SyncFacade::StationsToExcel(storage_path($filename));

        $this->info("同步车站数据 车间 → excel($organization_name)：完成");
    }

    /**
     * 同步车站 excel → 车间 修改名称
     * @throws PHPExcel_Reader_Exception
     * @throws Exception
     */
    final private function stationFromExcelForUpdateName(): void
    {
        $organizationCode = env("ORGANIZATION_CODE");
        $organization_name = env("ORGANIZATION_NAME");

        // 备份器材对应车站的数量
        $deviceCountWithStation = DB::table("entire_instances as ei")
            ->selectRaw("count(s.id) as aggregate, s.id as station_id")
            ->join(DB::raw("maintains s"), "ei.maintain_station_name", "=", "s.name")
            ->where("ei.maintain_station_name", "<>", "")
            ->groupBy(["ei.maintain_station_name"])
            ->get()
            ->pluck("aggregate", "station_id");
        file_put_contents(storage_path("deviceCountWithStation.json"), $deviceCountWithStation->toJson(256));

        if (env("ORGANIZATION_CODE") === "B049") {
            $this->comment("长沙特殊处理：常德站 → 常德高速场");
            OrganizationFacade::UpdateStationNameById(342, "常德高速场");


        }

        if (env("ORGANIZATION_CODE") === "B048") {
            $this->comment("广州特殊处理：深圳北线路所 → 阳台山");
            OrganizationFacade::UpdateStationNameById(236, "阳台山");
            DB::table("maintains")->where("id", 236)->delete();
        }

        if (env("ORGANIZATION_CODE") === "B053") {
            $this->comment("肇庆特殊处理：江门高铁信号车间 → 江门高铁车间");
            DB::table("entire_instances")->where("maintain_station_name", "江门高铁信号车间")->update(["maintain_station_name" => "",]);
            DB::table("maintains")->where("id", 217)->delete();
        }

        // 第一阶段：去掉最后一个'站'字
        $this->comment("第一阶段：去掉最后一个'站'字");
        Maintain::with([])
            ->where("type", "STATION")
            ->where("name", "like", "%站")
            ->each(function ($station) {
                $new_name = rtrim($station->name, "站");
                $this->comment("修改车站名称：{$station->name} → {$new_name}");
                OrganizationFacade::UpdateStationNameById($station->id, $new_name);
            });

        // 第二阶段：根据excel同步名称
        $this->comment("第二阶段：根据excel同步名称");
        $excel_reader = ExcelReaderService::File(storage_path(strtolower($organizationCode) . "-stations.xls"))
            ->SetOriginRow(2)
            ->SetFinishColNumber(6)
            ->ReadBySheetIndex();
        $excel_data = $excel_reader->GetData(["编号", "原始名称", "线别", "线别代码", "新名称", "是否需要新增"]);

        if ($excel_data->where("是否需要新增", "")->isNotEmpty()) {
            $excel_data->where("是否需要新增", "")
                ->each(function ($excel_datum) {
                    $this->comment("修改车站名称：{$excel_datum["编号"]} → ~~~{$excel_datum["新名称"]}");
                    OrganizationFacade::UpdateStationNameById($excel_datum["编号"], "~~~{$excel_datum["新名称"]}");
                });
        }

        // 第三阶段：去掉'~~~'
        $this->comment("第三阶段：去掉'~~~'");
        Maintain::with([])->where("name", "like", "~~~%")
            ->each(function ($station) {
                $new_name = ltrim($station->name, "~~~");
                $this->comment("修改车站名称：{$station->name} → {$new_name}");
                OrganizationFacade::UpdateStationNameById($station->id, $new_name);
            });

        $this->info("同步车站 excel → 车间 修改名称（{$organization_name}）：完成");
    }

    /**
     * 同步车站 excel → 车间 修改代码
     * @throws PHPExcel_Reader_Exception
     * @throws Exception
     */
    final private function stationFromExcelForUpdateUniqueCode(): void
    {
        $organization_name = env("ORGANIZATION_NAME");
        $organization_code = env("ORGANIZATION_CODE");

        $excel_reader = ExcelReaderService::File(storage_path("6.21附件3.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColNumber(9)
            ->ReadBySheetIndex();
        $excel_data = $excel_reader->GetData(["序号", "车", "原车站编号", "车站编号", "线别", "线别编号", "所属站段", "修订说明", "段编号"]);

        // 第一阶段：修改编号
        if ($excel_data->where("段编号", $organization_code)->isNotEmpty()) {
            $this->comment("第一阶段：修改编号");
            DB::transaction(function () use ($excel_data, $organization_code, &$excel_writer_sheet, &$current_row) {
                $excel_data
                    ->where("段编号", $organization_code)
                    ->each(function ($excel_datum) use (&$excel_writer_sheet, &$current_row) {
                        $station = Maintain::with([])
                            ->where("type", "STATION")
                            ->where("name", $excel_datum["车"])
                            ->first();
                        if ($station) {
                            if ($station->unique_code != $excel_datum["车站编号"]) {
                                $this->comment("修改编号：{$station->id} {$station->unique_code} → ~~~{$excel_datum["车站编号"]}");
                                OrganizationFacade::UpdateStationUniqueCodeById($station->id, "~~~{$excel_datum["车站编号"]}");
                            }
                        } else {
                            // 如果车站不存在，则增加该车站（无车间）
                            $this->comment("增加车站：{$excel_datum["车"]}");
                            Maintain::with([])->create([
                                "name" => $excel_datum["车"],
                                "unique_code" => "~~~{$excel_datum["车站编号"]}",
                                "type" => "STATION",
                                "is_show" => true,
                            ]);
                        }
                    });
            });
        }

        // 第二阶段
        DB::transaction(function () {
            $this->comment("第二阶段：去掉'~~~'");
            Maintain::with([])
                ->where("type", "STATION")
                ->where("unique_code", "like", "~~~%")
                ->each(function ($station) {
                    $new_unique_code = ltrim($station->unique_code, "~~~");
                    $this->comment("修改编码：{$station->id} {$station->unique_code} → {$new_unique_code}");
                    OrganizationFacade::UpdateStationUniqueCodeById($station->id, $new_unique_code);
                });
        });

        $this->info("同步车站 excel → 车间 修改代码（{$organization_name}）：完成");
    }

    /**
     * 同步车站 Excel → 车间 找到附件三有，系统里没有的
     * @throws PHPExcel_Reader_Exception
     * @throws Exception
     */
    final private function stationFromExcelForFindEx(string $filename): void
    {
        $organization_code = env("ORGANIZATION_CODE");
        $excel_reader = ExcelReaderService::File(storage_path("6.21附件3.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColNumber(9)
            ->ReadBySheetIndex();
        $excel_data = $excel_reader->GetData(["序号", "车", "原车站编号", "车站编号", "线别", "线别编号", "所属站段", "修订说明", "段编号"]);

        $excel_writer = ExcelWriterService::Init();
        $excel_writer_sheet = $excel_writer->GetSheet();
        $current_row = 1;

        // 设置表头
        ExcelRowService::Init()
            ->SetRow($current_row++)
            ->SetExcelCells([
                ExcelCellService::Init("序号"),
                ExcelCellService::Init("车"),
                ExcelCellService::Init("原车站编号"),
                ExcelCellService::Init("车站编号"),
                ExcelCellService::Init("线别"),
                ExcelCellService::Init("线别编号"),
                ExcelCellService::Init("所属站段"),
                ExcelCellService::Init("修订说明"),
                ExcelCellService::Init("所属车间"),
            ])
            ->Write($excel_writer_sheet);

        $stations = Maintain::with([])->where("type", "STATION")->get();
        $diff = $excel_data->where("段编号", $organization_code)->pluck("车", "序号")->diff($stations);

        $excel_data
            ->whereIn("序号", $diff->keys()->toArray())
            ->each(function ($excel_datum) use (&$excel_writer_sheet, &$current_row) {
                $this->comment("记录没有的车站：{$excel_datum["车"]}");
                ExcelRowService::Init()
                    ->SetRow($current_row++)
                    ->SetExcelCells([
                        ExcelCellService::Init($excel_datum["序号"]),
                        ExcelCellService::Init($excel_datum["车"]),
                        ExcelCellService::Init($excel_datum["原车站编号"]),
                        ExcelCellService::Init($excel_datum["车站编号"]),
                        ExcelCellService::Init($excel_datum["线别"]),
                        ExcelCellService::Init($excel_datum["线别编号"]),
                        ExcelCellService::Init($excel_datum["所属站段"]),
                        ExcelCellService::Init($excel_datum["修订说明"]),
                    ])
                    ->Write($excel_writer_sheet);
            });

        $excel_writer->Save(storage_path($filename));
    }

    /**
     * 同步车站 Excel → 车间 同步车站与线别对应关系
     * @throws PHPExcel_Exception
     * @throws Exception
     */
    final private function stationFromExcelForBindLine(): void
    {
        $organizationCode = env("ORGANIZATION_CODE");

        $excelReader = ExcelReaderService::File(storage_path("6.21附件3.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColText("I")
            ->ReadBySheetIndex();
        $excelData = $excelReader->GetData(["序号", "车", "原车站编号", "车站编号", "线别", "线别编号", "所属站段", "修订说明", "段编号"]);

        if ($excelData->where("段编号", $organizationCode)->where("线别", "<>", "")->isNotEmpty()) {
            $linesMaintains = [];

            DB::transaction(function () use ($excelData, $organizationCode, &$linesMaintains) {
                DB::table("lines_maintains")->truncate();

                $excelData
                    ->where("段编号", $organizationCode)
                    ->where("线别", "<>", "")
                    ->each(function ($excelDatum) use (&$linesMaintains) {
                        $maintain = Maintain::with([])->where("unique_code", $excelDatum["车站编号"])->where("type", "STATION")->first();
                        if (!$maintain) throw new EmptyException("车站不存在：{$excelDatum["车"]}");
                        $line = Line::with([])->where("unique_code", $excelDatum["线别编号"])->first();
                        if (!$line) dd("线别不存在", $excelDatum);
                        if (!$line) throw new EmptyException("线别不存在：{$excelDatum["线别"]}");

                        $linesMaintains[] = [
                            "lines_id" => $line->id,
                            "maintains_id" => $maintain->id,
                            "line_unique_code" => $line->unique_code,
                            "line_name" => $line->name,
                            "station_unique_code" => $maintain->unique_code,
                            "station_name" => $maintain->name,
                        ];
                    });

                DB::table("lines_maintains")->insert($linesMaintains);
                $this->info("绑定线别：完成");
            });
        }

    }

    /**
     * 同步线别 车间 → excel
     * @param string $filename
     */
    final private function lineToExcel(string $filename): void
    {
        $organization_name = env("ORGANIZATION_NAME");
        SyncFacade::LinesToExcel(storage_path($filename));

        $this->info("同步线别数据 车间 → excel($organization_name)：完成");
    }

    /**
     * 同步线别 excel → 车间
     * @param string $filename
     */
    final private function lineFromExcel(): void
    {
        $organization_name = env("ORGANIZATION_NAME");
        SyncFacade::LinesFromExcel(storage_path("lines.xlsx"));

        $this->info("同步线别 excel → 车间($organization_name)：完成");
    }

    /**
     * 同步车间数据 车间 → excel
     * @param string $filename
     */
    final private function workshopToExcel(string $filename): void
    {
        $organization_name = env("ORGANIZATION_NAME");
        SyncFacade::WorkshopsToExcel(storage_path($filename));

        $this->info("同步车间数据 excel → 车间($organization_name)：完成");
    }

    /**
     * 同步车间数据 车间 → 段中心
     */
    final public function workshopToParagraphCenter(): void
    {
        SyncFacade::WorkshopsToParagraphCenter("b048");
        $this->info("同步车间数据 车间 → 段中心(广州)：完成");
        SyncFacade::WorkshopsToParagraphCenter("b049");
        $this->info("同步车间数据 车间 → 段中心(长沙)：完成");
        SyncFacade::WorkshopsToParagraphCenter("b050");
        $this->info("同步车间数据 车间 → 段中心(怀化)：完成");
        SyncFacade::WorkshopsToParagraphCenter("b051");
        $this->info("同步车间数据 车间 → 段中心(衡阳)：完成");
        SyncFacade::WorkshopsToParagraphCenter("b052");
        $this->info("同步车间数据 车间 → 段中心(惠州)：完成");
        SyncFacade::WorkshopsToParagraphCenter("b053");
        $this->info("同步车间数据 车间 → 段中心(肇庆)：完成");
        SyncFacade::WorkshopsToParagraphCenter("b074");
        $this->info("同步车间数据 车间 → 段中心(海口)：完成");
    }

    /**
     * 同步车间数据 excel → 车间
     * @throws ForbiddenException
     */
    final public function workshopFromExcel(): void
    {
        $organizationCode = env("ORGANIZATION_CODE");
        $organizationName = env("ORGANIZATION_NAME");

        // 备份器材对应车站的数量
        $deviceCountWithWorkshop = DB::table("entire_instances")
            ->selectRaw("count(maintain_workshop_name) as aggregate, maintain_workshop_name")
            ->where("maintain_workshop_name", "<>", "")
            ->groupBy(["maintain_workshop_name"])
            ->get()
            ->pluck("aggregate", "maintain_workshop_name");
        file_put_contents(storage_path("deviceCountWithWorkshop.json"), $deviceCountWithWorkshop->toJson(256));

        // 第一阶段：补改车间信息
        $this->comment("第一阶段：补改车间信息");
        DB::transaction(function () use ($organizationCode) {
            $excelReader = ExcelReaderService::File(storage_path("workshops.xlsx"))
                ->SetOriginRow(2)
                ->SetFinishColText("N")
                ->ReadBySheetIndex()
                ->GetData([
                    "id", "created_at", "updated_at", "unique_code", "name", "paragraph_unique_code", "type",
                    "subject", "lon", "lat", "contact", "contact_phone", "contact_address", "is_show",
                ]);

            if ($excelReader->isNotEmpty()) {
                // 修改车间对应数据
                $excelReader->where("paragraph_unique_code", $organizationCode)
                    ->each(function ($excelDatum) {
                        $maintain = Maintain::with([])
                            ->where("type", "<>", "STATION")
                            ->where("name", $excelDatum["name"])
                            ->first();

                        if ($maintain) {
                            if ($maintain->unique_code != $excelDatum["unique_code"]) {
                                $this->comment("修改车间对应数据：{$maintain->unique_code} → ~~~{$excelDatum["unique_code"]}");
                                OrganizationFacade::UpdateWorkshopUniqueCodeById($maintain->id, "~~~{$excelDatum["unique_code"]}");
                            }
                        }
                    });

                // 修改车间数据
                $excelReader->where("paragraph_unique_code", $organizationCode)
                    ->each(function ($excelDatum) use ($organizationCode) {
                        $maintain = Maintain::with([])
                            ->where("type", "<>", "STATION")
                            ->where("name", $excelDatum["name"])
                            ->first();

                        if ($maintain) {
                            $this->comment("修改车间数据：{$maintain->unique_code} → ~~~{$excelDatum["unique_code"]}");
                            $maintain
                                ->fill([
                                    "created_at" => $excelDatum["created_at"],
                                    "updated_at" => now(),
                                    "unique_code" => "~~~{$excelDatum["unique_code"]}",
                                    "parent_unique_code" => @$excelDatum["paragraph_unique_code"] ?: "",
                                    "type" => @array_flip(Maintain::$TYPES_TO_PARAGRAPH_CENTER)[$excelDatum["type"]] ?: "",
                                    "lon" => @$excelDatum["lon"] ?: "",
                                    "lat" => @$excelDatum["lat"] ?: "",
                                    "contact" => @$excelDatum["contact"] ?: "",
                                    "contact_phone" => @$excelDatum["contact_phone"] ?: "",
                                    "contact_address" => @$excelDatum["contact_address"] ?: "",
                                    "is_show" => in_array($excelDatum["type"], [1, 2]),
                                ])
                                ->saveOrFail();
                        } else {
                            $this->comment("新建车间：{$excelDatum["name"]} {$excelDatum["unique_code"]}");
                            Maintain::with([])
                                ->insert([
                                    "created_at" => $excelDatum["created_at"],
                                    "updated_at" => now(),
                                    "unique_code" => "~~~{$excelDatum["unique_code"]}",
                                    "name" => $excelDatum["name"],
                                    "parent_unique_code" => @$excelDatum["paragraph_unique_code"] ?: "",
                                    "type" => @array_flip(Maintain::$TYPES_TO_PARAGRAPH_CENTER)[$excelDatum["type"]] ?: "",
                                    "lon" => @$excelDatum["lon"] ?: "",
                                    "lat" => @$excelDatum["lat"] ?: "",
                                    "contact" => @$excelDatum["contact"] ?: "",
                                    "contact_phone" => @$excelDatum["contact_phone"] ?: "",
                                    "contact_address" => @$excelDatum["contact_address"] ?: "",
                                    "is_show" => in_array($excelDatum["type"], [1, 2]),
                                ]);
                        }
                    });
            }
        });

        // 第二阶段：去掉'~~~'
        DB::transaction(function () {
            Maintain::with([])
                ->where("type", "<>", "STATION")
                ->get()
                ->each(function ($workshop) {
                    $new_unique_code = ltrim($workshop->unique_code, "~~~");
                    $this->comment("修改车间代码：{$workshop->unique_code} → {$new_unique_code}");
                    OrganizationFacade::UpdateWorkshopUniqueCodeById($workshop->id, $new_unique_code);
                });
        });

        $this->info("同步车间 excel → 车间({$organizationName})：完成");
    }

    /**
     * 检车车站器材正确性
     */
    final public function checkStation(): void
    {
        $this->comment("开始检查车站器材正确性");
        $deviceCountWithStation = json_decode(file_get_contents(storage_path("deviceCountWithStation.json")), true);
        // 第四阶段：检查
        DB::table("entire_instances as ei")
            ->selectRaw("count(s.id) as aggregate, s.id as station_id")
            ->join(DB::raw("maintains s"), "ei.maintain_station_name", "=", "s.name")
            ->where("ei.maintain_station_name", "<>", "")
            ->groupBy(["ei.maintain_station_name"])
            ->get()
            ->pluck("aggregate", "station_id")
            ->each(function ($__aggregate, $__stationId) use ($deviceCountWithStation) {
                if (array_key_exists($__stationId, $deviceCountWithStation)) {
                    $aggregate = $deviceCountWithStation[$__stationId];
                    if ($__aggregate != $aggregate) {
                        $this->error("车站比对器材数据错误：{$__stationId} {$__aggregate} → {$aggregate}");
                    } else {
                        $this->comment("车站比对器材数据{$__stationId}：正确");
                    }
                }
            });
        $this->info("车站器材比对数据正确");
    }

    /**
     * 检查车间器材正确性
     * @throws ForbiddenException
     */
    final private function checkWorkshop(): void
    {
        $this->comment("开始检查车间器材正确性");
        $deviceCountWithWorkshop = json_decode(file_get_contents(storage_path("deviceCountWithWorkshop.json")), true);
        DB::table("entire_instances")
            ->selectRaw("count(maintain_workshop_name) as aggregate, maintain_workshop_name")
            ->where("maintain_workshop_name", "<>", "")
            ->groupBy(["maintain_workshop_name"])
            ->get()
            ->pluck("aggregate", "maintain_workshop_name")
            ->each(function ($__aggregate, $__workshopId) use ($deviceCountWithWorkshop) {
                if (!array_key_exists($__workshopId, $deviceCountWithWorkshop)) throw new ForbiddenException("比对器材数据错误：{$__workshopId}不存在");
                $aggregate = $deviceCountWithWorkshop[$__workshopId];
                if ($__aggregate != $aggregate) {
                    $this->error("车间比对器材数据错误：{$__workshopId} {$__aggregate} → {$aggregate}");
                } else {
                    $this->error("车间比对器材数据{$__workshopId}：正确");
                }
            });
        $this->info("车间器材比对数据正确");
    }

    /**
     * 同步车间、车站、线别
     */
    final public function LWS(): void
    {
        $organizationName = env("ORGANIZATION_NAME");

        $this->comment("开始同步车间、车站、线别：$organizationName");

        collect([
            "init:statement 2.6.10-2",
            // "init:statement 2.6.10-3",
            "maintain-clear-up lines",
            "maintain-clear-up workshops",
            "maintain-clear-up findWorkshopName",
            "sync lineFromExcel",
            "sync workshopFromExcel",
            "sync checkWorkshop",
            "sync stationFromExcelForUpdateName",
            "sync stationFromExcelForUpdateUniqueCode",
            "sync stationFromExcelForBindLine",
            "sync checkStation",
        ])
            ->each(
                function ($shell) {
                    $this->comment(shell_exec("php artisan $shell"));
                }
            );
        $this->info("同步完成");
    }

    /**
     * 同步上道位置 检修车间 → 电子车间
     */
    final private function installLocationToElectricWorkshop(): void
    {
        SyncFacade::InstallLocationToElectricWorkshop(now(), now());
    }

    /**
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws Exception
     */
    final public function installLocationFromExcelForCount()
    {
        $organization_code = env("ORGANIZATION_CODE");
        $organization_name = env("ORGANIZATION_NAME");
        $excel_reader = ExcelReaderService::File(storage_path("installLocationSync/{$organization_code}-install-location.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColText("O")
            ->ReadBySheetIndex()
            ->Close();
        $excel_data = $excel_reader->GetData(["车间名称", "车间编码", "车站名称", "车站编码", "机房名称", "机房编码", "机房类型", "排名称", "排编码", "架名称", "架编码", "层名称", "层编码", "位名称", "位编码"]);

        $install_room_count = $excel_data->groupBy("机房编码")->keys()->unique()->values()->count();
        $install_platoon_count = $excel_data->groupBy("排编码")->keys()->unique()->values()->count();
        $install_position_count = $excel_data->pluck("位编码")->unique()->values()->count();

        $install_position_repeat_count = DB::table("install_positions")->whereIn("unique_code",$excel_data->pluck("位编码")->unique()->values()->toArray())->count();

        $this->info("$organization_name 机房总数：{$install_room_count} 排总数：{$install_platoon_count} 位总数：{$install_position_count} 位重复数：{$install_position_repeat_count}");
    }

    /**
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws Exception
     */
    final private function installLocationFromExcelForCheck(): void
    {
        $organization_code = env("ORGANIZATION_CODE");
        $organization_name = env("ORGANIZATION_NAME");
        $excel_reader = ExcelReaderService::File(storage_path("installLocationSync/{$organization_code}-install-location.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColText("O")
            ->ReadBySheetIndex()
            ->Close();
        $excel_data = $excel_reader->GetData(["车间名称", "车间编码", "车站名称", "车站编码", "机房名称", "机房编码", "机房类型", "排名称", "排编码", "架名称", "架编码", "层名称", "层编码", "位名称", "位编码"]);

        // 机房表格
        $excel_writer_repeat_install_room = ExcelWriterService::Init(ExcelWriterService::$VERSION_2007);
        $excel_writer_repeat_install_room_sheet = $excel_writer_repeat_install_room->GetSheet();
        $excel_writer_repeat_install_room_row = 1;
        // 制作表头
        ExcelRowService::Init()
            ->SetRow($excel_writer_repeat_install_room_row++)
            ->SetExcelCells([
                ExcelCellService::Init("车间名称"),
                ExcelCellService::Init("车间编码"),
                ExcelCellService::Init("车站名称"),
                ExcelCellService::Init("车站编码"),
                ExcelCellService::Init("机房名称"),
                ExcelCellService::Init("机房编码"),
                ExcelCellService::Init("机房类型"),
            ])
            ->Write($excel_writer_repeat_install_room_sheet);
        $excel_writer_without_repeat_install_room = ExcelWriterService::Init(ExcelWriterService::$VERSION_2007);
        $excel_writer_without_repeat_install_room_sheet = $excel_writer_without_repeat_install_room->GetSheet();
        $excel_writer_without_repeat_install_room_row = 1;
        // 制作表头
        ExcelRowService::Init()
            ->SetRow($excel_writer_without_repeat_install_room_row++)
            ->SetExcelCells([
                ExcelCellService::Init("车间名称"),
                ExcelCellService::Init("车间编码"),
                ExcelCellService::Init("车站名称"),
                ExcelCellService::Init("车站编码"),
                ExcelCellService::Init("机房名称"),
                ExcelCellService::Init("机房编码"),
                ExcelCellService::Init("机房类型"),
            ])
            ->Write($excel_writer_without_repeat_install_room_sheet);
        // 检查机房是否重复
        $excel_data->groupBy("机房编码")
            ->each(function (Collection $data, string $install_room_unique_code)
            use (
                &$excel_writer_repeat_install_room_sheet,
                &$excel_writer_repeat_install_room_row,
                &$excel_writer_without_repeat_install_room_sheet,
                &$excel_writer_without_repeat_install_room_row
            ) {
                if (DB::table("install_rooms")->where("unique_code", $install_room_unique_code)->exists()) {
                    $this->comment("机房重复：{$install_room_unique_code}");
                    ExcelRowService::Init()
                        ->SetRow($excel_writer_repeat_install_room_row++)
                        ->SetExcelCells([
                            ExcelCellService::Init($data->first()["车间名称"]),
                            ExcelCellService::Init($data->first()["车间编码"]),
                            ExcelCellService::Init($data->first()["车站名称"]),
                            ExcelCellService::Init($data->first()["车站编码"]),
                            ExcelCellService::Init($data->first()["机房名称"]),
                            ExcelCellService::Init($data->first()["机房编码"]),
                            ExcelCellService::Init($data->first()["机房类型"]),
                        ])
                        ->Write($excel_writer_repeat_install_room_sheet);
                } else {
                    ExcelRowService::Init()
                        ->SetRow($excel_writer_without_repeat_install_room_row++)
                        ->SetExcelCells([
                            ExcelCellService::Init($data->first()["车间名称"]),
                            ExcelCellService::Init($data->first()["车间编码"]),
                            ExcelCellService::Init($data->first()["车站名称"]),
                            ExcelCellService::Init($data->first()["车站编码"]),
                            ExcelCellService::Init($data->first()["机房名称"]),
                            ExcelCellService::Init($data->first()["机房编码"]),
                            ExcelCellService::Init($data->first()["机房类型"]),
                        ])
                        ->Write($excel_writer_without_repeat_install_room_sheet);
                }
            });
        // 保存机房排查结果
        $excel_writer_repeat_install_room->Save(storage_path("installLocationSync/$organization_name-机房重复"));
        $excel_writer_without_repeat_install_room->Save(storage_path("installLocationSync/$organization_name-机房不重复"));

        // 排表格
        $excel_writer_repeat_install_platoon = ExcelWriterService::Init(ExcelWriterService::$VERSION_2007);
        $excel_writer_repeat_install_platoon_sheet = $excel_writer_repeat_install_platoon->GetSheet();
        $excel_writer_repeat_install_platoon_row = 1;
        // 制作表头
        ExcelRowService::Init()
            ->SetRow($excel_writer_repeat_install_platoon_row++)
            ->SetExcelCells([
                ExcelCellService::Init("车间名称"),
                ExcelCellService::Init("车间编码"),
                ExcelCellService::Init("车站名称"),
                ExcelCellService::Init("车站编码"),
                ExcelCellService::Init("机房名称"),
                ExcelCellService::Init("机房编码"),
                ExcelCellService::Init("机房类型"),
                ExcelCellService::Init("排名称"),
                ExcelCellService::Init("排编码"),
            ])
            ->Write($excel_writer_repeat_install_platoon_sheet);
        $excel_writer_without_repeat_install_platoon = ExcelWriterService::Init(ExcelWriterService::$VERSION_2007);
        $excel_writer_without_repeat_install_platoon_sheet = $excel_writer_without_repeat_install_platoon->GetSheet();
        $excel_writer_without_repeat_install_platoon_row = 1;
        // 制作表头
        ExcelRowService::Init()
            ->SetRow($excel_writer_without_repeat_install_platoon_row++)
            ->SetExcelCells([
                ExcelCellService::Init("车间名称"),
                ExcelCellService::Init("车间编码"),
                ExcelCellService::Init("车站名称"),
                ExcelCellService::Init("车站编码"),
                ExcelCellService::Init("机房名称"),
                ExcelCellService::Init("机房编码"),
                ExcelCellService::Init("机房类型"),
                ExcelCellService::Init("排名称"),
                ExcelCellService::Init("排编码"),
            ])
            ->Write($excel_writer_without_repeat_install_platoon_sheet);
        // 检查排是否重复
        $excel_data->groupBy("排编码")
            ->each(function (Collection $data, string $install_platoon_unique_code)
            use (
                &$excel_writer_repeat_install_platoon_sheet,
                &$excel_writer_repeat_install_platoon_row,
                &$excel_writer_without_repeat_install_platoon_sheet,
                &$excel_writer_without_repeat_install_platoon_row
            ) {
                if (DB::table("install_platoons")->where("unique_code", $install_platoon_unique_code)->exists()) {
                    $this->comment("排重复：{$install_platoon_unique_code}");
                    ExcelRowService::Init()
                        ->SetRow($excel_writer_repeat_install_platoon_row++)
                        ->SetExcelCells([
                            ExcelCellService::Init($data->first()["车间名称"]),
                            ExcelCellService::Init($data->first()["车间编码"]),
                            ExcelCellService::Init($data->first()["车站名称"]),
                            ExcelCellService::Init($data->first()["车站编码"]),
                            ExcelCellService::Init($data->first()["机房名称"]),
                            ExcelCellService::Init($data->first()["机房编码"]),
                            ExcelCellService::Init($data->first()["机房类型"]),
                            ExcelCellService::Init($data->first()["排名称"]),
                            ExcelCellService::Init($data->first()["排编码"]),
                        ])
                        ->Write($excel_writer_repeat_install_platoon_sheet);
                } else {
                    $this->info("排不重复：{$install_platoon_unique_code}");
                    ExcelRowService::Init()
                        ->SetRow($excel_writer_without_repeat_install_platoon_row++)
                        ->SetExcelCells([
                            ExcelCellService::Init($data->first()["车间名称"]),
                            ExcelCellService::Init($data->first()["车间编码"]),
                            ExcelCellService::Init($data->first()["车站名称"]),
                            ExcelCellService::Init($data->first()["车站编码"]),
                            ExcelCellService::Init($data->first()["机房名称"]),
                            ExcelCellService::Init($data->first()["机房编码"]),
                            ExcelCellService::Init($data->first()["机房类型"]),
                            ExcelCellService::Init($data->first()["排名称"]),
                            ExcelCellService::Init($data->first()["排编码"]),
                        ])
                        ->Write($excel_writer_without_repeat_install_platoon_sheet);
                }
            });
        // 保存排排查结果
        $excel_writer_repeat_install_platoon->Save(storage_path("installLocationSync/$organization_name-排重复"));
        $excel_writer_without_repeat_install_platoon->Save(storage_path("installLocationSync/$organization_name-排不重复"));

        // 位表格
        $excel_writer_repeat_install_position = ExcelWriterService::Init(ExcelWriterService::$VERSION_2007);
        $excel_writer_repeat_install_position_sheet = $excel_writer_repeat_install_position->GetSheet();
        $excel_writer_repeat_install_position_row = 1;
        // 制作表头
        ExcelRowService::Init()
            ->SetRow($excel_writer_repeat_install_position_row++)
            ->SetExcelCells([
                ExcelCellService::Init("车间名称"),
                ExcelCellService::Init("车间编码"),
                ExcelCellService::Init("车站名称"),
                ExcelCellService::Init("车站编码"),
                ExcelCellService::Init("机房名称"),
                ExcelCellService::Init("机房编码"),
                ExcelCellService::Init("机房类型"),
                ExcelCellService::Init("排名称"),
                ExcelCellService::Init("排编码"),
                ExcelCellService::Init("架名称"),
                ExcelCellService::Init("架编码"),
                ExcelCellService::Init("层名称"),
                ExcelCellService::Init("层编码"),
                ExcelCellService::Init("位名称"),
                ExcelCellService::Init("位编码"),
            ])
            ->Write($excel_writer_repeat_install_position_sheet);
        $excel_writer_without_repeat_install_position = ExcelWriterService::Init(ExcelWriterService::$VERSION_2007);
        $excel_writer_without_repeat_install_position_sheet = $excel_writer_without_repeat_install_position->GetSheet();
        $excel_writer_without_repeat_install_position_row = 1;
        // 制作表头
        ExcelRowService::Init()
            ->SetRow($excel_writer_without_repeat_install_position_row++)
            ->SetExcelCells([
                ExcelCellService::Init("车间名称"),
                ExcelCellService::Init("车间编码"),
                ExcelCellService::Init("车站名称"),
                ExcelCellService::Init("车站编码"),
                ExcelCellService::Init("机房名称"),
                ExcelCellService::Init("机房编码"),
                ExcelCellService::Init("机房类型"),
                ExcelCellService::Init("排名称"),
                ExcelCellService::Init("排编码"),
                ExcelCellService::Init("架名称"),
                ExcelCellService::Init("架编码"),
                ExcelCellService::Init("层名称"),
                ExcelCellService::Init("层编码"),
                ExcelCellService::Init("位名称"),
                ExcelCellService::Init("位编码"),
            ])
            ->Write($excel_writer_without_repeat_install_position_sheet);
        // 检查位是否重复
        $excel_data->each(function (array $datum)
        use (
            &$excel_writer_repeat_install_position_sheet,
            &$excel_writer_repeat_install_position_row,
            &$excel_writer_without_repeat_install_position_sheet,
            &$excel_writer_without_repeat_install_position_row
        ) {
            if (DB::table("install_positions")->where("unique_code", $datum["位编码"])->exists()) {
                $this->comment("位重复：{$datum["位编码"]}");
                ExcelRowService::Init()
                    ->SetRow($excel_writer_repeat_install_position_row++)
                    ->SetExcelCells([
                        ExcelCellService::Init($datum["车间名称"]),
                        ExcelCellService::Init($datum["车间编码"]),
                        ExcelCellService::Init($datum["车站名称"]),
                        ExcelCellService::Init($datum["车站编码"]),
                        ExcelCellService::Init($datum["机房名称"]),
                        ExcelCellService::Init($datum["机房编码"]),
                        ExcelCellService::Init($datum["机房类型"]),
                        ExcelCellService::Init($datum["排名称"]),
                        ExcelCellService::Init($datum["排编码"]),
                        ExcelCellService::Init($datum["架名称"]),
                        ExcelCellService::Init($datum["架编码"]),
                        ExcelCellService::Init($datum["层名称"]),
                        ExcelCellService::Init($datum["层编码"]),
                        ExcelCellService::Init($datum["位名称"]),
                        ExcelCellService::Init($datum["位编码"]),
                    ])
                    ->Write($excel_writer_repeat_install_position_sheet);
            } else {
                ExcelRowService::Init()
                    ->SetRow($excel_writer_without_repeat_install_position_row++)
                    ->SetExcelCells([
                        ExcelCellService::Init($datum["车间名称"]),
                        ExcelCellService::Init($datum["车间编码"]),
                        ExcelCellService::Init($datum["车站名称"]),
                        ExcelCellService::Init($datum["车站编码"]),
                        ExcelCellService::Init($datum["机房名称"]),
                        ExcelCellService::Init($datum["机房编码"]),
                        ExcelCellService::Init($datum["机房类型"]),
                        ExcelCellService::Init($datum["排名称"]),
                        ExcelCellService::Init($datum["排编码"]),
                        ExcelCellService::Init($datum["架名称"]),
                        ExcelCellService::Init($datum["架编码"]),
                        ExcelCellService::Init($datum["层名称"]),
                        ExcelCellService::Init($datum["层编码"]),
                        ExcelCellService::Init($datum["位名称"]),
                        ExcelCellService::Init($datum["位编码"]),
                    ])
                    ->Write($excel_writer_without_repeat_install_position_sheet);
            }
        });
        // 保存位查结果
        $excel_writer_repeat_install_position->Save(storage_path("installLocationSync/$organization_name-位重复"));
        $excel_writer_without_repeat_install_position->Save(storage_path("installLocationSync/$organization_name-位不重复"));
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws Exception
     */
    final private function installLocationFromExcelForInstallRoom()
    {
        $organization_code = env("ORGANIZATION_CODE");
        $excel_reader = ExcelReaderService::File(storage_path("installLocationSync/{$organization_code}-install-location.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColText("O")
            ->ReadBySheetIndex()
            ->Close();
        $excel_data = $excel_reader->GetData(["车间名称", "车间编码", "车站名称", "车站编码", "机房名称", "机房编码", "机房类型", "排名称", "排编码", "架名称", "架编码", "层名称", "层编码", "位名称", "位编码"]);

        $repeat_install_room_unique_codes = [];
        if ($excel_data->isNotEmpty()) {
            // 检查机房是否重复
            $excel_data
                ->groupBy("机房编码")
                ->each(function (Collection $data, string $install_room_unique_code)
                use (&$excel_writer_sheet, &$repeat_install_room_unique_codes) {
                    if (DB::table("install_rooms")->where("unique_code", $install_room_unique_code)->exists()) {
                        $repeat_install_room_unique_codes[] = $install_room_unique_code;
                        $this->comment("机房重复：$install_room_unique_code");
                    } else {
                        $data->each(function (array $datum) {
                            if (!DB::table("maintains")->where("unique_code", $datum["车站编码"])->exists()) {
                                $this->error("车站不存在：{$datum["车站编码"]}");
                                throw new EmptyException("车站不存在");
                            }
                        });
                        // if(!DB::table("maintains")->where("unique_code",$excel_datum["车站编码"])->exists()) {
                        //     $this->error("车站不存在")
                        // }
                        // if($excel_datum[""]){
                        //
                        // }
                    }
                });
        }

        file_put_contents(storage_path("installLocationSync/{$organization_code}.json"), json_encode($repeat_install_room_unique_codes));
    }

    /**
     * 同步上道位置：机房 Excel ➡ 车间
     */
    final private function installRoomFromExcel()
    {
        DB::beginTransaction();
        try {
            $organization_code = env("ORGANIZATION_CODE");
            $table_name = "install_rooms";
            $excel_reader = ExcelReaderService::File(storage_path("installLocationSync/{$organization_code}-{$table_name}.xlsx"))
                ->SetOriginRow(2)
                ->SetFinishColText("G")
                ->ReadBySheetIndex();
            $excel_data = $excel_reader->GetData(["id", "created_at", "updated_at", "unique_code", "station_unique_code", "type", "name"]);

            $excel_writer = ExcelWriterService::Init();
            $excel_writer_sheet = $excel_writer->GetSheet();
            $excel_writer_row = 1;

            // 设置表头
            ExcelRowService::Init()
                ->SetRow($excel_writer_row++)
                ->SetExcelCells([
                    ExcelCellService::Init("id"),
                    ExcelCellService::Init("created_at"),
                    ExcelCellService::Init("updated_at"),
                    ExcelCellService::Init("unique_code"),
                    ExcelCellService::Init("station_unique_code"),
                    ExcelCellService::Init("type"),
                    ExcelCellService::Init("name"),
                ])
                ->Write($excel_writer_sheet);

            if ($excel_data->isNotEmpty()) {
                $excel_data->each(function ($excel_datum, $excel_reader_row) use ($table_name, &$excel_writer_sheet, &$excel_writer_row) {
                    $station = Maintain::with([])->where("unique_code", $excel_datum["station_unique_code"])->first();
                    if (!$station) throw new EmptyException("车站不存在：{$excel_reader_row} {$excel_datum["station_unique_code"]}");

                    if (DB::table($table_name)->where("unique_code", $excel_datum["unique_code"])->exists()) {
                        ExcelRowService::Init()
                            ->SetRow($excel_writer_row++)
                            ->SetExcelCells([
                                ExcelCellService::Init($excel_datum["id"]),
                                ExcelCellService::Init($excel_datum["created_at"]),
                                ExcelCellService::Init($excel_datum["updated_at"]),
                                ExcelCellService::Init($excel_datum["unique_code"]),
                                ExcelCellService::Init($excel_datum["station_unique_code"]),
                                ExcelCellService::Init($excel_datum["type"]),
                                ExcelCellService::Init($excel_datum["name"]),
                            ])
                            ->Write($excel_writer_sheet);
                    } else {
                        // DB::table($table_name)
                        //     ->insert([
                        //         "created_at" => $excel_datum["created_at"],
                        //         "updated_at" => $excel_datum["updated_at"],
                        //         "unique_code" => $excel_datum["unique_code"],
                        //         "station_unique_code" => $excel_datum["station_unique_code"],
                        //         "type" => $excel_datum["type"],
                        //     ]);
                    }
                });
            }

            $excel_writer->Save(storage_path("installLocationSync/{$organization_code}-{$table_name}_repeat"));
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * 同步上道位置：排 Excel ➡ 车间
     */
    final private function installPlatoonFromExcel()
    {
        DB::beginTransaction();
        try {
            $organization_code = env("ORGANIZATION_CODE");
            $table_name = "install_platoons";
            $excel_reader = ExcelReaderService::File(storage_path("installLocationSync/{$organization_code}-{$table_name}.xlsx"))
                ->SetOriginRow(2)
                ->SetFinishColText("F")
                ->ReadBySheetIndex();
            $excel_data = $excel_reader->GetData(["id", "created_at", "updated_at", "name", "unique_code", "install_room_unique_code"]);

            $excel_writer = ExcelWriterService::Init();
            $excel_writer_sheet = $excel_writer->GetSheet();
            $excel_writer_row = 1;

            // 设置表头
            ExcelRowService::Init()
                ->SetRow($excel_writer_row++)
                ->SetExcelCells([
                    ExcelCellService::Init("id"),
                    ExcelCellService::Init("created_at"),
                    ExcelCellService::Init("updated_at"),
                    ExcelCellService::Init("name"),
                    ExcelCellService::Init("unique_code"),
                    ExcelCellService::Init("install_room_unique_code"),
                ])
                ->Write($excel_writer_sheet);

            if ($excel_data->isNotEmpty()) {
                $excel_data->each(function ($excel_datum) use ($table_name, &$excel_writer_sheet, &$excel_writer_row) {
                    if (DB::table($table_name)->where("unique_code", $excel_datum["unique_code"])->exists()) {
                        ExcelRowService::Init()
                            ->SetRow($excel_writer_row++)
                            ->SetExcelCells([
                                ExcelCellService::Init($excel_datum["id"]),
                                ExcelCellService::Init($excel_datum["created_at"]),
                                ExcelCellService::Init($excel_datum["updated_at"]),
                                ExcelCellService::Init($excel_datum["name"]),
                                ExcelCellService::Init($excel_datum["unique_code"]),
                                ExcelCellService::Init($excel_datum["install_room_unique_code"]),
                            ])
                            ->Write($excel_writer_sheet);
                    } else {
                        DB::table($table_name)
                            ->insert([
                                "created_at" => $excel_datum["created_at"],
                                "updated_at" => $excel_datum["updated_at"],
                                "name" => $excel_datum["name"],
                                "unique_code" => $excel_datum["unique_code"],
                                "install_room_unique_code" => $excel_datum["install_room_unique_code"],
                            ]);
                    }
                });
            }

            $excel_writer->Save(storage_path("installLocationSync/{$organization_code}-{$table_name}_repeat"));
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * 同步上道位置：机柜 Excel ➡ 车间
     */
    final private function installShelfFromExcel()
    {
        DB::beginTransaction();
        try {
            $organization_code = env("ORGANIZATION_CODE");
            $table_name = "install_shelves";
            $excel_reader = ExcelReaderService::File(storage_path("installLocationSync/{$organization_code}-{$table_name}.xlsx"))
                ->SetOriginRow(2)
                ->SetFinishColText("M")
                ->ReadBySheetIndex();
            $excel_data = $excel_reader->GetData(["id", "created_at", "updated_at", "name", "unique_code", "install_platoon_unique_code", "type", "line_unique_code", "image_path", "sort", "vr_image_path", "vr_lon", "vr_lat"]);

            $excel_writer = ExcelWriterService::Init();
            $excel_writer_sheet = $excel_writer->GetSheet();
            $excel_writer_row = 1;

            // 设置表头
            ExcelRowService::Init()
                ->SetRow($excel_writer_row++)
                ->SetExcelCells([
                    ExcelCellService::Init("id"),
                    ExcelCellService::Init("created_at"),
                    ExcelCellService::Init("updated_at"),
                    ExcelCellService::Init("name"),
                    ExcelCellService::Init("unique_code"),
                    ExcelCellService::Init("install_platoon_unique_code"),
                    ExcelCellService::Init("type"),
                    ExcelCellService::Init("line_unique_code"),
                    ExcelCellService::Init("image_path"),
                    ExcelCellService::Init("sort"),
                    ExcelCellService::Init("vr_image_path"),
                    ExcelCellService::Init("vr_lon"),
                    ExcelCellService::Init("vr_lat"),
                ])
                ->Write($excel_writer_sheet);

            if ($excel_data->isNotEmpty()) {
                $excel_data->each(function ($excel_datum, $excel_reader_row) use ($table_name, &$excel_writer_sheet, &$excel_writer_row) {
                    if (DB::table($table_name)->where("unique_code", $excel_datum["unique_code"])->exists()) {
                        dump($excel_reader_row);
                        ExcelRowService::Init()
                            ->SetRow($excel_writer_row++)
                            ->SetExcelCells([
                                ExcelCellService::Init($excel_datum["id"]),
                                ExcelCellService::Init($excel_datum["created_at"]),
                                ExcelCellService::Init($excel_datum["updated_at"]),
                                ExcelCellService::Init($excel_datum["name"]),
                                ExcelCellService::Init($excel_datum["unique_code"]),
                                ExcelCellService::Init($excel_datum["install_platoon_unique_code"]),
                                ExcelCellService::Init($excel_datum["type"]),
                                ExcelCellService::Init($excel_datum["line_unique_code"]),
                                ExcelCellService::Init($excel_datum["image_path"]),
                                ExcelCellService::Init($excel_datum["sort"]),
                                ExcelCellService::Init($excel_datum["vr_image_path"]),
                                ExcelCellService::Init($excel_datum["vr_lon"]),
                                ExcelCellService::Init($excel_datum["vr_lat"]),
                            ])
                            ->Write($excel_writer_sheet);
                    } else {
                        DB::table($table_name)
                            ->insert([
                                "created_at" => $excel_datum["created_at"],
                                "updated_at" => $excel_datum["updated_at"],
                                "name" => $excel_datum["name"],
                                "unique_code" => $excel_datum["unique_code"],
                                "install_platoon_unique_code" => $excel_datum["install_platoon_unique_code"],
                                "type" => $excel_datum["type"],
                                "line_unique_code" => $excel_datum["line_unique_code"],
                                "image_path" => $excel_datum["image_path"],
                                "sort" => $excel_datum["sort"],
                                "vr_image_path" => $excel_datum["vr_image_path"],
                                "vr_lon" => $excel_datum["vr_lon"],
                                "vr_lat" => $excel_datum["vr_lat"],
                            ]);
                    }
                });
            }

            $excel_writer->Save(storage_path("installLocationSync/{$organization_code}-{$table_name}_repeat"));
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * 同步上道位置：层 Excel ➡ 车间
     */
    final private function installTierFromExcel()
    {
        DB::beginTransaction();
        try {
            $organization_code = env("ORGANIZATION_CODE");
            $table_name = "install_tiers";
            $excel_reader = ExcelReaderService::File(storage_path("installLocationSync/{$organization_code}-{$table_name}.xlsx"))
                ->SetOriginRow(2)
                ->SetFinishColText("G")
                ->ReadBySheetIndex();
            $excel_data = $excel_reader->GetData(["id", "created_at", "updated_at", "name", "unique_code", "install_shelf_unique_code", "sort"]);

            $excel_writer = ExcelWriterService::Init();
            $excel_writer_sheet = $excel_writer->GetSheet();
            $excel_writer_row = 1;

            // 设置表头
            ExcelRowService::Init()
                ->SetRow($excel_writer_row++)
                ->SetExcelCells([
                    ExcelCellService::Init("id"),
                    ExcelCellService::Init("created_at"),
                    ExcelCellService::Init("updated_at"),
                    ExcelCellService::Init("name"),
                    ExcelCellService::Init("unique_code"),
                    ExcelCellService::Init("install_shelf_unique_code"),
                    ExcelCellService::Init("sort"),
                ])
                ->Write($excel_writer_sheet);

            if ($excel_data->isNotEmpty()) {
                $excel_data->chunk(500)
                    ->each(function ($excel_datum, $excel_reader_row) use ($table_name, &$excel_writer_sheet, &$excel_writer_row) {
                        if (DB::table($table_name)->where("unique_code", $excel_datum["unique_code"])->exists()) {
                            dump($excel_reader_row);
                            ExcelRowService::Init()
                                ->SetRow($excel_writer_row++)
                                ->SetExcelCells([
                                    ExcelCellService::Init($excel_datum["id"]),
                                    ExcelCellService::Init($excel_datum["created_at"]),
                                    ExcelCellService::Init($excel_datum["updated_at"]),
                                    ExcelCellService::Init($excel_datum["name"]),
                                    ExcelCellService::Init($excel_datum["unique_code"]),
                                    ExcelCellService::Init($excel_datum["install_shelf_unique_code"]),
                                    ExcelCellService::Init($excel_datum["sort"]),
                                ])
                                ->Write($excel_writer_sheet);
                        } else {
                            DB::table($table_name)
                                ->insert([
                                    "created_at" => $excel_datum["created_at"],
                                    "updated_at" => $excel_datum["updated_at"],
                                    "unique_code" => $excel_datum["unique_code"],
                                    "install_tier_unique_code" => $excel_datum["install_tier_unique_code"],
                                    "name" => $excel_datum["name"],
                                    "volume" => $excel_datum["volume"],
                                    "sort" => $excel_datum["sort"],
                                ]);
                        }
                    });
            }

            $excel_writer->Save(storage_path("installLocationSync/{$organization_code}-{$table_name}_repeat"));
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * 同步上道位置：位 Excel ➡ 车间
     */
    final private function installPositionFromExcel()
    {
        DB::beginTransaction();
        try {
            $organization_code = env("ORGANIZATION_CODE");
            $table_name = "install_positions";
            $excel_reader = ExcelReaderService::File(storage_path("installLocationSync/{$organization_code}-{$table_name}.xlsx"))
                ->SetOriginRow(2)
                ->SetFinishColText("I")
                ->ReadBySheetIndex();
            $excel_data = $excel_reader->GetData(["id", "created_at", "updated_at", "name", "unique_code", "install_tier_unique_code", "line_unique_code", "sort", "volume"]);

            $excel_writer = ExcelWriterService::Init();
            $excel_writer_sheet = $excel_writer->GetSheet();
            $excel_writer_row = 1;

            // 设置表头
            ExcelRowService::Init()
                ->SetRow($excel_writer_row++)
                ->SetExcelCells([
                    ExcelCellService::Init("id"),
                    ExcelCellService::Init("created_at"),
                    ExcelCellService::Init("updated_at"),
                    ExcelCellService::Init("name"),
                    ExcelCellService::Init("unique_code"),
                    ExcelCellService::Init("install_tier_unique_code"),
                    ExcelCellService::Init("line_unique_code"),
                    ExcelCellService::Init("sort"),
                    ExcelCellService::Init("volume"),
                ])
                ->Write($excel_writer_sheet);

            if ($excel_data->isNotEmpty()) {
                $excel_data->each(function ($excel_datum, $excel_reader_row) use ($table_name, &$excel_writer_sheet, &$excel_writer_row) {
                    if (DB::table($table_name)->where("unique_code", $excel_datum["unique_code"])->exists()) {
                        dump($excel_reader_row);
                        ExcelRowService::Init()
                            ->SetRow($excel_writer_row++)
                            ->SetExcelCells([
                                ExcelCellService::Init($excel_datum["id"]),
                                ExcelCellService::Init($excel_datum["created_at"]),
                                ExcelCellService::Init($excel_datum["updated_at"]),
                                ExcelCellService::Init($excel_datum["name"]),
                                ExcelCellService::Init($excel_datum["unique_code"]),
                                ExcelCellService::Init($excel_datum["install_tier_unique_code"]),
                                ExcelCellService::Init($excel_datum["line_unique_code"]),
                                ExcelCellService::Init($excel_datum["sort"]),
                                ExcelCellService::Init($excel_datum["volume"]),
                            ])
                            ->Write($excel_writer_sheet);
                    } else {
                        DB::table($table_name)
                            ->insert([
                                "created_at" => $excel_datum["created_at"],
                                "updated_at" => $excel_datum["updated_at"],
                                "unique_code" => $excel_datum["unique_code"],
                                "install_tier_unique_code" => $excel_datum["install_tier_unique_code"],
                                "name" => $excel_datum["name"],
                                "volume" => $excel_datum["volume"],
                                "sort" => $excel_datum["sort"],
                            ]);
                    }
                });
            }

            $excel_writer->Save(storage_path("installLocationSync/{$organization_code}-{$table_name}_repeat"));
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $operation = $this->argument("operation");
        $arg1 = @$this->argument("arg1");

        $this->{$operation}($arg1);
    }
}
