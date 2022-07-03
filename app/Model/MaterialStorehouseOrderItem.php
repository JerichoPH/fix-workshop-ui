<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class MaterialStorehouseOrderItem
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $material_storehouse_order_serial_number
 * @property-read MaterialStorehouseOrder $Order
 * @property string $material_identity_code
 * @property-read Material $Material
 * @property string $workshop_unique_code
 * @property-read Maintain $Workshop
 * @property string $station_unique_code
 * @property-read Maintain $Station
 * @property string $work_area_unique_code
 * @property-read WorkArea $WorkArea
 * @property string $position_unique_code
 * @property-read Position $Position
 */
class MaterialStorehouseOrderItem extends Base
{
    protected $guarded = [];
    protected $__default_withs = ['Order', 'Material', 'Workshop', 'Station', 'WorkArea', 'Position',];

    final public function Order(): HasOne
    {
        return $this->hasOne(MaterialStorehouseOrder::class, 'serial_number', 'material_storehouse_order_serial_number');
    }

    final public function Material(): HasOne
    {
        return $this->hasOne(Material::class, 'identity_code', 'material_identity_code');
    }

    final public function Workshop(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'workshop_unique_code');
    }

    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'workshop_unique_code');
    }

    final public function WorkArea(): HasOne
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }

    final public function Position(): HasOne
    {
        return $this->hasOne(Position::class, 'unique_code', 'position_unique_code');
    }

}
