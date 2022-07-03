<?php

namespace App\Libraries\Super;
class CommonHelper
{
    /**
     * var_dump打印并停止
     * @param $data
     */
    final static public function varDumpDie($data)
    {
        echo('<pre>');
        var_dump($data);
        echo('</pre>');
        die;
    }

    /**
     * print_r打印并停止
     * @param $data
     */
    final static public function printRDie($data)
    {
        echo('<pre>');
        print_r($data);
        echo('</pre>');
        die;
    }
}
