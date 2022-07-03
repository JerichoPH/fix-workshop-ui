<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\WarehouseBatchReport
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $entire_instance_identity_code
 * @property string|null $fix_workflow_serial_number
 * @property int|null $processor_id
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport whereFixWorkflowSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseBatchReport whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WarehouseBatchReport extends Model
{
    protected $guarded = [];

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }
}
