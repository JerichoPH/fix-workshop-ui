<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\CollectDeviceOrder
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $serial_number 流水单号
 * @property int $station_install_user_id 操作人
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\CollectDeviceOrderEntireInstance[] $CollectDeviceOrderEntireInstances
 * @property-read int|null $collect_device_order_entire_instances_count
 * @property-read \App\Model\StationInstallUser $Processor
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrder whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrder whereStationInstallUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CollectDeviceOrder extends Model
{
    protected $guarded = [];

    /**
     * 数据采集单设备
     * @return HasMany
     */
    final public function CollectDeviceOrderEntireInstances():HasMany
    {
        return $this->hasMany(CollectDeviceOrderEntireInstance::class, 'collect_device_order_sn', 'serial_number');
    }

    /**
     * 操作人
     * @return HasOne
     */
    final public function Processor():HasOne
    {
        return $this->hasOne(StationInstallUser::class, 'id', 'station_install_user_id');
    }
}
