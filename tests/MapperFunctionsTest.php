<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\Literal;
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

        $this->assertInstanceOf(Literal::class, $result);
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

        $this->assertInstanceOf(Literal::class, $result);
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

    public function testFormatSelectUuidMapperWithArrayInstanceWithUuid()
    {
        $mapper = new FormatSelectUuidMapper();
        $binaryUuid = hex2bin('F47AC10B58CC4372A5670E02B2C3D479');
        $instance = ['uuid' => $binaryUuid];

        $result = $mapper->processedValue(null, $instance);

        $this->assertEquals('F47AC10B-58CC-4372-A567-0E02B2C3D479', $result);
    }

    public function testFormatSelectUuidMapperWithArrayInstanceWithoutUuid()
    {
        $mapper = new FormatSelectUuidMapper();
        $instance = ['id' => 123];

        $result = $mapper->processedValue('original value', $instance);

        $this->assertEquals('original value', $result);
    }

    public function testFormatSelectUuidMapperWithArrayInstanceEmptyUuid()
    {
        $mapper = new FormatSelectUuidMapper();
        $instance = ['uuid' => ''];

        $result = $mapper->processedValue('fallback value', $instance);

        $this->assertEquals('fallback value', $result);
    }

    public function testFormatSelectUuidMapperWithObjectWithGetUuidMethod()
    {
        $mapper = new FormatSelectUuidMapper();

        $instance = new class {
            public function getUuid()
            {
                return hex2bin('F47AC10B58CC4372A5670E02B2C3D479');
            }
        };

        $result = $mapper->processedValue(null, $instance);

        $this->assertEquals('F47AC10B-58CC-4372-A567-0E02B2C3D479', $result);
    }

    public function testFormatSelectUuidMapperWithObjectWithoutGetUuidMethod()
    {
        $mapper = new FormatSelectUuidMapper();
        $instance = new stdClass();

        $result = $mapper->processedValue('original value', $instance);

        $this->assertEquals('original value', $result);
    }

    public function testFormatSelectUuidMapperWithObjectGetUuidReturnsEmpty()
    {
        $mapper = new FormatSelectUuidMapper();

        $instance = new class {
            public function getUuid()
            {
                return null;
            }
        };

        $result = $mapper->processedValue('fallback value', $instance);

        $this->assertEquals('fallback value', $result);
    }

    public function testFormatSelectUuidMapperWithFormattedUuid()
    {
        $mapper = new FormatSelectUuidMapper();

        $instance = new class {
            public function getUuid()
            {
                return 'F47AC10B-58CC-4372-A567-0E02B2C3D479';
            }
        };

        $result = $mapper->processedValue(null, $instance);

        $this->assertEquals('F47AC10B-58CC-4372-A567-0E02B2C3D479', $result);
    }
}
