<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseExchangeModelOrder
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $serial_number
 * @property string $scene_workshop_code
 * @property string $station_code
 * @property string $status UNDONE未完成
 * UNSATISFIED不满足
 * SATISFY满足
 * DONE已完成
 * @property string $direction
 * @property int $work_area_id
 * @property string $in_sn
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseExchangeModelOrderEntireInstance[] $InEntireInstances
 * @property-read int|null $in_entire_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseExchangeModelOrderModel[] $Models
 * @property-read int|null $models_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseExchangeModelOrderEntireInstance[] $OutEntireInstances
 * @property-read int|null $out_entire_instances_count
 * @property-read \App\Model\Maintain $SceneWorkshop
 * @property-read \App\Model\Maintain $Station
 * @property-read mixed $work_area
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereInSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereSceneWorkshopCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereStationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrder whereWorkAreaId($value)
 * @mixin \Eloquent
 */
class RepairBaseExchangeModelOrder extends Model
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
        return $this->hasMany(RepairBaseExchangeModelOrderEntireInstance::class, 'in_sn', 'serial_number');
    }

    final public function OutEntireInstances()
    {
        return $this->hasMany(RepairBaseExchangeModelOrderEntireInstance::class, 'out_sn', 'serial_number');
    }

    final public function Models()
    {
        return $this->hasMany(RepairBaseExchangeModelOrderModel::class,'exchange_model_order_sn','serial_number');
    }
}
