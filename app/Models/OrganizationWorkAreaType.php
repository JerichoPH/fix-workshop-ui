<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrganizationWorkAreaType
 *
 * @package App\Models
 * @property string                      $id
 * @property string                      $created_at
 * @property string                      $updated_at
 * @property string                      $deleted_at
 * @property string                      $uuid
 * @property string                      $sort
 * @property string                      $unique_code
 * @property string                      $name
 * @property-read OrganizationWorkArea[] $organization_work_areas
 */
class OrganizationWorkAreaType extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 相关工区
	 *
	 * @return BelongsTo
	 */
	public function OrganizationWorkAreas(): BelongsTo
	{
		return $this->belongsTo(OrganizationWorkArea::class, "organization_work_area_type_uuid", "uuid");
	}
}
