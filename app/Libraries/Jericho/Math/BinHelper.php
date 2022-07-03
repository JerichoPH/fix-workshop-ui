<?php

namespace Jericho\Math;
class BinHelper
{
    private static $_INS = null;
    private $_bin;

    final public function __construct()
    {
    }

    final public static function INS()
    {
        if (!self::$_INS) self::$_INS = new self;
        return self::$_INS;
    }

    /**
     * 使用字符串初始化
     * @param string $bin
     * @return BinHelper
     */
    final public function fromString(string $bin): self
    {
        $this->_bin = $bin;
        return $this;
    }

    /**
     * 解析
     * @param int $type
     * @return float|int|string
     */
    final public function get(int $type)
    {
        switch ($type) {
            case 8:
                return decoct(strval(hexdec(bin2hex($this->_bin))));
                break;
            case 10:
                return strval(hexdec(bin2hex($this->_bin)));
                break;
            case 16:
                return strtoupper(bin2hex($this->_bin));
                break;
            case 64:
                $dec = hexdec(bin2hex($this->_bin));
                return $dec;
                break;
            default:
                return "";
                break;
        }
    }
}
