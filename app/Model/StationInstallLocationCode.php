<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\StationInstallLocationCode
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $maintain_station_unique_code 车站代码
 * @property string $maintain_location_code 室内位置代码
 * @property string $crossroad_number 道岔号
 * @property string $row 排
 * @property string $shelf 架
 * @property string $tier 层
 * @property string $position 位
 * @property int $is_indoor 是否是室内设备
 * @property string $section_unique_code 区间代码
 * @property-read \App\Model\Maintain $SceneWorkshop
 * @property-read \App\Model\Maintain $Station
 * @property-read \App\Model\StationInstallUser $processor_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereIsIndoor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereMaintainStationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereRow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereSectionUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereShelf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereTier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationCode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StationInstallLocationCode extends Model
{
    protected $guarded = [];

    /**
     * 现场车间
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function SceneWorkshop()
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'scene_workshop_unique_code');
    }

    /**
     * 车站
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function Station()
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'maintain_station_unique_code');
    }

    /**
     * 操作人
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function processor_id()
    {
        return $this->hasOne(StationInstallUser::class, 'processor_id', 'id');
    }

}
