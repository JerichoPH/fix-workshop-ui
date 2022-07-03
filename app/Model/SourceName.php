<?php

namespace App\Model;

use Illuminate\Support\Carbon;
use stdClass;

/**
 * Class SourceName
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $name
 * @property stdClass $source_type
 * @property string $unique_code
 */
class SourceName extends Base
{
    protected $guarded = [];
    protected $__default_withs = [];

    final public function getSourceTypeAttribute($value): stdClass
    {
        $_ = new stdClass();

        $_->value = $value;
        $_->text = @EntireInstance::$SOURCE_TYPES[$value] ?: '';

        return $_;
    }
}
