<?php

namespace App\Model;

use App\Facades\TextFacade;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Category
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 设备类型名称
 * @property string $unique_code 统一代码
 * @property string|null $race_unique_code 种型
 * @property string $nickname 别名
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireModel[] $EntireModels
 * @property-read int|null $entire_models_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\PartCategory[] $PartCategories
 * @property-read int|null $part_categories_count
 * @property-read \App\Model\Race $Race
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireModel[] $Subs
 * @property-read int|null $subs_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereRaceUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Category withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Category withoutTrashed()
 * @mixin \Eloquent
 */
class Category extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $__show_scope = [
        '1' => '现场车间',
        '2' => '检修车间',
        '3' => '电子车间',
        '4' => '车载车间',
        '5' => '驼峰车间',
    ];

    /**
     * 生成唯一编号
     * @param string $type
     * @param string $connection
     * @return string
     * @throws Exception
     */
    final static public function generateUniqueCode(string $type, string $connection = ''): string
    {
        if (!$type) throw new Exception('种类编码错误');
        if ($connection) {
            $category = self::on($connection)->with([]);
        } else {
            $category = self::with([]);
        }

        $last = $category->where('unique_code', 'like', "$type%")->orderByDesc('unique_code')->first();

        $last_unique_code = $last ? substr($last->unique_code, -2) : "00";
        if (env("CATEGORY_TAGGING_USE_36_SCALE")) {
            $last_unique_code = TextFacade::from36($last_unique_code);
        }

        $max = ($last ? intval($last_unique_code) : 0) + 1;
        if (env("CATEGORY_TAGGING_USE_36_SCALE")) {
            $max = TextFacade::to36($max);
        }

        return $type . str_pad($max, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @throws Exception
     */
    final static public function generateUniqueCode2(string $type, string $connection = ''): string
    {
        if (!$type) throw new Exception('种类编码错误');
        if ($connection) {
            $category = self::on($connection)->with([]);
        } else {
            $category = self::with([]);
        }

        $last = $category->where('new_unique_code', 'like', "$type%")->orderByDesc('new_unique_code')->first();

        $last_unique_code = $last ? substr($last->unique_code, -2) : "00";
        if (env("CATEGORY_TAGGING_USE_36_SCALE")) {
            $last_unique_code = TextFacade::from36($last_unique_code);
        }

        $max = ($last ? intval($last_unique_code) : 0) + 1;
        if (env("CATEGORY_TAGGING_USE_36_SCALE")) {
            $max = TextFacade::to36($max);
        }

        return $type . str_pad($max + 1, 2, '0', STR_PAD_LEFT);
    }

    /**
     * 该类目下所有实例
     * @return HasMany
     */
    public function EntireModels(): HasMany
    {
        return $this->hasMany(EntireModel::class, 'category_unique_code', 'unique_code');
    }

    /**
     * @return HasOne
     */
    public function Race(): HasOne
    {
        return $this->hasOne(Race::class, 'unique_code', 'race_unique_code');
    }

    /**
     * @return HasMany
     */
    public function PartCategories(): HasMany
    {
        return $this->hasMany(PartCategory::class, "category_unique_code", "unique_code");
    }

    /**
     * @return HasMany
     */
    public function Subs(): HasMany
    {
        return $this->hasMany(EntireModel::class, 'category_unique_code', 'unique_code')->where('is_sub_model', false);
    }
}
