<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\EntireInstanceChangePartLog
 *
 * @property int|null $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $entire_instance_identity_code
 * @property string $part_instance_identity_code
 * @property string $fix_workflow_serial_number
 * @property string|null $note
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\PartInstance $PartInstance
 * @property-read \App\Model\FixWorkflow $fixWorkflow
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstanceChangePartLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog whereFixWorkflowSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog wherePartInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceChangePartLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstanceChangePartLog withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstanceChangePartLog withoutTrashed()
 * @mixin \Eloquent
 */
class EntireInstanceChangePartLog extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class,'identity_code','entire_instance_identity_code');
    }

    public function PartInstance()
    {
        return $this->hasOne(PartInstance::class,'identity_code','part_instance_identity_code');
    }

    public function fixWorkflow()
    {
        return $this->hasOne(FixWorkflow::class,'serial_number','fix_workflow_serial_number');
    }
}
