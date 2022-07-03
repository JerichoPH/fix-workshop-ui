<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\FixWorkflowProcessPart
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $fix_workflow_process_serial_number 检测记录流水号
 * @property string $part_instance_identity_code 部件编号
 * @property string|null $note 备注
 * @property string $measurement_identity_code 检测模板身份码
 * @property string|null $measured_value 检测数据
 * @property int|null $processor_id 检测人
 * @property string|null $processed_at 检测时间
 * @property string $serial_number 部件检测单流水号
 * @property int $is_allow 检测是否合格
 * @property-read \App\Model\FixWorkflowProcess $FixWorkflowProcess
 * @property-read \App\Model\Measurement $Measurement
 * @property-read \App\Model\PartInstance $PartInstance
 * @property-read \App\Model\Account $Processor
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflowProcessPart onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereFixWorkflowProcessSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereIsAllow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereMeasuredValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereMeasurementIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart wherePartInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcessPart whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflowProcessPart withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflowProcessPart withoutTrashed()
 * @mixin \Eloquent
 */
class FixWorkflowProcessPart extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function Processor()
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }

    public function FixWorkflowProcess()
    {
        return $this->hasOne(FixWorkflowProcess::class, 'serial_number', 'fix_workflow_process_serial_number');
    }

    public function PartInstance()
    {
        return $this->hasOne(PartInstance::class, 'identity_code', 'part_instance_identity_code');
    }

    public function Measurement()
    {
        return $this->hasOne(Measurement::class, 'identity_code', 'measurement_identity_code');
    }
}
