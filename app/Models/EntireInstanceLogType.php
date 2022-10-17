<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class EntireInstanceLogType
 *
 * @package App\Models
 * @property int         $id
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property Carbon|null $deleted_at
 * @property int         $uuid
 * @property string      $sort
 * @property string      $unique_code
 * @property string      $name
 * @property string      $unique_code_for_paragraph
 * @property string      $number_code
 * @property string      $icon
 */
class EntireInstanceLogType extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString():string
	{
		return $this->attributes['name'];
	}
	
	/**
	 * 相关器材日志
	 *
	 * @return BelongsTo
	 */
	public function EntireInstanceLogs(): BelongsTo
	{
		return $this->belongsTo(EntireInstanceLog::class, "entire_instance_log_type_unique_code", "unique_code");
	}
}
