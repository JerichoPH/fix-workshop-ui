<?php

namespace App\Libraries\Super;

class TextHelper
{

    public static $RAND_STRING = 'string';  # 小写字母
    public static $RAND_STRING2 = 'STRING';  # 大写字母
    public static $RAND_STRING3 = 'String';  # 混合大小写字母
    public static $RAND_ADMIX = 'admix';  # 小写字母+数字
    public static $RAND_ADMIX2 = 'ADMIX';  # 大写字母+数字
    public static $RAND_ADMIX3 = 'Admix';  # 混搭大小写字母+数字
    public static $RAND_NUM = 'num';  # 数字


    /**
     * 驼峰转下划线
     * @param string $str
     * @return bool|string
     */
    final public static function hump2underline(string $str): string
    {
        if (empty($str)) return false;
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $str));
    }

    /**
     * 下划线转驼峰
     * @param string $str
     * @return bool|string|string[]|null
     */
    final public static function underline2hump(string $str): string
    {
        if (empty($str)) return false;
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    /**
     * 绝对字符串截取
     * @param string $str string
     * @param string $start start to cut out
     * @param string $length length to cut out
     * @return string
     * @author JerichoPH
     */
    final public static function sub($str, $start, $length): string
    {
        if (self::len($str) == 0) return $str;

        $str = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        $res = '';
        $length = $length <= count($str) ? $length : count($str);
        for ($i = $start; $i < $length; $i++) {
            $res .= $str[$i];
        }
        return $res;
    }

    /**
     * 取绝对字符串长度
     * JerichoPH
     * @param string $s string
     * @return bool|int
     */
    final public static function len(string $s)
    {
        if (!is_string($s)) {
            return false;
        }
        return count(preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * 字符串转数组
     * @param string $s
     * @return array|bool|false|string[]
     */
    final public static function toArray(string $s)
    {
        if (!is_string($s)) {
            return false;
        }
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * 设置变量默认值
     * @param mixed $val variable
     * @param mixed $default default value. default: 无
     * @return mixed
     * @author JerichoPH
     */
    final public static function def($val, $default = '')
    {
        return (is_null($val) || empty($val)) ? $val : $default;
    }

    /**
     * 加密字符串
     * @param string $data source sting
     * @param string $key key to do make secret
     * @return string
     * @author JerichoPH
     */
    final public static function enSecret($data, $key): string
    {
        $key = md5($key);
        $x = 0;
        $data_len = strlen($data);
        $key_len = strlen($key);
        $char = "";
        $str = "";
        for ($i = 0; $i < $data_len; $i++) {
            if ($x == $key_len) {
                $x = 0;
            }
            $char .= $key[$x];
            $x++;
        }
        for ($i = 0; $i < $data_len; $i++) {
            $str .= chr(ord($data[$i]) + (ord($char[$i])) % 256);
        }
        return base64_encode($str);
    }

    /**
     * 解密字符串
     * @param string $data source string
     * @param string $key key to do make secret
     * @return string
     * @author JerichoPH
     */
    final public static function deSecret($data, $key): string
    {
        $key = md5($key);
        $x = 0;
        $data = base64_decode($data);
        $len = strlen($data);
        $l = strlen($key);
        $char = "";
        $str = "";
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return $str;
    }

    /**
     * 生成随机字符串
     * @param string $TYPE ='admix' make char type
     * @explain:
     *         admix: lower char and numeric
     *         Admix: lower char and upper char and numeric
     *         ADMIX: upper char and numeric
     *         string: only lower char
     *         String: lower char upper char
     *         STRING: only upper char
     *         num: only numeric
     * @param integer $LENGTH =8 生成长度
     * @return string
     * @author JerichoPH
     */
    final public static function rand($TYPE = 'Admix', $LENGTH = 32): string
    {
        //dictionary
        $dictionary = array(
            'string' => 'qwertyuiopasdfghjklzxcvbnm',
            'STRING' => 'QWERTYUIOPASDFGHJKLZXCVBNM',
            'String' => 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM',
            'admix' => 'q1we3rty2ui6opa4sdf7ghj5klz8xc9vbn0m',
            'ADMIX' => 'Q1WE3RTY2UI6OPA4SDF7GHJ5KLZ8XC9VBN0M',
            'Admix' => 'Q1WE3RTY2UI6OPA4SDF7GHJ5KLZ8XC9VBN0Mq1we3rty2ui6opa4sdf7ghj5klz8xc9vbn0m',
            'num' => '1234567890'
        );
        $type = 'admix';
        if (empty($TYPE) == false) {
            $type = trim($TYPE);
        }
        $length = 8;
        if ($LENGTH > 1) {
            $length = (int)$LENGTH;
        }
        $str = '';
        switch ($type) {
            case 'string' :
            case 'STRING' :
                for ($i = 0; $i < $length; $i++) {
                    $str .= $dictionary[$type][rand(0, 25)];
                }
                break;
            case 'String' :
                for ($i = 0; $i < $length; $i++) {
                    $str .= $dictionary[$type][rand(0, 51)];
                }
                break;
            case 'admix' :
            case 'ADMIX' :
                for ($i = 0; $i < $length; $i++) {
                    $str .= $dictionary[$type][rand(0, 35)];
                }
                break;
            case 'Admix' :
                for ($i = 0; $i < $length; $i++) {
                    $str .= $dictionary[$type][rand(0, 71)];
                }
                break;
            case 'num' :
                for ($i = 0; $i < $length; $i++) {
                    $str .= $dictionary[$type][rand(0, 9)];
                }
                break;
        }
        return $str;
    }

    /**
     * 反序列化 xml
     * @param string $xml
     * @param bool $toArray
     * @return array|\SimpleXMLElement
     */
    final public static function parseXml(string $xml, bool $toArray = true)
    {
        return $toArray ? (array)simplexml_load_string($xml) : simplexml_load_string($xml);
    }

    /**
     * AES-CBC加密
     * @param string $text
     * @param string|null $key
     * @param string|null $iv
     * @return string
     */
    final public static function enAesCbc(string $text, string $key = null, string $iv = null): string
    {
        if (!$key) $key = config('aes.key');
        if (!$iv) $iv = config('aes.iv');

        return base64_encode(openssl_encrypt($text, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv));
    }

    /**
     * AES-CBC解密
     * @param string $text
     * @param string|null $key
     * @param string|null $iv
     * @return string
     */
    final public static function deAesCbc(string $text, string $key = null, string $iv = null): string
    {
        if (!$key) $key = config('aes.key');
        if (!$iv) $iv = config('aes.iv');

        return openssl_decrypt(base64_decode($text), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * AES-CBC加密2
     * @param string $text
     * @param string|null $key
     * @param string|null $iv
     * @return string
     */
    final public static function enAesCbc2(string $text, string $key = null, string $iv = null): string
    {
        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $text, MCRYPT_MODE_CBC, $iv);
        return base64_encode($encrypted);
    }

    /**
     * AES-CBC解密2
     * @param string $text
     * @param string|null $key
     * @param string|null $iv
     * @return string
     */
    final public static function deAesCbc2(string $text, string $key = null, string $iv = null): string
    {
        $decrypted = base64_decode($text);
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted, MCRYPT_MODE_CBC, $iv);
    }

    /**
     * 字母 转ASCII
     * @param $str
     * @return string
     */
    final public static function toAscii($str): string
    {
        $str = mb_convert_encoding($str, 'GB2312');
        $change_after = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $temp_str = dechex(ord($str[$i]));
            $change_after .= $temp_str[1] . $temp_str[0];
        }
        return strtoupper($change_after);
    }

    /**
     * ASCII 转字母
     * @param $ascii
     * @return bool|false|string|string[]|null
     */
    final public static function fromAscii($ascii)
    {
        $asc_arr = str_split(strtolower($ascii), 2);
        $str = '';
        for ($i = 0; $i < count($asc_arr); $i++) {
            $str .= chr(hexdec($asc_arr[$i][1] . $asc_arr[$i][0]));
        }
        return mb_convert_encoding($str, 'UTF-8', 'GB2312');
    }

    /**
     * 十进制转三十二进制
     * @param $num
     * @return string
     */
    final public static function to32($num): string
    {
        $to = 32;
        $dict = '0123456789abcdefghijklmnopqrstuv';
        $ret = '';
        do {
            $ret = $dict[bcmod($num, $to)] . $ret; //bcmod取得高精确度数字的余数。
            $num = bcdiv($num, $to);  //bcdiv将二个高精确度数字相除。
        } while ($num > 0);
        return $ret;
    }

    /**
     * 三十二进制转十进制
     * @param $str
     * @return int|string
     */
    final public static function from32($str)
    {
        $from = 32;
        $str = strval($str);
        $dict = '0123456789abcdefghijklmnopqrstuv';
        $len = strlen($str);
        $dec = 0;
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($dict, $str[$i]);
            $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
        }
        return $dec;
    }

    /**
     * 十进制转六十四进制
     * @param $num
     * @return string
     */
    final public static function to64($num): string
    {
        $to = 64;
        $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        $ret = '';
        do {
            $ret = $dict[bcmod($num, $to)] . $ret; //bcmod取得高精确度数字的余数。
            $num = bcdiv($num, $to);  //bcdiv将二个高精确度数字相除。
        } while ($num > 0);
        return $ret;
    }

    /**
     * 六十四进制转十进制
     * @param $str
     * @return int|string
     */
    final public static function from64($str)
    {
        $from = 64;
        $str = strval($str);
        $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        $len = strlen($str);
        $dec = 0;
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($dict, $str[$i]);
            $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
        }
        return $dec;
    }

    /**
     * 三十六进制+1
     * @param $char
     * @return string
     */
    final public static function inc36($char): string
    {
        return self::to36((self::from36($char) + 1));
    }

    /**
     * 十进制转三十六进制
     * @param $int
     * @param int $format
     * @return string
     */
    final public static function to36($int, $format = 8): string
    {
        $dic = [
            0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9',
            10 => 'A', 11 => 'B', 12 => 'C', 13 => 'D', 14 => 'E', 15 => 'F', 16 => 'G', 17 => 'H', 18 => 'I',
            19 => 'J', 20 => 'K', 21 => 'L', 22 => 'M', 23 => 'N', 24 => 'O', 25 => 'P', 26 => 'Q', 27 => 'R',
            28 => 'S', 29 => 'T', 30 => 'U', 31 => 'V', 32 => 'W', 33 => 'X', 34 => 'Y', 35 => 'Z'
        ];

        $arr = array();
        $loop = true;
        while ($loop) {
            $arr[] = $dic[bcmod($int, 36)];
            $int = floor(bcdiv($int, 36));
            if ($int == 0) {
                $loop = false;
            }
        }
        array_pad($arr, $format, $dic[0]);
        return implode('', array_reverse($arr));
    }

    /**
     * 三十六进制转十进制
     * @param $char
     * @return int|string
     */
    final public static function from36($char): int
    {
        $dic = [
            0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9',
            10 => 'A', 11 => 'B', 12 => 'C', 13 => 'D', 14 => 'E', 15 => 'F', 16 => 'G', 17 => 'H', 18 => 'I',
            19 => 'J', 20 => 'K', 21 => 'L', 22 => 'M', 23 => 'N', 24 => 'O', 25 => 'P', 26 => 'Q', 27 => 'R',
            28 => 'S', 29 => 'T', 30 => 'U', 31 => 'V', 32 => 'W', 33 => 'X', 34 => 'Y', 35 => 'Z'
        ];

        // 键值交换
        $dedic = array_flip($dic);
        // 去零
        $char = ltrim($char, $dic[0]);
        // 反转
        $char = strrev($char);
        $v = 0;
        for ($i = 0, $j = strlen($char); $i < $j; $i++) {
            $v = bcadd(bcmul($dedic[$char{$i}], bcpow(36, $i)), $v);
        }
        return $v;
    }

    /**
     * 清理字符串空格，缩进，换行，HTML标签
     * @param $str
     * @param bool $clear_html
     * @return string
     */
    final public static function strip($str, bool $clear_html = false): string
    {
        $str = trim($str);  # 清除字符串两边的空格
        if ($clear_html) $str = strip_tags($str, "");  # 利用php自带的函数清除html格式
        $str = preg_replace("/\t/", "", $str);  # 使用正则表达式匹配需要替换的内容，如空格和换行，并将替换为空
        $str = preg_replace("/\r\n/", "", $str);
        $str = preg_replace("/\r/", "", $str);
        $str = preg_replace("/\n/", "", $str);
        if ($clear_html) $str = preg_replace("/ /", "", $str);  # 匹配html中的空格
        return trim($str);  # 返回字符串
    }

    /**
     * 验签
     * @param array $data
     * @param string $secretKey
     * @param string $sign
     * @return bool
     */
    final public static function checkSign(array $data, string $secretKey, string $sign): bool
    {
        return $sign == self::makeSign($data, $secretKey);
    }

    /**
     * 生成Sign签名（json）
     * @param array $data
     * @param string $secretKey
     * @return string
     */
    final public static function makeSign(array $data, string $secretKey): string
    {
        $data = array_filter($data);
        $data['secret_key'] = $secretKey;
        ksort($data);
        return strtoupper(md5(http_build_query($data)));
    }

    final public static function checkSign2(array $data, string $secretKey, string $sign): array
    {
        $ret = [];
        $ret['收到的参数'] = $data;
        $data = array_filter($data);
        $ret['去空'] = $data;
        $data['secret_key'] = $secretKey;
        $ret['SecretKey'] = $secretKey;
        ksort($data);
        $ret['排序'] = $data;
        $query = http_build_query($data);
        $ret['序列化'] = $query;
        $md5 = md5($query);
        $ret['md5'] = $md5;
        $sign2 = strtoupper($md5);
        $ret['签名'] = $sign2;
        $ret['验签'] = $sign == $sign2;

        return ['result' => $sign == $sign2, 'details' => $ret];
    }

    /**
     * 取绝对字符串长度
     * JerichoPH
     * @param string $STR string
     * @return bool|int
     */
    final public static function strLen($STR)
    {
        if (!is_string($STR)) {
            return false;
        }
        return count(preg_split('//u', $STR, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * 密码加盐
     * @param string $pass 密码内容
     * @param int $saltLen 盐长度
     * @param bool $returnArray 是否返回数组
     * @param null $salt 盐s
     * @param string $saltType 盐类型（如果是随机盐）
     * @param bool string 原密码是否需要再次加密
     * @return array
     */
    final public static function enSalt($pass, $saltLen = 6, $returnArray = true, $salt = null, $saltType = 'Admix', $md5 = true)
    {
        $salt = trim($salt);
        $salt = $salt == null || self::strLen($salt) < $saltLen ? self::rand($saltType, $saltLen) : $salt;
        $pass = $md5 == true ? md5($pass) : $pass;
        return $returnArray ? [md5($pass . $salt), $salt] : ['pass' => md5($pass . $salt), 'salt' => $salt];
    }

    /**
     * 密码解盐
     * @param string $inputPass 表单输入密码
     * @param string $salt 盐
     * @param string $sourcePass 原密码
     * @param bool $md5 原密码是否需要md5加密
     * @return bool
     */
    final public static function deSalt($inputPass, $salt, $sourcePass, $md5 = true)
    {
        $salt = trim($salt);
        return md5(($md5 == true ? md5($inputPass) : $inputPass) . $salt) == $sourcePass;
    }

    /**
     * 解析json
     * @param $json
     * @param bool $isArray
     * @return mixed
     */
    final public static function parseJson($json, $isArray = true)
    {
        return json_decode($json, $isArray);
    }

    /**
     * 序列化json
     * @param $data
     * @param int $param
     * @return false|string
     */
    final public static function toJson($data, $param = 256): string
    {
        return json_encode($data, $param);
    }

    /**
     * 字母 转ASCII
     * @param $str
     * @return string
     */
    final public static function strToAscii($str)
    {
        $str = mb_convert_encoding($str, 'GB2312');
        $change_after = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $temp_str = dechex(ord($str[$i]));
            $change_after .= $temp_str[1] . $temp_str[0];
        }
        return strtoupper($change_after);
    }

    /**
     * ASCII 转字母
     * @param $ascii
     * @return bool|false|string|string[]|null
     */
    final public static function asciiToStr($ascii)
    {
        $asc_arr = str_split(strtolower($ascii), 2);
        $str = '';
        for ($i = 0; $i < count($asc_arr); $i++) {
            $str .= chr(hexdec($asc_arr[$i][1] . $asc_arr[$i][0]));
        }
        return mb_convert_encoding($str, 'UTF-8', 'GB2312');
    }


}
