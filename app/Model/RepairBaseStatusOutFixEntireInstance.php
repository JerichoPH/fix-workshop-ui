<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RepairBaseStatusOutFixEntireInstance extends Model
{
    protected $guarded = [];

    final public function Bill(): HasOne
    {
        return $this->hasOne(RepairBaseStatusOutFixBill::class, 'id', 'bill_id');
    }

    final public function OldEntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'old');
    }

    final public function NewEntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'new');
    }

    final public function getIsScanInAttribute($value)
    {
        return (object)[
            'code' => $value,
            'name' => boolval($value) ? '已扫码' : '未扫码',
        ];
    }

    final public function getIsScanOutAttribute($value)
    {
        return (object)[
            'code' => $value,
            'name' => boolval($value) ? '已扫码' : '未扫码',
        ];
    }

    final public function WarehouseOut(): HasOne
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'out_warehouse_sn');
    }

    final public function WarehouseIn(): HasOne
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'in_warehouse_in');
    }
}
