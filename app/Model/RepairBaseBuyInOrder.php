<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseBuyInOrder
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseBuyInOrderEntireInstance[] $InEntireInstances
 * @property-read int|null $in_entire_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseBuyInOrderEntireInstance[] $OutEntireInstances
 * @property-read int|null $out_entire_instances_count
 * @property-read \App\Model\Maintain $SceneWorkshop
 * @property-read \App\Model\Maintain $Station
 * @property mixed $direction
 * @property mixed $status
 * @property mixed $work_area
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBuyInOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBuyInOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBuyInOrder query()
 * @mixin \Eloquent
 */
class RepairBaseBuyInOrder extends Model
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

    final public function getWorkAreaAttribute($value)
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
        return $this->hasMany(RepairBaseBuyInOrderEntireInstance::class, 'in_sn', 'serial_number');
    }

    final public function OutEntireInstances()
    {
        return $this->hasMany(RepairBaseBuyInOrderEntireInstance::class, 'out_sn', 'serial_number');
    }
}
