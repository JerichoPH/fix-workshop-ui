<?php
namespace Jericho\Model;
use Illuminate\Support\Facades\DB;

class Log{
    public static function sqlLanguage(\Closure $closure)
    {
        DB::connection()->enableQueryLog();
        $closure();
        return DB::getQueryLog();
    }
}
