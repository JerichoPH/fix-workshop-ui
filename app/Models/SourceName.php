<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class SourceName
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
 * @property string                $source_type_uuid
 * @property-read SourceType       $source_type
 * @property-read EntireInstance[] $entire_instances
 */
class SourceName extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 所属来源类型
	 *
	 * @return HasOne
	 */
	public function SourceType(): HasOne
	{
		return $this->hasOne(SourceType::class, "uuid", "source_type_uuid");
	}
	
	/**
	 * 相关器材
	 *
	 * @return BelongsTo
	 */
	public function EntireInstances(): BelongsTo
	{
		return $this->belongsTo(EntireInstance::class, "source_name_uuid", "uuid");
	}
}
