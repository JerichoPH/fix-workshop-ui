<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseNewStationOrder
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseNewStationOrderEntireInstance[] $InEntireInstances
 * @property-read int|null $in_entire_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseNewStationOrderModel[] $Models
 * @property-read int|null $models_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseNewStationOrderEntireInstance[] $OutEntireInstances
 * @property-read int|null $out_entire_instances_count
 * @property-read \App\Model\Maintain $SceneWorkshop
 * @property-read \App\Model\Maintain $Station
 * @property-read mixed $direction
 * @property-read mixed $status
 * @property-read mixed $work_area_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseNewStationOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseNewStationOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseNewStationOrder query()
 * @mixin \Eloquent
 */
class RepairBaseNewStationOrder extends Model
{
    public static $STATUSES = [
        'UNDONE' => '未完成',
        'UNSATISFIED' => '未满足',
        'SATISFY' => '满足',
        'DONE' => '已完成',
    ];

    public static $DIRECTIONS = [
        'IN' => '入所',
        'OUT' => '出所',
    ];

    public static $WORK_ARES = [
        1 => '转辙机工区',
        2 => '继电器工区',
        3 => '综合工区'
    ];

    protected $guarded = [];

    final public function getDirectionAttribute($value)
    {
        return self::$DIRECTIONS[$value];
    }

    final public function getWorkAreaIdAttribute($value)
    {
        return self::$WORK_ARES[$value];
    }

    final public function getStatusAttribute($value)
    {
        return self::$STATUSES[$value];
    }

    final public function SceneWorkshop()
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'scene_workshop_code');
    }

    final public function Station()
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'station_code');
    }

    final public function InEntireInstances()
    {
        return $this->hasMany(RepairBaseNewStationOrderEntireInstance::class, 'in_sn', 'serial_number');
    }

    final public function OutEntireInstances()
    {
        return $this->hasMany(RepairBaseNewStationOrderEntireInstance::class, 'out_sn', 'serial_number');
    }

    final public function Models(){
        return $this->hasMany(RepairBaseNewStationOrderModel::class,'new_station_model_order_sn','serial_number');
    }
}
