<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseNewStationOrderEntireInstance
 *
 * @property-read \App\Model\RepairBaseNewStationOrder $InOrder
 * @property-read \App\Model\WarehouseReport $InWarehouse
 * @property-read \App\Model\EntireInstance $NewEntireInstance
 * @property-read \App\Model\EntireInstance $OldEntireInstance
 * @property-read \App\Model\RepairBaseNewStationOrder $OutOrder
 * @property-read \App\Model\WarehouseReport $OutWarehouse
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseNewStationOrderEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseNewStationOrderEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseNewStationOrderEntireInstance query()
 * @mixin \Eloquent
 */
class RepairBaseNewStationOrderEntireInstance extends Model
{
    protected $guarded = [];

    final public function InOrder()
    {
        return $this->hasOne(RepairBaseNewStationOrder::class, 'serial_number', 'in_sn');
    }

    public function OutOrder()
    {
        return $this->hasOne(RepairBaseNewStationOrder::class, 'serial_number', 'out_sn');
    }

    final public function OldEntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'old_entire_instance_identity_code');
    }

    final public function NewEntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'new_entire_instance_identity_code');
    }

    final public function InWarehouse()
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'in_warehouse_sn');
    }

    final public function OutWarehouse()
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'out_warehouse_sn');
    }
}
