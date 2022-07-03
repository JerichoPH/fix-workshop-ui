<?php

namespace App\Model;

use App\Model\Install\InstallPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\CollectionOrderMaterial
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $collection_order_unique_code 采集单编码
 * @property string $category_name 种类名称
 * @property string $entire_model_name 类型名称
 * @property string $sub_model_name 型号名称
 * @property string|null $ex_factory_at 出厂日期
 * @property string|null $factory_number 厂编号
 * @property float $service_life 使用年限，0.0没有年限
 * @property string|null $cycle_fix_at 周期修时间
 * @property float|null $cycle_fix_year 周期修年限
 * @property string|null $last_installed_at 最后上道时间
 * @property string|null $factory_name 供应商
 * @property string $workshop_unique_code 车间编码
 * @property string $station_unique_code 车站编码
 * @property string|null $version_number 版本号
 * @property string $state_unique_code
 * @property string $equipment_category_name 设备种类名称
 * @property string $equipment_entire_model_name 设备类型名称
 * @property string $equipment_sub_model_name 设备型号名称
 * @property string $material_unique_code 设备编码
 * @property string $install_location_unique_code 上道位置编码
 * @property-read \App\Model\Station $WithStation
 * @property-read \App\Model\Workshop $WithWorkshop
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereCollectionOrderUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereCycleFixAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereCycleFixYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereEntireModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereEquipmentCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereEquipmentEntireModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereEquipmentSubModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereExFactoryAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereFactoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereFactoryNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereInstallLocationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereLastInstalledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereMaterialUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereServiceLife($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereStateUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereStationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereSubModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereVersionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrderEntireInstance whereWorkshopUniqueCode($value)
 * @mixin \Eloquent
 */
class CollectionOrderEntireInstance extends Model
{
    protected $guarded = [];

    final public function EntireInstance():HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    final public function WithStation()
    {
        return $this->belongsTo(Maintain::class, 'station_unique_code', 'unique_code');
    }

    final public function WithWorkshop()
    {
        return $this->belongsTo(Maintain::class, 'workshop_unique_code', 'unique_code');
    }

    final public function WithInstallPosition()
    {
        return $this->belongsTo(InstallPosition::class, 'install_location_unique_code', 'unique_code');
    }

}
