<?php

namespace App\Model;

use App\Model\Base;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class MaterialType
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $identity_code
 * @property string $name
 * @property string $unit
 * @property string $creator_id
 * @property-read Account $Creator
 * @property-read Material[] $Materials
 */
class MaterialType extends Base
{
    protected $guarded = [];
    protected $__default_withs = ['Creator',];

    final public function Materials(): HasMany
    {
        return $this->hasMany(Material::class, 'material_type_identity_code', 'identity_code');
    }

    final public function Creator(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'creator_id');
    }
}
