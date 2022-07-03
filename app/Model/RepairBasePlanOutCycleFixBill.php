<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class RepairBasePlanOutCycleFixBill
 * @package App\Model
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $serial_number
 * @property int $operator_id
 * @property string $status
 * @property int $number
 * @property int $year
 * @property int $month
 * @property string $station_name
 * @property int $work_area_id
 * @property string $station_unique_code
 * @property-read Station $Station
 */
class RepairBasePlanOutCycleFixBill extends Model
{
    protected $guarded = [];

    final public function Station():HasOne
    {
        return $this->hasOne(Maintain::class,'unique_code','station_unique_code');
    }
}
