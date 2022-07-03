<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\V250WorkshopStayOut
 *
 * @property-read \App\Model\V250WorkshopOutEntireInstances $WithEntireInstances
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopStayOut newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopStayOut newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250WorkshopStayOut query()
 * @mixin \Eloquent
 */
class V250WorkshopStayOut extends Model
{
    protected $guarded = [];

    /**
     * 所属设备
     * @return HasOne
     */
    final public function WithEntireInstances():HasOne
    {
        return $this->hasOne(V250WorkshopOutEntireInstances::class,'v250_workshop_stay_out_serial_number','serial_number');
    }
}
