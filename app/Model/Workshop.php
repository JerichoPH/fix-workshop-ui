<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Model\Workshop
 *
 * @property int $id 车间表
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $unique_code 车间编码
 * @property string $name 车间名称
 * @property string|null $lon 经度
 * @property string|null $lat 纬度
 * @property string|null $contact 联系人
 * @property string|null $contact_phone 联系人电话
 * @property string|null $contact_address 联系人地址
 * @property string $type 车间类型:SCENE_WORKSHOP->现场车间,WORKSHOP->车间
 * @property string $is_show 是否显示台账:0->不显示,1->显示
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Station[] $Stations
 * @property-read int|null $stations_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereContactAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereIsShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Workshop whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Workshop extends Model
{
    protected $guarded = [];

    /**
     * 获取车间下所有车站
     * @return HasMany
     */
    final public function Stations():HasMany
    {
        return $this->hasMany(Station::class,'workshop_unique_code','unique_code');
    }
}
