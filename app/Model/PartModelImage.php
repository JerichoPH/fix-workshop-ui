<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PartModelImage
 * @package App\Model
 * @package App\Model
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $original_filename
 * @property string $original_extension
 * @property string $filename
 * @property string $part_model_unique_code
 * @property string $url
 * @property-read \App\Model\PartModel|null $PartModel
 */
class PartModelImage extends Model
{
    protected $guarded = [];

    final public function PartModel(): HasOne
    {
        return $this->hasOne(PartModel::class, 'unique_code', 'part_model_unique_code');
    }

    public function setFilenameAttribute($value)
    {
        $this->attributes['filename'] = "app/public/partModelImages/{$value}";
    }

    public function setUrlAttribute($value)
    {
        $this->attributes['url'] = "/storage/partModelImages/{$value}";
    }

    final public static function getLastFilenameByEntireModelUniqueCode(string $part_model_unique_code): int
    {
        $last = self::with([])->where('part_model_unique_code', $part_model_unique_code)->orderByDesc('id')->first();
        return $last ? intval(ltrim($last->filename, $part_model_unique_code)) : 0;
    }
}
