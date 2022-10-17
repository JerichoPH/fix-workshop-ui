<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PositionDepotRowType
 *
 * @package App\Models
 * @property int                     $id
 * @property Carbon                  $created_at
 * @property Carbon                  $updated_at
 * @property Carbon|null             $deleted_at
 * @property string                  $uuid
 * @property int                     $sort
 * @property string                  $unique_code
 * @property string                  $name
 * @property-read PositionDepotRow[] $PositionDepotRows
 */
class PositionDepotRowType extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return $this->attributes['name'];
	}
	
	/**
	 *
	 * @return BelongsTo
	 */
	public function PositionDepotRows(): BelongsTo
	{
		return $this->belongsTo(PositionDepotRow::class, "position_depot_row_type_uuid", "uuid");
	}
}
