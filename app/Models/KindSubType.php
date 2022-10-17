<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class KindSubType
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
 * @property string              $nickname
 * @property boolean             $be_enable
 * @property string              $kind_entire_type_uuid
 * @property-read KindEntireType $kind_entire_type
 * @property int                 $cycle_repair_year
 * @property int                 $life_year
 */
class KindSubType extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 所属类型
	 *
	 * @return HasOne
	 */
	public function KindEntireType(): HasOne
	{
		return $this->hasOne(KindEntireType::class, "uuid", "kind_entire_type_uuid");
	}
}
