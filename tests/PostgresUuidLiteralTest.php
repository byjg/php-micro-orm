<?php

namespace Tests;


use ByJG\MicroOrm\Literal\PostgresUuidLiteral;
use Override;

class PostgresUuidLiteralTest extends HexUuidLiteralTest
{
    #[Override]
    public function setUp(): void
    {
        $this->class = PostgresUuidLiteral::class;
    }

    #[Override]
    public function testBinaryString()
    {
        $value = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';
        $expectedBinaryString = "'F47AC10B-58CC-4372-A567-0E02B2C3D479'";

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedBinaryString, $hexUuidLiteral->binaryString($value));
    }

    #[Override]
    public function testFormatUuidFromBinRepresent()
    {
        $value = "'f47ac10b-58cc-4372-a567-0e02b2c3d479'";
        $expectedFormattedUuid = 'F47AC10B-58CC-4372-A567-0E02B2C3D479';

        $hexUuidLiteral = $this->instance($value);

        $this->assertEquals($expectedFormattedUuid, $hexUuidLiteral->formatUuid());
    }
}