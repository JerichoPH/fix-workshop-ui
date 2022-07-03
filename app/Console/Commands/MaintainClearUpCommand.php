<?php

namespace App\Console\Commands;

use App\Facades\MaintainFacade;
use App\Facades\OrganizationFacade;
use App\Model\Install\InstallRoom;
use App\Model\Maintain;
use App\Services\ExcelReaderService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PHPExcel_Exception;
use PHPExcel_Reader_Exception;

class MaintainClearUpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintain-clear-up {operator}';

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
    final public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->{$this->argument('operator')}();
    }

    /**
     * 纠正线别
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws Exception
     */
    private function correctLines()
    {
        $this->comment("纠正线别");
        $excel_reader = ExcelReaderService::File(storage_path("6.29附件3.xlsx"))
            ->SetOriginRow(2)
            ->SetFinishColText("I")
            ->ReadBySheetIndex()
            ->Close();
        $excel_data = $excel_reader->GetData(["序号", "车", "原车站编号", "车站编号", "线别", "线别编号", "所属站段", "修订说明", "段编号"]);

        if (
        $excel_data
            ->where("段编号", env("ORGANIZATION_CODE"))
            ->isNotEmpty()
        ) {
            DB::table("lines")->where("id", ">", 0)->update(["is_show" => 0,]);

            DB::table("lines")
                ->whereIn(
                    "unique_code",
                    $excel_data
                        ->where("段编号", env("ORGANIZATION_CODE"))
                        ->where("线别编号", "<>", "")
                        ->pluck("线别编号")
                        ->unique()
                        ->values()
                        ->toArray()
                )
                ->update(["is_show" => 1,]);
        } else {
            $this->error("没有找到数据");
        }
        $this->info("纠正线别");
    }

    final private function b048(): void
    {

        // OrganizationFacade::UpdateStationNameById(231, "贵广中继站36", false);
        // OrganizationFacade::UpdateStationNameById(229, "南广中继站36", false);
        //
        // DB::table("maintains")->whereIn("id", [231, 229, 242,])->update(["deleted_at" => now(),]);

        OrganizationFacade::UpdateStationParentUniqueCodesById([
            // 深圳车间 → 中山车间
            134 => "B048C12", 135 => "B048C12", 136 => "B048C12", 137 => "B048C12", 138 => "B048C12", 139 => "B048C12", 140 => "B048C12", 141 => "B048C12", 162 => "B048C12", 163 => "B048C12", 164 => "B048C12", 165 => "B048C12", 166 => "B048C12", 167 => "B048C12", 168 => "B048C12", 169 => "B048C12", 170 => "B048C12", 171 => "B048C12", 172 => "B048C12", 173 => "B048C12", 174 => "B048C12", 175 => "B048C12", 176 => "B048C12", 177 => "B048C12", 178 => "B048C12", 179 => "B048C12", 180 => "B048C12", 181 => "B048C12", 182 => "B048C12", 261 => "B048C12",

            // 虎门车间 → 天河车间
            183 => "B048C13", 184 => "B048C13", 185 => "B048C13", 186 => "B048C13", 187 => "B048C13", 188 => "B048C13", 189 => "B048C13", 190 => "B048C13", 191 => "B048C13", 192 => "B048C13", 195 => "B048C13", 196 => "B048C13", 197 => "B048C13", 199 => "B048C13", 201 => "B048C13", 260 => "B048C13",

            // 广州电务段电子设备车间 → 东莞车间
            193 => "B048C14", 194 => "B048C14", 198 => "B048C14", 200 => "B048C14", 202 => "B048C14", 203 => "B048C14", 254 => "B048C14",

            // 广州电务段技术科 → 深圳车间
            204 => "B048C15", 205 => "B048C15", 206 => "B048C15", 207 => "B048C15", 208 => "B048C15", 209 => "B048C15", 210 => "B048C15", 211 => "B048C15", 212 => "B048C15", 213 => "B048C15", 214 => "B048C15",

            // 广州电务段高铁科 → 新塘车间
            215 => "B048C16", 216 => "B048C16", 217 => "B048C16", 218 => "B048C16", 219 => "B048C16", 220 => "B048C16", 221 => "B048C16", 222 => "B048C16", 237 => "B048C16", 249 => "B048C16",

            // 广州电务段车载科 → 肇庆车间
            106 => "B048C17", 107 => "B048C17", 108 => "B048C17", 110 => "B048C17", 111 => "B048C17", 112 => "B048C17", 113 => "B048C17", 114 => "B048C17", 115 => "B048C17", 227 => "B048C17", 228 => "B048C17", 252 => "B048C17", 259 => "B048C17",

            // 广州电务段行政办 → 虎门车间
            119 => "B048C18", 121 => "B048C18", 145 => "B048C18", 146 => "B048C18", 147 => "B048C18", 148 => "B048C18", 149 => "B048C18", 150 => "B048C18", 151 => "B048C18", 152 => "B048C18", 153 => "B048C18", 154 => "B048C18", 155 => "B048C18", 156 => "B048C18", 157 => "B048C18", 158 => "B048C18", 159 => "B048C18", 160 => "B048C18", 241 => "B048C18", 243 => "B048C18", 246 => "B048C18", 253 => "B048C18", 255 => "B048C18",

            // 广州电务段材料科 → 职教科
            257 => "B048C27",
        ]);

        $this->info("清理广州车站：完成");
    }

    final private function b049(): void
    {
        # 汉寿(G00144) → 益阳高信车间(B049C17)
        # 汉寿南站(G10011) → 益阳车间(B049C07)
        // OrganizationFacade::updateStationParentUniqueCodes([
        //     "G00144" => "B049C17",
        //     "G10011" => "B049C07",
        // ]);

        // K18道口（邵2） → K18道口（韶2）
        // K19道口（邵1） → K19道口（韶1）
        // OrganizationFacade::UpdateStationNameById(143, "K18道口（韶2）");
        // OrganizationFacade::UpdateStationNameById(144, "K19道口（韶1）");

        // $tmp = function (string $from, string $to): array {
        //     $data = [];
        //     DB::table("maintains as s")
        //         ->select("s.id")
        //         ->join(DB::raw("maintains w"), "s.parent_unique_code", "=", "w.unique_code")
        //         ->where("w.name", $from)
        //         ->get()
        //         ->pluck("id")
        //         ->each(function ($item) use (&$data, $to) {
        //             $data[$item] = $to;
        //         });
        //     return $data;
        // };
        //
        // // 修改车站编码
        // DB::table("maintains")->where("id", 103)->update(["unique_code" => "G01181",]);  // 城际动车所
        // DB::table("maintains")->where("id", 208)->update(["unique_code" => "G01182",]);  // 上行到达楼
        // DB::table("maintains")->where("id", 319)->update(["unique_code" => "G01183",]);  // 株洲继电器工区
        // DB::table("maintains")->where("id", 320)->update(["unique_code" => "G01184",]);  // 长沙继电器工区
        // DB::table("maintains")->where("id", 322)->update(["unique_code" => "G01185",]);  // 株洲设备工区
        // DB::table("maintains")->where("id", 335)->update(["unique_code" => "G01186",]);  // 常益长中继站5
        // DB::table("maintains")->where("id", 336)->update(["unique_code" => "G01187",]);  // 常益长中继站4
        // DB::table("maintains")->where("id", 338)->update(["unique_code" => "G01188",]);  // 常益长中继站2
        // DB::table("maintains")->where("id", 339)->update(["unique_code" => "G01189",]);  // 常益长中继站3
        // DB::table("maintains")->where("id", 340)->update(["unique_code" => "G01190",]);  // 常益长中继站1
        // DB::table("maintains")->where("id", 342)->update(["unique_code" => "G01191",]);  // 常德高速场
        //
        // // 培训基地、工厂 ➡ 益阳高信车间 B049C44
        // OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("培训基地、工厂", "B049C44"));
        // $this->info("培训基地、工厂 ➡ 益阳高信车间 B049C44");
        //
        // // 岳阳高信车间 ➡ 岳阳东高信车间 B049C39
        // OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("岳阳高信车间", "B049C39"));
        // $this->info("岳阳高信车间 ➡ 岳阳东高信车间 B049C39");
        //
        // // 长沙高信车间 ➡ 长沙南高信车间 B049C40
        // OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("长沙高信车间", "B049C40"));
        // $this->info("长沙高信车间 ➡ 长沙南高信车间 B049C40");
        //
        // // 株洲北车间 ➡ 株北车间 B049C41
        // OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("株洲北车间", "B049C41"));
        // $this->info("株洲北车间 ➡ 株北车间 B049C41");
        //
        // // 长沙电务段电子设备车间 ➡ 长沙电务段信号检修车间  B049C34
        // OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("长沙电务段电子设备车间", "B049C34"));
        // $this->info("长沙电务段电子设备车间 ➡ 长沙电务段信号检修车间  B049C34");
        //
        // // 醴陵车间 ➡ 醴陵电务车间 B049C42
        // OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("醴陵车间", "B049C42"));
        // $this->info("醴陵车间 ➡ 醴陵电务车间 B049C42");
        //
        // OrganizationFacade::UpdateStationParentUniqueCodesById([380 => "B049C14"]);

        DB::table("maintains")->where("id", 361)->update(["name" => "长沙实训基地",]);
        Maintain::with([])->create([
            "unique_code" => "G10173",
            "name" => "普信实训室",
            "parent_unique_code" => "B049C17",
            "type" => "STATION",
            "is_show" => true,
        ]);
        Maintain::with([])->create([
            "unique_code" => "G10174",
            "name" => "高信实训室",
            "parent_unique_code" => "B049C17",
            "type" => "STATION",
            "is_show" => true,
        ]);
        // DB::table("maintains")->insert([
        //     "created_at" => now(),
        //     "updated_at" => now(),
        //     "unique_code" => "G10175",
        //     "name" => "汉寿区间",
        //     "parent_unique_code" => "B049C17",
        //     "type" => "STATION",
        //     "is_show" => true,
        // ]);
        // DB::table("maintains")->insert([
        //     "created_at" => now(),
        //     "updated_at" => now(),
        //     "unique_code" => "G10176",
        //     "name" => "常德区间",
        //     "parent_unique_code" => "B049C17",
        //     "type" => "STATION",
        //     "is_show" => true,
        // ]);
        $this->info("OK");
    }

    final private function b050(): void
    {
        // // 怀化西信号车间 ➡ 铜仁信号车间
        // OrganizationFacade::UpdateStationParentUniqueCodesById([156 => "B050C13", 157 => "B050C13", 158 => "B050C13", 159 => "B050C13", 160 => "B050C13", 161 => "B050C13", 162 => "B050C13", 163 => "B050C13", 164 => "B050C13", 165 => "B050C13", 166 => "B050C13", 208 => "B050C13", 209 => "B050C13", 210 => "B050C13", 211 => "B050C13", 214 => "B050C13",]);
        // $this->info("怀化西信号车间 ➡ 铜仁信号车间");
        //
        // // 吉首东高铁信号车间 ➡ 怀化西驼峰信号车间
        // OrganizationFacade::UpdateStationParentUniqueCodesById([168 => "B050C18", 169 => "B050C18", 170 => "B050C18", 171 => "B050C18", 172 => "B050C18", 173 => "B050C18", 174 => "B050C18", 175 => "B050C18", 202 => "B050C18", 203 => "B050C18", 204 => "B050C18", 212 => "B050C18",]);
        // $this->info("吉首东高铁信号车间 ➡ 怀化西驼峰信号车间");
        //
        // // 怀化电务段电子设备车间 ➡ 吉首东高铁信号车间
        // OrganizationFacade::UpdateStationParentUniqueCodesById([182 => "B050C15", 183 => "B050C15", 184 => "B050C15", 185 => "B050C15", 186 => "B050C15", 187 => "B050C15", 188 => "B050C15", 189 => "B050C15", 190 => "B050C15", 191 => "B050C15", 192 => "B050C15", 193 => "B050C15", 194 => "B050C15", 195 => "B050C15",]);
        // $this->info("怀化电务段电子设备车间 ➡ 吉首东高铁信号车间");

        // 删除怀化西信号车间
        // $this->info("怀化西信号车间 ➡ 怀化西驼峰信号车间：开始");
        // DB::table("maintains")->where("id", 167)->delete();
        // OrganizationFacade::UpdateStationParentUniqueCodesById([
        //     168 => "B050C18",
        //     169 => "B050C18",
        //     170 => "B050C18",
        //     171 => "B050C18",
        //     172 => "B050C18",
        //     173 => "B050C18",
        //     174 => "B050C18",
        //     175 => "B050C18",
        //     202 => "B050C18",
        //     203 => "B050C18",
        //     204 => "B050C18",
        //     212 => "B050C18",
        // ]);
        // $this->info("怀化西信号车间 ➡ 怀化西驼峰信号车间：完成");

        // 怀化西站调楼 ➡ 怀化西调楼
        // $this->comment("怀化西站调楼 ➡ G01096");
        // OrganizationFacade::UpdateStationUniqueCodeById(203,"G01096");
        // $this->info("怀化西站调楼 ➡ G01096");
        //
        // $this->comment("怀化西站调楼 ➡ 怀化西调楼");
        // Organizationfacade::UpdateStationNameById(203,"怀化西调楼");
        // $this->info("怀化西站调楼 ➡ 怀化西调楼");

        // B050C18 ➡ B050C14
        // $this->comment("B050C18 ➡ B050C14");
        // OrganizationFacade::UpdateStationParentUniqueCodesById([
        //     168 => "B050C14",
        //     169 => "B050C14",
        //     170 => "B050C14",
        //     171 => "B050C14",
        //     172 => "B050C14",
        //     173 => "B050C14",
        //     174 => "B050C14",
        //     175 => "B050C14",
        //     202 => "B050C14",
        //     203 => "B050C14",
        //     204 => "B050C14",
        //     212 => "B050C14",
        // ]);
        // $this->comment("B050C18 ➡ B050C14");

        // 怀化西站调楼 ➡ 怀化西调楼
        $this->comment("怀化西站调楼 ➡ 怀化西调楼");
        OrganizationFacade::UpdateStationNameById(220,"怀化西调楼");
        $this->info("怀化西站调楼 ➡ 怀化西调楼");

        // 磨砂溪线路所 ➡ 磨沙溪线路所
        $this->comment("磨砂溪线路所 ➡ 磨沙溪线路所");
        // 1. 磨沙溪线路所 ➡ B050C14
        OrganizationFacade::UpdateStationParentUniqueCodesById([219 => "B050C14"]);
        // 2. 磨砂溪线路所 器材改为 磨沙溪线路所
        $old_name = '磨砂溪线路所';
        $new_name = '磨沙溪线路所';
        DB::table("breakdown_logs")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("collect_device_order_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("entire_instances")->where("bind_station_name", $old_name)->update(["bind_station_name" => $new_name,]);
        DB::table("entire_instances")->where("last_maintain_station_name", $old_name)->update(["last_maintain_station_name" => $new_name,]);
        DB::table("fix_workflows")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("print_new_location_and_old_entire_instances")->where("old_maintain_station_name", $old_name)->update(["old_maintain_station_name" => $new_name,]);
        DB::table("repair_base_breakdown_order_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("repair_base_plan_out_cycle_fix_bills")->where("station_name", $old_name)->update(["station_name" => $new_name,]);
        DB::table("repair_base_plan_out_cycle_fix_entire_instances")->where("station_name", $old_name)->update(["station_name" => $new_name,]);
        DB::table("station_install_location_records")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("station_locations")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("temp_station_eis")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("temp_station_position")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("v250_workshop_in_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("v250_workshop_out_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("warehouse_in_batch_reports")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("warehouse_reports")->where("station_name", $old_name)->update(["station_name" => $new_name,]);
        DB::table("warehouse_report_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("warehouse_storage_batch_reports")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        $this->info("磨砂溪线路所 ➡ 磨沙溪线路所");

        // 同天湾 ➡ 同田湾
        $this->comment("同天湾 ➡ 同田湾");
        // 1. 同田湾 ➡ B050C13
        OrganizationFacade::UpdateStationParentUniqueCodesById([219 => "B050C14"]);
        // 2. 同天湾 器材改为 同田湾
        $old_name = '同天湾';
        $new_name = '同田湾';
        DB::table("breakdown_logs")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("collect_device_order_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("entire_instances")->where("bind_station_name", $old_name)->update(["bind_station_name" => $new_name,]);
        DB::table("entire_instances")->where("last_maintain_station_name", $old_name)->update(["last_maintain_station_name" => $new_name,]);
        DB::table("fix_workflows")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("print_new_location_and_old_entire_instances")->where("old_maintain_station_name", $old_name)->update(["old_maintain_station_name" => $new_name,]);
        DB::table("repair_base_breakdown_order_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("repair_base_plan_out_cycle_fix_bills")->where("station_name", $old_name)->update(["station_name" => $new_name,]);
        DB::table("repair_base_plan_out_cycle_fix_entire_instances")->where("station_name", $old_name)->update(["station_name" => $new_name,]);
        DB::table("station_install_location_records")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("station_locations")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("temp_station_eis")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("temp_station_position")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("v250_workshop_in_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("v250_workshop_out_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("warehouse_in_batch_reports")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("warehouse_reports")->where("station_name", $old_name)->update(["station_name" => $new_name,]);
        DB::table("warehouse_report_entire_instances")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        DB::table("warehouse_storage_batch_reports")->where("maintain_station_name", $old_name)->update(["maintain_station_name" => $new_name,]);
        $this->info("同天湾 ➡ 同田湾");

        // 增加：怀衡远控机房 G10172
        DB::table("maintains")
            ->insert([
                "created_at" => now(),
                "updated_at" => now(),
                "unique_code" => "G10172",
                "name" => "怀衡远控机房",
                "parent_unique_code" => "B050C02",
                "type" => "STATION",
                "is_show" => true,
            ]);

        // 增加：沪昆中心机房 G10175
        DB::table("maintains")
            ->insert([
                "created_at" => now(),
                "updated_at" => now(),
                "unique_code" => "G10175",
                "name" => "沪昆中心机房",
                "parent_unique_code" => "B050C01",
                "type" => "STATION",
                "is_show" => true,
            ]);

        // 增加：铜仁铜玉场 G01192
        DB::table("maintains")->insert([
            "created_at" => now(),
            "updated_at" => now(),
            "unique_code" => "G01192",
            "name" => "铜仁铜玉场",
            "parent_unique_code" => "B050C13",
            "type" => "STATION",
            "is_show" => true,
        ]);

        // 增加：溪口 G01193
        DB::table("maintains")->insert([
            "created_at" => now(),
            "updated_at" => now(),
            "unique_code" => "G01193",
            "name" => "溪口",
            "parent_unique_code" => "B050C07",
            "type" => "STATION",
            "is_show" => true,
        ]);

        $this->line("修改车站对应车间");
        // 涟源、石泉、金竹山、冷水江东、冷水江西、新化、金滩、西河、横阳山、安化、渠江 ➡ 新化信号车间
        $this->line("涟源、石泉、金竹山、冷水江东、冷水江西、新化、金滩、西河、横阳山、安化、渠江 ➡ 新化信号车间");
        $data = [
            126 => "B050C11",
            127 => "B050C11",
            128 => "B050C11",
            129 => "B050C11",
            130 => "B050C11",
            131 => "B050C11",
            132 => "B050C11",
            133 => "B050C11",
            134 => "B050C11",
            135 => "B050C11",
            136 => "B050C11",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("涟源、石泉、金竹山、冷水江东、冷水江西、新化、金滩、西河、横阳山、安化、渠江 ➡ 新化信号车间");

        // 烟溪、新胜利、低庄、川水、溆浦、思蒙、大江口、辰溪、小龙门、花桥镇、泸阳 ➡ 溆浦信号车间
        $this->line("烟溪、新胜利、低庄、川水、溆浦、思蒙、大江口、辰溪、小龙门、花桥镇、泸阳 ➡ 溆浦信号车间");
        $data = [
            138 => "B050C12",
            139 => "B050C12",
            140 => "B050C12",
            141 => "B050C12",
            142 => "B050C12",
            143 => "B050C12",
            144 => "B050C12",
            145 => "B050C12",
            146 => "B050C12",
            147 => "B050C12",
            148 => "B050C12",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("烟溪、新胜利、低庄、川水、溆浦、思蒙、大江口、辰溪、小龙门、花桥镇、泸阳 ➡ 溆浦信号车间");

        // 怀化东、怀化、公坪、芷江西、冷水铺、波州、新晃、酒店塘 ➡ 怀化信号车间
        $this->line("怀化东、怀化、公坪、芷江西、冷水铺、波州、新晃、酒店塘 ➡ 怀化信号车间");
        $data = [
            149 => "B050C09",
            102 => "B050C09",
            150 => "B050C09",
            151 => "B050C09",
            152 => "B050C09",
            153 => "B050C09",
            154 => "B050C09",
            217 => "B050C09",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("怀化东、怀化、公坪、芷江西、冷水铺、波州、新晃、酒店塘 ➡ 怀化信号车间");

        // 怀化西到达场、怀化西驼峰、怀化西站调楼、怀化西编尾、怀化西停车器、怀化西下行到发场、怀化西上行到发场、怀化西货场、池回村所、磨沙溪线路所 ➡ 怀化西驼峰信号车间
        $this->line("怀化西到达场、怀化西驼峰、怀化西站调楼、怀化西编尾、怀化西停车器、怀化西下行到发场、怀化西上行到发场、怀化西货场、池回村所、磨沙溪线路所 ➡ 怀化西驼峰信号车间");
        $data = [
            202 => "B050C14",
            174 => "B050C14",
            220 => "B050C14",
            175 => "B050C14",
            204 => "B050C14",
            171 => "B050C14",
            170 => "B050C14",
            172 => "B050C14",
            169 => "B050C14",
            219 => "B050C14",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("怀化西到达场、怀化西驼峰、怀化西站调楼、怀化西编尾、怀化西停车器、怀化西下行到发场、怀化西上行到发场、怀化西货场、池回村所、磨沙溪线路所 ➡ 怀化西驼峰信号车间");

        // 怀化客技所 ➡ 怀化信号车间
        $this->line("怀化客技所 ➡ 怀化信号车间");
        $data = [213 => "B050C09",];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("怀化客技所 ➡ 怀化信号车间");

        // 澧县、新安镇、石门北、石门县、七松、苗市、水汪、慈利、青山庙、岩泊渡、沙刀湾 ➡ 石门信号车间
        $this->line("澧县、新安镇、石门北、石门县、七松、苗市、水汪、慈利、青山庙、岩泊渡、沙刀湾 ➡ 石门信号车间");
        $data = [
            56 => "B050C06",
            57 => "B050C06",
            58 => "B050C06",
            59 => "B050C06",
            60 => "B050C06",
            61 => "B050C06",
            62 => "B050C06",
            63 => "B050C06",
            64 => "B050C06",
            65 => "B050C06",
            66 => "B050C06",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("澧县、新安镇、石门北、石门县、七松、苗市、水汪、慈利、青山庙、岩泊渡、沙刀湾 ➡ 石门信号车间");

        // 白马溪、溪口镇、渡坦坪、禾家村焦柳场、张家界北、张家界、二家河、后坪、永茂、官坝、回龙、新永顺、施溶溪 ➡ 张家界信号车间
        $this->line("白马溪、溪口镇、渡坦坪、禾家村焦柳场、张家界北、张家界、二家河、后坪、永茂、官坝、回龙、新永顺、施溶溪 ➡ 张家界信号车间");
        $data = [
            68 => "B050C07",
            69 => "B050C07",
            70 => "B050C07",
            71 => "B050C07",
            72 => "B050C07",
            73 => "B050C07",
            74 => "B050C07",
            75 => "B050C07",
            76 => "B050C07",
            77 => "B050C07",
            78 => "B050C07",
            79 => "B050C07",
            80 => "B050C07",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("白马溪、溪口镇、渡坦坪、禾家村焦柳场、张家界北、张家界、二家河、后坪、永茂、官坝、回龙、新永顺、施溶溪 ➡ 张家界信号车间");

        // 猛洞河、古丈、排口、万岩、龙鼻咀、马颈坳、湘泉、吉首、吉首南、周家寨、利略、新凤凰、大龙村、谷达坡、麻阳、大坡 ➡ 吉首信号车间
        $this->line("猛洞河、古丈、排口、万岩、龙鼻咀、马颈坳、湘泉、吉首、吉首南、周家寨、利略、新凤凰、大龙村、谷达坡、麻阳、大坡 ➡ 吉首信号车间");
        $data = [
            82 => "B050C08",
            83 => "B050C08",
            84 => "B050C08",
            85 => "B050C08",
            86 => "B050C08",
            87 => "B050C08",
            88 => "B050C08",
            89 => "B050C08",
            90 => "B050C08",
            91 => "B050C08",
            92 => "B050C08",
            93 => "B050C08",
            94 => "B050C08",
            95 => "B050C08",
            96 => "B050C08",
            97 => "B050C08",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("猛洞河、古丈、排口、万岩、龙鼻咀、马颈坳、湘泉、吉首、吉首南、周家寨、利略、新凤凰、大龙村、谷达坡、麻阳、大坡 ➡ 吉首信号车间");

        // 凉亭坳、黄金坳、象鼻子 ➡ 怀化信号车间
        $this->line("凉亭坳、黄金坳、象鼻子 ➡ 怀化信号车间");
        $data = [
            99 => "B050C09",
            100 => "B050C09",
            101 => "B050C09",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("凉亭坳、黄金坳、象鼻子 ➡ 怀化信号车间");

        // 鸭咀岩、中方、排楼、冷水井、黔城、大沙田、江市、相见、园冲、会同、连山、白腊桥、太阳坪、艮山口、靖州、八家旺、流坪、江口、通道、地阳坪、塘豹 ➡ 靖州信号车间
        $this->line("鸭咀岩、中方、排楼、冷水井、黔城、大沙田、江市、相见、园冲、会同、连山、白腊桥、太阳坪、艮山口、靖州、八家旺、流坪、江口、通道、地阳坪、塘豹 ➡ 靖州信号车间");
        $data = [
            104 => "B050C10",
            105 => "B050C10",
            106 => "B050C10",
            107 => "B050C10",
            108 => "B050C10",
            109 => "B050C10",
            110 => "B050C10",
            111 => "B050C10",
            112 => "B050C10",
            113 => "B050C10",
            114 => "B050C10",
            115 => "B050C10",
            116 => "B050C10",
            117 => "B050C10",
            118 => "B050C10",
            119 => "B050C10",
            120 => "B050C10",
            121 => "B050C10",
            122 => "B050C10",
            123 => "B050C10",
            124 => "B050C10",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("鸭咀岩、中方、排楼、冷水井、黔城、大沙田、江市、相见、园冲、会同、连山、白腊桥、太阳坪、艮山口、靖州、八家旺、流坪、江口、通道、地阳坪、塘豹 ➡ 靖州信号车间");

        // 同田湾 ➡ 怀化信号车间
        $this->line("同田湾 ➡ 怀化信号车间");
        OrganizationFacade::UpdateStationParentUniqueCodesById([218 => "B050C09"]);
        $this->comment("同田湾 ➡ 怀化信号车间");

        // 齐天坪、锦和、渝怀中继站12、漾头、渝怀中继站11、铜仁东、铜仁渝怀场、铜仁铜玉场、渝怀中继站10、渝怀中继站9、桃映、渝怀中继站8、普觉、松桃、渝怀中继站7、梅江 ➡ 铜仁信号车间
        $this->line("齐天坪、锦和、渝怀中继站12、漾头、渝怀中继站11、铜仁东、铜仁渝怀场、铜仁铜玉场、渝怀中继站10、渝怀中继站9、桃映、渝怀中继站8、普觉、松桃、渝怀中继站7、梅江 ➡ 铜仁信号车间");
        $data = [
            165 => "B050C13",
            164 => "B050C13",
            214 => "B050C13",
            163 => "B050C13",
            211 => "B050C13",
            162 => "B050C13",
            161 => "B050C13",
            160 => "B050C13",
            210 => "B050C13",
            159 => "B050C13",
            209 => "B050C13",
            158 => "B050C13",
            157 => "B050C13",
            208 => "B050C13",
            156 => "B050C13",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("齐天坪、锦和、渝怀中继站12、漾头、渝怀中继站11、铜仁东、铜仁渝怀场、铜仁铜玉场、渝怀中继站10、渝怀中继站9、桃映、渝怀中继站8、普觉、松桃、渝怀中继站7、梅江 ➡ 铜仁信号车间");

        // 沪昆高速中继站11、沪昆高速中继站12、新化南、沪昆高速中继站13、沪昆高速中继站14、沪昆高速中继站15、溆浦南、沪昆高速中继站16、沪昆高速中继站17、沪昆高速中继站18、沪昆高速中继站19、怀化南站沪昆场、沪昆高速中继站20、沪昆高速中继站21、芷江、沪昆高速中继站22、沪昆高速中继站23、沪昆高速中继站24、新晃西、沪昆中心机房 ➡ 怀化高铁信号车间
        $this->line("沪昆高速中继站11、沪昆高速中继站12、新化南、沪昆高速中继站13、沪昆高速中继站14、沪昆高速中继站15、溆浦南、沪昆高速中继站16、沪昆高速中继站17、沪昆高速中继站18、沪昆高速中继站19、怀化南站沪昆场、沪昆高速中继站20、沪昆高速中继站21、芷江、沪昆高速中继站22、沪昆高速中继站23、沪昆高速中继站24、新晃西、沪昆中心机房 ➡ 怀化高铁信号车间");
        $data = [
            2 => "B050C01",
            3 => "B050C01",
            4 => "B050C01",
            5 => "B050C01",
            6 => "B050C01",
            7 => "B050C01",
            8 => "B050C01",
            9 => "B050C01",
            10 => "B050C01",
            11 => "B050C01",
            12 => "B050C01",
            13 => "B050C01",
            14 => "B050C01",
            15 => "B050C01",
            16 => "B050C01",
            17 => "B050C01",
            18 => "B050C01",
            19 => "B050C01",
            20 => "B050C01",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("沪昆高速中继站11、沪昆高速中继站12、新化南、沪昆高速中继站13、沪昆高速中继站14、沪昆高速中继站15、溆浦南、沪昆高速中继站16、沪昆高速中继站17、沪昆高速中继站18、沪昆高速中继站19、怀化南站沪昆场、沪昆高速中继站20、沪昆高速中继站21、芷江、沪昆高速中继站22、沪昆高速中继站23、沪昆高速中继站24、新晃西、沪昆中心机房 ➡ 怀化高铁信号车间");

        // 隆回西、怀衡中继站3、洞口、月溪、怀衡中继站2、安江东、怀衡中继站1、杨村线路所、怀化南站怀衡场、怀衡远控机房 ➡ 安江高铁信号车间
        $this->line("隆回西、怀衡中继站3、洞口、月溪、怀衡中继站2、安江东、怀衡中继站1、杨村线路所、怀化南站怀衡场、怀衡远控机房 ➡ 安江高铁信号车间");
        $data = [
            30 => "B050C02",
            29 => "B050C02",
            28 => "B050C02",
            27 => "B050C02",
            26 => "B050C02",
            25 => "B050C02",
            24 => "B050C02",
            23 => "B050C02",
            22 => "B050C02",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("隆回西、怀衡中继站3、洞口、月溪、怀衡中继站2、安江东、怀衡中继站1、杨村线路所、怀化南站怀衡场、怀衡远控机房 ➡ 安江高铁信号车间");

        // 黔常中继站0、黔常中继站1、咸丰、黔常中继站2、来凤、龙山北、水沙坪、黔常中继站3 ➡ 龙山高铁信号车间
        $this->line("黔常中继站0、黔常中继站1、咸丰、黔常中继站2、来凤、龙山北、水沙坪、黔常中继站3 ➡ 龙山高铁信号车间");
        $data = [
            32 => "B050C03",
            205 => "B050C03",
            33 => "B050C03",
            206 => "B050C03",
            34 => "B050C03",
            35 => "B050C03",
            36 => "B050C03",
            207 => "B050C03",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("黔常中继站0、黔常中继站1、咸丰、黔常中继站2、来凤、龙山北、水沙坪、黔常中继站3 ➡ 龙山高铁信号车间");

        // 凤岩村、黔常中继站4、桑植、黔常中继站5、教字垭、张家界西、禾家村黔张常场 ➡ 张家界西高铁信号车间
        $this->line("凤岩村、黔常中继站4、桑植、黔常中继站5、教字垭、张家界西、禾家村黔张常场 ➡ 张家界西高铁信号车间");
        $data = [
            38 => "B050C04",
            39 => "B050C04",
            40 => "B050C04",
            41 => "B050C04",
            42 => "B050C04",
            43 => "B050C04",
            44 => "B050C04",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("凤岩村、黔常中继站4、桑植、黔常中继站5、教字垭、张家界西、禾家村黔张常场 ➡ 张家界西高铁信号车间");

        // 黔常中继站6、黔常中继站7、牛车河、龙潭镇、黔常中继站8、桃源、黔常中继站9、陬市、兴发村线路所 ➡ 桃源高铁信号车间
        $this->line("黔常中继站6、黔常中继站7、牛车河、龙潭镇、黔常中继站8、桃源、黔常中继站9、陬市、兴发村线路所 ➡ 桃源高铁信号车间");
        $data = [
            46 => "B050C05",
            47 => "B050C05",
            48 => "B050C05",
            49 => "B050C05",
            50 => "B050C05",
            51 => "B050C05",
            52 => "B050C05",
            53 => "B050C05",
            54 => "B050C05",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("黔常中继站6、黔常中继站7、牛车河、龙潭镇、黔常中继站8、桃源、黔常中继站9、陬市、兴发村线路所 ➡ 桃源高铁信号车间");

        // 张家界西张吉怀场、沙堤线路所、张吉怀中继站1、张吉怀中继站2、张吉怀中继站3 ➡ 张家界西高铁信号车间
        $this->line("张家界西张吉怀场、沙堤线路所、张吉怀中继站1、张吉怀中继站2、张吉怀中继站3 ➡ 张家界西高铁信号车间");
        $data = [
            200 => "B050C04",
            179 => "B050C04",
            178 => "B050C04",
            180 => "B050C04",
            181 => "B050C04",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("张家界西张吉怀场、沙堤线路所、张吉怀中继站1、张吉怀中继站2、张吉怀中继站3 ➡ 张家界西高铁信号车间");

        // 张吉怀中继站4、张吉怀中继站5、芙蓉镇、张吉怀中继站6、古丈西、张吉怀中继站7、张吉怀中继站8、张吉怀中继站9、吉首东站、张吉怀中继站10、凤凰古城、张吉怀中继站11、麻阳西站、张吉怀中继站12 ➡ 吉首东高铁信号车间
        $this->line("张吉怀中继站4、张吉怀中继站5、芙蓉镇、张吉怀中继站6、古丈西、张吉怀中继站7、张吉怀中继站8、张吉怀中继站9、吉首东站、张吉怀中继站10、凤凰古城、张吉怀中继站11、麻阳西站、张吉怀中继站12 ➡ 吉首东高铁信号车间");
        $data = [
            182 => "B050C15",
            183 => "B050C15",
            184 => "B050C15",
            185 => "B050C15",
            186 => "B050C15",
            187 => "B050C15",
            188 => "B050C15",
            189 => "B050C15",
            190 => "B050C15",
            191 => "B050C15",
            192 => "B050C15",
            193 => "B050C15",
            194 => "B050C15",
            195 => "B050C15",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("张吉怀中继站4、张吉怀中继站5、芙蓉镇、张吉怀中继站6、古丈西、张吉怀中继站7、张吉怀中继站8、张吉怀中继站9、吉首东站、张吉怀中继站10、凤凰古城、张吉怀中继站11、麻阳西站、张吉怀中继站12 ➡ 吉首东高铁信号车间");

        // 张吉怀中继站13、张吉怀中继站14、龙形村线路所、怀化南存车场 ➡ 安江高铁信号车间
        $this->line("张吉怀中继站13、张吉怀中继站14、龙形村线路所、怀化南存车场 ➡ 安江高铁信号车间");
        $data = [
            196 => "B050C02",
            197 => "B050C02",
            198 => "B050C02",
            199 => "B050C02",
        ];
        OrganizationFacade::UpdateStationParentUniqueCodesById($data);
        $this->comment("张吉怀中继站13、张吉怀中继站14、龙形村线路所、怀化南存车场 ➡ 安江高铁信号车间");

        // RBC机房 ➡ 怀化高铁信号车间
        $this->line("RBC机房 ➡ 怀化高铁信号车间");
        OrganizationFacade::UpdateStationParentUniqueCodesById([201 => "B050C01"]);
        $this->comment("RBC机房 ➡ 怀化高铁信号车间");
        $this->info("修改车站对应车间");
    }

    final private function b051(): void
    {
        $tmp = function (string $from, string $to): array {
            $data = [];
            DB::table("maintains as s")
                ->select("s.id")
                ->join(DB::raw("maintains w"), "s.parent_unique_code", "=", "w.unique_code")
                ->where("w.name", $from)
                ->get()
                ->pluck("id")
                ->each(function ($item) use (&$data, $to) {
                    $data[$item] = $to;
                });
            return $data;
        };

        // 邵阳车间 ➡ 邵阳信号车间 B051C11
        OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("邵阳车间", "B051C11"));
        $this->info("邵阳车间 ➡ 邵阳信号车间 B051C11");

        // 衡阳南车间 ➡ 衡阳南高铁信号车间 B051C12
        OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("衡阳南车间", "B051C12"));
        $this->info("衡阳南车间 ➡ 衡阳南高铁信号车间 B051C12");

        // 衡北车间 ➡ 衡北信号车间 B051C13
        OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("衡北车间", "B051C13"));
        $this->info("衡北车间 ➡ 衡北信号车间 B051C13");

        // 衡阳车间 ➡ 衡阳信号车间 B051C14
        OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("衡阳车间", "B051C14"));
        $this->info("衡阳车间 ➡ 衡阳信号车间 B051C14");

        // 永州车间 ➡ 永州信号车间 B051C15
        OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("永州车间", "B051C15"));
        $this->info("永州车间 ➡ 永州信号车间 B051C15");

        // 邵东车间 ➡ 邵东高铁信号车间 B051C16
        OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("邵东车间", "B051C16"));
        $this->info("邵东车间 ➡ 邵东高铁信号车间 B051C16");

        // 郴北车间 ➡ 郴北信号车间 B051C17
        OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("郴北车间", "B051C17"));
        $this->info("郴北车间 ➡ 郴北信号车间 B051C17");

        // 衡阳电务段电子设备车间 ➡ 安仁电务车间 B051C19
        OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("衡阳电务段电子设备车间", "B051C19"));
        $this->info("衡阳电务段电子设备车间 ➡ 安仁电务车间 B051C19");

        // 邵阳信号车间 ➡ 衡阳东高铁信号车间 B051C21
        OrganizationFacade::UpdateStationParentUniqueCodesById($tmp("邵阳信号车间", "B051C21"));
        $this->info("邵阳信号车间 ➡ 衡阳东高铁信号车间 B051C21");

    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws Exception
     * @throws \Throwable
     */
    final private function b052(): void
    {
        // 惠州区间挪到车站上，然后需要执行：php artisan maintain-clear-up findWorkshopName
        // DB::table("entire_instances as ei")
        //     ->selectRaw("ei.maintain_section_name,s.name as station_name")
        //     ->join(DB::raw("maintains s"), "ei.maintain_section_name", "=", "s.name")
        //     ->where("ei.maintain_section_name", "<>", "")
        //     ->groupBy(["maintain_section_name"])
        //     ->get()
        //     ->each(function ($datum) {
        //         $this->info("移动：{$datum["station_name"]}");
        //         DB::table("entire_instances")->where("maintain_section_name", $datum->maintain_section_name)->update([
        //             "maintain_section_name" => "",
        //             "maintain_station_name" => $datum->station_name,
        //         ]);
        //     });

        // OrganizationFacade::updateStationParentUniqueCodes([
        //     "G00661" => "~~~B052C12",
        //     "G00660" => "~~~B052C12",
        //     "G00664" => "~~~B052C12",
        //     "G00656" => "~~~B052C12",
        //     "G00662" => "~~~B052C12",
        //     "G00663" => "~~~B052C12",
        //     "G00659" => "~~~B052C12",
        //     "G01158" => "~~~B052C12",
        //     "G01159" => "~~~B052C12",
        //     "G01160" => "~~~B052C12",
        //     "G01161" => "~~~B052C12",
        //     "G01162" => "~~~B052C12",
        // ]);
        // $this->comment("1S");
        //
        // OrganizationFacade::updateStationParentUniqueCodes([
        //     "G00673" => "~~~B052C13",
        //     "G00674" => "~~~B052C13",
        //     "G00675" => "~~~B052C13",
        //     "G00676" => "~~~B052C13",
        //     "G00672" => "~~~B052C13",
        //     "G00671" => "~~~B052C13",
        //     "G00670" => "~~~B052C13",
        //     "G00669" => "~~~B052C13",
        //     "G00668" => "~~~B052C13",
        //     "G00667" => "~~~B052C13",
        //     "G00666" => "~~~B052C13",
        //     "G00665" => "~~~B052C13",
        // ]);
        // $this->comment("2S");
        //
        // OrganizationFacade::updateStationParentUniqueCodes([
        //     "G00661" => "B052C12",
        //     "G00660" => "B052C12",
        //     "G00664" => "B052C12",
        //     "G00656" => "B052C12",
        //     "G00662" => "B052C12",
        //     "G00663" => "B052C12",
        //     "G00659" => "B052C12",
        //     "G01158" => "B052C12",
        //     "G01159" => "B052C12",
        //     "G01160" => "B052C12",
        //     "G01161" => "B052C12",
        //     "G01162" => "B052C12",
        // ]);
        // $this->comment("1F");
        //
        // OrganizationFacade::updateStationParentUniqueCodes([
        //     "G00673" => "B052C13",
        //     "G00674" => "B052C13",
        //     "G00675" => "B052C13",
        //     "G00676" => "B052C13",
        //     "G00672" => "B052C13",
        //     "G00671" => "B052C13",
        //     "G00670" => "B052C13",
        //     "G00669" => "B052C13",
        //     "G00668" => "B052C13",
        //     "G00667" => "B052C13",
        //     "G00666" => "B052C13",
        //     "G00665" => "B052C13",
        // ]);
        // $this->comment("2F");
        //
        // OrganizationFacade::updateStationParentUniqueCodes([
        //     "G01119" => "B052C11",
        //     "G00498" => "B052C11",
        //     "G00496" => "B052C11",
        //     "G00491" => "B052C11",
        //     "G00489" => "B052C11",
        //     "G00494" => "B052C11",
        //     "G00485" => "B052C11",
        //     "G01120" => "B052C11",
        //     "G00500" => "B052C11",
        //     "G00492" => "B052C11",
        // ]);
        // $this->comment("3F");
    }

    final private function b053()
    {
        OrganizationFacade::UpdateStationParentUniqueCodesById([
            // 2 => "B053C17",
            // 3 => "B053C17",
            // 4 => "B053C17",
            // 5 => "B053C17",
            // 6 => "B053C17",
            // 186 => "B053C17",
            // 56 => "B053C17",
            // 57 => "B053C17",
            // 105 => "B053C17",
            // 158 => "B053C17",
            // 171 => "B053C17",
            // 172 => "B053C17",
            // 236 => "B053C17",

            52 => "B053C17",
            53 => "B053C17",
            54 => "B053C17",
            55 => "B053C17",
            56 => "B053C17",
            57 => "B053C17",
            105 => "B053C17",
            106 => "B053C17",
            114 => "B053C17",
            115 => "B053C17",
            116 => "B053C17",
            150 => "B053C17",
            151 => "B053C17",
            158 => "B053C17",
            171 => "B053C17",
            172 => "B053C17",
            176 => "B053C17",
            204 => "B053C17",
            236 => "B053C17",
        ]);

        dd("OK");
    }

    /**
     * 修改江门北信号工区归属 → 黄埔信号车间
     */
    final private function G53134_B053C14()
    {
        OrganizationFacade::updateStationParentUniqueCodes(["G53134" => "B053C14",]);
    }

    final private function zqG00581Backup(): void
    {
        $install_room = InstallRoom::with([
            "WithInstallPlatoons",
            "WithInstallPlatoons.WithInstallShelves",
            "WithInstallPlatoons.WithInstallShelves.WithInstallTiers",
            "WithInstallPlatoons.WithInstallShelves.WithInstallTiers.WithInstallPositions",
        ])
            ->where("station_unique_code", "G00581")
            ->first()
            ->toJson(256);

        file_put_contents(storage_path("zq_install_positions_g00581.json"), $install_room);
    }

    final private function zqG00581Insert(): void
    {
        $file = json_decode(file_get_contents(storage_path("zq_install_positions_g00581.json")), true);

        // 插入室
        $install_room = array_except($file, ["id", "with_install_platoons", "type",]);
        $install_room["type"] = "10";

        $install_platoons = [];  // 排
        $install_shelves = [];  // 架
        $install_tiers = [];  // 层
        $install_positions = [];  // 位

        collect($file["with_install_platoons"])->each(
            function ($install_platoon)
            use (
                &$install_platoons,
                &$install_shelves,
                &$install_tiers,
                &$install_positions
            ) {
                $install_platoons[] = array_except($install_platoon, ["id", "with_install_shelves",]);

                collect($install_platoon["with_install_shelves"])->each(function ($install_shelf)
                use (
                    &$install_shelves,
                    &$install_tiers,
                    &$install_positions
                ) {
                    $install_shelves[] = array_except($install_shelf, [
                        "id",
                        "with_install_tiers",
                        "vr_image_path",
                        "var_lon",
                        "vr_lat",
                    ]);

                    collect($install_shelf["with_install_tiers"])->each(function ($install_tier)
                    use (
                        &$install_tiers,
                        &$install_positions
                    ) {
                        $install_tiers[] = array_except($install_tier, ["id", "with_install_positions",]);

                        collect($install_tier["with_install_positions"])->each(function ($install_position) use (&$install_positions) {
                            $install_positions[] = array_except($install_position, ["id",]);
                        });
                    });
                });
            });


        DB::beginTransaction();
        DB::table("install_rooms")->insert($install_room);
        DB::table("install_platoons")->insert($install_platoons);
        DB::table("install_shelves")->insert($install_shelves);
        DB::table("install_tiers")->insert($install_tiers);
        DB::table("install_positions")->insert($install_positions);
        DB::commit();

        $this->info("导入完成");
    }

    final public function findWorkshopName()
    {
        DB::beginTransaction();
        try {
            $maintains = DB::table("maintains as s")
                ->selectRaw("s.name as station_name, sc.name as scene_workshop_name")
                ->join(DB::raw("maintains sc"), "s.parent_unique_code", "=", "sc.unique_code")
                ->get()
                ->pluck("scene_workshop_name", "station_name")
                ->toArray();

            DB::table("entire_instances as ei")
                ->select(["ei.maintain_station_name", "ei.maintain_workshop_name",])
                ->join(DB::raw("maintains s"), "ei.maintain_station_name", "=", "s.name")
                ->join(DB::raw("maintains sc"), "s.parent_unique_code", "=", "sc.unique_code")
                ->where("ei.maintain_station_name", "<>", "")
                // ->where("ei.maintain_workshop_name", "")
                ->get()
                ->groupBy(["maintain_station_name"])
                ->each(function ($eis, $maintain_station_name) use ($maintains) {
                    if (@$maintains[$maintain_station_name]) {
                        $edit_count = DB::table("entire_instances as ei")
                            ->where("maintain_station_name", $maintain_station_name)
                            ->update(["maintain_workshop_name" => $maintains[$maintain_station_name]]);
                        if ($edit_count) {
                            $this->info("修改：{$maintain_station_name} {$maintains[$maintain_station_name]} {$edit_count}");
                        }
                    }
                });

            DB::commit();
            $this->comment("执行完毕");
        } catch (Exception $e) {
            DB::rollback();
            dd($e->getMessage(), $e->getLine(), $e->getFile());
        }
    }

    /**
     * 清理线别数据
     */
    final private function lines()
    {
        $organizationCode = env("ORGANIZATION_CODE");
        $organizationName = env("ORGANIZATION_NAME");

        switch ($organizationCode) {
            case "B048":
                OrganizationFacade::UpdateLineNameById(3, "佛肇城际");
                OrganizationFacade::UpdateLineNameById(9, "广珠城际");
                $this->info("{$organizationName}线别清理完成");
                break;
            case "B049":
                // 修正线别和车站对应关系
                DB::table("lines_maintains")->where("id", 4)->update(["lines_id" => 2,]);
                // 删除多余的线别
                DB::table("lines")->whereIn("id", [10, 11, 12, 13, 14, 15, 16, 17, 18,])->delete();
                // 线别改名
                OrganizationFacade::UpdateLineNameById(7, "长株潭城际");
                OrganizationFacade::UpdateLineNameById(19, "常益长线");
                break;
            case "B050":
                OrganizationFacade::UpdateLineNameById(1, "沪昆高速线");
                OrganizationFacade::UpdateLineNameById(3, "渝厦高铁黔常段");
                break;
            case "B053":
                OrganizationFacade::UpdateLineNameById(7, "佛肇城际");
                OrganizationFacade::UpdateLineNameById(10, "南沙港铁路");
                break;
            default:
                $this->error("{$organizationName}没有需要清理的线别");
                break;
        }
    }

    /**
     * 清理车间数据
     */
    final private function workshops(): void
    {
        $organizationCode = env("ORGANIZATION_CODE");
        $organizationName = env("ORGANIZATION_NAME");

        switch ($organizationCode) {
            case "B074":
                OrganizationFacade::UpdateWorkshopNameById(2, "海口综合维修段电务专业修车间");
                DB::table("maintains")->where("id", 2)->update(["parent_unique_code" => "B074", "type" => "ELECTRON",]);
                $this->info("{$organizationName}车间清理完成");
                break;
            case "B053":
                OrganizationFacade::updateStationParentUniqueCodes([
                    "G00987" => "B053C07",
                    "G00988" => "B053C07",
                    "G00989" => "B053C07",
                    "G00990" => "B053C07",
                    "G53005" => "B053C07",
                    "G53165" => "B053C07",
                ]);
                OrganizationFacade::UpdateWorkshopNameById(228, "肇庆电务段信号技术科");
                DB::table("entire_instances")->where("maintain_station_name", "江门高铁信号车间")->update(["maintain_station_name" => "", "maintain_workshop_name" => "江门高铁车间",]);
                DB::table("maintains")->where("id", 217)->delete();
                $this->info("{$organizationName}车间清理完成");
                break;
            case "B049":
                OrganizationFacade::UpdateWorkshopNameById(318, "长沙电务段信号检修车间");
                // OrganizationFacade::UpdateWorkshopNameById(180, "长沙南高信车间");
                // OrganizationFacade::UpdateWorkshopNameById(173, "岳阳东高信车间");
                DB::table("maintains")->where("id", 318)->update(["type" => "WORKSHOP"]);
                DB::table("maintains")->where("id", 319)->update(["type" => "STATION"]);
                DB::table("maintains")->where("id", 320)->update(["type" => "STATION"]);
                DB::table("maintains")->where("id", 321)->update(["type" => "STATION"]);
                DB::table("maintains")->where("id", 322)->update(["type" => "STATION"]);
                DB::table("maintains")->where("id", 15)->delete();
                $this->info("{$organizationName}车间清理完成");
                break;
            default:
                $this->error("{$organizationName}没有需要清理的车间");
                break;
        }
    }
}
