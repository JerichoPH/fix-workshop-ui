<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class EntireInstanceUpdateFixerCheckerOrder
 * @package App\Model
 * @property int $id 编号
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property string $uuid 唯一编号
 * @property int $operator_id 操作人编号
 * @property-read Account $Operator 操作人
 * @property string $work_area_unique_code 所属工区代码
 * @property-read WorkArea $WorkArea 所属工区
 * @property string $error_filename 错误描述文件
 * @property int $new_tagging_count 新赋码数量
 * @property int $correct_count 修改成功数量
 * @property int $fail_count 错误数据数量
 */
class EntireInstanceUpdateFixerCheckerOrder extends Base
{
    protected $guarded = [];

    /**
     * @return HasOne
     */
    final public function Operator(): HasOne
    {
        return $this->hasOne(Account::class, "id", "operator_id");
    }

    /**
     * @return HasOne
     */
    final public function WorkArea(): HasOne
    {
        return $this->hasOne(Workarea::class, "unique_code", "work_area_unique_code");
    }

    /**
     * @return HasMany
     */
    final public function EntireInstanceUpdateFixerCheckerOrderItems(): HasMany
    {
        return $this->hasMany(EntireInstanceUpdateFixerCheckerOrderItem::class, "entire_instance_update_fixer_checker_order_uuid", "uuid");
    }
}
