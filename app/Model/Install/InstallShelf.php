<?php

namespace App\Model\Install;

use App\Facades\TextFacade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\Install\InstallShelf
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 架编码
 * @property string $install_platoon_unique_code 排关联编码
 * @property-read InstallPlatoon $WithInstallPlatoon
 * @property-read Collection|InstallTier[] $WithInstallTiers
 * @property-read int|null $with_install_tiers_count
 * @property-read string $real_name 真实名称
 */
class InstallShelf extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'name',
        'unique_code',
        'install_platoon_unique_code',
    ];

    /**
     * 获取真实名称
     * @param string $unique_code
     * @return string
     */
    public static function getRealName(string $unique_code): string
    {
        $self = self::with([
            'WithInstallPlatoon',  // 排
            'WithInstallPlatoon.WithInstallRoom',  // 室
        ])
            ->whereHas("WithInstallPlatoon")
            ->whereHas("WithInstallPlatoon.WithInstallRoom")
            ->where('unique_code', $unique_code)
            ->first();

        return self::__getRealName($self);
    }

    /**
     * 获取真实名称
     * @param InstallShelf|null $self
     * @return string
     */
    private static function __getRealName(?self $self): string
    {
        return $self ? TextFacade::joinWithNotEmpty(" ", [
            strval(@$self->WithInstallPlatoon->WithInstallRoom ?: ""),
            strval(@$self->WithInstallPlatoon ?: ""),
            strval(@$self ?: ""),
        ]) : "";
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
        return preg_match("/(架)+/", $this->attributes["name"]) ? $this->attributes["name"] : $this->attributes["name"] . "架";
    }

    /**
     * @param string $installPlatoonUniqueCode
     * @return string
     */
    final public function getUniqueCode(string $installPlatoonUniqueCode): string
    {
        return self::generateUniqueCode($installPlatoonUniqueCode);
    }

    /**
     * @param string $install_platoon_unique_code
     * @return string
     */
    final public static function generateUniqueCode(string $install_platoon_unique_code): string
    {
        $installShelf = DB::table('install_shelves')->where('install_platoon_unique_code', $install_platoon_unique_code)->orderByDesc('id')->first();
        $prefix = $install_platoon_unique_code;
        if (empty($installShelf)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $installShelf->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    final public function WithInstallTiers(): HasMany
    {
        return $this->hasMany(InstallTier::class, 'install_shelf_unique_code', 'unique_code');
    }

    final public function install_tiers(): HasMany
    {
        return $this->hasMany(InstallTier::class, 'install_shelf_unique_code', 'unique_code');
    }

    final public function WithInstallPlatoon(): BelongsTo
    {
        return $this->belongsTo(InstallPlatoon::class, 'install_platoon_unique_code', 'unique_code');
    }
}
