<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\TmpMaterial
 *
 * @property int $id
 * @property string $material_unique_code 设备唯一识别码
 * @property string $state  - 入库：IN
 *  - 出库（确定出库）：OUT
 *  - 报废（点击报废）：SCRAP
 *  - 报损（点击报损）：FRMLOSS
 *  - 送修：SEND_REPAIR
 * @property string $warehouse_unique_code 仓库关联
 * @property string $is_scan_code 是否扫码：0未扫码，1扫码
 * @property string $location_unique_code 位置编码
 * @property string $material_type
 * @property int $account_id 操作人id
 * @property string $repair_desc 送修描述
 * @property string $repair_remark 送修备注
 * @property-read \App\Model\EntireInstance $WithEntireInstance
 * @property-read \App\Model\PartInstance $WithPartInstance
 * @property-read \App\Model\Position $WithPosition
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereIsScanCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereLocationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereMaterialType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereMaterialUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereRepairDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereRepairRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TmpMaterial whereWarehouseUniqueCode($value)
 * @mixin \Eloquent
 */
class TmpMaterial extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public static $MATERIAL_TYPES = [
        'ENTIRE' => '整件',
        'PART' => '部件'
    ];

    public function getMaterialTypeAttribute($value)
    {
        return [
            'text' => self::$MATERIAL_TYPES[$value],
            'value' => $value
        ];
    }

    public function WithEntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'material_unique_code');
    }

    public function WithPosition()
    {
        return $this->belongsTo(Position::class, 'location_unique_code', 'unique_code');
    }

    public function WithPartInstance()
    {
        return $this->hasOne(PartInstance::class, 'identity_code', 'material_unique_code');
    }
}
