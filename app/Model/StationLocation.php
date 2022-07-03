<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\StationLocation
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $lon 经度
 * @property string $lat 纬度
 * @property string $line_name 线别名称
 * @property string $connection_name 联系人姓名
 * @property string $connection_phone 联系电话
 * @property string $connection_address 门牌号
 * @property string $scene_workshop_unique_code 现场车站代码
 * @property string $scene_workshop_name 现场车间名称
 * @property string $maintain_station_unique_code 车站代码
 * @property string $maintain_station_name 车站名称
 * @property int|null $processor_id 操作人
 * @property-read \App\Model\StationInstallUser $Processor
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereConnectionAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereConnectionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereConnectionPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereLineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereMaintainStationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereSceneWorkshopName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereSceneWorkshopUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationLocation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StationLocation extends Model
{
    protected $guarded = [];

    /**
     * 处理人
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function Processor()
    {
        return $this->hasOne(StationInstallUser::class, 'id', 'processor_id');
    }
}
