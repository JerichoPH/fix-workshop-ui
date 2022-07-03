<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class MaterialStorehouseOrder
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $serial_number
 * @property string $operator_id
 * @property-read Account $Operator
 */
class MaterialStorehouseOrder extends Base
{
    protected $guarded = [];
    protected $__default_withs = ['Operator',];

    final public function Items(): HasMany
    {
        return $this->hasMany(MaterialBatchUpdateOrderItem::class, 'material_storehouse_order_serial_number', 'serial_number');
    }

    final public function Operator(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'operator_id');
    }
}
