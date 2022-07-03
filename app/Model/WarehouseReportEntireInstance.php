<?php

namespace App\Model;

use App\Facades\TextFacade;
use App\Model\Install\InstallPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use stdClass;

/**
 * App\Model\WarehouseReportEntireInstance
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $warehouse_report_serial_number
 * @property string $entire_instance_identity_code
 * @property string $in_warehouse_breakdown_explain 入所故障描述
 * @property string $maintain_station_name 车站名称
 * @property string $maintain_location_code 组合位置
 * @property string $crossroad_number 道岔号
 * @property string $traction 牵引
 * @property string $line_name 线制
 * @property string $crossroad_type 道岔类型
 * @property int $extrusion_protect 防挤压保护罩
 * @property string $point_switch_group_type 转辙机分组类型
 * @property string $open_direction 开向
 * @property string $said_rod 表示杆特征
 * @property int $is_out 是否已经完成出所
 * @property-read Collection|PivotWarehouseReportEntireInstanceAndBreakdownTypes[] $BreakdownTypes
 * @property-read int|null $breakdown_types_count
 * @property-read EntireInstance $EntireInstance
 * @property-read WarehouseReport $WarehouseReport
 * @property-read Maintain $Station
 * @property-read Maintain $Workshop
 * @property-read InstallPosition $InstallPosition
 * @propert string $maintain_signal_post_main_or_indicator_code
 * @propert string $maintain_signal_post_main_light_position_code
 * @propert string $maintain_signal_post_indicator_light_position_code
 * @property-read stdClass $maintain_signal_post_main_or_indicator
 * @property-read stdClass $maintain_signal_post_main_light_position
 * @property-read stdClass $maintain_signal_post_indicator_light_position
 * @property-read stdClass $use_position
 * @property-read string $use_position_name
 */
class WarehouseReportEntireInstance extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    final public function WarehouseReport(): HasOne
    {
        return $this->hasOne(WarehouseReport::class, "serial_number", "warehouse_report_serial_number");
    }

    final public function Workshop(): HasOne
    {
        return $this->hasOne(Maintain::class, "name", "maintain_workshop_name");
    }

    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, "name", "maintain_station_name");
    }

    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, "identity_code", "entire_instance_identity_code");
    }

    final public function BreakdownTypes(): HasMany
    {
        return $this->hasMany(PivotWarehouseReportEntireInstanceAndBreakdownTypes::class, "warehouse_report_entire_instance_id", "id");
    }

    final public function Line(): HasOne
    {
        return $this->hasOne(Line::class, "unique_code", "line_unique_code");
    }

    final public function InstallPosition(): HasOne
    {
        return $this->hasOne(InstallPosition::class, "unique_code", "maintain_location_code");
    }

    /**
     * 获取信号机主体或表示器
     * @return stdClass
     */
    final public function getMaintainSignalPostMainOrIndicatorAttribute(): stdClass
    {
        return (object)[
            "value" => $this->attributes["maintain_signal_post_main_or_indicator_code"],
            "text" => @EntireInstance::$SIGNAL_POST_MAIN_OR_INDICATOR_CODES[$this->attributes["maintain_signal_post_main_or_indicator_code"]] ?: "",
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
            "text" => @EntireInstance::$SIGNAL_POST_MAIN_LIGHT_POSITION_CODES[$this->attributes["maintain_signal_post_main_light_position_code"]] ?: "",
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
            "text" => @EntireInstance::$SIGNAL_POST_INDICATOR_LIGHT_POSITION_CODES[$this->attributes["maintain_signal_post_indicator_light_position_code"]] ?: "",
        ];
    }

    /**
     * 获取上道使用位置名称
     * @return string
     */
    final public function getUsePositionNameAttribute(): string
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

    final public function getUsePositionAttribute()
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
            "name" => @$this->Workshop->name ?: "",
            "unique_code" => @$this->Workshop->unique_code ?: "",
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
        $__->signal_post = (object)[
            "main_or_indicator" => $this->getMaintainSignalPostMainOrIndicatorAttribute(),
            "main_light_position" => $this->getMaintainSignalPostMainLightPositionAttribute(),
            "indicator_light_position" => $this->getMaintainSignalPostIndicatorLightPositionAttribute(),
        ];

        return $__;
    }
}
