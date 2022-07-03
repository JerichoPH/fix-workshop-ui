<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Model\PivotBreakdownLogAndBreakdownType
 *
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $breakdown_log_id
 * @property int|null $breakdown_type_id
 * @property-read BreakdownLog $BreakdownLog
 * @property-read BreakdownType $BreakdownType
 */
class PivotBreakdownLogAndBreakdownType extends Model
{
    protected $guarded = [];

    final public function BreakdownLog(): HasOne
    {
        return $this->hasOne(BreakdownLog::class, 'id', 'breakdown_log_id');
    }

    final public function BreakdownType(): HasOne
    {
        return $this->hasOne(BreakdownType::class, 'id', 'breakdown_type_id');
    }
}
