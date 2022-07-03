<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\V250WorkshopInEntireInstances
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $v250_task_orders_serial_number 2.5.0版任务单流水号
 * @property string $entire_instance_identity_code 设备唯一编号
 * @property string|null $entire_instance_serial_number 设备所编号
 * @property string|null $maintain_station_name 车站名称
 * @property string|null $maintain_location_code 组合位置
 * @property string|null $crossroad_number 道岔号
 * @property string|null $traction 牵引
 * @property string|null $line_name 线制
 * @property string|null $crossroad_type 道岔类型
 * @property int|null $extrusion_protect 防挤压保护罩
 * @property string|null $point_switch_group_type 转辙机分组类型
 * @property string|null $open_direction 开向
 * @property string|null $said_rod 表示杆特征
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read array $is_scene_back
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereCrossroadType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereEntireInstanceSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereExtrusionProtect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereLineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances wherePointSwitchGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopInEntireInstances whereV250TaskOrdersSerialNumber($value)
 * @mixin \Eloquent
 */
class V250WorkshopInEntireInstances extends Model
{
    protected $guarded = [];

    /**
     * 相关设备
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    /**
     * 是否是现场返回设备标记
     * @param $value
     * @return array
     */
    final public function getIsSceneBackAttribute($value)
    {
        return ['code' => $value, 'name' => $value ? '是' : '否'];
    }
}
