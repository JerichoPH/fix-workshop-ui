<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PositionDepotCell
 * @package App\Models
 * @property string $id
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property string $uuid
 * @property string $sort
 * @property string $unique_code
 * @property string $name
 * @property string $position_depot_tier_uuid
 * @property-read PositionDepotTier $position_depot_tier
 */
class PositionDepotCell extends Model
{
    /**
     * 所属仓库层
     * @return HasOne
     */
    public function PositionDepotTier(): HasOne
    {
        return $this->hasOne(PositionDepotTier::class, "", "");
    }
}
