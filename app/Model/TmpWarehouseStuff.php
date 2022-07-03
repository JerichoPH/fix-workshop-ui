<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmpWarehouseStuff extends Model
{
    protected $fillable = [
        'stuff_unique_code', 'position_unique_code', 'type', 'account_id'
    ];

    final public function stuff(): BelongsTo
    {
        return $this->belongsTo(Stuff::class, 'stuff_unique_code', 'unique_code');
    }

    final public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    final public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_unique_code', 'unique_code');
    }

}
