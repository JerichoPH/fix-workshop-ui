<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Model\Warehouse
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $state 状态：'START'开始, 'END'结束, 'CANCEL'取消
 * @property string $unique_code
 * @property-read array $direction 入库（绑定位置）：IN
 * 出库（确定出库）：OUT
 * 报废（选择报废）：SCRAP
 * 报损（选择报损）：FRMLOSS
 * @property int $account_id 操作人id
 * @property string $receiver 领取人
 * @property string $go_direction 去向
 * @property string|null $connection_phone
 * @property string|null $maintain_unique_code 车站车间关联编码
 * @property-read Account $WithAccount
 * @property-read Collection|WarehouseMaterial[] $WithWarehouseMaterials
 * @property-read int|null $with_warehouse_materials_count
 */
class Warehouse extends Base
{
    use SoftDeletes;

    public static $DIRECTION = [
        'IN_WAREHOUSE' => '入库',
        'OUT_WAREHOUSE' => '出库',
        'SCRAP' => '报废',
        'FRMLOSS' => '报损',
    ];
    public static $STATE = [
        'START' => '开始',
        'END' => '结束',
        'CANCEL' => '作废'
    ];
    protected $guarded = [];

    final public function prototype(string $key)
    {
        return @$this->attributes[$key] ?: "";
    }

    public function getDirectionAttribute($value): array
    {
        return [
            'value' => $value,
            'text' => self::$DIRECTION[$value],
        ];
    }

    public function getStateAttribute($value): string
    {
        return self::$STATE[$value];
    }

    /**
     * 生成编号
     * @param string $direction
     * @return string
     */
    final public function getUniqueCode(string $direction): string
    {
        return self::generateUniqueCode($direction);
    }

    /**
     * 生成编号
     * @param string $direction
     * @return string
     */
    final public static function generateUniqueCode(string $direction): string
    {
        $time = date("Ymd", time());
        $ware = Warehouse::where('direction', $direction)->orderby('unique_code', 'desc')->select('unique_code')->first();
        if (empty($ware)) {
            $unique_code = $direction . $time . '0001';
        } else {
            if (strstr($ware->unique_code, $time)) {
                $suffix = sprintf("%04d", substr($ware->unique_code, -4) + 1);
                $unique_code = $direction . $time . $suffix;
            } else {
                $unique_code = $direction . $time . '0001';
            }
        }
        return $unique_code;
    }

    final public function WithAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    final public function WithWarehouseMaterials(): HasMany
    {
        return $this->hasMany(WarehouseMaterial::class, 'warehouse_unique_code', 'unique_code');
    }

}
