<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class OrganizationRailway
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
 * @property string                   $short_name
 * @property boolean                  $be_enable
 * @property-read Account[]           $Accounts
 * @property-read EntireInstance[]    $EntireInstances
 * @property-read EntireInstanceLog[] $EntireInstanceLogs
 */
class OrganizationRailway extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return $this->attributes['name'];
	}
	
	/**
	 * 相关用户
	 *
	 * @return BelongsTo
	 */
	public function Accounts(): BelongsTo
	{
		return $this->belongsTo(Account::class, "uuid", "organization_railway_uuid");
	}
	
	/**
	 * 相关器材（资产）
	 *
	 * @return BelongsTo
	 */
	public function EntireInstances(): BelongsTo
	{
		return $this->belongsTo(EntireInstance::class, "belong_to_organization_railway_uuid", "uuid");
	}
	
	/**
	 * 相关器材日志
	 *
	 * @return BelongsTo
	 */
	public function EntireInstanceLogs(): BelongsTo
	{
		return $this->belongsTo(EntireInstanceLog::class, "organization_railway_uuid", "uuid");
	}
}
