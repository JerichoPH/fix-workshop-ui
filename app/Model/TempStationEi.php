<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\TempStationEi
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $maintain_station_name
 * @property string $entire_instance_identity_code
 * @property string $maintain_location_code
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempStationEi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempStationEi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempStationEi query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempStationEi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempStationEi whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempStationEi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempStationEi whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempStationEi whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempStationEi whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TempStationEi extends Model
{
    protected $guarded = [];

    final public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class,'identity_code','entire_instance_identity_code');
    }
}
