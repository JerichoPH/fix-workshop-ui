<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class OrganizationWorkAreaProfession
 *
 * @package App\Models
 * @property int                         $id
 * @property Carbon                      $created_at
 * @property Carbon                      $updated_at
 * @property Carbon|null                 $deleted_at
 * @property string                      $uuid
 * @property int                         $sort
 * @property string                      $unique_code
 * @property string                      $name
 * @property-read OrganizationWorkArea[] $OrganizationWorkAreas
 */
class OrganizationWorkAreaProfession extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return $this->attributes['name'];
	}
	
	/**
	 * 相关工区
	 *
	 * @return BelongsTo
	 */
	public function OrganizationWorkAreas(): BelongsTo
	{
		return $this->belongsTo(OrganizationWorkArea::class, "organization_work_area_profession_uuid", "uuid");
	}
}
