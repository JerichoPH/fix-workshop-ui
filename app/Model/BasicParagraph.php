<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\BasicParagraph
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $unique_code 代码
 * @property string $name 名称
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BasicParagraph newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BasicParagraph newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BasicParagraph query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BasicParagraph whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BasicParagraph whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BasicParagraph whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BasicParagraph whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BasicParagraph whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BasicParagraph extends Model
{
    protected $guarded = [];
}
