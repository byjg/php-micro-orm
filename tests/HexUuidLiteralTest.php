<?php

namespace Tests;

use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\MySqlUuidLiteral;
use ByJG\MicroOrm\Literal\PostgresUuidLiteral;
use ByJG\MicroOrm\Literal\SqliteUuidLiteral;
use ByJG\MicroOrm\Literal\SqlServerUuidLiteral;
use PHPUnit\Framework\TestCase;

class HexUuidLiteralTest extends TestCase
{
    protected $class;

    public function setUp(): void
    {
        $this->class = HexUuidLiteral::class;
    }

    protected function instance($value = null): HexUuidLiteral
    {
        return new $this->class($value);
    }

    public function testBinaryString()
    {
        $value = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';
        $expectedBinaryString = "X'F47AC10B58CC4372A5670E02B2C3D479'";

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedBinaryString, $hexUuidLiteral->binaryString($value));
    }

    public function testFormatUuidFromBinRepresent()
    {
        $value = "X'F47AC10B58CC4372A5670E02B2C3D479'";
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }

    public function testFormatUuidFromLiteral()
    {
        $value = new HexUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }

    public function testFormatUuidFromMysql()
    {
        $value = new MySqlUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }
    public function testFormatUuidFromPostgres()
    {
        $value = new PostgresUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }
    public function testFormatUuidFromSqlServer()
    {
        $value = new SqlServerUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }
    public function testFormatUuidFromSqlite()
    {
        $value = new SqliteUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }



    public function testFormatUuid()
    {
        $value = 'F47AC10B58CC4372A5670E02B2C3D479';
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }

    public function testGetUuidFromLiteral()
    {
        $literal = new HexUuidLiteral('F47AC10B-58CC-4372-A567-0E02B2C3D479');
        $expectedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $this->assertEquals($expectedUuid, HexUuidLiteral::getUuidFromLiteral($literal));
    }

    public function testGetFormattedUuid()
    {
        $literal = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $this->assertEquals($expectedFormattedUuid, HexUuidLiteral::getFormattedUuid($literal));
    }

    public function testGetFormattedUuidBinaryStored()
    {
        $literal = hex2bin('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $this->assertEquals($expectedFormattedUuid, HexUuidLiteral::getFormattedUuid($literal));
    }

    public function testGetFormattedUuidWithInvalidInput()
    {
        $literal = 'invalid-uuid';

        $this->expectException(InvalidArgumentException::class);

        HexUuidLiteral::getFormattedUuid($literal);
    }

    public function testGetFormattedUuidWithInvalidInputNoException()
    {
        $literal = 'invalid-uuid';

        $value = HexUuidLiteral::getFormattedUuid($literal, false);

        $this->assertNull($value);
    }
}