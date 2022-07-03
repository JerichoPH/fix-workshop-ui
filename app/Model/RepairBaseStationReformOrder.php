<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseStationReformOrder
 *
 * @property-read \App\Model\Account $WithAccount
 * @property-read \App\Model\Maintain $WithSceneWorkshop
 * @property-read \App\Model\Maintain $WithStation
 * @property-read mixed $direction
 * @property-read mixed $status
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseStationReformOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseStationReformOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseStationReformOrder query()
 * @mixin \Eloquent
 */
class RepairBaseStationReformOrder extends Model
{
    protected $guarded = [];

    public static $STATUSES = [
        'ORIGIN' => '任务创建',
        'UNDONE' => '未完成',
        'DONE' => '完成',
    ];
    public static $DIRECTIONS = [
        'IN' => '入所',
        'OUT' => '出所',
    ];

    public static $TYPE = [
        'NEW' => '新站',
        'OLD' => '老站',
    ];

    final public function getDirectionAttribute($value)
    {
        return self::$DIRECTIONS[$value];
    }

    final public function getStatusAttribute($value)
    {
        return [
            'value' => $value,
            'text' => self::$STATUSES[$value]
        ];
    }

    public function WithStation()
    {
        return $this->belongsTo(Maintain::class, 'station_code', 'unique_code');
    }

    public function WithSceneWorkshop()
    {
        return $this->belongsTo(Maintain::class, 'scene_workshop_code', 'unique_code');
    }

    public function WithAccount()
    {
        return $this->belongsTo(Account::class, 'operator_id', 'id');
    }
}
