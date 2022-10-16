<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotLocationLineAndLocationSection
 *
 * @package App\Models
 * @property string               $location_line_uuid
 * @property-read LocationLine    $location_line
 * @property string               $location_section_uuid
 * @property-read LocationSection $location_section
 */
class PivotLocationLineAndLocationSection extends Model
{
	protected $guarded = [];
	
	/**
	 * 所属线别
	 *
	 * @return HasOne
	 */
	public function LocationLine(): HasOne
	{
		return $this->hasOne(LocationLine::class, 'uuid', 'location_line_uuid');
	}
	
	/**
	 * 所属区间
	 *
	 * @return HasOne
	 */
	public function LocationSection(): HasOne
	{
		return $this->hasOne(LocationSection::class, 'uuid', 'location_section_uuid');
	}
}
