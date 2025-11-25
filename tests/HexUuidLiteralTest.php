<?php

namespace Tests;

use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\MySqlUuidLiteral;
use ByJG\MicroOrm\Literal\PostgresUuidLiteral;
use ByJG\MicroOrm\Literal\SqliteUuidLiteral;
use ByJG\MicroOrm\Literal\SqlServerUuidLiteral;
use Override;
use PHPUnit\Framework\TestCase;

class HexUuidLiteralTest extends TestCase
{
    protected $class;

    #[Override]
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

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid());
    }

    public function testFormatUuidFromLiteral()
    {
        $value = new HexUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid());
    }

    public function testFormatUuidFromMysql()
    {
        $value = new MySqlUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid());
    }
    public function testFormatUuidFromPostgres()
    {
        $value = new PostgresUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid());
    }
    public function testFormatUuidFromSqlServer()
    {
        $value = new SqlServerUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid());
    }
    public function testFormatUuidFromSqlite()
    {
        $value = new SqliteUuidLiteral('F47AC10B58CC4372A5670E02B2C3D479');
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid());
    }



    public function testFormatUuid()
    {
        $value = 'F47AC10B58CC4372A5670E02B2C3D479';
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid());
    }

    public function testCreateWithHexUuidLiteralInstance()
    {
        $uuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';
        $hexUuidLiteral = new HexUuidLiteral($uuid);
        $newHexUuidLiteral = new HexUuidLiteral($hexUuidLiteral);
        $this->assertInstanceOf(HexUuidLiteral::class, $newHexUuidLiteral);
        $this->assertEquals($uuid, $newHexUuidLiteral->formatUuid());
    }

    public function testGetFormattedUuid()
    {
        $literal = 'F47AC10B58CC4372A5670E02B2C3D479';
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

    public function testGetFormattedUuidWithInvalidInputNoExceptionDefault()
    {
        $literal = 'invalid-uuid';

        $value = HexUuidLiteral::getFormattedUuid($literal, false, 'xyz');

        $this->assertEquals('xyz', $value);
    }

    public function testCreateWithValidUuidBinary()
    {
        $uuid = '123E4567-E89B-12D3-A456-426614174000';
        $hexUuidLiteral = new HexUuidLiteral(hex2bin('123E4567E89B12D3A456426614174000'));
        $this->assertInstanceOf(HexUuidLiteral::class, $hexUuidLiteral);
        $this->assertEquals($uuid, $hexUuidLiteral->formatUuid());
    }

    public function testEmpty()
    {
        $this->assertNull(HexUuidLiteral::getFormattedUuid(null));
        $this->assertNull(HexUuidLiteral::getFormattedUuid(null, throwErrorIfInvalid: false));
        $this->assertNull(HexUuidLiteral::getFormattedUuid(""));
        $this->assertNull(HexUuidLiteral::getFormattedUuid("", throwErrorIfInvalid: false));

        $this->assertNull(HexUuidLiteral::create(null));
    }

    public function testCreateWithInvalidUuidBinary2()
    {
        $this->assertEquals('123E4567-E89B-12D3-A456-426614174000', HexUuidLiteral::getFormattedUuid(hex2bin('123E4567E89B12D3A456426614174000')));
        $this->assertEquals('123E4567-E89B-12D3-A456-426614174000', (new HexUuidLiteral(hex2bin('123E4567E89B12D3A456426614174000')))->formatUuid());
        $this->assertEquals('123E4567-E89B-12D3-A456-426614174000', HexUuidLiteral::create(hex2bin('123E4567E89B12D3A456426614174000'))->formatUuid());
        $this->assertEquals('not-uuid', HexUuidLiteral::create("not-uuid"));
    }
}