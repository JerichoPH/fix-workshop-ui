<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\PartInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $part_model_unique_code 部件属性代码
 * @property string|null $part_model_name 部件类型名称
 * @property string|null $entire_instance_identity_code 整机实例代码
 * @property string $status 状态
 * @property string|null $factory_name 供应商名称
 * @property string|null $factory_device_code 供应商设备代码
 * @property string $identity_code 身份识别码
 * @property string|null $entire_instance_serial_number 整件设备流水号
 * @property int $cycle_fix_count 周期修入所次数
 * @property int $un_cycle_fix_count 非周期修入所次数
 * @property string $category_unique_code 种类代码
 * @property string $entire_model_unique_code 类型代码
 * @property string $self_category 部件种类：电机
 * @property string $self_model 部件型号：只有电机有型号
 * @property int|null $part_category_id
 * @property int|null $is_need_detection
 * @property string|null $location_unique_code
 * @property string|null $made_at
 * @property string|null $scraping_at
 * @property string|null $in_warehouse_time
 * @property string $old_identity_code
 * @property int $is_bind_location 是否绑定位置0未绑定，1绑定
 * @property string|null $last_take_stock_at 最后盘点时间
 * @property int $work_area 所属工区 0、未分配 1、转辙机工区 2、继电器工区 3、综合工区
 * @property int $last_repair_material_id 最后送修单设备关联
 * @property string $device_model_unique_code 器件型号
 * @property string $serial_number 所编号
 * @property string $work_area_unique_code 所属工区
 * @property-read \App\Model\Category $Category
 * @property-read \App\Model\EntireModel $DeviceModel
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\EntireModel $EntireModel
 * @property-read \App\Model\PartCategory $PartCategory
 * @property-read \App\Model\PartModel $PartModel
 * @property-read \App\Model\Position|null $WithPosition
 * @property-read \App\Model\WorkArea $WorkAreaByUniqueCode
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\PartInstance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereCycleFixCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereDeviceModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereEntireInstanceSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereFactoryDeviceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereFactoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereInWarehouseTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereIsBindLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereIsNeedDetection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereLastRepairMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereLastTakeStockAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereLocationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereMadeAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereOldIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance wherePartCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance wherePartModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance wherePartModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereScrapingAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereSelfCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereSelfModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereUnCycleFixCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereWorkArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartInstance whereWorkAreaUniqueCode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\PartInstance withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\PartInstance withoutTrashed()
 * @mixin \Eloquent
 */
class PartInstance extends Model
{
    use SoftDeletes;

    public static $STATUSES = [
        // 'BUY_IN' => '新购',
        'INSTALLING' => '现场备品',
        'INSTALLED' => '上道使用',
        'TRANSFER_OUT' => '出所在途',
        'TRANSFER_IN' => '入所在途',
        // 'UNINSTALLED' => '下道',
        'FIXING' => '待修',
        'FIXED' => '所内备品',
        // 'FACTORY_RETURN' => '送修入所',
        'SCRAP' => '报废',
        // 'FRMLOSS' => '报损',
        'SEND_REPAIR' => '送修中',
        // 'REPAIRING' => '检修中',
    ];

    protected $guarded = [];

    public function flipStatus()
    {
        return self::$STATUSES[$this->value];
    }

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    public function PartModel()
    {
        return $this->hasOne(PartModel::class, 'unique_code', 'part_model_unique_code');
    }

    final public function Category()
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    final public function EntireModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    final public function PartCategory()
    {
        return $this->hasOne(PartCategory::class, 'id', 'part_category_id');
    }

    final public function getStatusAttribute($value)
    {
        return self::$STATUSES[$value] ?? '';
    }

    final public function WithPosition()
    {
        return $this->belongsTo(Position::class, 'location_unique_code', 'unique_code');
    }

    final public function WorkAreaByUniqueCode()
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }

    final public function DeviceModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'device_model_unique_code');
    }
}
