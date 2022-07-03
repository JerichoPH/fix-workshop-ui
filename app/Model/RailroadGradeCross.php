<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RailroadGradeCross extends Model
{
    // 编码规则 I+4位十进制
    protected $guarded = [];

    /**
     * 获取线别
     * @return BelongsToMany
     */
    final public function Lines():BelongsToMany
    {
        return $this->belongsToMany(Line::class,"pivot_line_railroad_grade_crosses","railroad_grad_cross_id","line_id");
    }

}
