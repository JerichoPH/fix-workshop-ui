<?php

namespace App\Libraries\Super;

use Illuminate\Support\Facades\DB;

class BaiduHelper
{
    private static $instance;

    private $_currentLon = '';
    private $_currentLat = '';

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __sleep()
    {
        return [];
    }

    public static function init(string $currentLon = '', string $currentLat = '')
    {
        if (!(self::$instance instanceof self)) self::$instance = new self();
        self::$instance->_currentLon = $currentLon;
        self::$instance->_currentLat = $currentLat;
        return self::$instance;
    }

    /**
     * 计算地图距离
     * deg2rad()函数将角度转换为弧度
     * @param string $toLon
     * @param string $toLat
     * @return float|string
     */
    final public function distance(string $toLon, string $toLat)
    {
        // 将角度转为狐度
        $currentLonRad = deg2rad($this->_currentLon);
        $currentLatRad = deg2rad($this->_currentLat);
        $lonRad = deg2rad($toLon);
        $latRad = deg2rad($toLat);
        $a = $currentLatRad - $latRad;
        $b = $currentLonRad - $lonRad;
        return 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($currentLatRad) * cos($latRad) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
    }


}