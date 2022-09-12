<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class OrganizationRailway
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $unique_code
 * @property string $name
 * @property string $short_name
 * @property boolean $be_enable
 * @property-read Account[] $accounts
 */
class OrganizationRailway extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 相关用户
     * @return BelongsTo
     */
    public function Accounts(): BelongsTo
    {
        return $this->belongsTo(Account::class, "uuid", "organization_railway_uuid");
    }
}
