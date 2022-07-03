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
 * App\Model\Install\InstallTier
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 层编码
 * @property string $install_shelf_unique_code 架关联编码
 * @property-read Collection|InstallPosition[] $WithInstallPositions
 * @property-read int|null $with_install_positions_count
 * @property-read InstallShelf $WithInstallShelf
 * @property-read string $real_name 真实名称
 */
class InstallTier extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'name',
        'unique_code',
        'install_shelf_unique_code',
    ];

    /**
     * 获取真实名称
     * @param string $unique_code
     * @return string
     */
    public static function getRealName(string $unique_code): string
    {
        $self = self::with([
            'WithInstallShelf',  // 柜
            'WithInstallShelf.WithInstallPlatoon',  // 排
            'WithInstallShelf.WithInstallPlatoon.WithInstallRoom',  // 室
        ])
            ->whereHas("WithInstallShelf")
            ->whereHas("WithInstallShelf.WithInstallPlatoon")
            ->whereHas("WithInstallShelf.WithInstallPlatoon.WithInstallRoom")
            ->where('unique_code', $unique_code)
            ->first();

        return self::__getRealName($self);
    }

    /**
     * 获取真实名称
     * @param InstallTier|null $self
     * @return string
     */
    private static function __getRealName(?self $self): string
    {
        return $self ? TextFacade::joinWithNotEmpty(" ", [
            strval(@$self->WithInstallShelf->WithInstallPlatoon->WithInstallRoom ?: ""),
            strval(@$self->WithInstallShelf->WithInstallPlatoon ?: ""),
            strval(@$self->WithInstallShelf ?: ""),
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
        return preg_match("/(层)+/", $this->attributes["name"]) ? $this->attributes["name"] : $this->attributes["name"] . "层";
    }

    /**
     * @param string $installShelfUniqueCode
     * @return string
     */
    final public function getUniqueCode(string $installShelfUniqueCode): string
    {
        return self::generateUniqueCode($installShelfUniqueCode);
    }

    final public static function generateUniqueCode(string $install_shelf_unique_code): string
    {
        $installTier = DB::table('install_tiers')->where('install_shelf_unique_code', $install_shelf_unique_code)->orderByDesc('id')->first();
        $prefix = $install_shelf_unique_code;
        if (empty($installTier)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $installTier->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    final public function WithInstallPositions(): HasMany
    {
        return $this->hasMany(InstallPosition::class, 'install_tier_unique_code', 'unique_code');
    }

    public function install_positions(): HasMany
    {
        return $this->hasMany(InstallPosition::class, 'install_tier_unique_code', 'unique_code');
    }

    final public function WithInstallShelf(): BelongsTo
    {
        return $this->belongsTo(InstallShelf::class, 'install_shelf_unique_code', 'unique_code');
    }

}
