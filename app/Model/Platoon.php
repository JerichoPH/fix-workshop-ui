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
 * App\Model\Platoon
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 排编码
 * @property string $area_unique_code 区关联编码
 * @property-read Area $WithArea
 * @property-read Collection|Shelf[] $WithShelfs
 * @property-read int|null $with_shelfs_count
 * @property-read Collection|Shelf[] $subset
 * @property-read int|null $subset_count
 * @property-read Area $Area
 */
class Platoon extends Model
{
    public static $TYPES = [
        "FIXED" => "成品",
        "FIXING" => "待修",
        "MATERIAL" => "材料",
        "EMERGENCY" => "应急备品",
        "SCRAP" => "废品",
    ];
    protected $guarded = [];

    /**
     * 生成新编码
     * @param string $areaUniqueCode
     * @return string
     */
    final public static function generateUniqueCode(string $areaUniqueCode): string
    {
        $platoon = self::with([])->where('area_unique_code', $areaUniqueCode)->orderBy('id', 'desc')->first();
        if (empty($platoon)) {
            $uniqueCode = $areaUniqueCode . '01';
        } else {
            $lastUniqueCode = $platoon->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($areaUniqueCode)) + 1);
            $uniqueCode = $areaUniqueCode . $suffix;
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
            "WithArea",
            "WithArea.WithStorehouse",
        ])
            ->where("unique_code", $unique_code)
            ->first();
        return self::__getRealName($self);
    }

    /**
     * 获取真实名称
     * @param Platoon $self
     * @return string
     */
    private static function __getRealName(?self $self): string
    {
        return $self ? TextFacade::joinWithNotEmpty(" ", [
            strval(@$self->WithArea->WithStorehouse ?: ""),
            strval(@$self->WithArea ?: ""),
            strval(@$self ?: ""),
        ]) : "";
    }

    /**
     * 获取排编码
     * @param string $areaUniqueCode
     * @return string
     */
    public function getUniqueCode(string $areaUniqueCode): string
    {
        $platoon = Platoon::with([])->where('area_unique_code', $areaUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $areaUniqueCode;
        if (empty($platoon)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $platoon->unique_code;
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
     * @return string
     */
    public function __toString(): string
    {
        return $this->getNameAttribute($this->attributes["name"]);
    }

    /**
     * 获取名称
     * @param $value
     * @return string
     */
    final public function getNameAttribute($value): string
    {
        $name = preg_match("/(排)+/", $value) ? $value : $value . "排";
        $type = self::$TYPES[$this->attributes["type"]] ?? "";
        $type = $type ? "($type)" : "";
        return $name . $type;
    }

    /**
     * 类型名称
     * @param $value
     * @return string
     */
    final public function getTypeNameAttribute($value): string
    {
        return self::$TYPES[$value] ?? "";
    }

    final public function WithShelfs(): HasMany
    {
        return $this->hasMany(Shelf::class, 'platoon_unique_code', 'unique_code');
    }

    final public function WithShelves(): HasMany
    {
        return $this->hasMany(Shelf::class, 'platoon_unique_code', 'unique_code');
    }

    final public function WithArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_unique_code', 'unique_code');
    }

    final public function Area(): HasOne
    {
        return $this->hasOne(Area::class, 'unique_code', 'area_unique_code');
    }

    final public function subset(): HasMany
    {
        return $this->hasMany(Shelf::class, 'platoon_unique_code', 'unique_code');
    }

}
