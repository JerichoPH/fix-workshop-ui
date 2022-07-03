<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\FixMissionOrderEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $fix_mission_order_serial_number
 * @property int $account_id
 * @property string $entire_instance_identity_code
 * @property string $abort_date 截止日期
 * @property string|null $acceptance_date 验收日期
 * @property int $work_area_id 所属工区
 * @property string|null $model_name 型号名称
 * @property-read \App\Model\Account $Account
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\FixMissionOrder $FixMissionOrder
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereAbortDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereAcceptanceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereFixMissionOrderSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixMissionOrderEntireInstance whereWorkAreaId($value)
 * @mixin \Eloquent
 */
class FixMissionOrderEntireInstance extends Model
{
    protected $guarded = [];

    final public function FixMissionOrder()
    {
        return $this->hasOne(FixMissionOrder::class, 'serial_number', 'fix_mission_order_serial_number');
    }

    final public function Account()
    {
        return $this->hasone(Account::class, 'id', 'account_id');
    }

    final public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }
}
