<?php

namespace Jericho\Redis;

use Illuminate\Support\Facades\Redis;

class Strings extends Basic
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
     * 获取
     * @param string $key
     * @param \Closure|null $closure
     * @return mixed
     */
    final public function getOne(string $key, \Closure $closure = null)
    {
        $value = null;
        $reTryTimes = 0;  # 重试次数
        for ($i = 0; $i < parent::getRetryTimes(); $i++) {
            ++$reTryTimes;
            # 检查key是否存在
            if (!Redis::command('exists', [parent::getBucketName($key)])) {
                # 如果key不存在，则写入WAIT标记，读取数据库
                Redis::command('setex', [parent::getBucketName($key), parent::getWaitTagExpire(), 'WAIT']);  # 设置一个标记
                # 从数据库中读取……
                $value = null;
                if ($closure) $value = $closure();
                Redis::command('setex', [parent::getBucketName($key), parent::getExpireTime(), $value]);  # 更新缓存
            } else {
                # 值已经存在，读取该值
                $value = Redis::command('get', [parent::getBucketName($key)]);
                if (in_array($value, ['WAIT', 'CREATE', 'UPDATE'])) {
                    # 如果是标记，则暂停线程，稍后重试
                    $func = 'get' . ucfirst(strtolower($value)) . 'TagRetrySecond';
                    sleep(parent::$$func());  # n秒后重试
                    continue;
                }
            }
        }

        return $value;
    }

    /**
     * 获取多个
     * @param array $keys
     * @return array|null
     */
    final public function getMore(array $keys)
    {
        $names = $this->getBucketNames($keys);

        $values = Redis::command('mget', $names);
        if (!$values) return null;
        foreach ($values as $key => $value) $return[$keys[$key]] = $value;

        return $return;
    }

    /**
     * 设置多个值
     * @param array $arr
     * @return mixed
     */
    final public function setMore(array $arr)
    {
        foreach ($arr as $key => $value) {
            $data[] = $this->getBucketName($key);
            $data[] = ((is_array($value) || is_object($value)) ? json_encode($value, 256) : strval($value));
        }
        return Redis::command('mset', $data);
    }

    /**
     * 设置自增
     * @param string $key 键名
     * @param null $value 自增长值（不写为1）
     * @param int|null $expire 有效期
     * @return mixed
     */
    final public function setIncr(string $key, $value = null, int $expire = null)
    {
        # 如果key不存在，则返回0
        if (!Redis::command('exists', [$this->getBucketName($key)])) return 0;

        return Redis::command('decrby', [$this->getBucketName($key), $value ? $value : 1]);
    }

    /**
     * 设置自减
     * @param string $key 键名
     * @param null $value 值
     * @param int|null $expire
     * @return mixed
     */
    final public function setDecr(string $key, $value = null, int $expire = null)
    {
        # 如果key不存在，则设置累加数为1
        if (!Redis::command('exists', [$this->getBucketName($key)])) return $this->setOne($key, 0, $expire);

        return Redis::command('incrby', [$this->getBucketName($key), $value ? $value : 1]);
    }

    /**
     * 存储
     * @param string $key 键名
     * @param null $value 键值
     * @param int $expire 有效期
     * @return mixed
     */
    final public function setOne(string $key, $value = null, int $expire = null)
    {
        if (!$value) return Redis::command('del', [$this->getBucketName($key)]);
        $value = ((is_array($value) || is_object($value)) ? json_encode($value, 256) : strval($value));
        if (is_null($expire)) {
            return Redis::command('set', [$this->setBucketName($key), $value]);
        } elseif ($expire == 0) {
            return Redis::command('setex', [$this->getBucketName($key), $this->getExpireTime(), $value]);
        } else {
            return Redis::command('setex', [$this->getBucketName($key), $expire, $value]);
        }
    }

}
