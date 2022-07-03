<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\ThirdParty
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $username 登陆账号
 * @property string $password 登录密码
 * @property string $real_name 真实名称
 * @property string|null $jwt JWT内容
 * @property int $current_day_apply_for_times 当日申请JWT的次数
 * @property string $open_id
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\ThirdParty onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty whereCurrentDayApplyForTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty whereJwt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty whereOpenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty whereRealName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\ThirdParty whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\ThirdParty withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\ThirdParty withoutTrashed()
 * @mixin \Eloquent
 */
class ThirdParty extends Model
{
    use SoftDeletes;

    protected $guarded = [];
}
