<?php

namespace App\Libraries\Super\Math;
class HexHelper
{
    private static $_INS = null;
    private $_hex = null;
    private $_limit = null;

    final private function __construct()
    {
    }

    final public static function INS(): self
    {
        if (!self::$_INS) self::$_INS = new self();
        return self::$_INS;
    }

    /**
     * 使用数组初始化
     * @param array $hex
     * @return HexHelper
     */
    final public function fromArray(array $hex): self
    {
        $this->_hex = implode('', $hex);
        return $this;
    }

    /**
     * 使用字符串初始化
     * @param string $hex
     * @param string $limit
     * @return HexHelper
     */
    final public function fromString(string $hex, string $limit = ''): self
    {
        if ($limit) $hex = str_replace($this->_limit, '', $this->_hex);
        $this->_hex = $hex;
        return $this;
    }

    /**
     * 设置分隔符
     * @param string|null $limit
     * @return HexHelper
     */
    final public function limit(string $limit): self
    {
        $this->_limit = $limit;
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
                # 十六进制转二进制
                return strval(hex2bin($this->_hex));
                break;
            case 10:
                # 十六进制转十进制
                return strval(hexdec($this->_hex));
            case 8:
                # 十六进制转八进制
                return strtoupper(strval(decoct(hexdec($this->_hex))));
            default:
                return "";
                break;
        }
    }
}
