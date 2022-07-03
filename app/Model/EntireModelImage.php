<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class EntireModelImage
 * @package App\Model
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $original_filename
 * @property string $original_extension
 * @property string $filename
 * @property string $entire_model_unique_code
 * @property string $url
 * @property-read \App\Model\EntireModel|null $EntireModel
 */
class EntireModelImage extends Model
{
    protected $guarded = [];

    final public function EntireModel(): HasOne
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    public function setFilenameAttribute($value)
    {
        $this->attributes['filename'] = "app/public/entireModelImages/{$value}";
    }

    public function setUrlAttribute($value)
    {
        $this->attributes['url'] = "/storage/entireModelImages/{$value}";
    }

    final public static function getLastFilenameByEntireModelUniqueCode(string $entire_model_unique_code): int
    {
        $last = self::with([])->where('entire_model_unique_code', $entire_model_unique_code)->orderByDesc('id')->first();
        return $last ? intval(ltrim($last->filename, $entire_model_unique_code)) : 0;
    }
}
