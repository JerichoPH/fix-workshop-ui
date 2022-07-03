<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PrintIdentityCode
 * @package App\Model
 * @property string $entire_instance_identity_code
 * @property-read EntireInstance|null $EntireInstance
 */
class PrintIdentityCode extends Model
{
    protected $guarded = [];

    final public function EntireInstance():HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }
}
