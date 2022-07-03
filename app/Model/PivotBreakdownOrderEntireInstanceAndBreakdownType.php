<?php

namespace App\Model;

use App\Http\Controllers\RepairBase\BreakdownOrderEntireInstanceController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class PivotBreakdownOrderEntireInstanceAndBreakdownType
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $breakdown_type_id
 * @property string $breakdown_order_entire_instance_id
 * @property-read BreakdownType $BreakdownType
 * @property-read RepairBaseBreakdownOrderEntireInstance $BreakdownOrderEntireInstance
 */
class PivotBreakdownOrderEntireInstanceAndBreakdownType extends Model
{
    protected $guarded = [];

    final public function BreakdownType(): HasOne
    {
        return $this->hasOne(BreakdownType::class, "id", "breakdown_type_id");
    }

    final public function BreakdownOrderEntireInstance(): HasOne
    {
        return $this->hasOne(RepairBaseBreakdownOrderEntireInstance::class, "id", "breakdown_order_entire_instance_id");
    }
}

