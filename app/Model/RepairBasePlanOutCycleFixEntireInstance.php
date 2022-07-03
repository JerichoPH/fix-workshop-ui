<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\RepairBasePlanOutCycleFixEntireInstance
 *
 * @property int $id
 * @property int $bill_id
 * @property string $old 旧编号
 * @property string $new
 * @property string $location
 * @property string $station_name
 * @property string $new_tid
 * @property string $old_tid
 * @property string $is_scan 是否扫码，0未扫，1扫码
 * @property string $out_warehouse_sn 出所单编号
 * @property string $station_unique_code 车站编码
 * @property-read \App\Model\EntireInstance $WithEntireInstance
 * @property-read \App\Model\EntireInstance $WithEntireInstanceOld
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereBillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereIsScan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereNew($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereNewTid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereOld($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereOldTid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereOutWarehouseSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBasePlanOutCycleFixEntireInstance whereStationUniqueCode($value)
 * @mixin \Eloquent
 */
class RepairBasePlanOutCycleFixEntireInstance extends Model
{
    protected $guarded = [];

    public $timestamps = false;


    final public function WithEntireInstance()
    {
        return $this->belongsTo(EntireInstance::class, 'new', 'identity_code');
    }

    final public function WithEntireInstanceOld()
    {
        return $this->belongsTo(EntireInstance::class, 'old', 'identity_code');
    }

    final public function Bill():HasOne
    {
        return $this->hasOne(RepairBasePlanOutCycleFixBill::class,'id','bill_id');
    }
}
