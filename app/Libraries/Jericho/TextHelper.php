<?php

namespace Jericho;

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
    final public static function hump2underline(string $str)
    {
        if (empty($str)) return false;
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $str));
    }

    /**
     * 下划线转驼峰
     * @param string $str
     * @return bool|string|string[]|null
     */
    final public static function underline2hump(string $str)
    {
        if (empty($str)) return false;
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    /**
     * 绝对字符串截取
     * @param string $STR string
     * @param string $START start to cut out
     * @param string $LENGTH length to cut out
     * @return string
     * @author JerichoPH
     */
    final public static function sub($STR, $START, $LENGTH)
    {
        if (self::strLen($STR) == 0) {
            return $STR;
        }
        $str = preg_split('//u', $STR, -1, PREG_SPLIT_NO_EMPTY);
        $res = '';
        $LENGTH = $LENGTH <= count($str) ? $LENGTH : count($str);
        for ($i = $START; $i < $LENGTH; $i++) {
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
    final public static function strLen(string $s)
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
    final public static function strToArray(string $s)
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
    final public static function def($val, $default = '无')
    {
        return ValidateHelper::isEmpty($val) ? $default : $val;
    }

    /**
     * 加密字符串
     * @param string $data source sting
     * @param string $key key to do make secret
     * @return string
     * @author JerichoPH
     */
    final public static function enSecret($data, $key)
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
    final public static function deSecret($data, $key)
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
    final public static function rand($TYPE = 'Admix', $LENGTH = 32)
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
     * 反序列化json
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
     * 反序列化 yaml
     * @param $yaml
     */
    public static function parseYaml($yaml)
    {
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

    /**
     * 十进制转三十二进制
     * @param $num
     * @return string
     */
    final public static function to32($num)
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
    public static function from32($str)
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
     * @param int $n
     * @return string
     */
    final public static function to64($num)
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
        $str = preg_replace("/ /", "", $str);
        if ($clear_html) $str = preg_replace("/ /", "", $str);  # 匹配html中的空格
        return trim($str);  # 返回字符串
    }

    /**
     * 生成Sign签名（json）
     * @param array $data
     * @param string $secretKey
     * @return string
     */
    final public static function makeSign(array $data, string $secretKey)
    {
        $data = array_filter($data);
        $data['secret_key'] = $secretKey;
        ksort($data);
        return strtoupper(md5(http_build_query($data)));
    }
}
