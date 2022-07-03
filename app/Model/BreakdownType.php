<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\BreakdownType
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name
 * @property string $category_unique_code
 * @property string $category_name
 * @property int|null $work_area 0：通用
 *     1：转辙机工区
 *     2：继电器工区
 *     3：综合工区
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\BreakdownLog[] $BreakdownLogs
 * @property-read int|null $breakdown_logs_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\BreakdownType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownType whereWorkArea($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\BreakdownType withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\BreakdownType withoutTrashed()
 * @mixin \Eloquent
 */
class BreakdownType extends Model
{
    use SoftDeletes;

    public static $WORK_AREA_TYPES = [
        0 => '通用',
        1 => '转辙机',
        2 => '继电器',
        3 => '综合',
        4 => '电源屏',
    ];

    protected $guarded = [];

    /**
     * 故障日志
     * @return BelongsToMany
     */
    final public function BreakdownLogs(): BelongsToMany
    {
        return $this->belongsToMany(
            BreakdownLog::class,
            'pivot_breakdown_log_and_breakdown_types',
            'breakdown_type_id',
            'breakdown_log_id'
        );
    }

    /**
     * 所属种类
     * @return HasOne
     */
    final public function Category(): HasOne
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    /**
     * 工区类型
     * @param $value
     * @return object
     */
    final public function getWorkAreaAttribute($value)
    {
        return (object)[
            'code' => $value,
            'name' => self::$WORK_AREA_TYPES[$value] ?? '通用',
        ];
    }
}
