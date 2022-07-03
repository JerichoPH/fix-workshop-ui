<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Status
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 状态名称
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Account[] $accounts
 * @property-read int|null $accounts_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Status newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Status onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Status query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Status whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Status whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Status whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Status whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Status whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Status withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Status withoutTrashed()
 * @mixin \Eloquent
 */
class Status extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'status_id', 'id');
    }
}
