<?php

namespace Jericho\Redis;

use Illuminate\Support\Facades\Redis;

class Hashs extends Basic
{
    private static $_ins = null;

    final private function __construct($bucketName = null)
    {
        parent::setBucketName($bucketName);
    }

    final public static function ins($bucketName = null)
    {
        if (!self::$_ins) self::$_ins = new self($bucketName);
        return self::$_ins;
    }

    /**
     * 获取单个Hash
     * @param string $key 键名
     * @param string $field 字段名
     * @return mixed
     */
    final public function getOne(string $key, string $field)
    {
        return Redis::command('hget', [$this->getBucketName($key), $field]);
    }

    /**
     * 获取多个值
     * @param string $key
     * @param null $field
     * @return mixed
     * @throws \Exception
     */
    final public function getMore(string $key, $field = null)
    {
        if (!$field) throw new \Exception('filed不能为空');

        if (is_string($field)) $field = explode(',', $field);

        array_unshift($field, $this->getBucketName($key));
        return Redis::command('hmget', $field);
    }

    /**
     * 设置单个值
     * @param string $key
     * @param string $field
     * @param $value
     * @return mixed
     */
    final public function setOne(string $key, string $field, $value)
    {
        return Redis::command('hset', [$this->getBucketName($key), $field, $value]);
    }

    /**
     * 设置多值
     * @param string $key
     * @param array $arr
     * @param string|null $fieldName
     * @param int $jsonOption
     * @return bool
     * @throws \Exception
     */
    final public function setMore(string $key, array $arr, string $fieldName = null, int $jsonOption = 256)
    {
        foreach ($arr as $k => $v) {
            if ($fieldName) {
                if (!array_key_exists($fieldName, $v))
                    throw new \Exception('不存在' . $fieldName);
                $data[] = $v[$fieldName];
            } else {
                $data[] = $k;
            }
            $data[] = !(is_array($v) || is_object($v)) ? $v : json_encode($v, $jsonOption);
        }
        array_unshift($data, $this->getBucketName($key));

        return Redis::command('hmset', $data);
    }

    /**
     * 自增加一
     * @param string $key
     * @param string $field
     * @param int|null $value
     * @return mixed
     */
    final public function setIncr(string $key, string $field, int $value = null)
    {
        return Redis::command('hincrby', [$this->getBucketName($key), $field, $value ? $value : 1]);
    }

    /**
     * 删除hash
     * @param string $key
     * @param null $field
     * @return mixed
     */
    final public function del(string $key, $field = null)
    {
        if (!$field) return Redis::command('del', [$this->getBucketName($key)]);

        if (is_string($field)) {
            $field = explode(',', $field);
        }
        array_unshift($field, $this->getBucketName($key));
        return Redis::command('hdel', $field);
    }
}
