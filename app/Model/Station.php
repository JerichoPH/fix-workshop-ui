<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\Station
 *
 * @property int $id 车站表
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $workshop_unique_code 所属车间编码
 * @property string $unique_code 车站编码
 * @property string $name 车站名称
 * @property string|null $lon 经度
 * @property string|null $lat 纬度
 * @property string|null $contact 联系人
 * @property string|null $contact_phone 联系人电话
 * @property string|null $contact_address 联系人地址
 * @property string $is_show 是否显示台账:0->不显示,1->显示
 * @property-read \App\Model\Workshop $Workshop
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereContactAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereIsShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Station whereWorkshopUniqueCode($value)
 * @mixin \Eloquent
 */
class Station extends Model
{
    protected $guarded = [];

    /**
     * 所属车间
     * @return HasOne
     */
    final public function Workshop(): HasOne
    {
        return $this->hasOne(Workshop::class, 'unique_code', 'workshop_unique_code');
    }
}
