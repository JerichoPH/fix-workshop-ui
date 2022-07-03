<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\PartModel
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string $name
 * @property string $unique_code
 * @property string $category_unique_code
 * @property string $entire_model_unique_code
 * @property int $part_category_id
 * @property int $fix_cycle_value
 * @property int $life_year
 * @property-read Category $category
 * @property-read EntireModel $Parent
 * @property-read EntireModel $EntireModel
 * @property-read PartInstance[] $PartInstances
 * @property-read PartCategory $PartCategory
 * @property-read Measurement[] $Measurements
 * @property-read PartModelImage[] $PartModelImages
 */
class PartModel extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 生成型号代码
     * @param string $entire_model_unique_code
     * @param string $connection
     * @return string
     */
    final static public function generateUniqueCode(string $entire_model_unique_code, string $connection = ''): string
    {
        if ($connection) {
            $part_model = self::on($connection)->with([]);
        } else {
            $part_model = self::with([]);
        }

        $part_model = $part_model
            ->orderByDesc('unique_code')
            ->where('entire_model_unique_code', $entire_model_unique_code)
            ->first();
        $max = $part_model ? intval(substr($part_model->unique_code, -2)) : 0;
        return $entire_model_unique_code . 'N' . str_pad($max + 1, 2, '0', STR_PAD_LEFT);
    }

    final public function Category(): HasOne
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    final public function Parent(): HasOne
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    final public function EntireModel(): HasOne
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    final public function EntireModels(): BelongsToMany
    {
        return $this->belongsToMany(
            EntireModel::class,
            'pivot_entire_model_and_part_models',
            'part_model_unique_code',
            'entire_model_unique_code'
        );
    }

    final public function PartInstances(): HasMany
    {
        return $this->hasMany(PartInstance::class, 'part_model_unique_code', 'unique_code');
    }

    final public function PartCategory(): HasOne
    {
        return $this->hasOne(PartCategory::class, "id", "part_category_id");
    }

    final public function Measurements(): HasMany
    {
        return $this->hasMany(Measurement::class, 'part_model_unique_code', 'unique_code');
    }

    final public function PartModelImages(): HasMany
    {
        return $this->hasMany(PartModelImage::class, 'part_model_unique_code', 'unique_code');
    }
}
