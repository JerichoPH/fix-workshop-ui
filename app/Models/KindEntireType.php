<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class KindEntireType
 *
 * @package App\Models
 * @property int                   $id
 * @property Carbon                $created_at
 * @property Carbon                $updated_at
 * @property Carbon|null           $deleted_at
 * @property string                $uuid
 * @property int                   $sort
 * @property string                $unique_code
 * @property string                $name
 * @property string                $nickname
 * @property boolean               $be_enable
 * @property string                $kind_category_uuid
 * @property-read KindCategory     $KindCategory
 * @property int                   $cycle_repair_year
 * @property int                   $life_year
 * @property-read KindSubType[]    $KindSubTypes
 * @property-read EntireInstance[] $EntireInstances
 */
class KindEntireType extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return $this->attributes['name'];
	}
	
	/**
	 * 所属种类
	 *
	 * @return HasOne
	 */
	public function KindCategory(): HasOne
	{
		return $this->hasOne(KindCategory::class, "uuid", "kind_category_uuid");
	}
	
	/**
	 * 相关型号
	 *
	 * @return BelongsTo
	 */
	public function KindSubTypes(): BelongsTo
	{
		return $this->belongsTo(KindSubType::class, "kind_entire_type_uuid", "uuid");
	}
	
	/**
	 * 相关器材
	 *
	 * @return BelongsTo
	 */
	public function EntireInstances(): BelongsTo
	{
		return $this->belongsTo(EntireInstance::class, "kind_entire_type_uuid", "uuid");
	}
}
