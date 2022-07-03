<?php

namespace App\Libraries\Super\Excel;

class ExcelHelper
{
    private static $instance;


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


    public static function init()
    {
        if (!(self::$instance instanceof self)) self::$instance = new self();
        return self::$instance;
    }

    /**
     * 处理Excel单元格时间
     * @param $excelAt
     * @return false|string
     */
    final public function handleExcelTime($excelAt)
    {
        $at = null;
        if (!empty($excelAt)) {
            switch (gettype($excelAt)) {
                case 'double':
                    $at = gmdate('Y-m-d H:i:s', intval(($excelAt - 25569) * 3600 * 24));
                    break;
                case 'string':
                    $at = $excelAt;
                    break;
                default:
                    break;
            }
        }

        return $at;
    }

}