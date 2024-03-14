<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\AnyDataset\Db\PdoMysql;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\QueryBasic;
use ByJG\MicroOrm\Union;
use ByJG\Util\Uri;
use PHPUnit\Framework\TestCase;

class UnionTest extends TestCase
{
    public function testAddQuery()
    {
        $union = new Union();
        $union->addQuery(QueryBasic::getInstance()->table("table1")->fields(['name', 'price'])->where('name like :name', ['name' => 'a%']));
        $union->addQuery(QueryBasic::getInstance()->table("table2")->fields(['name', 'price'])->where('price > :price', ['price' => 10]));

        $build = $union->build();

        $this->assertEquals("SELECT  name, price FROM table1 WHERE name like :name UNION SELECT  name, price FROM table2 WHERE price > :price", $build->getSql());
        $this->assertEquals(["name" => 'a%', 'price' => 10], $build->getParameters());
    }

    public function testAddQueryWithTop()
    {
        $union = new Union();
        $union->addQuery(QueryBasic::getInstance()->table("table1")->fields(['name', 'price'])->where('name like :name', ['name' => 'a%']));
        $union->addQuery(QueryBasic::getInstance()->table("table2")->fields(['name', 'price'])->where('price > :price', ['price' => 10]));
        $union->top(10);

        $build = $union->build(Factory::getDbRelationalInstance(new Uri('sqlite:///tmp/teste.db')));

        $this->assertEquals("SELECT  name, price FROM table1 WHERE name like :name UNION SELECT  name, price FROM table2 WHERE price > :price LIMIT 0, 10", $build->getSql());
        $this->assertEquals(["name" => 'a%', 'price' => 10], $build->getParameters());
    }

    public function testAddQueryWithOrderBy()
    {
        $union = new Union();
        $union->addQuery(QueryBasic::getInstance()->table("table1")->fields(['name', 'price'])->where('name like :name', ['name' => 'a%']));
        $union->addQuery(QueryBasic::getInstance()->table("table2")->fields(['name', 'price'])->where('price > :price', ['price' => 10]));
        $union->orderBy(['name']);

        $build = $union->build(Factory::getDbRelationalInstance(new Uri('sqlite:///tmp/teste.db')));

        $this->assertEquals("SELECT  name, price FROM table1 WHERE name like :name UNION SELECT  name, price FROM table2 WHERE price > :price ORDER BY name", $build->getSql());
        $this->assertEquals(["name" => 'a%', 'price' => 10], $build->getParameters());
    }

    public function testInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The query must be an instance of " . QueryBasic::class);

        $union = new Union();
        $union->addQuery(Query::getInstance());
    }
}
