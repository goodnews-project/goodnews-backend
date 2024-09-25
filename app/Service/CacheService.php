<?php

namespace App\Service;

use Closure;
use Hyperf\Cache\Cache;
use function Hyperf\Support\make;

class CacheService
{
    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @template TCacheValue
     *
     * @param  string  $key
     * @param  int  $ttl
     * @param  \Closure(): TCacheValue  $callback
     * @return TCacheValue
     */
    public static function remember($key, $ttl, Closure $callback)
    {
        $cache = make(Cache::class);
        $value = $cache->get($key);
        if (! is_null($value)) {
            return $value;
        }
        $value = $callback();
        $cache->set($key, $value, $ttl);
        return $value;
    }
}
