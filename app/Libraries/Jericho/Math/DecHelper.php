<?php

namespace Jericho\Math;

class DecHelper
{
    private static $_INS = null;
    private $_dec = null;

    final private function __construct()
    {
    }

    final public static function INS()
    {
        if (!self::$_INS) self::$_INS = new self();
        return self::$_INS;
    }

    /**
     * 使用整数初始化
     * @param int $dec
     * @return DecHelper
     */
    final public function fromInt(int $dec): self
    {
        $this->_dec = intval($dec);
        return $this;
    }

    /**
     * 使用字符串初始化
     * @param string $dec
     * @return DecHelper
     */
    final public function fromString(string $dec): self
    {
        $this->_dec = intval($dec);
        return $this;
    }

    /**
     * 解码
     * @param int $type
     * @return string
     */
    final public function get(int $type): string
    {
        switch ($type) {
            case 2:
                return strval(decbin($this->_dec));
                break;
            case 8:
                return strval(decoct($this->_dec));
                break;
            case 16:
                return strtoupper(strval(dechex($this->_dec)));
                break;
            default:
                return '';
                break;
        }
    }
}
