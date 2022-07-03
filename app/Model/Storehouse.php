<?php


namespace App\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Model\Storehouse
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code
 * @property-read Collection|Area[] $WithAreas
 * @property-read int|null $with_areas_count
 * @property-read Collection|Area[] $subset
 * @property-read int|null $subset_count
 * @property-read string $real_name
 * @property string $workshop_unique_code
 */
class Storehouse extends Model
{
    protected $guarded = [];

    public static function getRealName(string $unique_code): string
    {
        $self = (new self())->with([])->where("unique_code", $unique_code)->first();
        if (!$self) return "";

        return self::__getRealName($self);
    }

    public function getRealNameAttribute(): string
    {
        return self::__getRealName($this);
    }

    private static function __getRealName(self $self): string
    {
        return strval($self ?: "");
    }

    /**
     * 获取仓编码
     * @return string
     */
    public function getUniqueCode(): string
    {
        $storehouse = Storehouse::orderBy('id', 'desc')->first();
        $prefix = env('ORGANIZATION_LOCATION_CODE');
        if (empty($storehouse)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $storehouse->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return preg_match("/(仓)+/", $this->attributes["name"]) ? $this->attributes["name"] : $this->attributes["name"] . "仓";
    }

    /**
     * @return HasMany
     */
    final public function WithAreas(): HasMany
    {
        return $this->hasMany(Area::class, 'storehouse_unique_code', 'unique_code');
    }

    /**
     * @return HasMany
     */
    final public function subset(): HasMany
    {
        return $this->hasMany(Area::class, 'storehouse_unique_code', 'unique_code');
    }

}
