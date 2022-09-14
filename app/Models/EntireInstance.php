<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class EntireInstance
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $identity_code
 * @property string $serial_number
 * @property string $entire_instance_status_unique_code
 * @property-read EntireInstanceStatus $entire_instance_status
 * @property string $kind_category_uuid
 * @property-read KindCategory $kind_category
 * @property string $kind_entire_type_uuid
 * @property-read KindEntireType $kind_entire_type
 * @property string $kind_sub_type_uuid
 * @property-read KindSubType $kind_sub_type
 * @property string $factory_uuid
 * @property-read Factory $factory
 * @property string $factory_made_serial_number
 * @property Carbon $factory_made_at
 * @property string $asset_code
 * @property string $fixed_asset_code
 * @property string $parent_identity_code
 * @property-read EntireInstance $parent
 * @property-read EntireInstance[] $subs
 * @property boolean $be_part
 * @property string $note
 * @property string $source_name_uuid
 * @property-read SourceName $source_name
 * @property string $delete_operator_uuid
 * @property-read Account $delete_operator
 * @property string $wiring_system
 * @property boolean $has_extrusion_shroud
 * @property string $said_rod
 * @property Carbon $use_expire_at
 * @property Carbon $use_destroy_at
 * @property Carbon $use_next_cycle_repair_at
 * @property Carbon $use_warehouse_in_at
 * @property string $use_warehouse_position_depot_cell_uuid
 * @property-read PositionDepotCell $use_warehouse_position_depot_cell
 * @property Carbon $use_repair_current_fixed_at
 * @property string $use_repair_current_fixer_name
 * @property Carbon $use_repair_current_checked_at
 * @property string $use_repair_current_checker_name
 * @property Carbon $use_repair_current_spot_checked_at
 * @property string $use_repair_current_spot_checker_name
 * @property Carbon $use_repair_last_fixed_at
 * @property string $use_repair_last_fixer_name
 * @property Carbon $use_repair_last_checked_at
 * @property string $use_repair_last_checker_name
 * @property Carbon $use_repair_last_spot_checked_at
 * @property string $use_repair_last_spot_checker_name
 * @property string $belong_to_organization_railway_uuid
 * @property-read OrganizationRailway $belong_to_organization_railway
 * @property string $belong_to_organization_paragraph_uuid
 * @property-read OrganizationParagraph $belong_to_organization_paragraph
 * @property string $belong_to_organization_workshop_uuid
 * @property-read OrganizationWorkshop $belong_to_organization_workshop
 * @property string $belong_to_organization_work_area_uuid
 * @property-read OrganizationWorkArea $belong_to_organization_work_area
 * @property string $use_place_current_organization_workshop_uuid
 * @property OrganizationWorkshop $use_place_current_organization_workshop
 * @property string $use_place_current_organization_work_area_uuid
 * @property-read OrganizationWorkArea $use_place_current_organization_work_area
 * @property string $use_place_current_location_line_uuid
 * @property-read LocationLine $use_place_current_location_line
 * @property string $use_place_current_location_station_uuid
 * @property-read LocationStation $use_place_current_location_station
 * @property string $use_place_current_location_section_uuid
 * @property-read LocationSection $use_place_current_location_section
 * @property string $use_place_current_location_center_uuid
 * @property-read LocationCenter $use_place_current_location_center
 * @property string $use_place_current_location_railroad_grade_cross_uuid
 * @property-read LocationRailroadGradeCross $use_place_current_location_railroad_grade_cross
 *
 * @property string $use_place_last_organization_workshop_uuid
 * @property-read OrganizationWorkshop $use_place_last_organization_workshop
 * @property string $use_place_last_organization_work_area_uuid
 * @property-read OrganizationWorkArea $use_place_last_organization_work_area
 * @property string $use_place_last_location_line_uuid
 * @property-read LocationLine $use_place_last_location_line
 * @property string $use_place_last_location_station_uuid
 * @property-read LocationLine $use_place_last_location_station
 * @property string $use_place_last_location_section_uuid
 * @property-read LocationSection $use_place_last_location_section
 * @property string $use_place_last_location_center_uuid
 * @property-read LocationCenter $use_place_last_location_center
 * @property string $use_place_last_location_railroad_grade_cross_uuid
 * @property-read LocationRailroadGradeCross $use_place_last_location_railroad_grade_cross
 * @property string $use_place_current_position_indoor_cell_uuid
 * @property-read PositionIndoorCell $use_place_current_position_indoor_cell
 * @property string $use_place_last_position_indoor_cell_uuid
 * @property-read PositionIndoorCell $use_place_last_position_indoor_cell
 * @property int $ex_cycle_repair_year
 * @property int $ex_life_year
 */
class EntireInstance extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属器材状态
     * @return HasOne
     */
    public function EntireInstanceStatus(): HasOne
    {
        return $this->hasOne(EntireInstanceStatus::class, "unique_code", "entire_instance_status_unique_code");
    }

    /**
     * 所属种类
     * @return HasOne
     */
    public function KindCategory(): HasOne
    {
        return $this->hasOne(KindCategory::class, "uuid", "kind_category_uuid");
    }

    /**
     * 所属类型
     * @return HasOne
     */
    public function KindEntireType(): HasOne
    {
        return $this->hasOne(KindEntireType::class, "uuid", "kind_entire_type_uuid");
    }

    /**
     * 所属型号
     * @return HasOne
     */
    public function KindSubType(): HasOne
    {
        return $this->hasOne(KindEntireType::class, "uuid", "kind_sub_type_uuid");
    }

    /**
     * 所属厂家
     * @return HasOne
     */
    public function Factory(): HasOne
    {
        return $this->hasOne(Factory::class, "uuid", "factory_uuid");
    }

    /**
     * 所属整件
     * @return HasOne
     */
    public function Parent(): HasOne
    {
        return $this->hasOne(self::class, "identity_code", "parent_identity_code");
    }

    /**
     * 相关部件
     * @return BelongsTo
     */
    public function Subs(): BelongsTo
    {
        return $this->belongsTo(self::class, "parent_identity_code", "identity_code");
    }

    /**
     * 所属来源名称
     * @return HasOne
     */
    public function SourceName(): HasOne
    {
        return $this->hasOne(SourceName::class, "uuid", "source_name_uuid");
    }

    /**
     * 所属删除操作人
     * @return HasOne
     */
    public function DeleteOperator(): HasOne
    {
        return $this->hasOne(Account::class, "uuid", "delete_operator_uuid");
    }

    /**
     * 所属仓库位置
     * @return HasOne
     */
    public function UseWarehousePositionDepotCell(): HasOne
    {
        return $this->hasOne(PositionDepotCell::class, "uuid", "use_warehouse_position_depot_cell_uuid");
    }

    /**
     * 所属路局（资产）
     * @return HasOne
     */
    public function BelongToOrganizationRailway(): HasOne
    {
        return $this->hasOne(OrganizationRailway::class, "uuid", "belong_to_organization_railway_uuid");
    }

    /**
     * 所属站段（资产）
     * @return HasOne
     */
    public function BelongToOrganizationParagraph(): HasOne
    {
        return $this->hasOne(OrganizationParagraph::class, "uuid", "belong_to_organization_paragraph_uuid");
    }

    /**
     * 所属车间（资产）
     * @return HasOne
     */
    public function BelongToOrganizationWorkshop(): HasOne
    {
        return $this->hasOne(OrganizationWorkshop::class, "uuid", "belong_to_organization_workshop_uuid");
    }

    /**
     * 所属工区（资产）
     * @return HasOne
     */
    public function BelongToOrganizationWorkArea(): HasOne
    {
        return $this->hasOne(OrganizationWorkArea::class, "uuid", "belong_to_organization_work_area_uuid");
    }

    /**
     * 所属车间（当前使用地点）
     * @return HasOne
     */
    public function UsePlaceCurrentOrganizationWorkshop(): HasOne
    {
        return $this->hasOne(OrganizationWorkshop::class, "uuid", "use_place_current_organization_workshop_uuid");
    }

    /**
     * 所属工区（当前使用地点）
     * @return HasOne
     */
    public function UsePlaceCurrentOrganizationWorkArea(): HasOne
    {
        return $this->hasOne(OrganizationWorkArea::class, "uuid", "use_place_current_organization_work_area_uuid");
    }

    /**
     * 所属线别（当前使用地点）
     * @return HasOne
     */
    public function UsePlaceCurrentLocationLine(): HasOne
    {
        return $this->hasOne(LocationLine::class, "uuid", "use_place_current_location_line_uuid");
    }

    /**
     * 所属站场（当前使用地点）
     * @return HasOne
     */
    public function UsePlaceCurrentLocationStation(): HasOne
    {
        return $this->hasOne(LocationStation::class, "uuid", "use_place_current_location_station_uuid");
    }

    /**
     * 所属区间（当前使用地点）
     * @return HasOne
     */
    public function UsePlaceCurrentLocationSection(): HasOne
    {
        return $this->hasOne(LocationSection::class, "uuid", "use_place_current_location_section_uuid");
    }

    /**
     * 所属中心（当前使用地点）
     * @return HasOne
     */
    public function UsePlaceCurrentLocationCenter(): HasOne
    {
        return $this->hasOne(LocationCenter::class, "uuid", "use_place_current_location_center_uuid");
    }

    /**
     * 所属道口（当前使用地点）
     * @return HasOne
     */
    public function UsePlaceCurrentLocationRailroadGradeCross(): HasOne
    {
        return $this->hasOne(LocationRailroadGradeCross::class, "uuid", "use_place_current_location_railroad_grade_cross_uuid");
    }

    /**
     * 所属车间（前次使用地点）
     * @return HasOne
     */
    public function UsePlaceLastOrganizationWorkshop(): HasOne
    {
        return $this->hasOne(OrganizationWorkshop::class, "uuid", "use_place_last_organization_workshop_uuid");
    }

    /**
     * 所属工区（前次使用地点）
     * @return HasOne
     */
    public function UsePlaceLastOrganizationWorkArea(): HasOne
    {
        return $this->hasOne(OrganizationWorkArea::class, "uuid", "use_place_last_organization_work_area_uuid");
    }

    /**
     * 所属线别（前次使用地点）
     * @return HasOne
     */
    public function UsePlaceLastLocationLine(): HasOne
    {
        return $this->hasOne(LocationLine::class, "uuid", "use_place_last_location_line_uuid");
    }

    /**
     * 所属站场（前次使用地点）
     * @return HasOne
     */
    public function UsePlaceLastLocationStation(): HasOne
    {
        return $this->hasOne(LocationStation::class, "uuid", "use_place_last_location_station_uuid");
    }

    /**
     * 所属区间（前次使用地点）
     * @return HasOne
     */
    public function UsePlaceLastLocationSection(): HasOne
    {
        return $this->hasOne(LocationSection::class, "uuid", "use_place_last_location_section_uuid");
    }

    /**
     * 所属中心（前次使用地点）
     * @return HasOne
     */
    public function UsePlaceLastLocationCenter(): HasOne
    {
        return $this->hasOne(LocationCenter::class, "uuid", "use_place_last_location_center_uuid");
    }

    /**
     * 所属道口（前次使用地点）
     * @return HasOne
     */
    public function UsePlaceLastLocationRailroadGradeCross(): HasOne
    {
        return $this->hasOne(LocationRailroadGradeCross::class, "uuid", "use_place_last_location_railroad_grade_cross_uuid");
    }

    /**
     * 所属室内上道位置（前次使用地点）
     * @return HasOne
     */
    public function UsePlaceCurrentPositionIndoorCell(): HasOne
    {
        return $this->hasOne(PositionIndoorCell::class, "uuid", "use_place_current_position_indoor_cell_uuid");
    }

    /**
     * 所属室内上道位置（前次使用地点）
     * @return HasOne
     */
    public function UsePlaceLastPositionIndoorCell(): HasOne
    {
        return $this->hasOne(PositionIndoorCell::class, "uuid", "use_place_last_position_indoor_cell_uuid");
    }

}
