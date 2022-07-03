<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Model\RepairBaseBreakdownOrder
 *
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $serial_number 流水号
 * @property string $scene_workshop_code 现场车间代码
 * @property string $station_code 车站代码
 * @property string $status 状态：
 * UNDONE未完成
 * UNSATISFIED未满足
 * SATISFY满足
 * DONE已完成
 * @property string $direction 方向：
 * IN入所
 * OUT出所
 * @property int $work_area_id 所属工区:
 * 1转辙机
 * 2继电器
 * 3综合
 * @property string $work_area_unique_code 所属工区代码
 * @property string $in_sn 高频修入所计划
 * @property int|null $processor_id 经办人
 * @property string|null $processed_at 处理时间
 * @property-read Collection|RepairBaseBreakdownOrderEntireInstance[] $InEntireInstances
 * @property-read int|null $in_entire_instances_count
 * @property-read Collection|RepairBaseBreakdownOrderEntireInstance[] $OutEntireInstances
 * @property-read int|null $out_entire_instances_count
 * @property-read Account $Processor
 */
class RepairBaseBreakdownOrder extends Model
{
    public static $STATUSES = [
        "UNDONE" => "未完成",
        "UNSATISFIED" => "未满足",
        "SATISFY" => "满足",
        "DONE" => "已完成",
    ];

    public static $DIRECTIONS = [
        "IN" => "入所",
        "OUT" => "出所",
    ];

    public static $WORK_ARES = [
        1 => "转辙机工区",
        2 => "继电器工区",
        3 => "综合工区"
    ];

    protected $guarded = [];

    final public function getDirectionAttribute($value): string
    {
        return self::$DIRECTIONS[$value];
    }

    final public function getWorkAreaIdAttribute($value): string
    {
        return @self::$WORK_ARES[$value] ?: "无";
    }

    final public function getStatusAttribute($value): string
    {
        return self::$STATUSES[$value];
    }

    final public function InEntireInstances(): HasMany
    {
        return $this->hasMany(RepairBaseBreakdownOrderEntireInstance::class, "in_sn", "serial_number");
    }

    final public function OutEntireInstances(): HasMany
    {
        return $this->hasMany(RepairBaseBreakdownOrderEntireInstance::class, "out_sn", "serial_number");
    }

    final public function Processor(): HasOne
    {
        return $this->hasOne(Account::class, "id", "processor_id");
    }

    final public function SceneWorkshop(): HasOne
    {
        return $this->hasOne(Maintain::class, "unique_code", "scene_workshop_code");
    }

    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, "unique_code", "station_code");
    }

    final public function WorkArea():HasOne
    {
        return $this->hasOne(WorkArea::class,"unique_code","work_area_unique_code");
    }
}
