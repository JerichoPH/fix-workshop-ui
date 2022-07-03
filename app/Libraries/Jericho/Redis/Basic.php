<?php

namespace Jericho\Redis;

class Basic
{
    private $_bucketName = null;  # 域名
    private $_expireTime = 0;  # 过期时间
    private $_readWaitingSecond = 0;  # 重试读秒
    private $_reTryTimes = 0;  # 重试次数
    private $_waitTagSecond = 0;  # wait标记有效期
    private $_waitTagRetrySecond = 0;  # wait标记重试间隔
    private $_createTagExpire = 0;  # 创建标记有效期
    private $_createTagRetrySecond = 0;  # wait标记重试间隔
    private $_updateTagSecond = 0;  # 更新标记有效期
    private $_updateTagRetrySecond = 0;  # wait标记重试间隔


    /**
     * 获取容器名称拼接key名称
     * @param string $key 键名
     * @return string
     */
    final public function getBucketName(string $key)
    {
        return $this->_bucketName . $key;
    }

    /**
     * 设置容器名称
     * @param string $bucketName 容器名称
     * @param string $sep 分隔符，默认："::"
     */
    final public function setBucketName(string $bucketName = null, string $sep = '::')
    {
        $this->_bucketName = $bucketName ? $bucketName . $sep : env('REDIS_BUCKET_NAME', '') . $sep;
    }

    /**
     * 获取多个key名称
     * @param array $keys
     * @return array
     */
    final public function getBucketNames(array $keys)
    {
        return array_map(function ($key) {
            return $this->_bucketName . $key;
        }, $keys);
    }

    /**
     * 获取生存时间
     * @param bool $timeSalt
     * @return int
     */
    final public function getExpireTime(bool $timeSalt = true)
    {
        return $timeSalt ? (intval($this->_expireTime) ?: 3600) + rand(1, 60) : (intval($this->_expireTime) ?: 3600);
    }

    /**
     * 设置生存时间
     * @param int $time
     */
    final public function setExpireTime(int $time)
    {
        $this->_expireTime = $time > 0 ?? 0;
    }

    /**
     * 获取等待时间
     * @return int|mixed
     */
    final public function getWaitingSecond()
    {
        return intval($this->_readWaitingSecond) > 0 ?: intval(env('REDIS_READ_WAITING_SECOND', 2));
    }

    /**
     * 设置等待时间
     * @param int $second
     */
    final public function setWaitingSecond(int $second)
    {
        $this->_readWaitingSecond = $second > 0 ?: intval(env('REDIS_READ_WAITING_SECOND', 2));
    }

    /**
     * 获取重试次数
     * @return int|mixed
     */
    final public function getRetryTimes()
    {
        return intval($this->_reTryTimes) > 0 ?: intval(env('REDIS_RETRY_TIMES', 3));
    }

    /**
     * 设置重试次数
     * @param int $times
     * @return bool|mixed
     */
    final public function setRetryTimes(int $times)
    {
        return $this->_reTryTimes = $times > 0 ?: intval(env('REDIS_RETRY_TIMES', 3));
    }

    /**
     * 获取wait标记时长
     * @return int
     */
    final public function getWaitTagExpire()
    {
        return $this->_waitTagSecond ?: env('REDIS_WAIT_TAG_EXPIRE', 1);
    }

    /**
     * 设置wait标记有效期
     * @param int $time
     * @return mixed
     */
    final public function setWaitTagExpire(int $time)
    {
        return $this->_waitTagSecond = $time > 0 ?: env('REDIS_WAIT_TAG_EXPIRE', 1);
    }

    /**
     * 获取create标记有效期
     * @return int
     */
    final public function getCreateTagExpire()
    {
        return $this->_createTagExpire ?: env('REDIS_CREATE_TAG_EXPIRE', 1);
    }

    /**
     * 设置create标记有效期
     * @param int $time
     * @return mixed
     */
    final public function setCreateTagExpire(int $time)
    {
        return $this->_createTagExpire = $time > 0 ?: env('REDIS_CREATE_TAG_EXPIRE', 1);
    }

    /**
     * 获取update标记有效期
     * @return int
     */
    final public function getUpdateTagExpire()
    {
        return $this->_updateTagSecond ?: env('REDIS_UPDATE_TAG_EXPIRE', 1);
    }

    /**
     * 设置update标记有效期
     * @param int $time
     * @return mixed
     */
    final public function setUpdateTagExpire(int $time)
    {
        return $this->_updateTagSecond = $time > 0 ?: env('REDIS_UPDATE_TAG_EXPIRE', 1);
    }

    /**
     * 获取wait标记重试间隔
     * @return mixed
     */
    public function getWaitTagRetrySecond()
    {
        return intval($this->_waitTagRetrySecond) ?: env('REDIS_WAIT_TAG_RETRY_SECOND', 1);
    }

    /**
     * 设置wait标记重试间隔
     * @param int $second
     */
    final public function setWaitTagRetrySecond(int $second)
    {
        $this->_waitTagRetrySecond = $second > 0 ?: env('REDIS_WAIT_TAG_RETRY_SECOND', 1);
    }

    /**
     * 获取create标记重试间隔
     * @return mixed
     */
    public function getCreateTagRetrySecond()
    {
        return intval($this->_createTagRetrySecond) ?: env('REDIS_CREATE_TAG_RETRY_SECOND', 1);
    }

    /**
     * 设置create标记重试间隔
     * @param int $second
     */
    final public function setCreateTagRetrySecond(int $second)
    {
        $this->_createTagRetrySecond = $second > 0 ?: env('REDIS_CREATE_TAG_RETRY_SECOND', 1);
    }

    /**
     * 获取update标记重试间隔
     * @return mixed
     */
    public function getUpdateTagRetrySecond()
    {
        return intval($this->_updateTagRetrySecond) ?: env('REDIS_UPDATE_TAG_RETRY_SECOND', 1);
    }

    /**
     * 设置update标记重试间隔
     * @param int $second
     */
    final public function setUpdateTagRetrySecond(int $second)
    {
        $this->_updateTagRetrySecond = $second > 0 ?: env('REDIS_UPDATE_TAG_RETRY_SECOND', 1);
    }
}
