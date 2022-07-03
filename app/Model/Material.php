<?php

namespace App\Model;

use App\Model\Base;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Class Material
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property string $identity_code
 * @property string $asset_code
 * @property string $fixed_asset_code
 * @property string $material_type_identity_code
 * @property-read MaterialType $Type
 * @property string $workshop_unique_code
 * @property-read Maintain $Workshop
 * @property string $station_unique_code
 * @property-read Maintain $Station
 * @property string $position_unique_code
 * @property-read Position $Position
 * @property string $work_area_unique_code
 * @property-read WorkArea $WorkArea
 * @property stdClass $source_type
 * @property string $source_name
 * @property stdClass $status
 * @property-read Account $TmpProcessor
 * @property stdClass $operation_direction
 */
class Material extends Base
{
    use SoftDeletes;

    protected $guarded = [];
    protected $__default_withs = ['Type', 'Workshop', 'Station', 'WorkArea', 'TmpProcessor',];

    public static $STATUSES = [
        'TAGGED' => '已赋码',
        'STORED_IN' => '已入库',
        'STORED_OUT' => '已出库',
    ];

    public static $OPERATION_DIRECTIONS = [
        '' => '无',
        'IN' => '入库',
        'OUT' => '出库',
    ];

    final public function getStatusAttribute($value): stdClass
    {
        $_ = new stdClass();

        $_->value = $value;
        $_->text = @self::$STATUSES[$value] ?: '';

        return $_;
    }

    final public function getSourceTypeAttribute($value): stdClass
    {
        $_ = new stdClass();

        $_->value = $value;
        $_->text = @EntireInstance::$SOURCE_TYPES[$value] ?: '';

        return $_;
    }

    final public function getOperationDirectionAttribute($value): stdClass
    {
        $_ = new stdClass();

        $_->value = $value;
        $_->text = @self::$OPERATION_DIRECTIONS[$value] ?: '';

        return $_;
    }

    final public function Type(): HasOne
    {
        return $this->hasOne(MaterialType::class, 'identity_code', 'material_type_identity_code');
    }

    final public function Workshop(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'workshop_unique_code');
    }

    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'station_unique_code');
    }

    final public function WorkArea(): HasOne
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }

    final public function Position(): HasOne
    {
        return $this->hasOne(Position::class, 'unique_code', 'position_unique_code');
    }

    final public function TmpProcessor(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'tmp_scan_processor_id');
    }

    final public static function generateIdentityCodes(string $asset_code, int $number): Collection
    {
        $last = DB::table('materials as m')->where('asset_code', $asset_code)->orderByDesc('id')->first();
        $last_number = intval($last ? substr($last->identity_code, -8) : 0);

        $new_identity_codes = [];
        for ($i = 0; $i < $number; $i++) {
            $new_number = str_pad(strval(++$last_number), 8, '0', STR_PAD_LEFT);
            $new_identity_codes[] = env('ORGANIZATION_CODE') . "$asset_code$new_number";
        }

        return collect($new_identity_codes);
    }

}
