<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\TakeStockInstance
 *
 * @property int $id
 * @property string $take_stock_unique_code 盘点关联编码
 * @property string $stock_identity_code 库存编码
 * @property string $real_stock_identity_code 实盘编码
 * @property string $difference 差异：+ 实盘比库存多；-实盘比库存少；=相等
 * @property string $category_unique_code 种类编码
 * @property string $category_name 种类名称
 * @property string $sub_model_unique_code 型号编码
 * @property string $sub_model_name 型号名称
 * @property string $location_unique_code 仓库位置编码
 * @property string $location_name 仓库位置名称
 * @property string $material_type
 * @property-read \App\Model\Position $WithPosition
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereDifference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereLocationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereLocationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereMaterialType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereRealStockIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereStockIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereSubModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereSubModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStockInstance whereTakeStockUniqueCode($value)
 * @mixin \Eloquent
 */
class TakeStockInstance extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public static $DIFFERENCE = [
        '+' => '盘盈',
        '-' => '盘亏',
        '=' => '正常',
    ];


    public function getDifferenceAttribute($value)
    {
        return [
            'text' => self::$DIFFERENCE[$value],
            'value' => $value
        ];
    }

    public function WithPosition()
    {
        return $this->belongsTo(Position::class, 'location_unique_code', 'unique_code');
    }


}
