<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class CollectionImage extends Model
{
    protected $guarded = [];

    final public function User(): HasOne
    {
        return $this->hasOne(StationInstallUser::class, 'station_install_user_id', 'id');
    }

    final public function getUrlAttribute($val): string
    {
        return Storage::disk('collectionImage')->url($this->attributes['filename']);
    }
}
