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
 * App\Model\Install\InstallPlatoon
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 排编码
 * @property string $install_room_unique_code 机房关联编码
 * @property-read InstallRoom $WithInstallRoom
 * @property-read Collection|InstallShelf[] $WithInstallShelves
 * @property-read int|null $with_install_shelves_count
 */
class InstallPlatoon extends Model
{
    protected $fillable = [
        "created_at",
        "updated_at",
        "name",
        "unique_code",
        "install_room_unique_code",
    ];
    /**
     * 获取真实名称
     * @param string $unique_code
     * @return string
     */
    public static function getRealName(string $unique_code): string
    {
        $self = self::with([
            'WithInstallRoom',  // 室
        ])
            ->whereHas("WithInstallRoom")
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
            strval(@$self->WithInstallRoom ?: ""),
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
     * @param string $install_room_unique_code
     * @return string
     */
    final public static function generateUniqueCode(string $install_room_unique_code):string
    {
        $installPlatoon = DB::table('install_platoons')->where('install_room_unique_code', $install_room_unique_code)->orderByDesc('id')->first();
        $prefix = $install_room_unique_code;
        if (empty($installPlatoon)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $installPlatoon->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    /**
     * @return string
     */
    public function __toString():string
    {
        return preg_match("/(排)+/", $this->attributes["name"]) ? $this->attributes["name"] : $this->attributes["name"] . "排";
    }

    /**
     * @param string $installRoomUniqueCode
     * @return string
     */
    final public function getUniqueCode(string $installRoomUniqueCode): string
    {
        return self::generateUniqueCode($installRoomUniqueCode);
    }

    final public function WithInstallShelves(): HasMany
    {
        return $this->hasMany(InstallShelf::class, 'install_platoon_unique_code', 'unique_code');
    }

    final public function WithInstallRoom(): BelongsTo
    {
        return $this->belongsTo(InstallRoom::class, 'install_room_unique_code', 'unique_code');
    }

}
