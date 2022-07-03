<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;


/**
 * Class EntireInstanceUpdateFixerCheckerOrderItem
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $entire_instance_identity_code 唯一编号
 * @property string $serial_number 所编号
 * @property EntireInstance $EntireInstance 器材
 * @property string $fixer_name 检修人
 * @property Carbon $fixed_at 检修时间
 * @property string $checker_name 验收人
 * @property Carbon $checked_at 验收时间
 * @property string $spot_checker_name 抽验人
 * @property Carbon $spot_checked_at 抽验时间
 * @property string $entire_instance_update_fixer_checker_order_uuid 所属上传检修人验收人单号
 * @property EntireInstanceUpdateFixerCheckerOrder $EntireInstanceUpdateFixerCheckerOrder 上传检修人验收人单
 * @property bool $be_new_tagging 是否是新赋码
 */
class EntireInstanceUpdateFixerCheckerOrderItem extends Base
{
    protected $guarded = [];

    /**
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, "identity_code", "entire_instance_identity_code");
    }

    /**
     * @return HasOne
     */
    final public function EntireInstanceUpdateFixerCheckerOrder(): HasOne
    {
        return $this->hasOne(EntireInstanceUpdateFixerCheckerOrder::class, "uuid", "entire_instance_update_fixer_checker_order_uuid");
    }
}
