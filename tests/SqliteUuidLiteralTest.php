<?php

namespace Tests;

use ByJG\MicroOrm\Literal\SqliteUuidLiteral;
use Override;

class SqliteUuidLiteralTest extends HexUuidLiteralTest
{
    #[Override]
    public function setUp(): void
    {
        $this->class = SqliteUuidLiteral::class;
    }
}