<?php

namespace App\Libraries\Super;
class FileHelper
{
    private static $_INS = null;
    private $_dir = null;

    final private function __construct(string $dir)
    {
        $this->_dir = $dir;
    }

    /**
     * 单例
     * @param string $dir
     * @return FileHelper
     */
    final public static function ins(string $dir): self
    {
        if (!self::$_INS) self::$_INS = new self($dir);
        return self::$_INS;
    }

    /**
     * 递归删除文件夹
     * @param string $dir
     * @return bool
     */
    final public function deleteDir(string $dir = ''): bool
    {
        $dir = $dir ? $dir : $this->_dir;
        if (!$handle = @opendir($dir)) {
            return false;
        }
        while (false !== ($file = readdir($handle))) {
            if ($file !== "." && $file !== "..") {       //排除当前目录与父级目录
                $file = $dir . '/' . $file;
                if (is_dir($file)) {
                    $this->deleteDir($file);
                } else {
                    @unlink($file);
                }
            }
        }
        @rmdir($dir);
        return true;
    }
}
