<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\BreakdownLog
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $entire_instance_identity_code 设备唯一编号
 * @property string $explain 描述
 * @property string $scene_workshop_name 现场车间
 * @property string $maintain_station_name 车站
 * @property string $maintain_location_code 组合位置
 * @property string $crossroad_number 岔道号
 * @property string $traction 牵引
 * @property string $line_name 线制名称
 * @property string $open_direction 开向
 * @property string $said_rod 表示杆特征
 * @property string $crossroad_type 道岔类型
 * @property string $point_switch_group_type 转辙机分组类型：单双机
 * @property int $extrusion_protect 挤压保护罩
 * @property string $type
 * @property string $submitter_name 故障上报人
 * @property string|null $submitted_at 故障上报时间
 * @property string $material_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\BreakdownType[] $BreakdownTypes
 * @property-read int|null $breakdown_types_count
 * @property-read \App\Model\EntireInstanceLog $EntireInstance
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereCrossroadType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereExplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereExtrusionProtect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereLineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereMaterialType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog wherePointSwitchGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereSceneWorkshopName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereSubmitterName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BreakdownLog extends Model
{
    public static $TYPES = [
        'STATION' => '现场故障描述',
        'WAREHOUSE_IN' => '入所故障描述',
    ];
    protected $guarded = [];

    final public function getTypeAttribute($value)
    {
        return @self::$TYPES[$value] ?: '无';
    }

    final public function EntireInstance()
    {
        return $this->hasOne(EntireInstanceLog::class, 'identity_code', 'entire_instance_identity_code');
    }

    final public function BreakdownTypes()
    {
        return $this->belongsToMany(
            BreakdownType::class,
            'pivot_breakdown_log_and_breakdown_types',
            'breakdown_log_id',
            'breakdown_type_id'
        );
    }
}
