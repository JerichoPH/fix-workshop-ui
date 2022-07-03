<?php

namespace Jericho\Math;

class Scale64Helper
{
    private static $_INS = null;
    private $_number = null;

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
     * @param string $number
     * @return Scale64Helper
     */
    final public function fromString(string $number): self
    {
        $this->_number = $number;
        return $this;
    }

    /**
     * 解析
     * @param int $type
     * @return string
     */
    final public function get(int $type): string
    {
        $map = array(
            '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
            'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16, 'H' => 17, 'I' => 18, 'J' => 19,
            'K' => 20, 'L' => 21, 'M' => 22, 'N' => 23, 'O' => 24, 'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28, 'T' => 29,
            'U' => 30, 'V' => 31, 'W' => 32, 'X' => 33, 'Y' => 34, 'Z' => 35, 'a' => 36, 'b' => 37, 'c' => 38, 'd' => 39,
            'e' => 40, 'f' => 41, 'g' => 42, 'h' => 43, 'i' => 44, 'j' => 45, 'k' => 46, 'l' => 47, 'm' => 48, 'n' => 49,
            'o' => 50, 'p' => 51, 'q' => 52, 'r' => 53, 's' => 54, 't' => 55, 'u' => 56, 'v' => 57, 'w' => 58, 'x' => 59,
            'y' => 60, 'z' => 61, '_' => 62, '=' => 63
        );
        $dec = 0;
        $len = strlen($this->_number);
        for ($i = 0; $i < $len; $i++) {
            $b = $map[$this->_number[$i]];
            if ($b === NULL) return false;
            $j = $len - $i - 1;
            $dec += ($j == 0 ? $b : (2 << (6 * $j - 1)) * $b);
        }

        switch ($type) {
            case 2:

                break;
            case 10:
                return strval($dec);
                break;
            case 8:
                break;
            case 16:
                break;
            default:
                return "";
                break;
        }
    }
}
