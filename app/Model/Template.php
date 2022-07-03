<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Template
 *
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Template newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Template newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Template onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Template query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Template withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Template withoutTrashed()
 * @mixin \Eloquent
 */
class Template extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    // public function deviceCategory()
    // {
    //     return $this->hasOne(DeviceCategory::class, 'id', 'device_category_id');
    // }

    protected function getFormatAttribute($value)
    {
        return json_decode($value, true);
    }
}
