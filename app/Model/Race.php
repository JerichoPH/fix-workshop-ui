<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Race
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $unique_code 种型编号
 * @property string|null $name 种型名称
 * @property int $serial_number_length 序号代码长度
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Category[] $Categories
 * @property-read int|null $categories_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Race onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race whereSerialNumberLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Race whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Race withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Race withoutTrashed()
 * @mixin \Eloquent
 */
class Race extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function Categories()
    {
        return $this->hasMany(Category::class, 'race_unique_code', 'unique_code');
    }
}
