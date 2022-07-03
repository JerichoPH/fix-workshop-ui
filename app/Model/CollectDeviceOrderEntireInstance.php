<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\CollectDeviceOrderEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $collect_device_order_sn 收集设备器材信息单流水号
 * @property string $entire_instance_serial_number
 * @property string $status 状态
 * @property string $factory_device_code
 * @property string $factory_name
 * @property string $model_unique_code
 * @property string $model_name
 * @property string $entire_model_unique_code
 * @property string $entire_model_name
 * @property string $category_unique_code
 * @property string $category_name
 * @property string|null $made_at
 * @property string|null $last_out_at
 * @property string|null $last_installed_time
 * @property int $cycle_fix_value
 * @property int $life_year
 * @property string|null $scarping_at
 * @property string $maintain_station_name
 * @property string $maintain_station_unique_code
 * @property string $maintain_workshop_name
 * @property string $maintain_workshop_unique_code
 * @property string $maintain_location_code
 * @property string $crossroad_number
 * @property string $open_direction
 * @property string $said_rod
 * @property string $point_switch_group_type
 * @property string $line_name
 * @property int $extrusion_protect 反挤压保护罩
 * @property int $station_install_user_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereCollectDeviceOrderSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereCycleFixValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereEntireInstanceSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereEntireModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereExtrusionProtect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereFactoryDeviceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereFactoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereLastInstalledTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereLastOutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereLifeYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereLineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereMadeAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereMaintainStationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereMaintainWorkshopName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereMaintainWorkshopUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance wherePointSwitchGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereScarpingAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereStationInstallUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderEntireInstance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CollectDeviceOrderEntireInstance extends Model
{
    protected $guarded = [];

    /**
     * 数据采集单
     */
    final public function CollectEquipmentOrder()
    {
        $this->hasOne(CollectDeviceOrderEntireInstance::class, 'collect_equipment_order_sn', 'serial_number');
    }

    /**
     * 设备器材
     */
    final public function EntireInstances()
    {
        $this->hasMany(EntireInstance::class, 'serial_number', 'serial_number');
    }
}
