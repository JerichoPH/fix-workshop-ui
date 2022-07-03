<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\FixMissionOrder
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $serial_number 流水单号
 * @property string $status 状态：
 * UNDONE：未开始
 * PROCESSING：进行中
 * DONE：已完成
 * @property int $initiator_id 任务发起人
 * @property int $work_area_id 所属工区
 * @property string $type 任务类型：
 * NONE：无
 * CYCLE_FIX：周期修
 * NEW_STATION：新站
 * FULL_FIX：大修
 * EXCHANGE_MODEL：技改
 * STATION_REFORM：站改
 * HIGH_FREQUENCY：高频修
 * @property string|null $complete 按时完成数量
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\FixMissionOrderEntireInstance[] $FixMissionOrderEntireInstances
 * @property-read int|null $fix_mission_order_entire_instances_count
 * @property-read \App\Model\Account $Initiator
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder whereComplete($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder whereInitiatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrder whereWorkAreaId($value)
 * @mixin \Eloquent
 */
class FixMissionOrder extends Model
{
    protected $guarded = [];

    final public function Initiator()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    final public function FixMissionOrderEntireInstances()
    {
        return $this->hasMany(FixMissionOrderEntireInstance::class, 'serial_number', 'fix_mission_order_serial_number');
    }
}
