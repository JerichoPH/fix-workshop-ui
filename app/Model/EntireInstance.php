<?php

namespace App\Model;

use App\Facades\TextFacade;
use App\Model\Install\InstallPosition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * App\Model\EntireInstance
 *
 * @property int $id
 * @property SupportCarbon|null $created_at
 * @property SupportCarbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $entire_model_unique_code 设备型号统一代码
 * @property string|null $entire_model_id_code 整件型号代码
 * @property string|null $serial_number 设备出所流水号
 * @property string $status 设备状态
 *  'BUY_IN' => '新购',
 *  'INSTALLING' => '备品',
 *  'INSTALLED' => '上道',
 *  'TRANSFER_OUT' => '出所在途',
 *  'TRANSFER_IN' => '入所在途',
 *  'UNINSTALLED' => '下道',
 *  'FIXING' => '待修',
 *  'FIXED' => '成品',
 *  'RETURN_FACTORY' => '送修',
 *  'FACTORY_RETURN' => '送修入所',
 *  'SCRAP' => '报废',
 *  'FRMLOSS' => '报损',
 *  'SEND_REPAIR'=>'送修',
 * @property string|null $maintain_station_name 台账：站名称
 * @property string|null $maintain_location_code 台账位置代码
 * @property int $work_area 所属工区 0、未分配 1、转辙机工区 2、继电器工区 3、综合工区
 * @property int|null $is_main 主/备用设备标识
 * @property string $factory_name 工厂名称
 * @property string|null $factory_device_code 出场设备号
 * @property string|null $identity_code 身份识别码
 * @property string $installed_at 上道时间
 * @property int|null $last_installed_time 上一次上道时间
 * @property string|null $in_warehouse_time 是否在库
 * @property string $category_unique_code 设备类型唯一代码
 * @property string|null $category_name 类型名称
 * @property string|null $fix_workflow_serial_number 所在工单序列号
 * @property string|null $last_warehouse_report_serial_number_by_out 最后一次出库单流水号
 * @property int|null $is_flush_serial_number 出库时是否需要刷新所编号
 * @property int|null $next_auto_making_fix_workflow_time 下次自动生成工单时间
 * @property int|null $next_fixing_time 下次检修时间
 * @property string|null $next_auto_making_fix_workflow_at 下次自动生成检修单的日期
 * @property string|null $next_fixing_month 下次检修月份
 * @property string|null $next_fixing_day 下次检修日期
 * @property string $fix_cycle_unit 周期修单位
 * @property int $fix_cycle_value 周期修长度数值
 * @property int|null $cycle_fix_count 周期修入所次数
 * @property int|null $un_cycle_fix_count 非周期修入所次数
 * @property string|null $made_at 出场日期
 * @property string|null $scarping_at 预计报废日期
 * @property string|null $residue_use_year 剩余年限
 * @property string|null $old_number 设备老编号
 * @property string|null $purpose 用途
 * @property string $warehouse_name 仓库名称
 * @property string $location_unique_code 仓库位置
 * @property string $to_direction 去向
 * @property string $crossroad_number 岔道号
 * @property string $traction 牵引
 * @property string|null $source 来源
 * @property string|null $source_crossroad_number 来源岔道号
 * @property string|null $source_traction 来源牵引
 * @property string|null $forecast_install_at 理论上道日期
 * @property string|null $line_unique_code 线制
 * @property string $line_name 线制名称
 * @property string|null $open_direction 开向
 * @property string|null $said_rod 表示杆特征
 * @property string|null $scarped_note 报废原因
 * @property string|null $railway_name 路局名称
 * @property string|null $section_name 站名
 * @property string|null $base_name 基地名称
 * @property string|null $rfid_code RFID TID
 * @property string|null $scene_workshop_status 现场车间状态（速普瑞）
 * @property string|null $rfid_epc EPC码
 * @property string|null $note 说明
 * @property string|null $before_fixed_at 预检日期（株洲导入）
 * @property string|null $before_fixer_name 预检人姓名（株洲导入)
 * @property int|null $useable_year 0
 * @property string $crossroad_type 道岔类型
 * @property int|null $allocated_to 分配到员工
 * @property string|null $allocated_at 分配到员工时间
 * @property string $point_switch_group_type 转辙机分组类型：单双机
 * @property int $extrusion_protect 挤压保护罩
 * @property string $model_unique_code 型号代码
 * @property string $model_name 型号名称
 * @property int|null $in_warehouse
 * @property string $in_warehouse_breakdown_explain 入所故障描述
 * @property string|null $last_fix_workflow_at 最后检修时间
 * @property int $is_rent 是否是租借状态
 * @property string $emergency_identity_code 应急中心仓库码
 * @property string $bind_device_code 绑定设备代码
 * @property string $bind_device_type_code 绑定设备类型代码
 * @property string $bind_device_type_name 绑定设备型号名称
 * @property string $bind_crossroad_number 绑定设备所在道岔名称
 * @property string $bind_crossroad_id 绑定设备所在道岔编号
 * @property string $bind_station_name 绑定设备所在车站名称
 * @property string $bind_station_code 绑定设备所在车站编号
 * @property string|null $last_out_at 最后出所时间
 * @property int $is_bind_location 是否绑定位置0未绑定，1绑定
 * @property string $maintain_workshop_name
 * @property string|null $last_take_stock_at 最后盘点时间
 * @property int $last_repair_material_id 最后送修单设备关联
 * @property string $v250_task_order_sn 2.5.0新版任务单编号
 * @property string $work_area_unique_code 所属工区
 * @property string|null $warehousein_at 入库时间
 * @property string|null $is_overhaul 是否分配检修0:未分配,1:已分配
 * @property string $fixer_name 检修人姓名
 * @property Carbon|null $fixed_at 检测时间
 * @property string $checker_name 验收人姓名
 * @property Carbon|null $checked_at 验收时间
 * @property string $spot_checker_name 抽验人姓名
 * @property Carbon|null $spot_checked_at 抽验时间
 * @property int $life_year 寿命(年)
 * @property string $last_maintain_location_code 上一次室内安装位置
 * @property string $last_crossroad_number 上一次道岔号
 * @property string $last_open_direction 上一次开向
 * @property string $last_maintain_station_name 上一次车站名称
 * @property string $last_maintain_workshop_name 上一次现场车间名称
 * @property string $asset_code 物资编码
 * @property string $fixed_asset_code 固资编码
 * @property SupportCarbon|null $last_installed_at 最后上道时间
 * @property int $is_emergency 是否是应急备品
 * @property-read string|bool $can_i_warehouse_in_or_out 是否可以执行出入所
 * @property-read string|bool $can_i_installed 是否可以上道
 * @property-read string|bool $can_i_installing 是否可以入柜
 * @property-read string|bool $can_i_uninstall 是否可以下道
 * @property-read string|bool $can_i_un_install 是否可以下道
 * @property string $source_type 来源类型
 * @property string $source_name 来源名称
 * @property boolean $is_part 是否是部件
 * @property string $entire_instance_identity_code 整件对应编号
 * @property string $part_model_unique_code 部件型号代码
 * @property string $part_model_name 部件型号名称
 * @property int $part_category_id 部件种类代码
 * @property string $last_line_unique_code 最后上道线别代码
 * @property-read EntireInstance $ParentInstance
 * @property-read PartModel $PartModel
 * @property-read PartCategory $PartCategory
 * @property-read EntireModel $OldEntireModel
 * @property-read EntireModel $OldSubModel
 * @property-read Collection|BreakdownLog[] $BreakdownLogs
 * @property-read int|null $breakdown_logs_count
 * @property-read Category $Category
 * @property-read Collection|EntireInstanceLog[] $EntireInstanceLogs
 * @property-read int|null $entire_instance_logs_count
 * @property-read EntireModel $EntireModel
 * @property-read EntireModelIdCode $EntireModelIdCode
 * @property-read Factory $Factory
 * @property-read FixWorkflow $FixWorkflow
 * @property-read Collection|FixWorkflow[] $FixWorkflows
 * @property-read int|null $fix_workflows_count
 * @property-read Collection|Measurement[] $Measurements
 * @property-read int|null $measurements_count
 * @property-read PartInstance $PartInstance
 * @property-read Collection|EntireInstance[] $PartInstances
 * @property-read int|null $part_instances_count
 * @property-read Maintain $SceneWorkshop
 * @property-read Line $Line
 * @property-read Maintain $Station
 * @property-read EntireModel $SubModel
 * @property-read WarehouseReport $WarehouseReportByOut
 * @property-read Collection|WarehouseReport[] $WarehouseReports
 * @property-read int|null $warehouse_reports_count
 * @property-read Position $WithPosition
 * @property-read Collection|SendRepairInstance[] $WithSendRepairInstances
 * @property-read int|null $with_send_repair_instances_count
 * @property-read array $storehouse_location
 * @property string $last_scene_breakdown_description
 * @property-read stdClass $use_position
 * @property-read string $use_position_name
 * @property-read stdClass $last_use_position
 * @property-read string $last_use_position_name
 * @property-read Maintain $LastSceneWorkshop
 * @property-read Maintain $LastStation
 * @property-read Line $LastLine
 * @property double $price
 * @property-read string $status_to_paragraph_center
 * @proeprty-read string $entire_model_name
 * @property-read stdClass $full_kind
 * @property-read string $full_kind_name
 * @property-read string $full_kind_name_br
 * @property-read string $full_kind_name_rn
 * @property-read string $maintain_section_name
 * @property-read string $last_maintain_section_name
 * @property-read string $maintain_send_or_receive
 * @property-read string $last_maintain_send_or_receive
 * @property-read string $entire_model_nickname
 * @property-read stdClass $full_kind_nickname
 * @property-read WorkArea $WorkAreas
 * @property string $maintain_signal_post_main_or_indicator_code
 * @property string $last_maintain_signal_post_main_or_indicator_code
 * @property string $maintain_signal_post_main_light_position_code
 * @property string $last_maintain_signal_post_main_light_position_code
 * @property string $maintain_signal_post_indicator_light_position_code
 * @property string $last_maintain_signal_post_indicator_light_position_code
 * @property-read stdClass $maintain_signal_post_main_or_indicator
 * @property-read stdClass $last_maintain_signal_post_main_or_indicator
 * @property-read stdClass $maintain_signal_post_main_light_position
 * @property-read stdClass $last_maintain_signal_post_main_light_position
 * @property-read stdClass $maintain_signal_post_indicator_light_position
 * @property-read stdClass $last_maintain_signal_post_indicator_light_position
 * @property-read SourceName $SourceName
 */
class EntireInstance extends Model
{
    use SoftDeletes;

    public static $STATUSES = [
        "FIXING" => "待修",
        "FIXED" => "所内备品",
        "TRANSFER_OUT" => "出所在途",
        "INSTALLED" => "上道使用",
        "INSTALLING" => "现场备品",
        "TRANSFER_IN" => "入所在途",
        "UNINSTALLED" => "下道停用",
        "UNINSTALLED_BREAKDOWN" => "故障停用",
        "SEND_REPAIR" => "送修中",
        "SCRAP" => "报废",
    ];

    public static $STATUS_NAMES_FOR_INTEGER = [
        "01" => "上道使用",
        "02" => "出所在途",
        "03" => "备品",
        "04" => "下道使用",
        "05" => "故障停用",
        "06" => "入所在途",
        "07" => "待修",
        "08" => "所内成品",
        "09" => "返厂修",
        "10" => "报废",
    ];

    public static $STATUS_TO_PARAGRAPH_CENTER = [
        "INSTALLED" => "01",
        "TRANSFER_OUT" => "02",
        "INSTALLING" => "03",
        "UNINSTALLED" => "04",
        "UNINSTALLED_BREAKDOWN" => "05",
        "TRANSFER_IN" => "06",
        "FIXING" => "07",
        "FIXED" => "08",
        "SEND_REPAIR" => "09",
        "SCRAP" => "10",
    ];
    public static $FIX_CYCLE_UNIT = [
        "YEAR" => "年",
        "MONTH" => "月",
        "WEEK" => "周",
        "DAY" => "日",
    ];
    public static $SOURCE_TYPES = [
        "01" => "新线建设",
        "02" => "大修",
        "03" => "更新改造",
        "04" => "专项整治",
        "05" => "材料计划",
        "06" => "拆旧回收",
        "07" => "外局调入",
        "08" => "其他",
        "09" => "工程遗留",
        "0A" => "故障回所",
    ];
    protected $guarded = [];

    // 信号机主体或表示器
    public static $SIGNAL_POST_MAIN_OR_INDICATOR_CODES = [
        "000001" => "信号灯主体",
        "000002" => "表示器",
    ];

    // 信号机主体灯位：01-1黄，02-绿，03-红，04-2黄，05-白，06-蓝
    public static $SIGNAL_POST_MAIN_LIGHT_POSITION_CODES = [
        "01" => "1黄",
        "02" => "绿",
        "03" => "红",
        "04" => "2黄",
        "05" => "白",
        "06" => "蓝",
    ];

    // 信号机表示器灯位：01-紫，02-黄，03-红，04-1白，05-2白
    public static $SIGNAL_POST_INDICATOR_LIGHT_POSITION_CODES = [
        "01" => "紫",
        "02" => "黄",
        "03" => "红",
        "04" => "1白",
        "05" => "2白",
    ];

    /**
     * 获取信号机主体或表示器
     * @return stdClass
     */
    final public function getMaintainSignalPostMainOrIndicatorAttribute(): stdClass
    {
        return (object)[
            "value" => $this->attributes["maintain_signal_post_main_or_indicator_code"],
            "text" => @self::$SIGNAL_POST_MAIN_OR_INDICATOR_CODES[$this->attributes["maintain_signal_post_main_or_indicator_code"]] ?: "",
        ];
    }

    /**
     * 获取信号机主体灯位
     * @return stdClass
     */
    final public function getMaintainSignalPostMainLightPositionAttribute(): stdClass
    {
        return (object)[
            "value" => $this->attributes["maintain_signal_post_main_light_position_code"],
            "text" => @self::$SIGNAL_POST_MAIN_LIGHT_POSITION_CODES[$this->attributes["maintain_signal_post_main_light_position_code"]] ?: "",
        ];
    }

    /**
     * 获取表示器灯位
     * @return stdClass
     */
    final public function getMaintainSignalPostIndicatorLightPositionAttribute(): stdClass
    {
        return (object)[
            "value" => $this->attributes["maintain_signal_post_indicator_light_position_code"],
            "text" => @self::$SIGNAL_POST_INDICATOR_LIGHT_POSITION_CODES[$this->attributes["maintain_signal_post_indicator_light_position_code"]] ?: "",
        ];
    }

    /**
     * 获取上次信号机主体或表示器
     * @return stdClass
     */
    final public function getLastMaintainSignalPostMainOrIndicatorAttribute(): stdClass
    {
        return (object)[
            "value" => $this->attributes["last_maintain_signal_post_main_or_indicator_code"],
            "text" => @self::$SIGNAL_POST_MAIN_OR_INDICATOR_CODES[$this->attributes["last_maintain_signal_post_main_or_indicator_code"]] ?: "",
        ];
    }

    /**
     * 获取上次信号机主体灯位
     * @return stdClass
     */
    final public function getLastMaintainSignalPostMainLightPositionAttribute(): stdClass
    {
        return (object)[
            "value" => $this->attributes["last_maintain_signal_post_main_light_position_code"],
            "text" => @self::$SIGNAL_POST_MAIN_LIGHT_POSITION_CODES[$this->attributes["last_maintain_signal_post_main_light_position_code"]] ?: "",
        ];
    }

    /**
     * 获取上次表示器灯位
     * @return stdClass
     */
    final public function getLastMaintainSignalPostIndicatorLightPositionAttribute(): stdClass
    {
        return (object)[
            "value" => $this->attributes["last_maintain_signal_post_indicator_light_position_code"],
            "text" => @self::$SIGNAL_POST_INDICATOR_LIGHT_POSITION_CODES[$this->attributes["last_maintain_signal_post_indicator_light_position_code"]] ?: "",
        ];
    }

    /**
     * 判断当前设备器材是否可以出入所
     * @param string $status
     * @param string $direction
     * @return bool|string
     */
    final public static function canIWarehouseInOrOut(string $status, string $direction)
    {
        return self::__canIWarehouseInOrOut($status, $direction);
    }

    /**
     * 判断当前设备器材是否可以出入所
     * @param string $status
     * @param string $direction
     * @return bool|string
     */
    final private static function __canIWarehouseInOrOut(string $status, string $direction)
    {
        // @todo: temporary close
        $statuses = collect(self::$STATUSES);

        switch ($direction) {
            case 'IN':
                return true;
                if (!in_array($status, ['TRANSFER_IN', 'INSTALLING']))
                    return "状态必须是：{$statuses->get('TRANSFER_IN')}、{$statuses->get('INSTALLING')}。当前状态({$statuses->get($status)})";
                return true;
            case 'OUT':
                if ($status != 'FIXED')
                    return "状态必须是：{$statuses->get('FIXED')}。当前状态（{$statuses->get($status)}）。";
                return true;
            default:
                return "获取出入所方向参数错误";
        }
    }

    /**
     * 判断当前设备器材是否可以上道
     * @param string $status
     * @return bool|string
     */
    final public static function canIInstalled(string $status)
    {
        return self::_canIInstalled($status);
    }

    /**
     * 判断当前设备器材是否可以上道
     * @param string $status
     * @return bool|string
     */
    final private static function _canIInstalled(string $status)
    {
        return true;
        $statuses = collect(self::$STATUSES);

        if (!in_array($status, ['TRANSFER_OUT', 'INSTALLING',]))
            return "状态必须是：{$statuses->get('TRANSFER_OUT')}、{$statuses->get('INSTALLING')}。当前状态（{$statuses->get($status)}）。";
        return true;
    }

    /**
     * 判断当前设备器材是否可以入柜
     * @param string $status
     * @return bool|string
     */
    final public static function canIInstalling(string $status)
    {
        return self::_canIInstalling($status);
    }

    /**
     * 判断当前设备器材是否可以入柜
     * @param string $status
     * @return bool|string
     */
    final private static function _canIInstalling(string $status)
    {
        return true;
        $statuses = collect(self::$STATUSES);

        if (!in_array($status, ['TRANSFER_OUT', 'INSTALLED',]))
            return "状态必须是：{$statuses->get('TRANSFER_OUT')}、{$statuses->get('INSTALLED')}。当前状态（{$statuses->get($status)}）。";
        return true;
    }

    /**
     * 生成新唯一编号
     * @param string $entire_model_unique_code
     * @return string
     */
    final public static function generateIdentityCode(string $entire_model_unique_code): string
    {
        $first_code = substr($entire_model_unique_code, 0, 1);
        $len = strlen($entire_model_unique_code);

        $max_code = self::with([])->where('entire_model_unique_code', $entire_model_unique_code)->max('identity_code');
        $next_code = intval(substr($max_code, strlen($entire_model_unique_code) + 4)) + 1;

        return "{$entire_model_unique_code}" . env('ORGANIZATION_CODE') . str_pad(
            $next_code,
            $len,
            '0',
            STR_PAD_LEFT
        ) . $first_code == 'Q' ? 'H' : '';
    }

    /**
     * 获取原始周期修单位
     * @param $value
     * @return int|string
     */
    final public static function flipFixCycleUnit($value)
    {
        return array_flip(self::$FIX_CYCLE_UNIT)[$value];
    }

    protected static function boot()
    {
        parent::boot();

        /**
         * 所有查询不包括已报废
         */
        static::addGlobalScope('status', function (Builder $builder) {
            $builder->where('status', '<>', 'SCRAP');
        });
    }

    /**
     * 获取原始值
     * @param string $key
     * @return mixed
     */
    final public function property(string $key)
    {
        return $this->attributes[$key];
    }

    public function getStatusToParagraphCenterAttribute($value)
    {
        return @self::$STATUS_TO_PARAGRAPH_CENTER[$this->attributes["status"]] ?: "";
    }

    final public function prototype(string $key, $default = "")
    {
        return @$this->attributes[$key] ?: $default;
    }

    /**
     * 判断当前设备器材是否可以入所
     * @param $value
     * @return bool|string
     */
    final public function getCanIWarehouseInAttribute($value)
    {
        return self::__canIWarehouseInOrOut($this->attributes['status'], 'IN');
    }

    /**
     * 判断当前设备器材是否可以出所
     * @param $value
     * @return bool|string
     */
    final public function getCanIWarehouseOutAttribute($value)
    {
        return self::__canIWarehouseInOrOut($this->attributes['status'], 'OUT');
    }

    /**
     * 判断当前设备器材是否可以上道
     * @param $value
     * @return bool|string
     */
    final public function getCanIInstalledAttribute($value)
    {
        return self::_canIInstalled($this->attributes['status']);
    }

    /**
     * 判断当前设备器材是否可以入柜
     * @param $value
     * @return bool|string
     */
    final public function getCanIInstallingAttribute($value)
    {
        return self::_canIInstalling($this->attributes['status']);
    }

    /**
     * 判断当前设备器材是否可以下道
     * @param string $status
     * @return bool|string
     */
    final public function canIUnInstall(string $status)
    {
        return self::_canIUnInstall($status);
    }

    /**
     * 判断当前设备器材是否可以下道
     * @param string $status
     * @return bool|string
     */
    final private static function _canIUnInstall(string $status)
    {
        return true;
        $statuses = collect(self::$STATUSES);

        if ($status != 'INSTALLED')
            return "状态必须是：{$statuses->get('INSTALLED')}。当前状态（{$statuses->get($status)}）。";
        return true;
    }

    /**
     * 判断当前设备器材是否可以下道
     * @param $value
     * @return bool|string
     */
    final public function getCanIUnInstallAttribute($value)
    {
        return self::_canIUnInstall($this->attributes['status']);
    }

    /**
     * 故障日志
     * @return HasMany
     */
    final public function BreakdownLogs(): HasMany
    {
        return $this->hasMany(BreakdownLog::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 状态
     * @param $value
     * @return string
     */
    final public function getStatusAttribute($value): string
    {
        return @self::$STATUSES[$value] ?: '无';
    }

    /**
     * 获取来源类型
     * @param $value
     * @return string
     */
    final public function getSourceTypeAttribute($value): string
    {
        return @self::$SOURCE_TYPES[$value] ?? '无';
    }

    /**
     * 获取来源名称
     * @return HasOne
     */
    final public function SourceName(): HasOne
    {
        return $this->hasOne(SourceName::class, "name", "source_name");
    }

    /**
     * 原始状态（代码）
     * @param $value
     * @return string
     */
    final public function getOriginalStatusAttribute($value): string
    {
        return $this->attributes['status'] ?: '';
    }

    /**
     * 获取仓库位置名称和代码
     * @param $value
     * @return array
     */
    final public function getStorehouseLocationAttribute($value): array
    {
        $has_img = false;

        $position = DB::table("storehouses as s")
            ->selectRaw(join(",", [
                "s.name as storehouse_name",
                "a.name as area_name",
                "p.name as platoon_name",
                "sh.name as shelf_name",
                "t.name as tier_name",
                "po.name as position_name",
            ]))
            ->join(DB::raw("areas a"), "s.unique_code", "=", "a.storehouse_unique_code")
            ->join(DB::raw("platoons p"), "a.unique_code", "=", "p.area_unique_code")
            ->join(DB::raw("shelves sh"), "p.unique_code", "=", "sh.platoon_unique_code")
            ->join(DB::raw("tiers t"), "sh.unique_code", "=", "t.shelf_unique_code")
            ->join(DB::raw("positions po"), "t.unique_code", "=", "po.tier_unique_code")
            ->where("po.unique_code", $this->attributes["location_unique_code"])
            ->first();

        $name = $position
            ? collect([
                $position->storehouse_name ? rtrim($position->storehouse_name, "仓") . "仓" : "",
                $position->area_name ? rtrim($position->area_name, "区") . "区" : "",
                $position->platoon_name ? rtrim($position->platoon_name, "排") . "排" : "",
                $position->shelf_name ? rtrim($position->shelf_name, "架") . "架" : "",
                $position->tier_name ? rtrim($position->tier_name, "层") . "层" : "",
                $position->position_name ? rtrim($position->position_name, "位") . "位" : "",
            ])
                ->implode("")
            : "";

        return ["code" => $this->attributes["location_unique_code"], "name" => $name, 'has_img' => $has_img];
    }

    /**
     * 上一次所属车间
     * @return HasOne
     */
    final public function LastSceneWorkshop(): HasOne
    {
        return $this->hasOne(Maintain::class, "name", "last_maintain_workshop_name");
    }

    /**
     * 所属现场车间
     * @return HasOne
     */
    final public function SceneWorkshop(): HasOne
    {
        return $this->hasOne(Maintain::class, 'name', 'maintain_workshop_name');
    }

    /**
     * 上一次所属线别
     * @return HasOne
     */
    final public function LastLine(): HasOne
    {
        return $this->hasOne(Line::class, "unique_code", "last_unique_code");
    }

    /**
     * 所属线别
     * @return HasOne
     */
    final public function Line(): HasOne
    {
        return $this->hasOne(Line::class, 'unique_code', 'line_unique_code');
    }

    /**
     * 上一次所属车站
     * @return HasOne
     */
    final public function LastStation(): HasOne
    {
        return $this->hasOne(Maintain::class, "name", "last_maintain_station_name");
    }

    /**
     * 所属车站
     * @return HasOne
     */
    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'name', 'maintain_station_name');
    }

    /**
     * 种类
     * @return HasOne
     */
    final public function Category(): HasOne
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    /**
     * 类型
     * @return HasOne
     */
    final public function EntireModel(): HasOne
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    /**
     * 子类
     * @return HasOne
     */
    final public function SubModel(): HasOne
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'model_unique_code');
    }

    /**
     * 型号
     * @return HasOne
     */
    final public function PartModel(): HasOne
    {
        return $this->hasOne(PartModel::class, 'unique_code', 'part_model_unique_code');
    }

    /**
     * 整机
     * @return HasOne
     */
    final public function ParentInstance(): HasOne
    {
        return $this->hasOne(self::class, 'identity_code', 'entire_instance_identity_code');
    }

    /**
     * 部件列表
     * @return HasMany
     */
    final public function PartInstances(): HasMany
    {
        return $this->hasMany(self::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 部件种类
     * @return HasOne
     */
    final public function PartCategory(): HasOne
    {
        return $this->hasOne(PartCategory::class, 'id', 'part_category_id');
    }

    final public function PartInstance(): HasOne
    {
        return $this->hasOne(PartInstance::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 最后一张检修单
     * @return HasOne
     */
    final public function FixWorkflow(): HasOne
    {
        return $this->hasOne(FixWorkflow::class, 'serial_number', 'fix_workflow_serial_number');
    }

    /**
     * 检修单历史
     * @return HasMany
     */
    final public function FixWorkflows(): HasMany
    {
        return $this->hasMany(FixWorkflow::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 检测标准值
     * @return HasMany
     */
    final public function Measurements(): HasMany
    {
        return $this->hasMany(Measurement::class, 'entire_model_unique_code', 'entire_model_unique_code');
    }

    /**
     * 出所单
     * @return HasOne
     */
    final public function WarehouseReportByOut(): HasOne
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'last_warehouse_report_serial_number_by_out');
    }

    /**
     * 出入所单
     * @return BelongsToMany
     */
    final public function WarehouseReports(): BelongsToMany
    {
        return $this->belongsToMany(WarehouseReport::class, 'warehouse_report_entire_instances', 'warehouse_report_serial_number', 'entire_instance_identity_code');
    }

    final public function EntireModelIdCode(): HasOne
    {
        return $this->hasOne(EntireModelIdCode::class, 'code', 'entire_model_id_code');
    }

    /**
     * 日志
     * @return HasMany
     */
    final public function EntireInstanceLogs(): HasMany
    {
        return $this->hasMany(EntireInstanceLog::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 仓库位置
     * @return BelongsTo
     */
    final public function WithPosition(): HasOne
    {
        return $this->hasOne(Position::class, 'unique_code', 'location_unique_code');
    }

    /**
     * 供应商
     * @return HasOne
     */
    final public function Factory(): HasOne
    {
        return $this->hasOne(Factory::class, 'name', 'factory_name');
    }

    /**
     * 最后一张送修单
     * @return HasMany
     */
    final public function WithSendRepairInstances(): HasMany
    {
        return $this->hasMany(SendRepairInstance::class, 'material_unique_code', 'identity_code');
    }

    /**
     * 室内组合位置
     * @return HasOne
     */
    final public function InstallPosition(): HasOne
    {
        return $this->hasOne(InstallPosition::class, 'unique_code', 'maintain_location_code');
    }

    /**
     * 上一次室内组合位置
     * @return HasOne
     */
    final public function LastInstallPosition(): HasOne
    {
        return $this->hasOne(InstallPosition::class, 'unique_code', 'last_maintain_location_code');
    }

    /**
     * 获取上道位置名称
     * @param $value
     * @return string
     */
    public function getUsePositionNameAttribute(): string
    {
        $use_position = $this->getUsePositionAttribute();
        return TextFacade::joinWithNotEmpty(" ", [
            @$use_position->line->name ?: "",
            @$use_position->workshop->name ?: "",
            @$use_position->station->name ?: "",
            @$use_position->inside->text ?: "",
            TextFacade::joinWithNotEmpty(" ", [
                @$use_position->outside->crossroad_number ?: "",
                @$use_position->outside->open_direction ?: "",
                @$use_position->outside->maintain_section_name ?: "",
                @$use_position->outside->maintain_send_or_receive ?: "",
                @$use_position->signal_post->main_or_indicator->text ?: "",
                @$use_position->signal_post->main_light_position->text ?: "",
                @$use_position->signal_post->indicator_light_position->text ?: "",
            ]),
        ]);
    }

    /**
     * 获取上道使用位置
     * @param $value
     * @return stdClass
     */
    final public function getUsePositionAttribute(): stdClass
    {
        $__ = new stdClass();

        $__->line = (object)[
            "name" => @$this->Line->name ?: "",
            "unique_code" => @$this->Line->unique_code ?: "",
        ];
        $__->station = (object)[
            "name" => @$this->Station->name ?: "",
            "unique_code" => @$this->Station->unique_code ?: "",
            "scene_workshop_unique_code" => @$this->Station->parent_unique_code ?: "",
        ];
        $__->workshop = (object)[
            "name" => @$this->SceneWorkshop->name ?: "",
            "unique_code" => @$this->SceneWorkshop->unique_code ?: "",
        ];
        $__->inside = (object)[
            "InstallPosition" => @$this->InstallPosition,
            "text" => @$this->InstallPosition->real_name,
            "unique_code" => $this->attributes["maintain_location_code"],
        ];
        $__->outside = (object)[
            "crossroad_number" => $this->attributes["crossroad_number"],
            "open_direction" => $this->attributes["open_direction"],
            "maintain_section_name" => $this->attributes["maintain_section_name"],
            "maintain_send_or_receive" => $this->attributes["maintain_send_or_receive"],
        ];
        switch ($this->maintain_signal_post_main_or_indicator_code) {
            case "000001":
                $__->signal_post = (object)[
                    "main_or_indicator" => $this->getMaintainSignalPostMainOrIndicatorAttribute(),
                    "main_light_position" => $this->getMaintainSignalPostMainLightPositionAttribute(),
                    "indicator_light_position" => "",
                ];
                break;
            case "000002":
                $__->signal_post = (object)[
                    "main_or_indicator" => $this->getMaintainSignalPostMainOrIndicatorAttribute(),
                    "main_light_position" => "",
                    "indicator_light_position" => $this->getMaintainSignalPostIndicatorLightPositionAttribute(),
                ];
                break;
            default:
                $__->signal_post = (object)[
                    "main_or_indicator" => "",
                    "main_light_position" => "",
                    "indicator_light_position" => "",
                ];
                break;
        }


        return $__;
    }

    /**
     * 获取上一次上道使用位置名称
     * @param $value
     * @return string
     */
    final public function getLastUsePositionNameAttribute(): string
    {
        $use_position = $this->getLastUsePositionAttribute();
        return TextFacade::joinWithNotEmpty(" ", [
            @$use_position->line->name ?: "",
            @$use_position->workshop->name ?: "",
            @$use_position->station->name ?: "",
            @$use_position->inside->text ?: "",
            TextFacade::joinWithNotEmpty(" ", [
                @$use_position->outside->crossroad_number ?: "",
                @$use_position->outside->open_direction ?: "",
                @$use_position->outside->maintain_section_name ?: "",
                @$use_position->outside->maintain_send_or_receive ?: "",
                @$use_position->signal_post->main_or_indicator->text ?: "",
                @$use_position->signal_post->main_light_position->text ?: "",
                @$use_position->signal_post->indicator_light_position->text ?: "",
            ]),
        ]);
    }

    /**
     * 获取上一次上道使用位置
     * @param $value
     * @return stdClass
     */
    final public function getLastUsePositionAttribute(): stdClass
    {
        $__ = new stdClass();

        $__->line = (object)[
            "name" => @$this->LastLine->name ?: "",
            "unique_code" => @$this->LastLine->unique_code ?: "",
        ];
        $__->station = (object)[
            "name" => @$this->LastStation->name ?: "",
            "unique_code" => @$this->LastStation->unique_code ?: "",
            "scene_workshop_unique_code" => @$this->LastStation->parent_unique_code ?: "",
        ];
        $__->workshop = (object)[
            "name" => @$this->LastSceneWorkshop->name ?: "",
            "unique_code" => @$this->LastSceneWorkshop->unique_code ?: "",
        ];
        $__->inside = (object)[
            "InstallPosition" => @$this->LastInstallPosition,
            "text" => @$this->LastInstallPosition->real_name,
            "unique_code" => $this->attributes["last_maintain_location_code"],
        ];
        $__->outside = (object)[
            "crossroad_number" => $this->attributes["last_crossroad_number"],
            "open_direction" => $this->attributes["last_open_direction"],
            "maintain_section_name" => $this->attributes["last_maintain_section_name"],
            "maintain_send_or_receive" => $this->attributes["last_maintain_send_or_receive"],
        ];
        switch ($this->last_maintain_signal_post_main_or_indicator_code) {
            case "000001":
                $__->signal_post = (object)[
                    "main_or_indicator" => $this->getLastMaintainSignalPostMainOrIndicatorAttribute(),
                    "main_light_position" => $this->getLastMaintainSignalPostMainLightPositionAttribute(),
                    "indicator_light_position" => "",
                ];
                break;
            case "000002":
                $__->signal_post = (object)[
                    "main_or_indicator" => $this->getLastMaintainSignalPostMainOrIndicatorAttribute(),
                    "main_light_position" => "",
                    "indicator_light_position" => $this->getLastMaintainSignalPostIndicatorLightPositionAttribute(),
                ];
                break;
            default:
                $__->signal_post = (object)[
                    "main_or_indicator" => "",
                    "main_light_position" => "",
                    "indicator_light_position" => "",
                ];
                break;
        }


        return $__;
    }

    /**
     * 设备锁
     * @return HasOne
     */
    final public function EntireInstanceLock(): HasOne
    {
        return $this->hasOne(EntireInstanceLock::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 任务锁
     * @return HasOne|null
     */
    final public function TaskLock()
    {
        switch ($this->attributes['task_lock_type']) {
            case 'CYCLE_FIX':
                return $this->hasOne(RepairBasePlanOutCycleFixBill::class, 'serial_number', 'task_sn');
            case 'BREAKDOWN':
                return $this->hasOne(RepairBaseBreakdownOrder::class, 'serial_number', 'task_sn');
            case 'NEW_STATION':
                return $this->hasOne(V250TaskOrder::class, 'serial_number', 'task_sn');
            default:
                return null;
        }
    }

    /**
     * 是否有锁
     * @param $value
     * @return bool|string
     */
    final public function getHasTaskLockAttribute($value)
    {
        return $this->TaskLock ? (@$this->TaskLock->remark ?: '设备器材被其他任务占用。') : true;
    }

    /**
     * entire instance in tagging reports
     * @return HasMany
     */
    final public function EntireInstanceExcelTaggingItems(): HasMany
    {
        return $this->hasMany(EntireInstanceExcelTaggingIdentityCode::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 旧类型
     * @return HasOne
     */
    final public function OldEntireModel(): HasOne
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'old_entire_model_unique_code');
    }

    /**
     * 旧型号
     * @return HasOne
     */
    final public function OldSubModel(): HasOne
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'old_model_unique_code');
    }

    /**
     * 器材删除人
     * @return HasOne
     */
    final public function Deleter(): HasOne
    {
        return $this->hasOne(Account::class, "id", "deleter_id");
    }

    /**
     * 获取完整种类型名称
     * @return string
     */
    final public function getFullKindNameAttribute(): string
    {
        $full_kind = $this->getFullKindAttribute();
        return TextFacade::joinWithNotEmpty(" ", [
            $full_kind->category_name,
            $full_kind->entire_model_name,
            $full_kind->sub_model_name,
        ]);
    }

    /**
     * 获取种类型名称（br）换行
     * @return string
     */
    final public function getFullKindNameBrAttribute(): string
    {
        $full_kind = $this->getFullKindAttribute();
        return TextFacade::joinWithNotEmpty("<br>", [
            $full_kind->category_name,
            $full_kind->entire_model_name,
            $full_kind->sub_model_name,
        ]);
    }

    /**
     * 获取种类型名称（rn）换行
     * @return string
     */
    final public function getFullKindNameRnAttribute(): string
    {
        $full_kind = $this->getFullKindAttribute();
        return TextFacade::joinWithNotEmpty("\r\n", [
            $full_kind->category_name,
            $full_kind->entire_model_name,
            $full_kind->sub_model_name,
        ]);
    }

    /**
     * 获取种类型结构化数据
     * @return stdClass
     */
    final public function getFullKindAttribute(): stdClass
    {
        return (object)[
            "category_name" => @$this->Category->name ?: "",
            "category_unique_code" => $this->Category->unique_code ?: "",
            "entire_model_name" => @$this->getEntireModelNameAttribute() ?: "",
            "entire_model_unique_code" => strlen($this->attributes["entire_model_unique_code"]) == 7 ? substr($this->attributes["entire_model_unique_code"], 0, 5) : $this->attributes["entire_model_unique_code"],
            "sub_model_name" => @$this->SubModel->name ?: "",
            "sub_model_unique_code" => @$this->SubModel->unique_code ?: "",
        ];
    }

    public function getFullKindNicknameAttribute(): stdClass
    {
        return (object)[
            "category_nickname" => @$this->Category->nickname ?: "",
            "entire_model_nickname" => @$this->getEntireModelNicknameAttribute(),
            "sub_model_nickname" => @$this->SubModel->nickname ?: "",
        ];
    }

    /**
     * 获取类型名称
     * @return string
     */
    final public function getEntireModelNameAttribute(): string
    {
        if ((@$this->SubModel->Parent->name != @$this->SubModel->name) && !empty(@$this->SubModel->name)) {
            return @$this->SubModel->Parent->name ?: "";
        } else {
            return @$this->SubModel->name ?: "";
        }
    }

    /**
     * 获取类型昵称
     * @return string
     */
    final public function getEntireModelNicknameAttribute(): string
    {
        if ((@$this->SubModel->Parent->name != @$this->SubModel->name) && !empty(@$this->SubModel->name)) {
            return @$this->SubModel->Parent->nickname ?: "";
        } else {
            return @$this->SubModel->nickname ?: "";
        }
    }

    /**
     * 所属工区
     * @return HasOne
     */
    final public function WorkArea(): HasOne
    {
        return $this->hasOne(WorkArea::class, "unique_code", "work_area_unique_code");
    }

    /**
     * 继承上道位置（下道时）
     * @param array $ex
     * @return $this
     */
    final public function FillInheritInstallPositionForUnInstall(array $ex = []): self
    {
        $ret = [
            "last_maintain_station_name" => @$this->maintain_station_name ?: "",  // 继承车站
            "maintain_station_name" => "",  // 清空车站
            "last_maintain_workshop_name" => @$this->maintain_workshop_name ?: "",  // 继承车间
            "maintain_workshop_name" => "",  // 清空车间
            "last_line_unique_code" => @$this->line_unique_code ?: "",  // 继承线别
            "line_unique_code" => "",  // 清空线别
            "last_crossroad_number" => @$this->crossroad_number ?: "",  // 继承道岔号
            "crossroad_number" => "",  // 清空道岔号
            "last_open_direction" => @$this->open_direction ?: "",  // 继承开向
            "open_direction" => "",  // 开向
            "last_maintain_location_code" => @$this->maintain_location_code ?: "",  // 继承室内上道位置
            "maintain_location_code" => "",  // 清空室内上道位置
            "next_fixing_time" => null,  // 下次周期修时间戳
            "next_fixing_month" => null,  // 下次周期修月份
            "next_fixing_day" => null,  // 下次周期修日期
            "location_unique_code" => "",  // 仓库位置
            "is_bind_location" => 0,  // 绑定位置
            "last_out_at" => null,  // 上次出所时间
            "last_maintain_section_name" => @$this->maintain_section_name ?: "",  // 继承区间名称
            "maintain_section_name" => "",  // 清空区间名称
            "last_maintain_send_or_receive" => @$this->last_maintain_send_or_receive ?: "",  // 继承送/受端
            "maintain_send_or_receive" => "",  // 清除送/受端
            "last_maintain_signal_post_main_or_indicator_code" => @$this->maintain_signal_post_main_or_indicator_code ?: "",  // 继承信号机主体或表示器
            "maintain_signal_post_main_or_indicator_code" => "",  // 清空信号机主体或表示器
            "last_maintain_signal_post_main_light_position_code" => @$this->maintain_signal_post_main_light_position_code ?: "",  // 继承信号机主体灯位
            "maintain_signal_post_main_light_position_code" => "",  // 清空信号机主体灯位
            "last_maintain_signal_post_indicator_light_position_code" => @$this->maintain_signal_post_indicator_light_position_code ?: "",  // 继承表示器灯位
            "maintain_signal_post_indicator_light_position_code" => "",  // 清空表示器灯位
            "last_installed_at" => @$this->installed_at,  // 继承上道时间
            "installed_at" => null,  // 清除上道时间
            "last_fixer_name" => @$this->fixer_name,  // 继承检修人
            "fixer_name" => "",  // 清除检修人
            "last_fixed_at" => @$this->fixed_at,  // 继承检修时间
            "fixed_at" => null,  // 清除检修时间
            "last_checker_name" => @$this->checker_name,  // 继承验收人
            "checker_name" => "",  // 清除验收人
            "last_checked_at" => @$this->checked_at,  // 继承验收时间
            "checked_at" => null,  // 清除验收时间
        ];

        $this->fill($ex ? array_merge($ret, $ex) : $ret);

        return $this;
    }

    /**
     * 清除上道位置（入所时）
     * @param array $ex
     * @return $this
     */
    final public function FillClearInstallPositionForIn(array $ex = []): self
    {
        $ret = [
            "status" => "FIXING",  // 状态
            "maintain_station_name" => "",  // 清空车站
            "maintain_workshop_name" => env("JWT_ISS"),  // 所属车间（当前专业车间）
            "line_unique_code" => "",  // 清空线别
            "crossroad_number" => "",  // 清空道岔号
            "open_direction" => "",  // 开向
            "maintain_location_code" => "",  // 清空室内上道位置
            "next_fixing_time" => null,  // 下次周期修时间戳
            "next_fixing_month" => null,  // 下次周期修月份
            "next_fixing_day" => null,  // 下次周期修日期
            "location_unique_code" => "",  // 仓库位置
            "is_bind_location" => 0,  // 绑定位置
            "last_out_at" => null,  // 上次出所时间
            "maintain_section_name" => "",  // 清空区间名称
            "maintain_send_or_receive" => "",  // 清除送/受端
            "maintain_signal_post_main_or_indicator_code" => "",  // 清空信号机主体或表示器
            "maintain_signal_post_main_light_position_code" => "",  // 清空信号机主体灯位
            "maintain_signal_post_indicator_light_position_code" => "",  // 清空表示器灯位
            "installed_at" => null,  // 清除上道时间
            "fixer_name" => "",  // 清除检修人
            "fixed_at" => null,  // 清除检修时间
            "checker_name" => "",  // 清除验收人
            "checked_at" => null,  // 清除验收时间
        ];
        $this->fill($ex ? array_merge($ret, $ex) : $ret);

        return $this;
    }
}
