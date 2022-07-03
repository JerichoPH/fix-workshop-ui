<?php

namespace App\Model\ParagraphCenter;

use App\Model\Account;
use App\Model\Base;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class ParagraphCenterMeasurement
 * @package App\Model\ParagraphCenter
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $account_id
 * @property-read Account $Account
 * @property string $serial_number
 * @property string $name
 * @property string $type
 * @property string $category_unique_code
 * @property string $entire_model_unique_code
 * @property string $sub_model_unique_code
 * @property string $business_type
 * @property-read ParagraphCenterMeasurementStep[] $Steps
 */
class ParagraphCenterMeasurement extends Base
{
    protected $guarded = [];

    /**
     * 创建用户
     * @return HasOne
     */
    final public function Account():HasOne
    {
        return $this->hasOne(Account::class,"id","account_id");
    }

    /**
     * 相关步骤
     * @return HasMany
     */
    final public function Steps():HasMany
    {
        return $this->hasMany(ParagraphCenterMeasurementStep::class,"paragraph_center_measurement_sn","serial_number");
    }
}
