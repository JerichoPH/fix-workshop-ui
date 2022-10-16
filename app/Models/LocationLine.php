<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class LocationLine
 *
 * @package App\Models
 * @property int                      $id
 * @property Carbon                   $created_at
 * @property Carbon                   $updated_at
 * @property Carbon|null              $deleted_at
 * @property string                   $uuid
 * @property int                      $sort
 * @property string                   $unique_code
 * @property string                   $name
 * @property boolean                  $be_enable
 * @property-read LocationStation     $location_station
 * @property-read LocationCenter      $location_center
 * @property-read LocationSection     $location_section
 * @property-read LocationRailroad    $location_railroads
 * @property-read EntireInstanceLog[] $entire_instance_logs
 */
class LocationLine extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function LocationStation(): BelongsTo
	{
		return $this->belongsTo(LocationStation::class, 'location_line_uuid', 'uuid');
	}
	
	public function LocationCenter(): BelongsTo
	{
		return $this->belongsTo(LocationCenter::class, 'location_line_uuid', 'uuid');
	}
	
	public function LocationSection(): BelongsTo
	{
		return $this->belongsTo(LocationCenter::class, 'location_line_uuid', 'uuid');
	}
	
	public function LocationRailroad(): BelongsTo
	{
		return $this->belongsTo(LocationRailroad::class, 'location_line_uuid', 'uuid');
	}
	
	/**
	 * 相关器材日志
	 *
	 * @return BelongsTo
	 */
	public function EntireInstanceLogs(): BelongsTo
	{
		return $this->belongsTo(EntireInstanceLog::class, "location_line_uuid", "uuid");
	}
}
