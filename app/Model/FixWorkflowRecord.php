<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use test\Mockery\SimpleTrait;

/**
 * App\Model\FixWorkflowRecord
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $fix_workflow_process_serial_number 测试记录单序列号
 * @property string|null $entire_instance_identity_code 整件设备身份码
 * @property string|null $part_instance_identity_code 设备身份识别码
 * @property string|null $note 记录备注
 * @property string|null $measurement_identity_code 测试模板身份码
 * @property string|null $measured_value 实测记录
 * @property int|null $processor_id 测试人
 * @property string|null $processed_at 测试时间
 * @property string $serial_number 记录流水号
 * @property string $type
 * @property int|null $is_allow 是否通过检测
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\FixWorkflowProcess $FixWorkflowProcess
 * @property-read \App\Model\Measurement $Measurement
 * @property-read \App\Model\PartInstance $PartInstance
 * @property-read \App\Model\Account $Processor
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflowRecord onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereFixWorkflowProcessSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereIsAllow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereMeasuredValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereMeasurementIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord wherePartInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflowRecord withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflowRecord withoutTrashed()
 * @mixin \Eloquent
 */
class FixWorkflowRecord extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function Processor()
    {
        return $this->hasOne(Account::class,'id','processor_id');
    }

    public function FixWorkflowProcess()
    {
        return $this->hasOne(FixWorkflowProcess::class,'serial_number','fix_workflow_process_serial_number');
    }

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class,'identity_code','entire_instance_identity_code');
    }

    public function PartInstance()
    {
        return $this->hasOne(PartInstance::class,'identity_code','part_instance_identity_code');
    }

    public function Measurement()
    {
        return $this->hasOne(Measurement::class,'identity_code','measurement_identity_code');
    }
}
