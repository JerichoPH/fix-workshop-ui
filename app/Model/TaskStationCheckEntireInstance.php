<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\TaskStationCheckEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $task_station_check_order_sn 任务单号
 * @property string $entire_instance_identity_code
 * @property string $task_name
 * @property string $processed_at 执行时间
 * @property int $processor_id 执行人
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\Account $Processor
 * @property-read \App\Model\TaskStationCheckOrder $TaskStationCheckOrder
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance whereTaskName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance whereTaskStationCheckOrderSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckEntireInstance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TaskStationCheckEntireInstance extends Model
{
    protected $guarded = [];

    /**
     * 任务单
     * @return HasOne
     */
    final public function TaskStationCheckOrder(): HasOne
    {
        return $this->hasOne(TaskStationCheckOrder::class, 'serial_number', 'task_station_check_order_sn');
    }

    /**
     * 设备器材
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    /**
     * 操作人
     * @return HasOne
     */
    final public function Processor(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }
}
