<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

/**
 * App\Model\TakeStock
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $unique_code 编码
 * @property string $state 状态：START 开始 ； END 结束；CANCEL 取消
 * @property string $result 盘点结果：NODIF 无差异，YESDIF 有差异
 * @property int $stock_diff 库存
 * @property int $real_stock_diff 实际库存
 * @property int $account_id 操作人
 * @property string $location_unique_code 仓库编码
 * @property string $name 盘点名称
 * @property-read \App\Model\Account $WithAccount
 * @property-read \App\Model\Area $WithArea
 * @property-read \App\Model\Platoon $WithPlatoon
 * @property-read \App\Model\Position $WithPosition
 * @property-read \App\Model\Shelf $WithShelf
 * @property-read \App\Model\Storehouse $WithStorehouse
 * @property-read \App\Model\Tier $WithTier
 * @property-read mixed $r_e_s_u_l_t
 * @property-read mixed $s_t_a_t_e
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereLocationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereRealStockDiff($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereStockDiff($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TakeStock whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TakeStock extends Model
{
    protected $guarded = [];

    public static $STATE = [
        'START' => '盘点开始',
        'END' => '盘点结束',
        'CANCEL' => '盘点作废'
    ];

    public static $RESULT = [
        'NODIF' => '无差异',
        'YESDIF' => '有差异'
    ];


    public function getSTATEAttribute($value)
    {
        return self::$STATE[$value];
    }

    public function getRESULTAttribute($value)
    {
        return self::$RESULT[$value];
    }

    /**
     * 获取编码
     * @param string $direction
     * @return string
     */
    public function getUniqueCode(string $direction = 'PD')
    {

        $time = date('Ymd');
        $take = DB::table('take_stocks')->orderby('unique_code', 'desc')->select('unique_code')->first();
        if (empty($take)) {
            $unique_code = $direction . $time . '0001';
        } else {
            if (strstr($take->unique_code, $time)) {
                $suffix = sprintf("%04d", substr($take->unique_code, -4) + 1);
                $unique_code = $direction . $time . $suffix;
            } else {
                $unique_code = $direction . $time . '0001';
            }
        }
        return $unique_code;
    }

    public function WithAccount()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function WithPosition()
    {
        return $this->belongsTo(Position::class, 'location_unique_code', 'unique_code');
    }

    public function WithTier()
    {
        return $this->belongsTo(Tier::class, 'location_unique_code', 'unique_code');
    }

    public function WithShelf()
    {
        return $this->belongsTo(Shelf::class, 'location_unique_code', 'unique_code');
    }

    public function WithPlatoon()
    {
        return $this->belongsTo(Platoon::class, 'location_unique_code', 'unique_code');
    }

    public function WithArea()
    {
        return $this->belongsTo(Area::class, 'location_unique_code', 'unique_code');
    }

    public function WithStorehouse()
    {
        return $this->belongsTo(Storehouse::class, 'location_unique_code', 'unique_code');
    }
}
