---
sidebar_position: 11
---

# Caching the Results

The `AnyDatasetDb` library has a built-in cache mechanism that can be used to cache the results of a query. 
This is useful when you have a query that is expensive to run and you want to cache the results for a 
certain amount of time.

To enable caching, you need to pass a `CacheQueryResult` object when you´ll query the database.

Here is an example of how to use the cache mechanism:

```php
<?php

$query = Query::getInstance()
    ->where('id = :id1', ['id1'=>3]);

$cacheEngine = /* any SimpleCache implementation */;

// Get the result and save to cache
$result = $repository->getByQuery($query, cache: new CacheQueryResult($cacheEngine, 'key', 120));
```

In this example, the result of the query will be saved in the cache for 120 seconds.

The `CacheQueryResult` object has the following parameters:

- `cacheEngine`: The cache engine to use. It must implement the `Psr\SimpleCache\CacheInterface` interface.
- `key`: The key to use to save the cache. It must be unique for each query. See the notes below.
- `ttl`: The time-to-live of the cache in seconds. If the cache is older than this value, it will be considered expired.

**NOTES**
The key must be unique for each query. If you use the same key for different queries, it will not differentiate one from
another,
and you can get unexpected results.

If the query has parameters, the key will be concatenated with the hash of the parameters.




