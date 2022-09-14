<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Factory
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $unique_code
 * @property string $name
 * @property string $shot_name
 */
class Factory extends Model
{
    use SoftDeletes;

    protected $guarded = [];
}
