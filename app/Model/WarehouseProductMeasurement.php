<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProductMeasurement
 *
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductMeasurement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductMeasurement newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductMeasurement onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductMeasurement query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductMeasurement withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductMeasurement withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProductMeasurement extends Model
{
    use SoftDeletes;

    protected $guarded = [];
}
