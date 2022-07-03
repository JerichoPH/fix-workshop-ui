<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class ScrapTempEntireInstance
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $entire_instance_identity_code
 * @property EntireInstance $EntireInstance
 * @property string $warehouse_sn
 * @property Warehouse $Warehouse
 * @property string $processor_id
 * @property Account $Processor
 */
class ScrapTempEntireInstance extends Base
{
    protected $guarded = [];

    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, "identity_code", "entire_instance_identity_code");
    }

    final public function Processor(): HasOne
    {
        return $this->hasOne(Account::class, "id", "processor_id");
    }
}
