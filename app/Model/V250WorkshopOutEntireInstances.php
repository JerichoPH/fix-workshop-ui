<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\V250WorkshopOutEntireInstances
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $v250_task_orders_serial_number 2.5.0版任务单流水号
 * @property string $v250_workshop_stay_out_serial_number 待出所单唯一编号
 * @property string $entire_instance_identity_code 设备唯一编号
 * @property string $entire_instance_serial_number 设备所编号
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
 * @property string|null $is_scan_code 是否扫码 0:未扫码,1:已扫码
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\Position $WithPosition
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereCrossroadType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereEntireInstanceSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereExtrusionProtect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereIsScanCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereLineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances wherePointSwitchGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereV250TaskOrdersSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopOutEntireInstances whereV250WorkshopStayOutSerialNumber($value)
 * @mixin \Eloquent
 */
class V250WorkshopOutEntireInstances extends Model
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
     * 仓库位置
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    final public function WithPosition()
    {
        return $this->belongsTo(Position::class, 'location_unique_code', 'unique_code');
    }
}
