<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class EntireInstanceLock
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $entire_instance_identity_code
 * @property Carbon|null $expire_at
 * @property string $lock_name
 * @property string $lock_description
 * @property string $business_order_table_name
 * @property string $business_order_uuid
 * @property string $business_item_table_name
 * @property string $business_item_uuid
 */
class EntireInstanceLock extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属器材
     * @return HasOne
     */
    public function EntireInstance():HasOne
    {
        return $this->hasOne(EntireInstance::class,"identity_code","entire_instance_identity_code");
    }
}
