---
sidebar_position: 16
---

# Using With Recursive SQL Command

```php
<?php
$recursive = \ByJG\MicroOrm\Recursive::getInstance('test')
    ->field('start', 1, 'start + 10')
    ->field('end', 120, "end - 10")
    ->where('start < 100')
;

$query = \ByJG\MicroOrm\Query::getInstance()
    ->withRecursive($recursive)
    ->fields(['start', 'end']);

/*
This will produce the following SQL:

WITH RECURSIVE test(start, end) AS (
    SELECT 1 as start, 120 as end
    UNION ALL SELECT start + 10, end - 10 FROM test WHERE start < 100
) SELECT start, end FROM test
*/
```
