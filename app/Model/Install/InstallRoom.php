<?php

namespace App\Model\Install;

use App\Model\Maintain;
use App\Model\Station;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use stdClass;

/**
 * App\Model\Install\InstallRoom
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $unique_code 机房编码
 * @property string $station_unique_code 车站编码
 * @property string $type 机房类型：11微机房
 * @property-read Collection|InstallPlatoon[] $WithInstallPlatoons
 * @property-read int|null $with_install_platoons_count
 * @property-read Station $WithStation
 */
class InstallRoom extends Model
{
    public static $TYPES = [
        "10" => "机械室",
        "11" => "微机室",
        "12" => "电源室",
        "13" => "防雷分线室",
        "14" => "备品间",
        "15" => "运转室",
        "16" => "仿真实验室",
        "17" => "SAM调度大厅",
        "18" => "SAM联络室",
        "19" => "分线柜室",
        "29" => "联合调度室",
    ];
    protected $fillable = [
        "created_at",
        "updated_at",
        "unique_code",
        "station_unique_code",
        "type",
        "name",
    ];

    public function __toString(): string
    {
        return self::$TYPES[$this->attributes["type"] ?: ""] ?: "";
    }

    public function getTypeAttribute($value): stdClass
    {
        $__ = new stdClass();
        $__->value = $value;
        $__->text = self::$TYPES[$value] ?: "";

        return $__;
    }

    public function WithInstallPlatoons(): HasMany
    {
        return $this->hasMany(InstallPlatoon::class, "install_room_unique_code", "unique_code");
    }

    public function WithStation(): BelongsTo
    {
        return $this->belongsTo(Maintain::class, "station_unique_code", "unique_code");
    }


}
