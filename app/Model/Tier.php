<?php

namespace App\Model;

use App\Facades\TextFacade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\Tier
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name 层名称
 * @property string $unique_code 层代码
 * @property string $shelf_unique_code 所属架
 * @property-read Collection|Position[] $WithPositions
 * @property-read int|null $with_positions_count
 * @property-read Shelf $WithShelf
 * @property-read Collection|Position[] $subset
 * @property-read int|null $subset_count
 * @property-read Shelf $Shelf
 * @property-read string $real_name
 */
class Tier extends Model
{
    protected $fillable = [
        'name',
        'unique_code',
        'shelf_unique_code',
    ];

    /**
     * @param string $shelf_unique_code
     * @param int $count
     * @return array
     */
    public static function generateUniqueCodes(string $shelf_unique_code, int $count): array
    {
        $tier = DB::table('tiers')->where('shelf_unique_code', $shelf_unique_code)->orderByDesc('id')->select('unique_code')->first();
        if (empty($tier)) {
            $start = '00';
        } else {
            $start = substr($tier->unique_code, -2);
        }
        $uniqueCodes = [];
        for ($i = 1; $i <= $count; $i++) {
            $start += 1;
            $uniqueCodes[] = [
                'unique_code' => $shelf_unique_code . str_pad($start, 2, '0', STR_PAD_LEFT),
                'name' => $start
            ];
        }
        return $uniqueCodes;
    }

    /**
     * 获取真实名称
     * @param string $unique_code
     * @return string
     */
    public static function getRealName(string $unique_code): string
    {
        $self = (new self())->with([
            "WithShelf",
            "WithShelf.WithPlatoon",
            "WithShelf.WithPlatoon.WithArea",
            "WithShelf.WithPlatoon.WithArea.WithStorehouse",
        ])
            ->where("unique_code", $unique_code)
            ->first();
        return self::__getRealName($self);
    }

    /**
     * 获取真实名称
     * @param Tier $self
     * @return string
     */
    private static function __getRealName(?self $self): string
    {
        return $self ? TextFacade::joinWithNotEmpty(" ", [
            strval(@$self->WithShelf->WithPlatoon->WithArea->WithStorehouse ?: ""),
            strval(@$self->WithShelf->WithPlatoon->WithArea ?: ""),
            strval(@$self->WithShelf->WithPlatoon ?: ""),
            strval(@$self->WithShelf ?: ""),
            strval(@$self ?: ""),
        ]) : "";
    }

    /**
     * 获取真实名称
     * @param $value
     * @return string
     */
    final public function getRealNameAttribute($value): string
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

    final public function WithShelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class, 'shelf_unique_code', 'unique_code');
    }

    final public function Shelf(): HasOne
    {
        return $this->hasOne(Shelf::class, 'unique_code', 'shelf_unique_code');
    }

    final public function WithPositions(): HasMany
    {
        return $this->hasMany(Position::class, 'tier_unique_code', 'unique_code');
    }

    final public function subset(): HasMany
    {
        return $this->hasMany(Position::class, 'tier_unique_code', 'unique_code');
    }

}
