<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\StationInstallUser
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $wechat_open_id
 * @property string $nickname
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\StationInstallLocationCode[] $LocationCodes
 * @property-read int|null $location_codes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\StationLocation[] $StationLocation
 * @property-read int|null $station_location_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallUser whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\StationInstallUser whereWechatOpenId($value)
 * @mixin \Eloquent
 */
class StationInstallUser extends Model
{
    protected $guarded = [];

    /**
     * 扫码记录
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    final public function LocationCodes()
    {
        return $this->hasMany(StationInstallLocationCode::class, 'id', 'processor_id');
    }

    /**
     * 车站补登记录
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    final public function StationLocation()
    {
        return $this->hasMany(StationLocation::class, 'id', 'processor_id');
    }
}
