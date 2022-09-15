<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class LocationLine
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $unique_code
 * @property string $name
 * @property boolean $be_enable
 * @property-read LocationStation[] $location_stations
 * @property-read LocationCenter[] $location_centers
 * @property-read LocationSection[] $location_sections
 * @property-read LocationRailroadGradeCross[] $location_railroad_grade_crosses
 * @property-read EntireInstanceLog[] $entire_instance_logs
 */
class LocationLine extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 相关站场
     * @return BelongsToMany
     */
    public function LocationStations(): BelongsToMany
    {
        return $this->belongsToMany(LocationStation::class, "pivot_location_line_and_location_stations", "location_line_id", "location_station_id");
    }

    /**
     * 相关中心
     * @return BelongsToMany
     */
    public function LocationCenter(): BelongsToMany
    {
        return $this->belongsToMany(LocationCenter::class, "pivot_location_line_and_location_centers", "location_line_id", "location_center_id");
    }

    /**
     * 相关区间
     * @return BelongsToMany
     */
    public function LocationSection(): BelongsToMany
    {
        return $this->belongsToMany(LocationSection::class, "pivot_location_line_and_location_sections", "location_line_id", "location_section_id");
    }

    /**
     * 相关道口
     * @return BelongsToMany
     */
    public function LocationRailroadGradeCrosses(): BelongsToMany
    {
        return $this->belongsToMany(LocationRailroadGradeCross::class, "pivot_location_line_and_location_railroad_grade_crosses", "location_line_id", "location_railroad_grade_cross_id");
    }

    /**
     * 相关器材日志
     * @return BelongsTo
     */
    public function EntireInstanceLogs():BelongsTo
    {
        return $this->belongsTo(EntireInstanceLog::class,"location_line_uuid","uuid");
    }
}
