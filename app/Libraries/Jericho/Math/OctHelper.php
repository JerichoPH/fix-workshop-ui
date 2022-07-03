<?php

namespace Jericho\Math;
class OctHelper
{
    private static $_INS = null;
    private $_oct = null;

    final private function __construct()
    {
    }

    final public static function INS(): self
    {
        if (!self::$_INS) self::$_INS = new self;
        return self::$_INS;
    }

    /**
     * 使用字符串初始化
     * @param string $oct
     * @return OctHelper
     */
    final public function fromString(string $oct): self
    {
        $this->_oct = $oct;
        return $this;
    }

    final public function get(int $type): string
    {
        $dec = octdec($this->_oct);
        switch ($type) {
            case 2:
                return strval(decbin($dec));
                break;
            case 10:
                return strval(octdec($this->_oct));
                break;
            case 16:
                return strtoupper(strval(dechex($dec)));
                break;
            default:
                return "";
                break;
        }
    }
}