<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Model\Line
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $unique_code 线别编码
 * @property string $name 线别名称
 * @property-read Organization $organization
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Line onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Line withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Line withoutTrashed()
 * @mixin \Eloquent
 */
class Line extends Base
{
    use SoftDeletes;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        /**
         * 所有查询不包括已报废
         */
        static::addGlobalScope('is_show', function (Builder $builder) {
            $builder->where('is_show', true);
        });
    }

    final public function organization(): HasOne
    {
        return $this->hasOne(Organization::class, 'id', 'organization_id');
    }

    final public function Stations(): BelongsToMany
    {
        return $this->belongsToMany(Maintain::class, 'lines_maintains', 'lines_id', 'maintains_id');
    }
}
