<?php

namespace Test;

use ByJG\AnyDataset\Db\Helpers\DbSqliteFunctions;
use ByJG\MicroOrm\DeleteQuery;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Updatable;
use PHPUnit\Framework\TestCase;

class DeleteQueryTest extends TestCase
{
    /**
     * @var Updatable
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new DeleteQuery();
    }

    protected function tearDown(): void
    {
        $this->object = null;
    }

    public function testDelete()
    {
        $this->object->table('test');
        $this->object->where('fld1 = [[id]]', ['id' => 10]);

        $params = [];
        $sql = $this->object->build($params);
        $this->assertEquals(
            [
                'DELETE FROM test WHERE fld1 = [[id]]',
                [ 'id' => 10 ]
            ],
            [
                $sql,
                $params
            ]

        );
    }

    public function testDeleteError()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = [];

        $this->object->table('test');
        $this->object->build($params);
    }

    public function testQueryUpdatable()
    {
        $this->object->table('test');
        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test',
                'params' => []
            ],
            $this->object->convert()->build()
        );


        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test',
                'params' => []
            ],
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld2 = :teste', [ 'teste' => 10 ]);

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test WHERE fld2 = :teste',
                'params' => [ 'teste' => 10 ]
            ],
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld3 = 20');

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test WHERE fld2 = :teste AND fld3 = 20',
                'params' => [ 'teste' => 10 ]
            ],
            $this->object->convert()->build()
        );

        $this->object
            ->where('fld1 = [[teste2]]', [ 'teste2' => 40 ]);

        $this->assertEquals(
            [
                'sql' => 'SELECT  * FROM test WHERE fld2 = :teste AND fld3 = 20 AND fld1 = [[teste2]]',
                'params' => [ 'teste' => 10, 'teste2' => 40 ]
            ],
            $this->object->convert()->build()
        );
    }

}
