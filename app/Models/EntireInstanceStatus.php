<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class EntireInstanceStatus
 *
 * @package App\Models
 * @property int                 $id
 * @property Carbon              $created_at
 * @property Carbon              $updated_at
 * @property Carbon|null         $deleted_at
 * @property string              $uuid
 * @property int                 $sort
 * @property string              $unique_code
 * @property string              $name
 * @property string              $number_code
 * @property-read EntireInstance $entire_instance
 */
class EntireInstanceStatus extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 相关器材
	 *
	 * @return BelongsTo
	 */
	public function EntireInstances(): BelongsTo
	{
		return $this->belongsTo(EntireInstance::class, "entire_instance_status_unique_code", "unique_code");
	}
}
