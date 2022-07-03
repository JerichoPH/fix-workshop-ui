<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StuffWarehouseList extends Model
{
    protected $fillable = [
        'stuff_warehouse_unique_code',
        'stuff_unique_code',
    ];

    final public function stuff(): BelongsTo
    {
        return $this->belongsTo(Stuff::class, 'stuff_unique_code', 'unique_code');
    }

}
