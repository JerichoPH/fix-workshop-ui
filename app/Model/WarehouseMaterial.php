<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Model\WarehouseMaterial
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $material_unique_code
 * @property string $warehouse_unique_code
 * @property string $material_type
 * @property-read EntireInstance $WithEntireInstance
 * @property-read PartInstance[] $WithPartInstance
 * @property-read Warehouse $WithWarehouse
 */
class WarehouseMaterial extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public static $MATERIAL_TYPES = [
        'ENTIRE' => '整件',
        'PART' => '部件'
    ];

    /**
     * @param $value
     * @return string
     */
    public function getMaterialTypeAttribute($value): string
    {
        return self::$MATERIAL_TYPES[$value] ?: "";
    }

    /**
     * @return BelongsTo
     */
    final public function WithEntireInstance(): BelongsTo
    {
        // return $this->hasOne(EntireInstance::class,'identity_code','material_unique_code');
        return $this->belongsTo(EntireInstance::class, 'material_unique_code', 'identity_code');
    }

    /**
     * @return BelongsTo
     */
    final public function WithWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_unique_code', 'unique_code');
    }

    /**
     * @return BelongsTo
     */
    final public function WithPartInstance(): BelongsTo
    {
        return $this->belongsTo(PartInstance::class, 'material_unique_code', 'identity_code');
    }

}
