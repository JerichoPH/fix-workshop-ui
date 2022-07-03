<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\StationInstallLocationRecord
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $maintain_station_unique_code 车站代码
 * @property string $maintain_station_nam 车站名称
 * @property string $maintain_location_code 室内位置代码
 * @property string $crossroad_number 道岔号
 * @property int $is_indoor 是否是室内设备
 * @property string $section_unique_code 区间代码
 * @property int $processor_id 处理人
 * @property string $open_direction 开向
 * @property-read \App\Model\Account $Processor
 * @property-read \App\Model\Maintain $Station
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereIsIndoor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereMaintainStationNam($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereMaintainStationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereSectionUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallLocationRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StationInstallLocationRecord extends Model
{
    protected $guarded = [];

    /**
     * 车站
     * @return HasOne
     */
    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'name', 'maintain_station_name');
    }

    /**
     * 操作人
     * @return HasOne
     */
    final public function Processor(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }
}
