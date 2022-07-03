<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Model\Maintain
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $unique_code 统一标识
 * @property string $name 名称
 * @property string|null $location_code 位置编码
 * @property string|null $explain 说明
 * @property string|null $parent_unique_code 父级
 * @property string $type 类型
 * @property string|null $lon
 * @property string|null $lat
 * @property string|null $contact
 * @property string|null $contact_phone
 * @property string|null $contact_address
 * @property bool $is_show
 * @property-read string $type_to_paragraph_center
 * @property-read Collection|EntireInstance[] $EntireInstances
 * @property-read int|null $entire_instances_count
 * @property-read Maintain|null $Parent
 * @property-read Maintain $ParentLine
 * @property-read Collection|Maintain[] $Subs
 * @property-read int|null $subs_count
 */
class Maintain extends Base
{
    use SoftDeletes;

    public static $TYPES = [
        'WORKSHOP' => '检修车间',
        'SCENE_WORKSHOP' => '现场车间',
        'STATION' => '车站',
        'ELECTRON' => '电子车间',
        'VEHICLE' => '车载车间',
        "HUMP" => '驼峰车间',
    ];

    // 车间类型:1-现场车间/2-检修车间/3-电子车间/4-车载车间/5-驼峰车间
    public static $TYPES_TO_PARAGRAPH_CENTER = [
        "SCENE_WORKSHOP" => "1",
        "WORKSHOP" => "2",
        "ELECTRON" => "3",
        "VEHICLE" => "4",
        "HUMP" => "5",
    ];

    protected $guarded = [];

    final public function __toString(): string
    {
        return rtrim($this->attributes["name"],"站");
        // return str_replace("站", "", $this->attributes["name"]);
    }

    final public function prototype($attributeKey)
    {
        return $this->attributes[$attributeKey];
    }

    final public function getTypeAttribute($value): string
    {
        return self::$TYPES[$value];
    }

    final public function getTypeToParagraphCentreAttribute($value): string
    {
        return self::$TYPES_TO_PARAGRAPH_CENTER[$this->attributes["type"]] ?: "";
    }

    final public function EntireInstances(): HasMany
    {
        return $this->hasMany(EntireInstance::class, 'maintain_station_name', 'name');
    }

    final public function Parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_unique_code', 'unique_code');
    }

    final public function ParentLine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_line_code', 'line_unique_code');
    }

    final public function Subs(): HasMany
    {
        return $this->hasMany(self::class, 'parent_unique_code', 'unique_code');
    }

    /**
     * 电子图纸
     * @return HasMany
     */
    final public function ElectricImages(): HasMany
    {
        return $this->hasMany(StationElectricImage::class, 'maintain_station_unique_code', 'unique_code');
    }

    /**
     * 机柜
     * @return HasMany
     */
    final public function EquipmentCabinets(): HasMany
    {
        return $this->hasMany(EquipmentCabinet::class, 'maintain_station_unique_code', 'unique_code');
    }

    /**
     * lines
     * @return BelongsToMany
     */
    final public function Lines(): BelongsToMany
    {
        return $this->belongsToMany(Line::class, 'lines_maintains', 'maintains_id', 'lines_id');
    }
}
