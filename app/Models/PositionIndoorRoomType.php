<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PositionIndoorRoomType
 *
 * @package App\Models
 * @property string                    $id
 * @property string                    $created_at
 * @property string                    $updated_at
 * @property string                    $deleted_at
 * @property string                    $uuid
 * @property string                    $sort
 * @property string                    $unique_code
 * @property string                    $name
 * @property-read PositionIndoorRoom[] $position_indoor_rooms
 */
class PositionIndoorRoomType extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 相关室内上道位置机房
	 *
	 * @return BelongsTo
	 */
	public function PositionIndoorRooms(): BelongsTo
	{
		return $this->belongsTo(PositionIndoorRoom::class, "position_indoor_room_type_uuid", "uuid");
	}
}
