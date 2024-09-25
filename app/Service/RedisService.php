<?php

namespace App\Service;

use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;

/**
 * redis 加锁 --单Redis实例实现分布式锁
 * -- 多Redis实例参考：Redlock:https://github.com/ronnylt/redlock-php
 */
class RedisService
{
    const LOCK_VALUE = 1;
    protected RedisProxy $redis;

    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis->get('default');
    }

    public function acquireLock($key, $timeout = 10): bool
    {
         return $this->redis->set($key, self::LOCK_VALUE, ['NX', 'EX' => $timeout]);
    }

    public function releaseLock($key)
    {
        $script = <<<'LUA'
            if redis.call("get", KEYS[1]) == ARGV[1] then
                return redis.call("del", KEYS[1])
            else
                return 0
            end
        LUA;

        return $this->redis->eval($script, [$key, self::LOCK_VALUE], 1);
    }
}