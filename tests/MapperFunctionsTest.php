<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\Literal;
use ByJG\MicroOrm\Literal\LiteralInterface;
use ByJG\MicroOrm\MapperFunctions\FormatSelectUuidMapper;
use ByJG\MicroOrm\MapperFunctions\FormatUpdateUuidMapper;
use ByJG\MicroOrm\MapperFunctions\NowUtcMapper;
use ByJG\MicroOrm\MapperFunctions\ReadOnlyMapper;
use ByJG\MicroOrm\MapperFunctions\StandardMapper;
use PHPUnit\Framework\TestCase;
use stdClass;

class MapperFunctionsTest extends TestCase
{
    // StandardMapper Tests

    public function testStandardMapperWithNonEmptyValue()
    {
        $mapper = new StandardMapper();
        $result = $mapper->processedValue('test value', new stdClass());

        $this->assertEquals('test value', $result);
    }

    public function testStandardMapperWithEmptyString()
    {
        $mapper = new StandardMapper();
        $result = $mapper->processedValue('', new stdClass());

        $this->assertNull($result);
    }

    public function testStandardMapperWithNull()
    {
        $mapper = new StandardMapper();
        $result = $mapper->processedValue(null, new stdClass());

        $this->assertNull($result);
    }

    public function testStandardMapperWithZeroInteger()
    {
        $mapper = new StandardMapper();
        $result = $mapper->processedValue(0, new stdClass());

        $this->assertSame(0, $result);
    }

    public function testStandardMapperWithZeroString()
    {
        $mapper = new StandardMapper();
        $result = $mapper->processedValue('0', new stdClass());

        $this->assertSame('0', $result);
    }

    public function testStandardMapperWithFalse()
    {
        $mapper = new StandardMapper();
        $result = $mapper->processedValue(false, new stdClass());

        $this->assertFalse($result);
    }

    // ReadOnlyMapper Tests

    public function testReadOnlyMapperAlwaysReturnsFalse()
    {
        $mapper = new ReadOnlyMapper();
        $result = $mapper->processedValue('any value', new stdClass());

        $this->assertFalse($result);
    }

    public function testReadOnlyMapperWithNullValue()
    {
        $mapper = new ReadOnlyMapper();
        $result = $mapper->processedValue(null, new stdClass());

        $this->assertFalse($result);
    }

    public function testReadOnlyMapperWithNumericValue()
    {
        $mapper = new ReadOnlyMapper();
        $result = $mapper->processedValue(123, new stdClass());

        $this->assertFalse($result);
    }

    // NowUtcMapper Tests

    public function testNowUtcMapperReturnsLiteral()
    {
        $dbHelperMock = $this->createMock(DbFunctionsInterface::class);
        $dbHelperMock->method('sqlDate')
            ->with('Y-m-d H:i:s')
            ->willReturn('2024-01-15 12:30:45');

        $executorMock = $this->createMock(DatabaseExecutor::class);
        $executorMock->method('getHelper')
            ->willReturn($dbHelperMock);

        $mapper = new NowUtcMapper();
        $result = $mapper->processedValue(null, new stdClass(), $executorMock);

        $this->assertInstanceOf(LiteralInterface::class, $result);
        $this->assertEquals('2024-01-15 12:30:45', $result->getLiteralValue());
    }

    public function testNowUtcMapperIgnoresInputValue()
    {
        $dbHelperMock = $this->createMock(DbFunctionsInterface::class);
        $dbHelperMock->method('sqlDate')
            ->with('Y-m-d H:i:s')
            ->willReturn('2024-01-15 12:30:45');

        $executorMock = $this->createMock(DatabaseExecutor::class);
        $executorMock->method('getHelper')
            ->willReturn($dbHelperMock);

        $mapper = new NowUtcMapper();
        $result = $mapper->processedValue('ignored value', new stdClass(), $executorMock);

        $this->assertInstanceOf(LiteralInterface::class, $result);
        $this->assertEquals('2024-01-15 12:30:45', $result->getLiteralValue());
    }

    // FormatUpdateUuidMapper Tests

    public function testFormatUpdateUuidMapperWithEmptyValue()
    {
        $mapper = new FormatUpdateUuidMapper();
        $result = $mapper->processedValue('', new stdClass());

        $this->assertNull($result);
    }

    public function testFormatUpdateUuidMapperWithNullValue()
    {
        $mapper = new FormatUpdateUuidMapper();
        $result = $mapper->processedValue(null, new stdClass());

        $this->assertNull($result);
    }

    public function testFormatUpdateUuidMapperWithStringUuid()
    {
        $mapper = new FormatUpdateUuidMapper();
        $uuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';
        $result = $mapper->processedValue($uuid, new stdClass());

        $this->assertInstanceOf(HexUuidLiteral::class, $result);
        $this->assertEquals("X'" . str_replace("-", "", $uuid) . "'", $result->getLiteralValue());
    }

    public function testFormatUpdateUuidMapperWithLiteralValue()
    {
        $mapper = new FormatUpdateUuidMapper();
        $literal = new Literal('some value');
        $result = $mapper->processedValue($literal, new stdClass());

        $this->assertSame($literal, $result);
    }

    public function testFormatUpdateUuidMapperWithHexUuidLiteral()
    {
        $mapper = new FormatUpdateUuidMapper();
        $uuid = new HexUuidLiteral('F47AC10B-58CC-4372-A567-0E02B2C3D479');
        $result = $mapper->processedValue($uuid, new stdClass());

        $this->assertSame($uuid, $result);
    }

    // FormatSelectUuidMapper Tests

    public function testFormatSelectUuidMapperWithBinaryUuid()
    {
        $mapper = new FormatSelectUuidMapper();
        $binaryUuid = hex2bin('F47AC10B58CC4372A5670E02B2C3D479');

        $result = $mapper->processedValue($binaryUuid, []);

        $this->assertEquals('F47AC10B-58CC-4372-A567-0E02B2C3D479', $result);
    }

    public function testFormatSelectUuidMapperWithHexString()
    {
        $mapper = new FormatSelectUuidMapper();
        $hexUuid = 'F47AC10B58CC4372A5670E02B2C3D479';

        $result = $mapper->processedValue($hexUuid, []);

        $this->assertEquals('F47AC10B-58CC-4372-A567-0E02B2C3D479', $result);
    }

    public function testFormatSelectUuidMapperWithFormattedUuid()
    {
        $mapper = new FormatSelectUuidMapper();
        $formattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $result = $mapper->processedValue($formattedUuid, []);

        $this->assertEquals('F47AC10B-58CC-4372-A567-0E02B2C3D479', $result);
    }

    public function testFormatSelectUuidMapperWithEmptyValue()
    {
        $mapper = new FormatSelectUuidMapper();

        $result = $mapper->processedValue('', []);

        $this->assertEquals('', $result);
    }

    public function testFormatSelectUuidMapperWithNullValue()
    {
        $mapper = new FormatSelectUuidMapper();

        $result = $mapper->processedValue(null, []);

        $this->assertNull($result);
    }

    public function testFormatSelectUuidMapperWithInvalidValue()
    {
        $mapper = new FormatSelectUuidMapper();

        $result = $mapper->processedValue('invalid-uuid', []);

        // Should return null when invalid and throwErrorIfInvalid is false
        $this->assertNull($result);
    }
}
