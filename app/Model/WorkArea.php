<?php

namespace App\Model;

use App\Facades\TextFacade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use stdClass;

/**
 * App\Model\WorkArea
 *
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $workshop_unique_code
 * @property string $name
 * @property string $unique_code
 * @property string $type
 * @property string $paragraph_unique_code 所属电务段代码
 * @property-read Collection|Account[] $Accounts
 * @property-read int|null $accounts_count
 * @property-read Maintain $Workshop
 * @property-read stdClass $type_obj 类型对象
 */
class WorkArea extends Base
{

    public static $WORK_AREA_TYPES = [
        "转辙机工区" => "pointSwitch",
        "继电器工区" => "relay",
        "综合工区" => "synthesize",
        "电源屏工区" => "powerSupplyPanel",
        "现场工区" => "scene",
    ];

    protected $guarded = [];

    /**
     * 生成唯一编号
     * @return string
     */
    final public static function generateUniqueCode(): string
    {
        $last_work_area = self::with([])->orderByDesc('id')->first();
        $last_unique_code = $last_work_area ? TextFacade::from36(Str::substr($last_work_area->unique_code, -2)) : 0;
        return env('ORGANIZATION_CODE') . 'D' . str_pad(TextFacade::to36($last_unique_code + 1), 2, '0', 0);
    }

    protected static function boot()
    {
        parent::boot();

        /**
         * 只查询受显示的
         */
        static::addGlobalScope("is_show", function (Builder $builder) {
            $builder->where("is_show", true);
        });
    }

    final public function getTypeObjAttribute(): stdClass
    {
        return (object)[
            "value" => $this->attributes["type"],
            "text" => @array_flip(self::$WORK_AREA_TYPES)[$this->attributes["type"]] ?? "",
        ];
    }

    /**
     * 所属车间
     * @return HasOne
     */
    final public function Workshop(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'workshop_unique_code');
    }

    /**
     * 所属用户
     * @return HasMany
     */
    final public function Accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'work_area_unique_code', 'unique_code');
    }
}
