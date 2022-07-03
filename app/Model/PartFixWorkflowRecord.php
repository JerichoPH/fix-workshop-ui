<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PartFixWorkflowRecord
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $part_instance_identity_code 部件标识
 * @property string $measurement_identity_code 检测模版编号
 * @property string|null $measured_value 检修记录
 * @property int|null $processor_id 检测人编号
 * @property string|null $processed_at 检测时间
 * @property int $is_allow 检修结果
 * @property-read \App\Model\Measurement $Measurement
 * @property-read \App\Model\PartInstance $PartInstance
 * @property-read \App\Model\Account $Processor
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord whereIsAllow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord whereMeasuredValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord whereMeasurementIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord wherePartInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartFixWorkflowRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PartFixWorkflowRecord extends Model
{
    protected $guarded = [];

    public function PartInstance()
    {
        return $this->hasOne(PartInstance::class, 'identity_code', 'part_instance_identity_code');
    }

    public function Measurement()
    {
        return $this->hasOne(Measurement::class, 'identity_code', 'measurement_identity_code');
    }

    public function Processor()
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }
}
