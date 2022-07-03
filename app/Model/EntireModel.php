<?php

namespace App\Model;

use App\Facades\TextFacade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Model\EntireModel
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 设备型号名称
 * @property string $unique_code 设备型号开放代码
 * @property string $category_unique_code 类型唯一代码
 * @property string $fix_cycle_unit 维修周期时长单位
 * @property int $fix_cycle_value 维修周期时长
 * @property int $is_sub_model 是否是子类
 * @property string|null $parent_unique_code 父级代码
 * @property int $life_year 寿命
 * @property string $nickname 别名
 * @property-read EntireInstance[] $EntireInstancesByEntireModel
 * @property-read EntireInstance[] $EntireInstancesByModel
 * @property-read Category $Category
 * @property-read Collection|EntireInstance[] $EntireInstances
 * @property-read int|null $entire_instances_count
 * @property-read EntireModel $EntireModel
 * @property-read Collection|EntireModelIdCode[] $EntireModelIdCodes
 * @property-read int|null $entire_model_id_codes_count
 * @property-read Collection|Factory[] $Factories
 * @property-read int|null $factories_count
 * @property-read Collection|Measurement[] $Measurements
 * @property-read int|null $measurements_count
 * @property-read EntireModel $Parent
 * @property-read Collection|PartModel[] $PartModels
 * @property-read int|null $part_models_count
 * @property-read Collection|EntireModel[] $Subs
 * @property-read int|null $subs_count
 * @property-read Collection|EntireModelImage[] $EntireModelImages
 * @property bool $custom_fix_cycle
 */
class EntireModel extends Model
{
    use SoftDeletes;

    public static $FIX_CYCLE_UNIT = [
        'YEAR' => '年',
        'MONTH' => '月',
        'WEEK' => '周',
        'DAY' => '日',
    ];

    protected $guarded = [];

    /**
     * 生成类型代码
     * @param string $category_unique_code
     * @param string $connection
     * @return string
     */
    final static public function generateEntireModelUniqueCode(string $category_unique_code, string $connection = ''): string
    {
        if ($connection) {
            $entire_model = self::on($connection)->with([]);
        } else {
            $entire_model = self::with([]);
        }

        $entire_model = $entire_model
            ->orderByDesc('unique_code')
            ->where('category_unique_code', $category_unique_code)
            ->where('is_sub_model', false)
            ->first();

        $last_unique_code = $entire_model ? substr($entire_model->unique_code, -2) : "00";
        if (env("ENTIRE_MODEL_TAGGING_USE_36_SCALE")) {
            $last_unique_code = TextFacade::from36($last_unique_code);
        }

        $max = ($entire_model ? intval($last_unique_code) : 0) + 1;
        if (env("ENTIRE_MODEL_TAGGING_USE_36_SCALE")) {
            $max = TextFacade::to36($max);
        }
        return $category_unique_code . str_pad($max, 2, '0', STR_PAD_LEFT);
    }

    final static public function generateEntireModelUniqueCode2(string $category_unique_code, string $connection = ''): string
    {
        if ($connection) {
            $entire_model = self::on($connection)->with([]);
        } else {
            $entire_model = self::with([]);
        }

        $entire_model = $entire_model
            ->orderByDesc('new_unique_code')
            ->where('category_unique_code', $category_unique_code)
            ->where('is_sub_model', false)
            ->first();

        $last_unique_code = $entire_model ? substr($entire_model->new_unique_code, -2) : "00";
        if (env("ENTIRE_MODEL_TAGGING_USE_36_SCALE")) {
            $last_unique_code = TextFacade::from36($last_unique_code);
        }

        $max = ($entire_model ? intval($last_unique_code) : 0) + 1;
        if (env("ENTIRE_MODEL_TAGGING_USE_36_SCALE")) {
            $max = TextFacade::to36($max);
        }
        return $category_unique_code . str_pad($max, 2, '0', STR_PAD_LEFT);
    }

    /**
     * 生成子类代码
     * @param string $parent_unique_code
     * @param string $connection
     * @return string
     */
    final static public function generateSubModelUniqueCode(string $parent_unique_code, string $connection = ''): string
    {
        if ($connection) {
            $sub_model = self::on($connection)->with([]);
        } else {
            $sub_model = self::with([]);
        }
        $sub_model = $sub_model
            ->orderByDesc('unique_code')
            ->where('parent_unique_code', $parent_unique_code)
            ->where('is_sub_model', true)
            ->first();

        $last_unique_code = $sub_model ? substr($sub_model->unique_code, -2) : "00";
        if (env("SUB_MODEL_TAGGING_USE_36_SCALE")) {
            $last_unique_code = TextFacade::from36($last_unique_code);
        }

        $max = ($sub_model ? intval($last_unique_code) : 0) + 1;
        if (env("SUB_MODEL_TAGGING_USE_36_SCALE")) {
            $max = TextFacade::to36($max);
        }
        return $parent_unique_code . str_pad($max, 2, '0', STR_PAD_LEFT);
    }

    final public static function generateSubModelUniqueCode2(string $parent_unique_code, string $connection = ''): string
    {
        if ($connection) {
            $sub_model = self::on($connection)->with([]);
        } else {
            $sub_model = self::with([]);
        }

        $sub_model = $sub_model
            ->orderByDesc('new_unique_code')
            ->where('parent_unique_code', $parent_unique_code)
            ->where('is_sub_model', true)
            ->first();

        $last_unique_code = $sub_model ? substr($sub_model->new_unique_code, -2) : "00";
        if (env("SUB_MODEL_TAGGING_USE_36_SCALE")) {
            $last_unique_code = TextFacade::from36($last_unique_code);
        }

        $max = ($sub_model ? intval($last_unique_code) : 0) + 1;
        if (env("SUB_MODEL_TAGGING_USE_36_SCALE")) {
            $max = TextFacade::to36($max);
        }
        return $parent_unique_code . str_pad($max, 2, '0', STR_PAD_LEFT);
    }

    public static function flipFixCycleUnit($value)
    {
        return array_flip(self::$FIX_CYCLE_UNIT)[$value];
    }

    final public function prototype($attributeKey)
    {
        return $this->attributes[$attributeKey];
    }

    final public function getFixCycleUnitAttribute($value)
    {
        return self::$FIX_CYCLE_UNIT[$value];
    }

    final public function Category(): HasOne
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    final public function Parent(): HasOne
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'parent_unique_code');
    }

    final public function EntireModel(): HasOne
    {
        return $this->hasOne(self::class, 'unique_code', 'parent_unique_code');
    }

    final public function Subs(): HasMany
    {
        return $this->hasMany(EntireModel::class, 'parent_unique_code', 'unique_code');
    }

    final public function EntireInstances(): HasMany
    {
        return $this->hasMany(EntireInstance::class, 'entire_model_unique_code', 'unique_code');
    }

    final public function EntireInstancesByEntireModel(): HasMany
    {
        return $this->hasMany(EntireInstance::class, 'entire_model_unique_code', 'unique_code');
    }

    final public function EntireInstancesByModel(): HasMany
    {
        return $this->hasMany(EntireInstance::class, 'model_unique_code', 'unique_code');
    }

    final public function Measurements(): HasMany
    {
        return $this->hasMany(Measurement::class, 'entire_model_unique_code', 'unique_code');
    }

    final public function PartModels(): HasMany
    {
        return $this->hasMany(PartModel::class, 'entire_model_unique_code', 'unique_code');
    }

    // final public function PartModels()
    // {
    //     return $this->belongsToMany(
    //         'App\Model\PartModel',
    //         'pivot_entire_model_and_part_models',
    //         'entire_model_unique_code',
    //         'part_model_unique_code'
    //     );
    // }

    final public function EntireModelIdCodes(): HasMany
    {
        return $this->hasMany(EntireModelIdCode::class, 'entire_model_unique_code', 'unique_code');
    }

    final public function Factories(): BelongsToMany
    {
        return $this->belongsToMany(
            Factory::class,
            'pivot_entire_model_and_factories',
            'entire_model_unique_code',
            'factory_name'
        );
    }

    final public function EntireModelImages(): HasMany
    {
        return $this->hasMany(EntireModelImage::class, 'entire_model_unique_code', 'unique_code');
    }
}
