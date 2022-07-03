<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property int|null $repair_base_breakdown_order_temp_entire_instance_id 故障入所单设备
 * @property int $breakdown_type_id 故障类型
 * @property string $type 故障描述类型
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\BreakdownType[] $BreakdownTypes
 * @property-read int|null $breakdown_types_count
 * @property-read \App\Model\RepairBaseBreakdownOrderTempEntireInstance $RepairBaseBreakdownOrderTempEntireInstance
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes whereBreakdownTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes whereRepairBaseBreakdownOrderTempEntireInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes extends Model
{
    public static $TYPES = [
        'WAREHOUSE_IN' => '入所故障类型',
        'STATION' => '现场故障类型',
    ];
    protected $guarded = [];

    final public function RepairBaseBreakdownOrderTempEntireInstance()
    {
        return $this->hasOne(RepairBaseBreakdownOrderTempEntireInstance::class, 'id', 'repair_base_breakdown_order_temp_entire_instance_id');
    }

    final public function BreakdownTypes()
    {
        return $this->belongsToMany(BreakdownType::class, 'pivot_breakdown_order_temp_entire_instance_and_breakdown_types', 'repair_base_breakdown_order_temp_entire_instance_id', 'breakdown_type_id');
    }

    final public function getTypeAttribute($val)
    {
        return @self::$TYPES[$val] ?: '无';
    }
}
