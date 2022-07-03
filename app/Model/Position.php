<?php


namespace App\Model;

use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\Position
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code
 * @property string $tier_unique_code 层关联编码
 * @property string|null $img_url 图片路径
 * @property-read Collection|EntireInstance[] $WithEntireInstances
 * @property-read int|null $with_entire_instances_count
 * @property-read Collection|PartInstance[] $WithPartInstances
 * @property-read int|null $with_part_instances_count
 * @property-read Tier $WithTier
 * @property-read Tier $tier
 * @property-read string $real_name
 */
class Position extends Model
{
    protected $fillable = [
        'name',
        'unique_code',
        'tier_unique_code',
    ];

    /**
     * @param string $tierUniqueCode
     * @param int $count
     * @return array
     */
    public static function generateUniqueCodes(string $tierUniqueCode, int $count): array
    {
        $position = DB::table('positions')->where('tier_unique_code', $tierUniqueCode)
            ->orderByDesc('id')
            ->select('unique_code')
            ->first();
        if (empty($position)) {
            $start = '00';
        } else {
            $start = substr($position->unique_code, -2);
        }
        $uniqueCodes = [];
        for ($i = 1; $i <= $count; $i++) {
            $start += 1;
            $uniqueCodes[] = [
                'unique_code' => $tierUniqueCode . str_pad($start, 2, '0', STR_PAD_LEFT),
                'name' => $start
            ];
        }
        return $uniqueCodes;
    }

    /**
     * 通过编号获取真实名称
     * @param string $unique_code
     * @return string
     */
    final public static function getRealName(string $unique_code): string
    {
        $position = self::with([
            'WithTier',  // 层
            'WithTier.WithShelf',  // 架
            'WithTier.WithShelf.WithPlatoon',  // 排
            'WithTier.WithShelf.WithPlatoon.WithArea',  // 区
            'WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse',  // 仓
        ])
            ->whereHas("WithTier")
            ->whereHas("WithTier.WithShelf")
            ->whereHas("WithTier.WithShelf.WithPlatoon")
            ->whereHas("WithTier.WithShelf.WithPlatoon.WithArea")
            ->whereHas("WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse")
            ->where('unique_code', $unique_code)
            ->first();
        if (!$position) return "";

        return self::__getRealName($position);
    }

    /**
     * 获取真实名称
     * @param Position|null $position
     * @return string
     */
    final private static function __getRealName(Position $position): string
    {
        $storehouse_name = strval(@$position->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse ?: "");
        $area_name = strval(@$position->WithTier->WithShelf->WithPlatoon->WithArea ?: "");
        $platoon_name = strval(@$position->WithTier->WithShelf->WithPlatoon ?: "");
        $shelf_name = strval(@$position->WithTier->WithShelf ?: "");
        $tier_name = strval(@$position->WithTier ?: "");
        $position_name = strval(@$position ?: "");

        return collect([
            $storehouse_name,
            $area_name,
            $platoon_name,
            $shelf_name,
            $tier_name,
            $position_name,
        ])
            ->implode(" ");
    }

    public function __toString(): string
    {
        return preg_match("/(位)+/", $this->attributes["name"]) ? $this->attributes["name"] : $this->attributes["name"] . "位";
    }

    /**
     * 根据编号获取真实名称
     * @return string
     */
    final public function getRealNameAttribute(): string
    {
        return self::__getRealName($this);
    }

    final public function WithTier(): BelongsTo
    {
        return $this->belongsTo(Tier::class, 'tier_unique_code', 'unique_code');
    }

    final public function Tier(): HasOne
    {
        return $this->hasOne(Tier::class, 'unique_code', 'tier_unique_code');
    }

    final public function WithEntireInstances(): HasMany
    {
        return $this->hasMany(EntireInstance::class, 'location_unique_code', 'unique_code');
    }

    final public function WithPartInstances(): HasMany
    {
        return $this->hasMany(PartInstance::class, 'location_unique_code', 'unique_code');
    }
}
