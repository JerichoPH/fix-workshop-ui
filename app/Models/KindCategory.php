<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class KindCategory
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
 * @property string                $race
 * @property-read KindEntireType[] $kind_entire_types
 * @property-read EntireInstance[] $entire_instances
 */
class KindCategory extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 相关类型
	 *
	 * @return BelongsTo
	 */
	public function KindEntireTypes(): BelongsTo
	{
		return $this->belongsTo(KindEntireType::class, "kind_category_uuid", "uuid");
	}
	
	/**
	 * 相关器材
	 *
	 * @return BelongsTo
	 */
	public function EntireInstances(): BelongsTo
	{
		return $this->belongsTo(EntireInstance::class, "kind_category_uuid", "uuid");
	}
}
