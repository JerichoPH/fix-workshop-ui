<?php

namespace App\Model\Install;

use App\Model\EntireInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\Install\InstallPosition
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $unique_code 位编码
 * @property string $name 位名称
 * @property string $install_tier_unique_code 层关联编码
 * @property-read InstallTier $WithInstallTier
 * @property-read EntireInstance $EntireInstance
 * @property-read EntireInstance[] $EntireInstances
 * @property-read string $real_name 真实名称
 * @property-read string $real_name_and_station_name 真实名称+车站名称
 */
class InstallPosition extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'unique_code',
        'install_tier_unique_code',
        'name',
        'volume',
    ];

    /**
     * 获取上次或当前上道位置（优先上次上道位置）
     * @param EntireInstance $entire_instance
     * @return string
     */
    public static function lastLocationToString(EntireInstance $entire_instance): string
    {
        return self::__locationToString(
            self::getRealName(@$entire_instance->last_maintain_location_code ?: (@$entire_instance->maintain_location_code ?: '')),
            @$entire_instance->last_maintain_location_code ?: (@$entire_instance->maintain_location_code ?: ''),
            @$entire_instance->last_crossroad_number ?: (@$entire_instance->crossroad_number ?: ''),
            @$entire_instance->last_open_direction ?: (@$entire_instance->open_direction ?: '')
        );
    }

    /**
     * 上道位置
     * @param string|null $real_name
     * @param string|null $maintain_location_code
     * @param string|null $crossroad_number
     * @param string|null $open_direction
     * @return string
     */
    private static function __locationToString(string $real_name = null, string $maintain_location_code = null, string $crossroad_number = null, string $open_direction = null): string
    {
        $location = [];
        if (empty($real_name) && empty($maintain_location_code) && empty($crossroad_number) && empty($open_direction)) {
            return '';
        } else {
            if ((@$real_name ?: @$maintain_location_code)) {
                $location[] = (@$real_name ?: @$maintain_location_code);
            }
            if ($crossroad_number) {
                $location[] = $crossroad_number;
            }
            if ($open_direction) {
                $location[] = $open_direction;
            }
            return '位置：' . implode(' ', $location);
        }
    }

    /**
     * 通过编号获取真实名称
     * @param string $unique_code
     * @param string|null $default
     * @return string
     */
    final public static function getRealName(string $unique_code, string $default = ""): string
    {
        $self = (new self())->with([
            'WithInstallTier',  // 层
            'WithInstallTier.WithInstallShelf',  // 柜
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon',  // 排
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom',  // 室
        ])
            ->whereHas("WithInstallTier")
            ->whereHas("WithInstallTier.WithInstallShelf")
            ->whereHas("WithInstallTier.WithInstallShelf.WithInstallPlatoon")
            ->whereHas("WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom")
            ->where('unique_code', $unique_code)
            ->first();
        if (!$self) return $default;

        return self::__getRealName($self, $default);
    }

    /**
     * 获取真实名称
     * @param InstallPosition|null $self
     * @param string|null $default
     * @return string
     */
    final private static function __getRealName(InstallPosition $self, string $default = ""): string
    {
        return $self
            ? collect([
                strval(@$self->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom ?: ""),
                strval(@$self->WithInstallTier->WithInstallShelf->WithInstallPlatoon ?: ""),
                strval(@$self->WithInstallTier->WithInstallShelf ?: ""),
                strval(@$self->WithInstallTier ?: ""),
                strval(@$self ?: ""),
            ])
                ->implode(" ")
            : ($default ?: "");
    }

    /**
     * 获取上道位置
     * @param EntireInstance $entire_instance
     * @return string
     */
    public static function locationToString(EntireInstance $entire_instance): string
    {
        return self::__locationToString(
            self::getRealName(@$entire_instance->maintain_location_code ?: ''),
            @$entire_instance->maintain_location_code ?: '',
            @$entire_instance->crossroad_number ?: '',
            @$entire_instance->open_direction ?: ''
        );
    }

    /**
     * 获取真实名称和车间、车站
     * @param string $unique_code
     * @param string|null $default
     * @return string|null
     */
    public static function getRealNameAndStationName(string $unique_code, string $default = ""): ?string
    {
        $position = self::with([
            'WithInstallTier',  // 层
            'WithInstallTier.WithInstallShelf',  // 柜
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon',  // 排
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom',  // 室
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation',  // 车站
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation.Parent',  // 车间
        ])
            ->where('unique_code', $unique_code)
            ->first();
        if (!$position) return $default;

        return self::__getRealNameAndStationName($position, $default);
    }

    /**
     * 获取真实名称和车间、车站
     * @param InstallPosition $position
     * @param string|null $default
     * @return string
     */
    final private static function __getRealNameAndStationName(InstallPosition $position, string $default = ""): string
    {
        return $position
            ? collect([
                @$position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->Parent->name ?? "",
                @$position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name ?? "",
                @$position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text ?? "",
                @$position->WithInstallTier->WithInstallShelf->WithInstallPlatoon ? strval($position->WithInstallTier->WithInstallShelf->WithInstallPlatoon) : "",
                @$position->WithInstallTier->WithInstallShelf ? strval($position->WithInstallTier->WithInstallShelf) : "",
                @$position->WithInstallTier ? strval($position->WithInstallTier) : "",
                @$position ? strval($position) : "",
            ])
                ->implode(" ")
            : ($default ?: "");
    }

    /**
     * 生成新编号
     * @param string $install_tier_unique_code
     * @return string
     */
    final public static function generateUniqueCode(string $install_tier_unique_code): string
    {
        $last = self::with([])
            ->where('install_tier_unique_code', $install_tier_unique_code)
            // ->orderByDesc('unique_code')
            // ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->select('unique_code')
            ->first();

        return $install_tier_unique_code . ($last ? str_pad(strval(intval(substr($last->unique_code, -2)) + 1), 2, '0', STR_PAD_LEFT) : '01');
    }

    /**
     * 获取编码
     * @param string $installTierUniqueCode
     * @param int $count
     * @return array
     */
    public static function getUniqueCodes(string $installTierUniqueCode, int $count): array
    {
        return self::generateUniqueCodes($installTierUniqueCode, $count);
    }

    /**
     * 生成多个编号
     * @param string $install_tier_unique_code
     * @param int $count
     * @return array
     */
    public static function generateUniqueCodes(string $install_tier_unique_code, int $count): array
    {
        $installPosition = DB::table('install_positions')
            ->where('install_tier_unique_code', $install_tier_unique_code)
            // ->orderByDesc('unique_code')
            // ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->select('unique_code')
            ->first();
        if (empty($installPosition)) {
            $start = '00';
        } else {
            $start = substr($installPosition->unique_code, -2);
        }
        $uniqueCodes = [];
        for ($i = 1; $i <= $count; $i++) {
            $start += 1;
            $uniqueCodes[] = [
                'unique_code' => $install_tier_unique_code . str_pad($start, 2, '0', STR_PAD_LEFT),
                'name' => $start,
                'install_tier_unique_code' => $install_tier_unique_code,
            ];
        }
        return $uniqueCodes;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return preg_match("/(位)+/", $this->attributes["name"]) ? ($this->attributes["name"] ?: "") : $this->attributes["name"] . "位";
    }

    /**
     * 根据编号获取真实名称和车间、车站
     * @return string
     */
    final public function getRealNameAndStationNameAttribute(): string
    {
        return self::__getRealNameAndStationName($this);
    }

    /**
     * 根据编号获取真实名称
     * @return string
     */
    final public function getRealNameAttribute(): string
    {
        return self::__getRealName($this, @$this->attributes['install_location_code'] ?: '');
    }

    /**
     * 获取最后一位unique_code
     * @param string $installTierUniqueCode
     * @return string
     */
    final public function getLastUniqueCode(string $installTierUniqueCode): string
    {
        $installPosition = DB::table('install_positions')->where('install_tier_unique_code', $installTierUniqueCode)->orderByDesc('id')->select('unique_code')->first();
        return empty($installPosition->unique_code) ? '00' : substr($installPosition->unique_code, -2);
    }

    /**
     * 层
     * @return BelongsTo
     */
    final public function WithInstallTier(): BelongsTo
    {
        return $this->belongsTo(InstallTier::class, 'install_tier_unique_code', 'unique_code');
    }

    /**
     * 设备
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'maintain_location_code', 'unique_code');
    }

    /**
     * 设备器材列表
     * @return HasMany
     */
    final public function EntireInstances(): HasMany
    {
        return $this->hasMany(EntireInstance::class, 'maintain_location_code', 'unique_code');
    }
}
