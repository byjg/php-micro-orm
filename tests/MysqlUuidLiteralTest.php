<?php

namespace Tests;

use ByJG\MicroOrm\Literal\MySqlUuidLiteral;
use Override;

class MysqlUuidLiteralTest extends HexUuidLiteralTest
{
    #[Override]
    public function setUp(): void
    {
        $this->class = MySqlUuidLiteral::class;
    }
}