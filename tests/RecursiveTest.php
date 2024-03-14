<?php

namespace Tests;

use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Recursive;
use ByJG\MicroOrm\SqlObject;
use PHPUnit\Framework\TestCase;

class RecursiveTest extends TestCase
{
    public function testRecursive()
    {
        $recursive = Recursive::getInstance('test')
            ->field('start', 1, 'start + 10')
            ->field('end', 120, "end - 10")
            ->where('start < 100')
        ;
        
        $expected = "WITH RECURSIVE test(start, end) AS (SELECT 1 as start, 120 as end UNION ALL SELECT start + 10, end - 10 FROM test WHERE start < 100) ";
        $this->assertEquals($expected, $recursive->build()->getSql());

        $query = Query::getInstance()
            ->withRecursive($recursive)
            ->fields(['start', 'end']);

        $expected = $expected . "SELECT  start, end FROM test";
        $this->assertEquals(
            new SqlObject($expected),
            $query->build()
        );
    
    }
}