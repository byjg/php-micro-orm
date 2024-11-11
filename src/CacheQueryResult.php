<?php

namespace ByJG\MicroOrm;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class CacheQueryResult
{
    protected CacheInterface $cache;
    protected string $cacheKey;
    protected int|DateInterval $ttl;

    public function __construct(CacheInterface $cache, string $cacheKey, int|DateInterval $ttl)
    {
        $this->cache = $cache;
         $this->cacheKey = $cacheKey;
        $this->ttl = $ttl;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    public function getTtl(): DateInterval|int
    {
        return $this->ttl;
    }
}