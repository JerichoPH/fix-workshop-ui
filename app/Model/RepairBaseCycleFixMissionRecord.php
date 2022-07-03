<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseCycleFixMissionRecord
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $completing_at  任务预计完成时间
 * @property int $belongs_to_account_id  任务所属人
 * @property int $number  分配数量
 * @property string $model_unique_code  型号
 * @property string $model_name  型号名称
 * @property string $category_unique_code  种类
 * @property string $category_name  种类名称
 * @property-read \App\Model\Account $BelongsToAccount
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereBelongsToAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereCompletingAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseCycleFixMissionRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RepairBaseCycleFixMissionRecord extends Model
{
    protected $guarded = [];

    /**
     * 任务所属人
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function BelongsToAccount()
    {
        return $this->hasOne(Account::class, 'id', 'belongs_to_account_id');
    }
}
