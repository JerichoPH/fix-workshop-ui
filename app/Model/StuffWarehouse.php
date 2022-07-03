<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class StuffWarehouse extends Model
{
    protected $fillable = [
        'unique_code',
        'type',
        'account_id',
        'workshop_unique_code',
        'station_unique_code',
        'receiver',
        'connection_phone',
        'remark',
    ];

    public static $TYPE = [
        1 => '入库',
        2 => '出库',
    ];


    public function getTypeAttribute($value): array
    {
        return [
            'value' => $value,
            'text' => self::$TYPE[$value]
        ];
    }

    final public static function generateUniqueCode(): string
    {
        $unique_code = time();
        if (!empty(DB::table('stuff_warehouses')->where('unique_code', $unique_code)->select('unique_code')->first())) self::generateUniqueCode();

        return $unique_code;
    }

    final public function stuff_warehouse_lists(): HasMany
    {
        return $this->hasMany(StuffWarehouseList::class, 'stuff_warehouse_unique_code', 'unique_code');
    }

    final public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    final public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class, 'workshop_unique_code', 'unique_code');
    }

    final public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_unique_code', 'unique_code');
    }
}
