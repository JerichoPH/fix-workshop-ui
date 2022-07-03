<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\FixUnCycleReports
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $entire_instance_identity_code
 * @property string|null $part_instance_identity_code
 * @property string|null $fix_workflow_serial_number
 * @property string|null $fix_workflow_process_serial_number
 * @property string|null $stage
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireInstance[] $EntireInstances
 * @property-read int|null $entire_instances_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports whereFixWorkflowProcessSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports whereFixWorkflowSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports wherePartInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixUnCycleReports whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FixUnCycleReports extends Model
{
    protected $guarded = [];

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, "identity_code", "entire_instance_identity_code");
    }

    public function EntireInstances()
    {
        return $this->hasMany(EntireInstance::class,"identity_code","entire_instance_identity_code");
    }
}
