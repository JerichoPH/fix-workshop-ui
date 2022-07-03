<?php

namespace Jericho\Redis;

use Illuminate\Support\Facades\Redis;

class SortedSets extends Basic
{
    private static $_ins = null;
    private static $_start = 1;
    private static $_end = 10;

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
     * 设置单个值
     * @param string $key
     * @param $score
     * @param $value
     * @return bool
     */
    final public function setOne(string $key, $score, $value)
    {
        return Redis::command('zadd', [$this->getBucketName($key), $score, $value]) ? true : false;
    }

    /**
     * 设置多个值
     * @param string $key
     * @param $map
     * @return mixed
     */
    final public function setMore(string $key, $map)
    {
        $value = [$this->getBucketName($key)];
        foreach ($map as $k => $v) {
            $value[] = $k;
            $value[] = $v;
        }

        return Redis::command('zadd', $value);
    }

    /**
     * 通过score分页
     * @param string $key
     * @param int|null $scoreStart
     * @param int|null $scoreEnd
     * @param int $currentPage
     * @param int $prePage
     * @param bool $desc
     * @return array
     */
    final public function paginateByScore(string $key, int $scoreStart = null, int $scoreEnd = null, int $currentPage = 1, int $prePage = 10, bool $desc = true)
    {
        $scores = $this->getMoreByScore($key, $scoreStart, $scoreEnd);
        if ($currentPage < 1) $currentPage = 1;
        $start = ($currentPage - 1) * $prePage;
        return array_slice($scores, $start, $prePage);
    }

    /**
     * 获取score多个值
     * @param string $key
     * @param int $start
     * @param int $end
     * @return mixed
     */
    final public function getMoreByScore(string $key, int $start = 0, int $end = 0)
    {
        if ($start > $end) {
            return Redis::command('zrevrangebyscore', [parent::getBucketName($key), $start, $end]);
        } elseif ($end > $start) {
            return Redis::command('zrangebyscore', [parent::getBucketName($key), $start, $end]);
        } else {
            return Redis::command('zrangebyscore', [parent::getBucketName($key), $start, $end]);
        }
    }

    /**
     * 分页器
     * @param string $key
     * @param int $currentPage
     * @param int $prePage
     * @param bool $desc
     * @return mixed
     */
    final public function paginate(string $key, int $currentPage = 1, int $prePage = 10, bool $desc = true)
    {
        if ($currentPage < 1) $currentPage = 1;
        $start = ($currentPage - 1) * $prePage;
        $end = $start + $prePage - 1;
        return $desc ? $this->getMore($key, $end, $start) : $this->getMore($key, $start, $end);
    }

    /**
     * 根据行编号获取值
     * @param string $key
     * @param int $start
     * @param int $end
     * @return mixed
     */
    final public function getMore(string $key, int $start = 0, int $end = 0)
    {
        if ($start > $end) {
            return Redis::command('zrevrange', [parent::getBucketName($key), $end, $start]);
        } else {
            return Redis::command('zrange', [parent::getBucketName($key), $start, $end]);
        }
    }
}
