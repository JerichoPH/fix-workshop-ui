<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Factory
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
 * @property string                $shot_name
 * @property-read EntireInstance[] $EntireInstances
 */
class Factory extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return $this->attributes['name'];
	}
	
	/**
	 * 相关器材
	 *
	 * @return BelongsTo
	 */
	public function EntireInstances(): BelongsTo
	{
		return $this->belongsTo(EntireInstance::class, "factory_uuid", "uuid");
	}
}
