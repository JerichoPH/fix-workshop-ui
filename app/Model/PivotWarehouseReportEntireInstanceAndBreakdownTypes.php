<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property int $warehouse_report_entire_instance_id 入所单设备
 * @property int $breakdown_type_id 故障类型
 * @property string $type 故障描述类型
 * @property-read \App\Model\BreakdownType $BreakdownType
 * @property-read \App\Model\WarehouseReportEntireInstance $WarehouseReportEntireInstance
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes whereBreakdownTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes whereWarehouseReportEntireInstanceId($value)
 * @mixin \Eloquent
 */
class PivotWarehouseReportEntireInstanceAndBreakdownTypes extends Model
{
    public static $TYPES = [
        'WAREHOUSE_IN' => '入所故障类型',
        'STATION' => '现场故障类型',
    ];
    protected $guarded = [];

    final public function WarehouseReportEntireInstance()
    {
        return $this->hasOne(WarehouseReportEntireInstance::class, 'id', 'warehouse_report_entire_instance_id');
    }

    final public function BreakdownType()
    {
        return $this->hasOne(BreakdownType::class, 'id', 'breakdown_type_id');
    }

    final public function getTypeAttribute($val)
    {
        return @self::$TYPES[$val] ?: '无';
    }
}
