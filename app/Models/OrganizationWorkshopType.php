<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class OrganizationWorkshopType
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
 * @property string                      $number_code
 * @property-read OrganizationWorkshop[] $OrganizationWorkshops
 */
class OrganizationWorkshopType extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return $this->attributes['name'];
	}
	
	/**
	 * 相关车间
	 *
	 * @return BelongsTo
	 */
	public function OrganizationWorkshops(): BelongsTo
	{
		return $this->belongsTo(OrganizationWorkshop::class, "organization_workshop_type_uuid", "uuid");
	}
}
