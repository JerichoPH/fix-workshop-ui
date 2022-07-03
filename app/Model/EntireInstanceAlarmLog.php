<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class EntireInstanceAlarmLog
 * @package App\Model
 * @property \Illuminate\Support\Carbon created_at
 * @property \Illuminate\Support\Carbon updated_at
 * @property string entire_instance_identity_code
 * @property string station_unique_code
 * @property \Illuminate\Support\Carbon alarm_at
 * @property string alarm_level
 * @property string alarm_content
 * @property string alarm_cause
 * @property int msg_id
 * @property-read \App\Model\Maintain|null $Station
 * @property-read \App\Model\EntireInstance|null $EntireInstance
 */
class EntireInstanceAlarmLog extends Model
{
    protected $guarded = [];

    public static $STATUSES = [
        'WARNING' => '报警未处理',
        'MANUAL_RELEASE' => '手动消除',
        'MONITOR_RELEASE' => '集中检测平台消除',
    ];

    /**
     * station
     * @return HasOne
     */
    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'station_unique_code');
    }

    /**
     * entire instance
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    /**
     * status
     * @param $value
     * @return string
     */
    final public function getStatusAttribute($value)
    {
        return self::$STATUSES[$value] ?? '';
    }
}
