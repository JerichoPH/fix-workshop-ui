<?php


namespace App\Model;

use App\Facades\TextFacade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Model\Area
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $storehouse_unique_code 仓关联编码
 * @property string $unique_code 区编码
 * @property-read Collection|Platoon[] $WithPlatoons
 * @property-read int|null $with_platoons_count
 * @property-read Storehouse $WithStorehouse
 * @property-read Collection|Platoon[] $subset
 * @property-read int|null $subset_count
 * @property-read Storehouse $Storehouse
 */
class Area extends Model
{
    protected $guarded = [];

    /**
     * 生成新编码
     * @param $storehouseUniqueCode
     * @return string
     */
    final public static function generateUniqueCode(string $storehouseUniqueCode): string
    {
        $area = self::with([])->where('storehouse_unique_code', $storehouseUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $storehouseUniqueCode;
        if (empty($area)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $area->unique_code;
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
            "WithStorehouse",
        ])
            ->where("unique_code", $unique_code)
            ->first();
        return self::__getRealName($self);
    }

    /**
     * 获取区编码
     * @param string $storehouseUniqueCode
     * @return string
     */
    public function getUniqueCode(string $storehouseUniqueCode): string
    {
        $area = Area::with([])->where('storehouse_unique_code', $storehouseUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $storehouseUniqueCode;
        if (empty($area)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $area->unique_code;
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
     * @param Area $self
     * @return string
     */
    private static function __getRealName(?self $self): string
    {
        return $self ? TextFacade::joinWithNotEmpty(" ", [
            strval(@$self->WithStorehouse ?: ""),
            strval(@$self ?: ""),
        ]) : "";
    }

    public function __toString(): string
    {
        return preg_match("/(区)+/", $this->attributes["name"]) ? $this->attributes["name"] : $this->attributes["name"] . "区";
    }

    final public function WithPlatoons(): HasMany
    {
        return $this->hasMany(Platoon::class, 'area_unique_code', 'unique_code');
    }

    final public function WithStorehouse(): HasOne
    {
        return $this->hasOne(Storehouse::class, 'unique_code', 'storehouse_unique_code');
    }

    final public function Storehouse(): HasOne
    {
        return $this->hasOne(Storehouse::class, 'unique_code', 'storehouse_unique_code');
    }

    final public function subset(): HasMany
    {
        return $this->hasMany(Platoon::class, 'area_unique_code', 'unique_code');
    }
}
