<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EntireInstanceExcelEditReport extends Model
{
    protected $guarded = [];

    final public function WorkArea(): HasOne
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }

    public function Processor(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }
}
