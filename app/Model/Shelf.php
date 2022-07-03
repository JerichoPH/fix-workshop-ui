<?php


namespace App\Model;

use App\Facades\TextFacade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Model\Shelf
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 架编码
 * @property string $platoon_unique_code 排关联编码
 * @property string $location_img 位置图片
 * @property-read Platoon $WithPlatoon
 * @property-read Collection|Tier[] $WithTiers
 * @property-read int|null $with_tiers_count
 * @property-read Collection|Tier[] $subset
 * @property-read int|null $subset_count
 * @property-read Platoon $Platoon
 * @property-read string $real_name
 */
class Shelf extends Model
{
    protected $guarded = [];

    /**
     * 获取新编码
     * @param string $platoonUniqueCode
     * @return string
     */
    final public static function generateUniqueCode(string $platoonUniqueCode): string
    {
        $shelf = self::with([])->where('platoon_unique_code', $platoonUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $platoonUniqueCode;
        if (empty($shelf)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $shelf->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    /**
     * 获取真实名称
     * @param string $unique_code
     * @return string
     */
    public static function getRealName(string $unique_code): string
    {
        $self = (new self())->with([
            "WithPlatoon",
            "WithPlatoon.WithArea",
            "WithPlatoon.WithArea.WithStorehouse",
        ])
            ->where("unique_code", $unique_code)
            ->first();
        return self::__getRealName($self);
    }

    /**
     * 获取架编码
     * @param string $platoonUniqueCode
     * @return string
     */
    public function getUniqueCode(string $platoonUniqueCode): string
    {
        $shelf = Shelf::with([])->where('platoon_unique_code', $platoonUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $platoonUniqueCode;
        if (empty($shelf)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $shelf->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    /**
     * 获取真实名称
     * @param $value
     * @return string
     */
    public function getRealNameAttribute($value): string
    {
        return self::__getRealName($this);
    }

    /**
     * 获取真实名称
     * @param Shelf $self
     * @return string
     */
    private static function __getRealName(?self $self): string
    {
        return $self ? TextFacade::joinWithNotEmpty(" ", [
            strval(@$self->WithPlatoon->WithArea->WithStorehouse ?: ""),
            strval(@$self->WithPlatoon->WithArea ?: ""),
            strval(@$self->WithPlatoon ?: ""),
            strval(@$self ?: ""),
        ]) : "";
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return preg_match("/(架)+/", $this->attributes["name"]) ? $this->attributes["name"] : $this->attributes["name"] . "架";
    }

    final public function WithTiers(): HasMany
    {
        return $this->hasMany(Tier::class, 'shelf_unique_code', 'unique_code');
    }

    final public function WithPlatoon(): BelongsTo
    {
        return $this->belongsTo(Platoon::class, 'platoon_unique_code', 'unique_code');
    }

    final public function Platoon(): HasOne
    {
        return $this->hasOne(Platoon::class, 'unique_code', 'platoon_unique_code');
    }

    final public function subset(): HasMany
    {
        return $this->hasMany(Tier::class, 'shelf_unique_code', 'unique_code');
    }

}
