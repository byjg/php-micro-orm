<?php

namespace ByJG\MicroOrm;

use DateInterval;
use DateTimeImmutable;
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

    public function getTtlInSeconds(): int
    {
        if ($this->ttl instanceof DateInterval) {
            // Convert DateInterval to seconds
            $reference = new DateTimeImmutable();
            $endTime = $reference->add($this->ttl);
            return $endTime->getTimestamp() - $reference->getTimestamp();
        }
        return $this->ttl;
    }
}