<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WarehouseReportDisplayBoard extends Model
{
    protected $guarded = [];

    final public function Station()
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'station_unique_code');
    }

    final public function SceneWorkshop()
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'scene_workshop_unique_code');
    }

    final public function Category()
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    final public function EntireModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    final public function SubModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'model_unique_code');
    }

    final public function PartModel()
    {
        return $this->hasOne(PartModel::class, 'unique_code', 'model_unique_code');
    }

    final public function WorkArea()
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }
}
