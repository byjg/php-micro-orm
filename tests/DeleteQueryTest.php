<?php

namespace Tests;

use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\DeleteQuery;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Updatable;
use Override;
use PHPUnit\Framework\TestCase;

class DeleteQueryTest extends TestCase
{
    /**
     * @var Updatable
     */
    protected $object;

    #[Override]
    protected function setUp(): void
    {
        $this->object = new DeleteQuery();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->object = null;
    }

    public function testDelete()
    {
        $this->object->table('test');
        $this->object->where('fld1 = :id', ['id' => 10]);

        $sqlStatement = $this->object->build();
        $this->assertEquals(
            new SqlStatement('DELETE FROM test WHERE fld1 = :id', ['id' => 10]),
            $sqlStatement
        );
    }

    public function testDeleteError()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = [];

        $this->object->table('test');
        $this->object->build();
    }

    public function testQueryUpdatable()
    {
        $this->object->table('test');
        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test'),
            $this->object->convert()->build()
        );

        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test'),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test WHERE fld2 = :teste', ['teste' => 10]),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test WHERE fld2 = :teste AND fld3 = 20', ['teste' => 10]),
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld1 = :teste2', [ 'teste2' => 40 ]);

        $this->assertEquals(
            new SqlStatement('SELECT  * FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = :teste2', ['teste' => 10, 'teste2' => 40]),
            $this->object->convert()->build()
        );
    }

}
