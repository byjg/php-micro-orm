<?php

namespace Tests;

use ByJG\MicroOrm\Literal\PostgresUuidLiteral;
use ByJG\MicroOrm\Literal\SqlServerUuidLiteral;

class SqlServerUuidLiteralTest extends \Tests\HexUuidLiteralTest
{
    public function setUp(): void
    {
        $this->class = SqlServerUuidLiteral::class;
    }

    public function testBinaryString()
    {
        $value = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $expectedBinaryString = '0xf47ac10b58cc4372a5670e02b2c3d479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedBinaryString, $hexUuidLiteral->binaryString($value));
    }

    public function testFormatUuidFromBinRepresent()
    {
        $value = '0xf47ac10b58cc4372a5670e02b2c3d479';
        $expectedFormattedUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid($value));
    }
}