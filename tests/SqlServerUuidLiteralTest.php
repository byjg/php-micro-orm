<?php

namespace Tests;

use ByJG\MicroOrm\Literal\SqlServerUuidLiteral;
use Override;

class SqlServerUuidLiteralTest extends HexUuidLiteralTest
{
    #[Override]
    public function setUp(): void
    {
        $this->class = SqlServerUuidLiteral::class;
    }

    #[Override]
    public function testBinaryString()
    {
        $value = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';
        $expectedBinaryString = '0xF47AC10B58CC4372A5670E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedBinaryString, $hexUuidLiteral->binaryString($value));
    }

    #[Override]
    public function testFormatUuidFromBinRepresent()
    {
        $value = '0xF47AC10B58CC4372A5670E02B2C3D479';
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid());
    }
}