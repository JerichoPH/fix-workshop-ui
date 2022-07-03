<?php

namespace App\Model;


use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * 程序更新记录
 * Class AppUpgrade
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $unique_code
 * @property string $version
 * @property string $target
 * @property string $description
 * @property string $operating_steps
 * @property string $upgrade_reports
 * @property-read File $Accessories
 */
class AppUpgrade extends Base
{
    protected $guarded = [];

    final public function Accessories(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            "pivot_app_upgrade_and_accessories",
            "app_upgrade_id",
            "file_id"
        );
    }
}
