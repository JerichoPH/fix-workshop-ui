<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PivotAppUpgradeAndAccessory extends Model
{
    protected $guarded = [];

    final public function File(): HasOne
    {
        return $this->hasOne(File::class, "id", "file_id");
    }

    final public function AppUpgrade(): HasOne
    {
        return $this->hasOne(AppUpgrade::class, "id", "app_upgrade_id");
    }
}
