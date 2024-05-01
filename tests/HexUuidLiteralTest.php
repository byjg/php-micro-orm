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
        $value = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $expectedBinaryString = "X'f47ac10b58cc4372a5670e02b2c3d479'";

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedBinaryString, $hexUuidLiteral->binaryString($value));
    }

    public function testFormatUuidFromBinRepresent()
    {
        $value = "X'f47ac10b58cc4372a5670e02b2c3d479'";
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }

    public function testFormatUuidFromLiteral()
    {
        $value = new HexUuidLiteral('f47ac10b58cc4372a5670e02b2c3d479');
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }

    public function testFormatUuidFromMysql()
    {
        $value = new MySqlUuidLiteral('f47ac10b58cc4372a5670e02b2c3d479');
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }
    public function testFormatUuidFromPostgres()
    {
        $value = new PostgresUuidLiteral('f47ac10b58cc4372a5670e02b2c3d479');
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }
    public function testFormatUuidFromSqlServer()
    {
        $value = new SqlServerUuidLiteral('f47ac10b58cc4372a5670e02b2c3d479');
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }
    public function testFormatUuidFromSqlite()
    {
        $value = new SqliteUuidLiteral('f47ac10b58cc4372a5670e02b2c3d479');
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }



    public function testFormatUuid()
    {
        $value = 'f47ac10b58cc4372a5670e02b2c3d479';
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }

    public function testGetUuidFromLiteral()
    {
        $literal = new HexUuidLiteral('f47ac10b-58cc-4372-a567-0e02b2c3d479');
        $expectedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $this->assertEquals($expectedUuid, HexUuidLiteral::getUuidFromLiteral($literal));
    }

    public function testGetFormattedUuid()
    {
        $literal = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $this->assertEquals($expectedFormattedUuid, HexUuidLiteral::getFormattedUuid($literal));
    }

    public function testGetFormattedUuidBinaryStored()
    {
        $literal = hex2bin('f47ac10b58cc4372a5670e02b2c3d479');
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

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