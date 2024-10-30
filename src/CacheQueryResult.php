<?php

namespace ByJG\MicroOrm;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class CacheQueryResult
{
    protected CacheInterface $cache;
    protected int|DateInterval $ttl;

    public function __construct(CacheInterface $cache, int|DateInterval $ttl)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getTtl(): DateInterval|int
    {
        return $this->ttl;
    }
}