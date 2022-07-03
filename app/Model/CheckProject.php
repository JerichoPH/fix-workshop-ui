<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class TaskStationProject
 * @package App\Model
 * @property int id
 * @property \Illuminate\Support\Carbon created_at
 * @property \Illuminate\Support\Carbon updated_at
 * @property string name 项目名称
 * @property int type 项目
 */
class CheckProject extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];
    public static $TYPE = [
        1 => '临时',
        2 => '年表维修',
        3 => '任务变更',
    ];

    public function getTypeAttribute($value)
    {
        return [
            'value' => $value,
            'text' => self::$TYPE[$value]
        ];
    }

    /**
     * 现场检修任务
     * @return HasMany
     */
    final public function TaskStationCheckOrders():HasMany
    {
        return $this->hasMany(TaskStationCheckOrder::class, 'task_station_check_project_id', 'id');
    }
}
