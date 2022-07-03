<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Centre extends Base
{
    // 编码规则：A12（路局代码）+F2位十进制
    protected $guarded = [];
    protected $__default_withs = ["SceneWorkshop", "Lines",];

    /**
     * 绑定线别
     * @param array $line_unique_codes
     */
    final public function bindLines(array $line_unique_codes): void
    {
        $id = $this->attributes["id"];
        DB::table("pivot_line_centres")->where("centre_id", $id)->delete();  // 清空原有对应关系
        Line::with([])
            ->whereIn("unique_code", $line_unique_codes)
            ->get()
            ->pluck("id")
            ->each(function ($line_id) use (&$pivot_line_centres, $id) {
                $pivot_line_centres[] = [
                    "created_at" => now(),
                    "updated_at" => now(),
                    "line_id" => $line_id,
                    "centre_id" => $id,
                ];
            });
        if ($pivot_line_centres) DB::table("pivot_line_centres")->insert($pivot_line_centres);
    }

    /**
     * 获取线别
     * @return BelongsToMany
     */
    final public function Lines(): BelongsToMany
    {
        return $this->belongsToMany(Line::class, "pivot_line_centres", "centre_id", "line_id");
    }

    /**
     * 获取所属现场车间
     * @return HasOne
     */
    final public function SceneWorkshop(): HasOne
    {
        return $this->hasOne(Maintain::class, "unique_code", "scene_workshop_unique_code");
    }
}
