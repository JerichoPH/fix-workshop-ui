<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class SourceType
 *
 * @package App\Models
 * @property int          $id
 * @property Carbon       $created_at
 * @property Carbon       $updated_at
 * @property Carbon|null  $deleted_at
 * @property string       $uuid
 * @property int          $sort
 * @property string       $unique_code
 * @property string       $name
 * @property Sourcename[] $SourceNames
 */
class SourceType extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 相关来源名称
	 *
	 * @return BelongsTo
	 */
	public function SourceNames(): BelongsTo
	{
		return $this->belongsTo(SourceName::class, "source_type_uuid", "uuid");
	}
}
