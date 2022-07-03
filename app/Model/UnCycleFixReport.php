<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\UnCycleFixReport
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $entire_instance_identity_code
 * @property string|null $part_instance_identity_code
 * @property string|null $fix_workflow_serial_number
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\FixWorkflow $FixWorkflow
 * @property-read \App\Model\PartInstance $PartInstance
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\UnCycleFixReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\UnCycleFixReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\UnCycleFixReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\UnCycleFixReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\UnCycleFixReport whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\UnCycleFixReport whereFixWorkflowSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\UnCycleFixReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\UnCycleFixReport wherePartInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\UnCycleFixReport whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UnCycleFixReport extends Model
{
    protected $guarded = [];

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    public function PartInstance()
    {
        return $this->hasOne(PartInstance::class, 'identity_code', 'part_instance_identity_code');
    }

    public function FixWorkflow()
    {
        return $this->hasOne(FixWorkflow::class, 'serial_number', 'fix_workflow_serial_number');
    }
}
