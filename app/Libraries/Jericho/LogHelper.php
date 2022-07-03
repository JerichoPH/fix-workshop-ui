<?php

namespace Jericho;
class LogHelper
{
    private static $_INS = null;
    private $_full_path = null;

    public static function INS(string $filename, string $dir = 'logs')
    {
        if (!self::$_INS) self::$_INS = new self($filename, $dir);
        return self::$_INS;
    }

    public function __construct(string $filename, string $dir = 'logs')
    {
        if (!is_dir(storage_path($dir))) mkdir(storage_path($dir));
        $this->_full_path = storage_path("{$dir}/{$filename}");
    }

    public function write(string $content, string $sep = "\r\n")
    {
        $f = fopen($this->_full_path, 'a+');
        fwrite($f, "{$content}{$sep}");
        fclose($f);
        return strlen($content);
    }

    public function json($data, string $sep = "\r\n")
    {
        $content = json_encode($data, 256);
        $this->write($content, $sep);
    }
}