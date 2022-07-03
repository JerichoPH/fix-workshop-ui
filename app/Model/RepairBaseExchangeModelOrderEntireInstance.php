<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseExchangeModelOrderEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $old_entire_instance_identity_code 设备唯一编号
 * @property string $new_entire_instance_identity_code 新设备唯一编号
 * @property string $maintain_location_code 旧组合位置
 * @property string $crossroad_number 旧道岔号
 * @property string $in_sn 更换型号出入计划单流水号
 * @property string $out_sn 更换型号出所计划流水号
 * @property int $in_scan 入所计划扫码
 * @property int $out_scan 出所计划扫码
 * @property string $in_warehouse_sn 入所单号
 * @property string $out_warehouse_sn 出所单号
 * @property string|null $source 来源
 * @property string|null $source_traction 来源牵引
 * @property string|null $source_crossroad_number 来源道岔号
 * @property string|null $traction 牵引
 * @property string|null $open_direction 开向
 * @property string|null $said_rod 表示杆特征
 * @property-read \App\Model\RepairBaseExchangeModelOrder $InOrder
 * @property-read \App\Model\WarehouseReport $InWarehouse
 * @property-read \App\Model\EntireInstance $NewEntireInstance
 * @property-read \App\Model\EntireInstance $OldEntireInstance
 * @property-read \App\Model\RepairBaseExchangeModelOrder $OutOrder
 * @property-read \App\Model\WarehouseReport $OutWarehouse
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereInScan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereInSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereInWarehouseSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereNewEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereOldEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereOutScan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereOutSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereOutWarehouseSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereSourceCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereSourceTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderEntireInstance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RepairBaseExchangeModelOrderEntireInstance extends Model
{
    protected $guarded = [];

    final public function InOrder()
    {
        return $this->hasOne(RepairBaseExchangeModelOrder::class, 'serial_number', 'in_sn');
    }

    public function OutOrder()
    {
        return $this->hasOne(RepairBaseExchangeModelOrder::class, 'serial_number', 'out_sn');
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
        return $this->hasOne(WarehouseReport::class,'serial_number','in_warehouse_sn');
    }

    final public function OutWarehouse()
    {
        return $this->hasOne(WarehouseReport::class,'serial_number','out_warehouse_sn');
    }
}
