<?php

namespace Tests;

use ByJG\MicroOrm\Literal\SqliteUuidLiteral;
use ByJG\MicroOrm\Literal\SqlServerUuidLiteral;

class SqliteUuidLiteralTest extends \Tests\HexUuidLiteralTest
{
    public function setUp(): void
    {
        $this->class = SqliteUuidLiteral::class;
    }
}