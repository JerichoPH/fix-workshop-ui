<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CheckPlanEntireInstance extends Model
{
    protected $fillable = [
        'check_plan_serial_number',
        'entire_instance_identity_code',
        'is_use',
        'task_station_check_order_serial_number',
    ];

    /**
     * 获取设备
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }
}
